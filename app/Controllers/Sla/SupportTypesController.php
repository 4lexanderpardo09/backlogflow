<?php

namespace App\Controllers\Sla;

use App\Core\Controller;
use App\Models\Catalog;

/**
 * Registry of support types (functional support, user administration,
 * permission administration, etc.) classified by level: Nivel 1 = things
 * like user/permission administration, Nivel 2 = everything else the
 * application itself provides support for. This is only a classification
 * catalog — actual support tickets/incidents are tracked elsewhere.
 */
class SupportTypesController extends Controller
{
    public function indexAction(): void
    {
        $this->render('sla/support-types/index', [
            'pageTitle' => 'Tipos de soporte',
            'activeModule' => 'sla-supporttypes',
            'supportTypes' => (new Catalog('cat_support_types'))->all('code ASC'),
        ]);
    }

    public function updateAction(?string $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $level = (int) $this->input('level', 2);
            $level = in_array($level, [1, 2], true) ? $level : 2;
            (new Catalog('cat_support_types'))->update((int) $id, ['level' => $level]);
            $this->flash('success', 'Nivel actualizado correctamente.');
        }

        $this->redirect('sla/support-types/index');
    }
}
