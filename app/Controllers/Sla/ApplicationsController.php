<?php

namespace App\Controllers\Sla;

use App\Core\Controller;
use App\Helpers\ContractAlert;
use App\Helpers\SlaCompliance;
use App\Models\Application;
use App\Models\Catalog;
use App\Models\Provider;

class ApplicationsController extends Controller
{
    public function indexAction(): void
    {
        $this->render('sla/applications/index', [
            'pageTitle' => 'Aplicaciones',
            'activeModule' => 'sla-applications',
            'applications' => (new Application())->allWithDetails(),
        ]);
    }

    public function createAction(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (new Application())->insert($this->collectApplicationInput());
            $this->saveOwnershipAndProvider($id);
            (new Application())->saveSupportTypes($id, $this->collectSupportTypeInput());
            $this->flash('success', 'Aplicación creada correctamente.');
            $this->redirect('sla/applications/view/' . $id);
            return;
        }

        $this->render('sla/applications/form', [
            'pageTitle' => 'Nueva aplicación',
            'activeModule' => 'sla-applications',
            'application' => null,
            'ownership' => null,
            'assignedSupportTypes' => [],
            ...$this->formOptions(),
        ]);
    }

    public function editAction(?string $id): void
    {
        $applicationId = (int) $id;
        $application = (new Application())->findWithDetails($applicationId);

        if ($application === null) {
            $this->redirect('sla/applications/index');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new Application())->update($applicationId, $this->collectApplicationInput());
            $this->saveOwnershipAndProvider($applicationId);
            (new Application())->saveSupportTypes($applicationId, $this->collectSupportTypeInput());
            $this->flash('success', 'Aplicación actualizada correctamente.');
            $this->redirect('sla/applications/view/' . $applicationId);
            return;
        }

        $assignedSupportTypes = [];
        foreach ((new Application())->supportTypes($applicationId) as $st) {
            $assignedSupportTypes[(int) $st['support_type_id']] = $st;
        }

        $this->render('sla/applications/form', [
            'pageTitle' => 'Editar aplicación',
            'activeModule' => 'sla-applications',
            'application' => $application,
            'ownership' => (new Application())->ownership($applicationId),
            'assignedSupportTypes' => $assignedSupportTypes,
            ...$this->formOptions(),
        ]);
    }

    public function viewAction(?string $id): void
    {
        $applicationId = (int) $id;
        $model = new Application();
        $application = $model->findWithDetails($applicationId);

        if ($application === null) {
            $this->redirect('sla/applications/index');
            return;
        }

        $indicators = $model->monthlyIndicators($applicationId);
        $latestIndicator = $indicators[0] ?? null;
        $complianceLight = $latestIndicator !== null
            ? SlaCompliance::semaphore(
                (float) $latestIndicator['response_compliance_pct'],
                (float) $latestIndicator['resolution_compliance_pct'],
                (int) $latestIndicator['breach_count']
            )
            : null;

        $contractBucket = $application['contract_expiration_date']
            ? ContractAlert::bucket($application['contract_expiration_date'])
            : null;

        $this->render('sla/applications/view', [
            'pageTitle' => $application['name'],
            'activeModule' => 'sla-applications',
            'application' => $application,
            'ownership' => $model->ownership($applicationId),
            'schedule' => $model->schedule($applicationId),
            'availability' => $model->availability($applicationId),
            'supportTypes' => $model->supportTypes($applicationId),
            'integrations' => $model->integrations($applicationId),
            'dependencies' => $model->dependencies($applicationId),
            'raci' => $model->raci($applicationId),
            'incidentSla' => $model->incidentSla($applicationId),
            'indicators' => $indicators,
            'latestIndicator' => $latestIndicator,
            'complianceLight' => $complianceLight,
            'contractBucket' => $contractBucket,
        ]);
    }

    public function deleteAction(?string $id): void
    {
        (new Application())->delete((int) $id);
        $this->flash('success', 'Aplicación eliminada.');
        $this->redirect('sla/applications/index');
    }

    /** Printable "Ficha de ANS" (spec section 18), consolidating everything about one application. */
    public function datasheetAction(?string $id): void
    {
        $applicationId = (int) $id;
        $model = new Application();
        $application = $model->findWithDetails($applicationId);

        if ($application === null) {
            $this->redirect('sla/applications/index');
            return;
        }

        $indicators = $model->monthlyIndicators($applicationId);
        $latestIndicator = $indicators[0] ?? null;

        $this->render('sla/applications/datasheet', [
            'pageTitle' => 'Ficha ANS: ' . $application['name'],
            'activeModule' => 'sla-applications',
            'application' => $application,
            'ownership' => $model->ownership($applicationId),
            'schedule' => $model->schedule($applicationId),
            'availability' => $model->availability($applicationId),
            'supportTypes' => $model->supportTypes($applicationId),
            'incidentSla' => $model->incidentSla($applicationId),
            'latestIndicator' => $latestIndicator,
        ]);
    }

    private function collectApplicationInput(): array
    {
        return [
            'name' => trim((string) $this->input('name')),
            'commercial_name' => $this->input('commercial_name') ?: null,
            'description' => $this->input('description') ?: null,
            'business_process' => $this->input('business_process') ?: null,
            'requesting_area' => $this->input('requesting_area') ?: null,
            'functional_owner' => $this->input('functional_owner') ?: null,
            'technical_owner' => $this->input('technical_owner') ?: null,
            'application_type_id' => (int) $this->input('application_type_id'),
            'category' => $this->input('category') ?: null,
            'criticality_id' => (int) $this->input('criticality_id'),
            'status' => $this->input('status', 'active'),
            'approx_users' => $this->input('approx_users') ?: null,
            'url' => $this->input('url') ?: null,
            'environment' => $this->input('environment') ?: null,
            'notes' => $this->input('notes') ?: null,
        ];
    }

    private function saveOwnershipAndProvider(int $applicationId): void
    {
        // Ownership and provider live in their own 1:1 tables; upsert via raw SQL
        // since they don't have their own dedicated CRUD model (spec sections 2-3).
        $pdo = \App\Core\Database::connection();

        $ownershipData = [
            'application_id' => $applicationId,
            'ownership' => $this->input('ownership', 'in_house'),
            'modality_id' => $this->input('modality_id') ?: null,
            'license_type' => $this->input('license_type') ?: null,
            'acquisition_model' => $this->input('acquisition_model') ?: null,
            'acquisition_date' => $this->input('acquisition_date') ?: null,
            'contract_start_date' => $this->input('contract_start_date') ?: null,
            'expiration_date' => $this->input('expiration_date') ?: null,
            'auto_renewal' => $this->input('auto_renewal') ? 1 : 0,
            'license_count' => $this->input('license_count') ?: null,
            'approx_cost' => $this->input('approx_cost') ?: null,
            'payment_frequency' => $this->input('payment_frequency') ?: null,
        ];

        $pdo->prepare(
            'INSERT INTO application_ownership (application_id, ownership, modality_id, license_type, acquisition_model,
                acquisition_date, contract_start_date, expiration_date, auto_renewal, license_count, approx_cost, payment_frequency)
             VALUES (:application_id, :ownership, :modality_id, :license_type, :acquisition_model,
                :acquisition_date, :contract_start_date, :expiration_date, :auto_renewal, :license_count, :approx_cost, :payment_frequency)
             ON DUPLICATE KEY UPDATE ownership = VALUES(ownership), modality_id = VALUES(modality_id),
                license_type = VALUES(license_type), acquisition_model = VALUES(acquisition_model),
                acquisition_date = VALUES(acquisition_date), contract_start_date = VALUES(contract_start_date),
                expiration_date = VALUES(expiration_date), auto_renewal = VALUES(auto_renewal),
                license_count = VALUES(license_count), approx_cost = VALUES(approx_cost), payment_frequency = VALUES(payment_frequency)'
        )->execute($ownershipData);

        $providerId = $this->input('provider_id') ?: null;

        $providerData = [
            'application_id' => $applicationId,
            'provider_id' => $providerId,
            'contract_number' => $this->input('contract_number') ?: null,
            'contract_start_date' => $this->input('contract_start_date') ?: null,
            'contract_expiration_date' => $this->input('expiration_date') ?: null,
        ];

        $pdo->prepare(
            'INSERT INTO application_provider (application_id, provider_id, contract_number, contract_start_date, contract_expiration_date)
             VALUES (:application_id, :provider_id, :contract_number, :contract_start_date, :contract_expiration_date)
             ON DUPLICATE KEY UPDATE provider_id = VALUES(provider_id), contract_number = VALUES(contract_number),
                contract_start_date = VALUES(contract_start_date), contract_expiration_date = VALUES(contract_expiration_date)'
        )->execute($providerData);
    }

    /**
     * Reads the "Tipos de soporte" section: support_type_ids[] marks which
     * types the application offers; level_<id>, responsible_<id>,
     * channel_<id>, etc. carry that type's escalation/contact info for this
     * application. Only checked types are returned.
     *
     * @return array<int,array<string,mixed>> support_type_id => fields
     */
    private function collectSupportTypeInput(): array
    {
        $selected = array_map('intval', (array) ($_POST['support_type_ids'] ?? []));
        $result = [];

        foreach ($selected as $typeId) {
            $result[$typeId] = [
                'level' => (int) $this->input('level_' . $typeId, 2),
                'responsible' => $this->input('responsible_' . $typeId) ?: null,
                'channel' => $this->input('channel_' . $typeId) ?: null,
                'hours' => $this->input('hours_' . $typeId) ?: null,
                'max_escalation_time' => $this->input('max_escalation_time_' . $typeId) ?: null,
                'contact' => $this->input('contact_' . $typeId) ?: null,
                'email' => $this->input('email_' . $typeId) ?: null,
                'phone' => $this->input('phone_' . $typeId) ?: null,
                'procedure_notes' => $this->input('procedure_notes_' . $typeId) ?: null,
            ];
        }

        return $result;
    }

    private function formOptions(): array
    {
        return [
            'types' => (new Catalog('cat_application_types'))->all(),
            'criticalityLevels' => (new Catalog('cat_criticality_levels'))->all(),
            'modalities' => (new Catalog('cat_modalities'))->all(),
            'providers' => (new Provider())->all('name ASC'),
            'supportTypesCatalog' => (new Catalog('cat_support_types'))->all('code ASC'),
        ];
    }
}
