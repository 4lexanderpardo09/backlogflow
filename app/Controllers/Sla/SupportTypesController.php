<?php

namespace App\Controllers\Sla;

use App\Core\Controller;
use App\Models\Catalog;

/**
 * Registry of support types (functional support, user administration,
 * permission administration, etc.), each with a free-form level number the
 * team assigns per their own escalation scheme. This is only a
 * classification catalog — actual support tickets/incidents are tracked
 * elsewhere.
 */
class SupportTypesController extends Controller
{
    public function indexAction(): void
    {
        $this->render('sla/support-types/index', [
            'pageTitle' => 'Tipos de soporte',
            'activeModule' => 'sla-supporttypes',
            'supportTypes' => (new Catalog('cat_support_types'))->all('name ASC'),
        ]);
    }

    public function createAction(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim((string) $this->input('name'));
            $level = max(1, (int) $this->input('level', 1));

            if ($name !== '') {
                $catalog = new Catalog('cat_support_types');
                $catalog->insert(['code' => $this->uniqueCode($name), 'name' => $name, 'level' => $level]);
                $this->flash('success', 'Tipo de soporte creado correctamente.');
            } else {
                $this->flash('error', 'El nombre del tipo de soporte es obligatorio.');
            }
        }

        $this->redirect('sla/support-types/index');
    }

    public function updateAction(?string $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $level = max(1, (int) $this->input('level', 1));
            $name = trim((string) $this->input('name'));
            $data = ['level' => $level];
            if ($name !== '') {
                $data['name'] = $name;
            }
            (new Catalog('cat_support_types'))->update((int) $id, $data);
            $this->flash('success', 'Tipo de soporte actualizado correctamente.');
        }

        $this->redirect('sla/support-types/index');
    }

    public function deleteAction(?string $id): void
    {
        $catalog = new Catalog('cat_support_types');
        $typeId = (int) $id;

        if ($catalog->isReferenced($typeId)) {
            $this->flash('error', 'No se puede eliminar: este tipo de soporte está asignado a una o más aplicaciones.');
        } else {
            $catalog->delete($typeId);
            $this->flash('success', 'Tipo de soporte eliminado.');
        }

        $this->redirect('sla/support-types/index');
    }

    private function uniqueCode(string $name): string
    {
        $base = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', $name), '_'));
        $base = $base !== '' ? $base : 'support_type';
        $code = $base;
        $pdo = \App\Core\Database::connection();

        $i = 2;
        while (true) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM cat_support_types WHERE code = :code');
            $stmt->execute(['code' => $code]);
            if ((int) $stmt->fetchColumn() === 0) {
                return $code;
            }
            $code = $base . '_' . $i;
            $i++;
        }
    }
}
