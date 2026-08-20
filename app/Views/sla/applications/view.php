<?php

use App\Helpers\Labels;
use App\Helpers\Ui;

/** @var array $application @var array|null $ownership @var array|null $schedule @var array|null $availability
 *  @var array $supportMatrix @var array $supportTypes @var array $integrations @var array $dependencies
 *  @var array $raci @var array $incidentSla @var array $indicators @var array|null $latestIndicator
 *  @var string|null $complianceLight @var string|null $contractBucket */
?>
<div class="toolbar">
    <div></div>
    <div>
        <a class="btn btn-secondary" href="/index.php?r=sla/applications/edit/<?= $application['id'] ?>">Editar</a>
        <a class="btn" href="/index.php?r=sla/applications/datasheet/<?= $application['id'] ?>">Ver ficha ANS</a>
    </div>
</div>

<div class="three-col">
    <div class="card">
        <p class="card-title">Identificación</p>
        <div class="table-scroll"><table>
            <tr><td>Tipo</td><td><?= htmlspecialchars(Labels::get('application_type', $application['type_code'])) ?></td></tr>
            <tr><td>Criticidad</td><td><?= Ui::priorityBadge($application['criticality_code']) ?></td></tr>
            <tr><td>Área usuaria</td><td><?= htmlspecialchars($application['requesting_area'] ?? Labels::NOT_DEFINED) ?></td></tr>
            <tr><td>Responsable funcional</td><td><?= htmlspecialchars($application['functional_owner'] ?? Labels::NOT_DEFINED) ?></td></tr>
            <tr><td>Responsable técnico</td><td><?= htmlspecialchars($application['technical_owner'] ?? Labels::NOT_DEFINED) ?></td></tr>
            <tr><td>Usuarios aprox.</td><td><?= htmlspecialchars((string) ($application['approx_users'] ?? Labels::NOT_DEFINED)) ?></td></tr>
        </table></div>
    </div>
    <div class="card">
        <p class="card-title">Proveedor y contrato</p>
        <div class="table-scroll"><table>
            <tr><td>Proveedor</td><td><?= htmlspecialchars($application['provider_name'] ?? Labels::IN_HOUSE_PROVIDER) ?></td></tr>
            <tr><td>Contrato</td><td><?= htmlspecialchars($application['contract_number'] ?? Labels::NOT_DEFINED) ?></td></tr>
            <tr><td>Vence</td><td><?= Ui::formatDate($application['contract_expiration_date'] ?? null) ?></td></tr>
            <tr><td>Alerta</td><td><?= $contractBucket ? Ui::contractAlertBadge($contractBucket) : Labels::NOT_DEFINED ?></td></tr>
            <tr><td>Modalidad</td><td><?= htmlspecialchars(Labels::get('modality', $ownership['modality_code'] ?? null)) ?></td></tr>
        </table></div>
    </div>
    <div class="card">
        <p class="card-title">Cumplimiento ANS (último mes)</p>
        <?php if ($latestIndicator): ?>
            <div class="table-scroll"><table>
                <tr><td>Mes</td><td><?= Ui::formatDate($latestIndicator['month']) ?></td></tr>
                <tr><td>Cumpl. respuesta</td><td><?= $latestIndicator['response_compliance_pct'] ?>%</td></tr>
                <tr><td>Cumpl. solución</td><td><?= $latestIndicator['resolution_compliance_pct'] ?>%</td></tr>
                <tr><td>Disponibilidad</td><td><?= $latestIndicator['availability_pct'] ?>%</td></tr>
                <tr><td>Semáforo</td><td><?= Ui::trafficLight($complianceLight) ?></td></tr>
            </table></div>
        <?php else: ?>
            <p class="empty-state">Sin indicadores registrados</p>
        <?php endif; ?>
    </div>
</div>

