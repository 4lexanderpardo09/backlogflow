<?php

namespace App\Controllers\Sla;

use App\Core\Controller;
use App\Models\Provider;

class ProvidersController extends Controller
{
    public function indexAction(): void
    {
        $providerModel = new Provider();
        $providers = $providerModel->all('name ASC');

        $providers = array_map(function (array $p) use ($providerModel) {
            $p['applications'] = $providerModel->applicationsFor((int) $p['id']);
            return $p;
        }, $providers);

        $this->render('sla/providers/index', [
            'pageTitle' => 'Proveedores',
            'activeModule' => 'sla-providers',
            'providers' => $providers,
        ]);
    }

    public function createAction(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new Provider())->insert($this->collectInput());
            $this->flash('success', 'Proveedor creado correctamente.');
            $this->redirect('sla/providers/index');
            return;
        }

        $this->render('sla/providers/form', [
            'pageTitle' => 'Nuevo proveedor',
            'activeModule' => 'sla-providers',
            'provider' => null,
        ]);
    }

    public function editAction(?string $id): void
    {
        $provider = (new Provider())->find((int) $id);

        if ($provider === null) {
            $this->redirect('sla/providers/index');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new Provider())->update((int) $id, $this->collectInput());
            $this->flash('success', 'Proveedor actualizado correctamente.');
            $this->redirect('sla/providers/index');
            return;
        }

        $this->render('sla/providers/form', [
            'pageTitle' => 'Editar proveedor',
            'activeModule' => 'sla-providers',
            'provider' => $provider,
        ]);
    }

    public function deleteAction(?string $id): void
    {
        $model = new Provider();

        if ($model->isReferenced((int) $id)) {
            $this->flash('error', 'No se puede eliminar: este proveedor tiene aplicaciones asociadas.');
        } else {
            $model->delete((int) $id);
            $this->flash('success', 'Proveedor eliminado.');
        }

        $this->redirect('sla/providers/index');
    }

    private function collectInput(): array
    {
        return [
            'name' => trim((string) $this->input('name')),
            'tax_id' => $this->input('tax_id') ?: null,
            'commercial_contact' => $this->input('commercial_contact') ?: null,
            'technical_contact' => $this->input('technical_contact') ?: null,
            'email' => $this->input('email') ?: null,
            'phone' => $this->input('phone') ?: null,
            'support_portal' => $this->input('support_portal') ?: null,
            'support_channel' => $this->input('support_channel') ?: null,
            'support_hours' => $this->input('support_hours') ?: null,
            'notes' => $this->input('notes') ?: null,
        ];
    }
}
