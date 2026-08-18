<?php

namespace App\Controllers\Projects;

use App\Core\Controller;
use App\Helpers\Labels;
use App\Models\Catalog;

class CatalogsController extends Controller
{
    /** @var array<string,string> catalog key (used in the URL) => [table, label group] */
    private const CATALOGS = [
        'priorities' => ['cat_priorities', 'priority', 'Prioridades'],
        'project_statuses' => ['cat_project_statuses', 'project_status', 'Estados de proyecto'],
        'backlog_statuses' => ['cat_backlog_statuses', 'backlog_status', 'Estados de backlog'],
        'activity_statuses' => ['cat_activity_statuses', 'activity_status', 'Estados de actividad'],
        'backlog_types' => ['cat_backlog_types', 'backlog_type', 'Tipos de backlog'],
        'activity_types' => ['cat_activity_types', 'activity_type', 'Tipos de actividad'],
    ];

    public function indexAction(): void
    {
        $catalogs = [];

        foreach (self::CATALOGS as $key => [$table, $labelGroup, $title]) {
            $catalogs[$key] = [
                'title' => $title,
                'label_group' => $labelGroup,
                'rows' => (new Catalog($table))->all(),
            ];
        }

        $this->render('projects/catalogs/index', [
            'pageTitle' => 'Catálogos',
            'activeModule' => 'projects-catalogs',
            'catalogs' => $catalogs,
        ]);
    }

    public function createAction(?string $id, array $params): void
    {
        $key = $params['catalog'] ?? $this->input('catalog');
        $this->guardCatalog($key);
        [$table] = self::CATALOGS[$key];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $code = trim((string) $this->input('code'));
            if ($code === '') {
                $this->flash('error', 'Escribe un valor antes de agregarlo.');
                $this->redirect('projects/catalogs/index');
                return;
            }

            $data = ['code' => $code];
            if ($this->input('sort_order') !== null) {
                $data['sort_order'] = (int) $this->input('sort_order');
            }
            (new Catalog($table))->insert($data);
            $this->flash('success', 'Valor agregado al catálogo.');
        }

        $this->redirect('projects/catalogs/index');
    }

    public function deleteAction(?string $id, array $params): void
    {
        $key = $params['catalog'] ?? $this->input('catalog');
        $this->guardCatalog($key);
        [$table] = self::CATALOGS[$key];

        $model = new Catalog($table);
        if ($model->isReferenced((int) $id)) {
            $this->flash('error', 'No se puede eliminar: este valor ya está en uso.');
        } else {
            $model->delete((int) $id);
            $this->flash('success', 'Valor eliminado del catálogo.');
        }

        $this->redirect('projects/catalogs/index');
    }

    private function guardCatalog(?string $key): void
    {
        if ($key === null || !isset(self::CATALOGS[$key])) {
            $this->redirect('projects/catalogs/index');
            exit;
        }
    }
}
