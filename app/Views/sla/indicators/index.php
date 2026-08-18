<?php use App\Helpers\Ui; ?>
<div class="toolbar">
    <div></div>
    <a class="btn" href="/index.php?r=sla/indicators/create">+ Registrar indicador mensual</a>
</div>

<div class="card">
    <div class="table-scroll"><table>
        <thead>
        <tr>
            <th>Aplicación</th><th>Mes</th><th>Cumpl. respuesta</th><th>Cumpl. solución</th>
            <th>Disponibilidad</th><th>Incidentes</th><th>Incumplimientos</th><th>Semáforo</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['application_name']) ?></td>
                <td><?= Ui::formatDate($r['month']) ?></td>
                <td><?= $r['response_compliance_pct'] ?>%</td>
                <td><?= $r['resolution_compliance_pct'] ?>%</td>
                <td><?= $r['availability_pct'] ?>%</td>
                <td><?= $r['incident_count'] ?></td>
                <td><?= $r['breach_count'] ?></td>
                <td><?= Ui::trafficLight($r['semaphore']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?><tr><td colspan="8" class="empty-state">Sin indicadores registrados</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
