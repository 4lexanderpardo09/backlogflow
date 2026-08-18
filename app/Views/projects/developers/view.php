<?php

use App\Helpers\Labels;
use App\Helpers\Ui;

/** @var array $developer @var array $projects @var array $backlogs @var array $activities */
?>
<?= Ui::kpiStrip(
    Ui::kpiHero($overallProgress . '%', $developer['name'] . ' — ' . ($developer['position'] ?? Labels::NOT_DEFINED)),
    Ui::kpiStat((string) count($projects), 'Proyectos', '<rect x="3" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="3" width="7" height="7" rx="1.5"></rect><rect x="3" y="14" width="7" height="7" rx="1.5"></rect><rect x="14" y="14" width="7" height="7" rx="1.5"></rect>')
    . Ui::kpiStat((string) count($backlogs), 'Backlogs', '<path d="M4 6h16M4 12h16M4 18h7"></path>')
    . Ui::kpiStat((string) count($activities), 'Actividades', '<rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M8 12l3 3 5-6"></path>')
    . Ui::kpiStat((string) count($pendingActivities), 'Pendientes', '<circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path>', 'warning')
    . Ui::kpiStat((string) count($overdueActivities), 'Vencidas', '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>', 'danger')
    . Ui::kpiStat((string) count($highPriorityActivities), 'Alta/crítica', '<path d="M13 2 3 14h9l-1 8 10-12h-9l1-8Z"></path>', 'danger')
) ?>

<div class="card">
    <p class="card-title">Sus proyectos</p>
    <div class="table-scroll"><table>
        <thead><tr><th>Proyecto</th><th>Estado</th><th>Avance</th></tr></thead>
        <tbody>
        <?php foreach ($projects as $p): ?>
            <tr>
                <td><a href="/index.php?r=projects/projects/view/<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></a></td>
                <td><?= Ui::statusBadge('project_status', $p['status_code']) ?></td>
                <td style="min-width:120px;"><?= Ui::progressBar($p['progress_percent']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($projects === []): ?><tr><td colspan="3" class="empty-state">Sin proyectos asignados</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>

<div class="card">
    <p class="card-title">Sus actividades</p>
    <div class="table-scroll"><table>
        <thead><tr><th>Actividad</th><th>Backlog</th><th>Prioridad</th><th>Estado</th><th>Avance</th><th>Fecha límite</th></tr></thead>
        <tbody>
        <?php foreach ($activities as $a): ?>
            <tr>
                <td><?= htmlspecialchars($a['name']) ?></td>
                <td><?= htmlspecialchars($a['backlog_description']) ?></td>
                <td><?= Ui::priorityBadge($a['priority_code']) ?></td>
                <td><?= Ui::statusBadge('activity_status', $a['system_status']) ?></td>
                <td style="min-width:100px;"><?= Ui::progressBar($a['progress_percent']) ?></td>
                <td><?= Ui::formatDate($a['due_date']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($activities === []): ?><tr><td colspan="6" class="empty-state">Sin actividades asignadas</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
