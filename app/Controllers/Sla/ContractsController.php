<?php

namespace App\Controllers\Sla;

use App\Core\Controller;
use App\Helpers\ContractAlert;
use App\Models\Application;
use App\Models\ContractExpiration;

class ContractsController extends Controller
{
    public function indexAction(): void
    {
        $contracts = (new ContractExpiration())->allWithApplication();
        $today = date('Y-m-d');

        $contracts = array_map(function (array $c) use ($today) {
            $c['bucket'] = ContractAlert::bucket($c['expiration_date'], $today);
            return $c;
        }, $contracts);

        usort($contracts, fn ($a, $b) => $a['expiration_date'] <=> $b['expiration_date']);

        $this->render('sla/contracts/index', [
            'pageTitle' => 'Contratos y vencimientos',
            'activeModule' => 'sla-contracts',
            'contracts' => $contracts,
        ]);
    }

    public function createAction(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new ContractExpiration())->insert([
                'application_id' => (int) $this->input('application_id'),
                'type' => $this->input('type'),
                'label' => $this->input('label') ?: null,
                'expiration_date' => $this->input('expiration_date'),
                'notes' => $this->input('notes') ?: null,
            ]);
            $this->flash('success', 'Vencimiento registrado correctamente.');
            $this->redirect('sla/contracts/index');
            return;
        }

        $this->render('sla/contracts/form', [
            'pageTitle' => 'Nuevo vencimiento',
            'activeModule' => 'sla-contracts',
            'applications' => (new Application())->all('name ASC'),
        ]);
    }

    public function deleteAction(?string $id): void
    {
        (new ContractExpiration())->delete((int) $id);
        $this->flash('success', 'Registro de vencimiento eliminado.');
        $this->redirect('sla/contracts/index');
    }
}