<div class="two-col">
    <div class="card">
        <p class="card-title">Matriz de soporte</p>
        <div class="table-scroll"><table>
            <thead><tr><th>Nivel</th><th>Responsable</th><th>Canal</th><th>Horario</th><th>Tiempo máx. escalar</th></tr></thead>
            <tbody>
            <?php foreach ($supportMatrix as $row): ?>
                <tr>
                    <td><?= htmlspecialchars(Labels::get('support_level', (int) $row['level'])) ?></td>
                    <td><?= htmlspecialchars($row['responsible'] ?? Labels::NOT_DEFINED) ?></td>
                    <td><?= htmlspecialchars($row['channel'] ?? Labels::NOT_DEFINED) ?></td>
                    <td><?= htmlspecialchars($row['hours'] ?? Labels::NOT_DEFINED) ?></td>
                    <td><?= htmlspecialchars($row['max_escalation_time'] ?? Labels::NOT_DEFINED) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($supportMatrix === []): ?><tr><td colspan="5" class="empty-state">Sin matriz de soporte definida</td></tr><?php endif; ?>
            </tbody>
        </table></div>
        <p style="margin-top:10px;font-size:12.5px;">
            <?php foreach ([1, 2, 3, 4] as $lvl): ?>
                <a class="btn btn-secondary" style="padding:4px 10px;font-size:12px;" href="/index.php?r=sla/support-matrix/edit/<?= $application['id'] ?>&level=<?= $lvl ?>">Editar N<?= $lvl ?></a>
            <?php endforeach; ?>
        </p>
    </div>
    <div class="card">
        <p class="card-title">ANS de incidentes por prioridad</p>
        <div class="table-scroll"><table>
            <thead><tr><th>Prioridad</th><th>Descripción</th><th>T. respuesta</th><th>T. solución</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($incidentSla as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['priority']) ?></td>
                    <td><?= htmlspecialchars($row['description'] ?? Labels::NOT_DEFINED) ?></td>
                    <td><?= (int) $row['response_time_minutes'] ?> min</td>
                    <td><?= (int) $row['resolution_time_minutes'] ?> min</td>
                    <td style="white-space:nowrap;">
                        <a class="link-button" href="/index.php?r=sla/incident-sla/edit/<?= $application['id'] ?>&priority=<?= urlencode($row['priority']) ?>">Editar</a>
                        <?php if ($row['application_id'] !== null): ?>
                            &middot;
                            <button type="button" class="link-button" data-confirm-delete="/index.php?r=sla/incident-sla/delete/<?= $row['id'] ?>" data-confirm-message="¿Eliminar esta prioridad?">Eliminar</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($incidentSla === []): ?><tr><td colspan="5" class="empty-state">Sin tiempos de respuesta definidos</td></tr><?php endif; ?>
            </tbody>
        </table></div>
        <p style="margin-top:10px;">
            <a class="btn btn-secondary" style="padding:4px 10px;font-size:12px;" href="/index.php?r=sla/incident-sla/edit/<?= $application['id'] ?>">+ Agregar prioridad</a>
        </p>
    </div>
</div>

<div class="two-col">
    <div class="card">
        <p class="card-title">Horario y disponibilidad</p>
        <div class="table-scroll"><table>
            <tr><td>Horario de operación</td><td><?= htmlspecialchars($schedule['operating_hours'] ?? Labels::NOT_DEFINED) ?></td></tr>
            <tr><td>Horario de soporte</td><td><?= htmlspecialchars($schedule['support_hours'] ?? Labels::NOT_DEFINED) ?></td></tr>
            <tr><td>Atención 24x7</td><td><?= ($schedule['support_24x7'] ?? 0) ? 'Sí' : 'No' ?></td></tr>
            <tr><td>Disponibilidad objetivo</td><td><?= htmlspecialchars((string) ($availability['target_availability'] ?? Labels::NOT_DEFINED)) ?><?= isset($availability['target_availability']) ? '%' : '' ?></td></tr>
            <tr><td>Disponibilidad real</td><td><?= htmlspecialchars((string) ($availability['actual_availability'] ?? Labels::NOT_DEFINED)) ?><?= isset($availability['actual_availability']) ? '%' : '' ?></td></tr>
            <tr><td>RTO / RPO</td><td><?= htmlspecialchars(($availability['rto'] ?? Labels::NOT_DEFINED) . ' / ' . ($availability['rpo'] ?? Labels::NOT_DEFINED)) ?></td></tr>
        </table></div>
    </div>
    <div class="card">
        <p class="card-title">Integraciones y dependencias</p>
        <p style="font-weight:600;font-size:12.5px;color:#6b7280;">Integraciones</p>
        <?php foreach ($integrations as $i): ?>
            <p style="margin:0 0 4px;"><?= htmlspecialchars($i['related_system']) ?> — <?= htmlspecialchars($i['integration_type'] ?? Labels::NOT_DEFINED) ?> (<?= htmlspecialchars($i['frequency'] ?? Labels::NOT_DEFINED) ?>)</p>
        <?php endforeach; ?>
        <?php if ($integrations === []): ?><p class="empty-state">Sin integraciones registradas</p><?php endif; ?>
        <p style="font-weight:600;font-size:12.5px;color:#6b7280;margin-top:10px;">Dependencias</p>
        <?php foreach ($dependencies as $d): ?>
            <p style="margin:0 0 4px;"><?= htmlspecialchars($d['dependency']) ?></p>
        <?php endforeach; ?>
        <?php if ($dependencies === []): ?><p class="empty-state">Sin dependencias registradas</p><?php endif; ?>
    </div>
</div>

<div class="card">
    <p class="card-title">Matriz RACI</p>
    <div class="table-scroll"><table>
        <thead><tr><th>Actividad</th><th>Responsable (R)</th><th>Aprobador (A)</th><th>Consultado (C)</th><th>Informado (I)</th></tr></thead>
        <tbody>
        <?php foreach ($raci as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['activity']) ?></td>
                <td><?= htmlspecialchars($r['responsible'] ?? Labels::NOT_DEFINED) ?></td>
                <td><?= htmlspecialchars($r['accountable'] ?? Labels::NOT_DEFINED) ?></td>
                <td><?= htmlspecialchars($r['consulted'] ?? Labels::NOT_DEFINED) ?></td>
                <td><?= htmlspecialchars($r['informed'] ?? Labels::NOT_DEFINED) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($raci === []): ?><tr><td colspan="5" class="empty-state">Sin matriz RACI definida</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
