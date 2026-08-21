<?php

use App\Helpers\Labels;
use App\Helpers\Ui;

/** @var array $application @var array|null $ownership @var array|null $schedule @var array|null $availability
 *  @var array $supportTypes @var array $incidentSla @var array|null $latestIndicator */
?>
<div class="no-print" style="margin-bottom:16px;">
    <button class="btn" onclick="window.print()">Imprimir / Guardar como PDF</button>
    <a class="btn btn-secondary" href="/index.php?r=sla/applications/view/<?= $application['id'] ?>">Volver</a>
</div>

<div class="card">
    <p class="card-title">1-6. Identificación del servicio</p>
    <div class="table-scroll"><table>
        <tr><td style="width:220px;">Aplicación</td><td><?= htmlspecialchars($application['name']) ?></td></tr>
        <tr><td>Descripción</td><td><?= htmlspecialchars($application['description'] ?? Labels::NOT_DEFINED) ?></td></tr>
        <tr><td>Alcance / proceso</td><td><?= htmlspecialchars($application['business_process'] ?? Labels::NOT_DEFINED) ?></td></tr>
        <tr><td>Horario de operación</td><td><?= htmlspecialchars($schedule['operating_hours'] ?? Labels::NOT_DEFINED) ?></td></tr>
        <tr><td>Usuarios</td><td><?= htmlspecialchars((string) ($application['approx_users'] ?? Labels::NOT_DEFINED)) ?></td></tr>
        <tr><td>Criticidad</td><td><?= Ui::priorityBadge($application['criticality_code']) ?></td></tr>
    </table></div>
</div>

<div class="card">
    <p class="card-title">7-10. Responsables y canales de soporte</p>
    <div class="table-scroll"><table>
        <tr><td style="width:220px;">Responsable funcional</td><td><?= htmlspecialchars($application['functional_owner'] ?? Labels::NOT_DEFINED) ?></td></tr>
        <tr><td>Responsable técnico</td><td><?= htmlspecialchars($application['technical_owner'] ?? Labels::NOT_DEFINED) ?></td></tr>
        <tr><td>Proveedor</td><td><?= htmlspecialchars($application['provider_name'] ?? Labels::IN_HOUSE_PROVIDER) ?></td></tr>
    </table></div>
</div>

<div class="card">
    <p class="card-title">11. Niveles de soporte (matriz de soporte)</p>
    <div class="table-scroll"><table>
        <thead><tr><th>Tipo de soporte</th><th>Nivel</th><th>Responsable</th><th>Canal</th><th>Contacto</th></tr></thead>
        <tbody>
        <?php foreach ($supportTypes as $st): ?>
            <tr>
                <td><?= htmlspecialchars($st['name']) ?></td>
                <td><?= htmlspecialchars(Labels::get('support_type_level', (int) $st['level'])) ?></td>
                <td><?= htmlspecialchars($st['responsible'] ?? Labels::NOT_DEFINED) ?></td>
                <td><?= htmlspecialchars($st['channel'] ?? Labels::NOT_DEFINED) ?></td>
                <td><?= htmlspecialchars($st['contact'] ?? Labels::NOT_DEFINED) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($supportTypes === []): ?><tr><td colspan="5" class="empty-state">Sin matriz de soporte definida</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>

<div class="card">
    <p class="card-title">12-13. Tiempos de respuesta y solución</p>
    <div class="table-scroll"><table>
        <thead><tr><th>Prioridad</th><th>Descripción</th><th>T. respuesta</th><th>T. solución</th></tr></thead>
        <tbody>
        <?php foreach ($incidentSla as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['priority']) ?></td>
                <td><?= htmlspecialchars($row['description'] ?? Labels::NOT_DEFINED) ?></td>
                <td><?= (int) $row['response_time_minutes'] ?> min</td>
                <td><?= (int) $row['resolution_time_minutes'] ?> min</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>


<div class="card">
    <p class="card-title">20-23. Indicadores y vigencia</p>
    <div class="table-scroll"><table>
        <?php if ($latestIndicator): ?>
            <tr><td style="width:220px;">Último mes reportado</td><td><?= Ui::formatDate($latestIndicator['month']) ?></td></tr>
            <tr><td>Cumplimiento respuesta</td><td><?= $latestIndicator['response_compliance_pct'] ?>%</td></tr>
            <tr><td>Cumplimiento solución</td><td><?= $latestIndicator['resolution_compliance_pct'] ?>%</td></tr>
            <tr><td>Disponibilidad medida</td><td><?= $latestIndicator['availability_pct'] ?>%</td></tr>
        <?php else: ?>
            <tr><td colspan="2" class="empty-state">Sin indicadores registrados aún</td></tr>
        <?php endif; ?>
        <tr><td>Periodicidad de revisión</td><td>Mensual</td></tr>
        <tr><td>Fecha de generación de esta ficha</td><td><?= date('d/m/Y') ?></td></tr>
    </table></div>
</div>
