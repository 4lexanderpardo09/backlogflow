<?php

use App\Helpers\Ui;

/** @var array $sprints @var array $projects @var int $projectFilter */
?>
<p style="color:var(--color-muted-foreground);margin-top:0;">
    Cada sprint es un ciclo de reunión de backlog con los dueños de proceso (la duración la define cada proyecto).
    Al cerrar un sprint se calcula qué % de lo comprometido se cumplió y lo pendiente pasa automáticamente al siguiente.
</p>

<div class="toolbar">
    <form method="get" action="/index.php" class="filters" style="margin:0;">
        <input type="hidden" name="r" value="projects/sprints/index">
        <select name="project_id" onchange="this.form.submit()" aria-label="Filtrar por proyecto">
            <option value="">Todos los proyectos</option>
            <?php foreach ($projects as $p): ?>
                <option value="<?= $p['id'] ?>" <?= (string) $p['id'] === (string) $projectFilter ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <button type="button" class="btn" data-open-modal="modal-open-sprint">+ Abrir sprint</button>
</div>

<div class="card">
    <div class="table-scroll"><table>
        <thead><tr><th>Proyecto</th><th>#</th><th>Periodo</th><th>Dueño de proceso</th><th>Estado</th><th>% cumplido</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($sprints as $s): ?>
            <tr>
                <td><?= htmlspecialchars($s['project_name']) ?></td>
                <td><?= (int) $s['sequence_number'] ?></td>
                <td><?= Ui::formatDate($s['start_date']) ?> — <?= Ui::formatDate($s['end_date']) ?></td>
                <td><?= htmlspecialchars($s['process_owner'] ?? '—') ?></td>
                <td><span class="badge <?= $s['status'] === 'open' ? 'badge-blue' : 'badge-gray' ?>"><?= $s['status'] === 'open' ? 'Abierto' : 'Cerrado' ?></span></td>
                <td style="min-width:120px;"><?= $s['completion_percent'] !== null ? Ui::progressBar((float) $s['completion_percent']) : '<span class="text-muted">—</span>' ?></td>
                <td>
                    <a class="link-button" href="/index.php?r=projects/sprints/view/<?= $s['id'] ?>">Ver</a>
                    <?php if ($s['status'] === 'open'): ?>
                        &middot;
                        <button type="button" class="link-button" data-confirm-delete="/index.php?r=projects/sprints/close/<?= $s['id'] ?>" data-confirm-message="¿Cerrar este sprint? Se calculará el % cumplido y lo pendiente pasará al siguiente sprint.">Cerrar</button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($sprints === []): ?><tr><td colspan="7" class="empty-state">Sin sprints registrados</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>

<dialog id="modal-open-sprint" class="modal">
    <div class="modal-header">
        <strong>Abrir nuevo sprint</strong>
        <button type="button" class="modal-close" data-close-modal aria-label="Cerrar">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
        </button>
    </div>
    <div class="modal-body">
        <div class="card" style="max-width:520px;">
            <form method="post" action="/index.php?r=projects/sprints/create">
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Proyecto</label>
                        <select name="project_id" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($projects as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (cada <?= (int) $p['sprint_duration_days'] ?> días)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Dueño de proceso</label>
                        <input type="text" name="process_owner" placeholder="Persona o área que participa en la reunión">
                    </div>
                </div>
                <p style="color:var(--color-muted-foreground);font-size:12.5px;">La fecha de fin se calcula sola según la duración de sprint definida en el proyecto.</p>
                <div class="form-actions">
                    <button class="btn" type="submit">Abrir sprint</button>
                    <button type="button" class="btn btn-secondary" data-close-modal>Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</dialog>
