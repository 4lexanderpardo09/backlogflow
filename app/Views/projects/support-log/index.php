<?php

use App\Core\View;
use App\Helpers\Labels;
use App\Helpers\SupportLog;
use App\Helpers\Ui;

/** @var array $entries @var array $summary @var array $filters @var array $developers */
$closeIcon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>';
?>
<div class="toolbar">
    <div></div>
    <button type="button" class="btn" data-open-modal="modal-create-support">+ Registrar soporte</button>
</div>

<div class="card kpi-strip-card">
    <div class="kpi-strip">
        <?= Ui::kpiStat((string) $summary['total'], 'Soportes registrados', '<path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>') ?>
        <?= Ui::kpiStat((string) $summary['outside_helpdesk'] . ' (' . $summary['outside_pct'] . '%)', 'Sin mesa de ayuda', '<circle cx="12" cy="12" r="9"></circle><path d="M12 8v4M12 16h.01"></path>', 'warning') ?>
        <?= Ui::kpiStat((string) $summary['helpdesk'], 'En mesa de ayuda', '<path d="M12 2 3 6v6c0 5 3.8 8.7 9 10 5.2-1.3 9-5 9-10V6l-9-4Z"></path>') ?>
        <?= Ui::kpiStat(SupportLog::formatMinutes($summary['total_minutes']), 'Tiempo total', '<circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path>') ?>
        <?= Ui::kpiStat(SupportLog::formatMinutes($summary['avg_minutes']), 'Promedio por soporte', '<circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path>') ?>
    </div>
</div>

<form method="get" action="/index.php" class="filters filters-card">
    <input type="hidden" name="r" value="projects/support-log/index">
    <div class="filters-head">
        <p class="filters-title">Filtros</p>
        <div class="filters-actions">
            <button class="btn btn-secondary" type="submit">Aplicar fechas</button>
            <?php if (array_filter($filters) !== []): ?>
                <a class="btn btn-secondary" href="/index.php?r=projects/support-log/index">Limpiar filtros</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="filters-grid">
        <label class="filter-field"><span>Atendido por</span>
            <select name="developer_id" onchange="this.form.submit()">
                <option value="">Todos</option>
                <?php foreach ($developers as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= (int) $d['id'] === $filters['developer_id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="filter-field"><span>Tipo de soporte</span>
            <select name="category" onchange="this.form.submit()">
                <option value="">Todos</option>
                <?php foreach (Labels::options('support_category') as $code => $label): ?>
                    <option value="<?= $code ?>" <?= $code === $filters['category'] ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="filter-field"><span>Mesa de ayuda</span>
            <select name="helpdesk" onchange="this.form.submit()">
                <option value="">Todos</option>
                <option value="1" <?= $filters['helpdesk'] === '1' ? 'selected' : '' ?>>Sí</option>
                <option value="0" <?= $filters['helpdesk'] === '0' ? 'selected' : '' ?>>No</option>
            </select>
        </label>
        <label class="filter-field"><span>Desde</span>
            <input type="date" name="desde" value="<?= htmlspecialchars($filters['desde'] ?? '') ?>">
        </label>
        <label class="filter-field"><span>Hasta</span>
            <input type="date" name="hasta" value="<?= htmlspecialchars($filters['hasta'] ?? '') ?>">
        </label>
    </div>
</form>

<div class="card">
    <div class="table-scroll"><table>
        <thead>
        <tr>
            <th>Fecha</th><th>Atendido por</th><th>Solicitante</th><th>Tipo</th><th>Qué se hizo</th>
            <th>Mesa de ayuda</th><th>Tiempo</th><th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($entries as $e): ?>
            <tr>
                <td><?= Ui::formatDate($e['log_date']) ?></td>
                <td><?= htmlspecialchars($e['developer_name']) ?></td>
                <td>
                    <?= htmlspecialchars($e['requester'] ?? Labels::NOT_DEFINED) ?>
                    <?php if (!empty($e['requester_area'])): ?><br><span class="text-muted" style="font-size:11.5px;"><?= htmlspecialchars($e['requester_area']) ?></span><?php endif; ?>
                </td>
                <td><?= htmlspecialchars(Labels::get('support_category', $e['category'])) ?></td>
                <td><?= nl2br(htmlspecialchars($e['description'])) ?></td>
                <td>
                    <?php if ((int) $e['in_helpdesk'] === 1): ?>
                        <span class="badge badge-green">Sí</span>
                        <?php if (!empty($e['helpdesk_ticket'])): ?><br><span class="text-muted" style="font-size:11.5px;">#<?= htmlspecialchars($e['helpdesk_ticket']) ?></span><?php endif; ?>
                    <?php else: ?>
                        <span class="badge badge-yellow">No</span>
                    <?php endif; ?>
                </td>
                <td><?= SupportLog::formatMinutes((int) $e['time_minutes']) ?></td>
                <td>
                    <button type="button" class="link-button" data-open-modal="modal-edit-support-<?= $e['id'] ?>">Editar</button>
                    &middot;
                    <button type="button" class="link-button" data-confirm-delete="/index.php?r=projects/support-log/delete/<?= $e['id'] ?>" data-confirm-message="¿Eliminar este registro de soporte?">Eliminar</button>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($entries === []): ?><tr><td colspan="8" class="empty-state">Sin soportes registrados</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>

<dialog id="modal-create-support" class="modal">
    <div class="modal-header">
        <strong>Registrar soporte</strong>
        <button type="button" class="modal-close" data-close-modal aria-label="Cerrar"><?= $closeIcon ?></button>
    </div>
    <div class="modal-body">
        <?php View::renderPartial('projects/support-log/form', ['entry' => null, 'developers' => $developers]) ?>
    </div>
</dialog>

<?php foreach ($entries as $e): ?>
    <dialog id="modal-edit-support-<?= $e['id'] ?>" class="modal">
        <div class="modal-header">
            <strong>Editar soporte</strong>
            <button type="button" class="modal-close" data-close-modal aria-label="Cerrar"><?= $closeIcon ?></button>
        </div>
        <div class="modal-body">
            <?php View::renderPartial('projects/support-log/form', ['entry' => $e, 'developers' => $developers]) ?>
        </div>
    </dialog>
<?php endforeach; ?>
