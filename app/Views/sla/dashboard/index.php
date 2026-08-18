<?php

use App\Helpers\Charts;
use App\Helpers\Labels;
use App\Helpers\Ui;

/** @var array $summary @var array $filters @var array $criticalityLevels @var array $types @var array $providerNames */
$kpi = $summary['kpi'];
$hasActiveFilters = array_filter($filters) !== [];
?>

<form method="get" action="/index.php" class="filters">
    <input type="hidden" name="r" value="sla/dashboard/index">
    <select name="criticality" onchange="this.form.submit()">
        <option value="">Toda criticidad</option>
        <?php foreach ($criticalityLevels as $c): ?>
            <option value="<?= $c['code'] ?>" <?= $c['code'] === $filters['criticality'] ? 'selected' : '' ?>><?= htmlspecialchars(Labels::get('criticality', $c['code'])) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="type" onchange="this.form.submit()">
        <option value="">Todo tipo</option>
        <?php foreach ($types as $t): ?>
            <option value="<?= $t['code'] ?>" <?= $t['code'] === $filters['type'] ? 'selected' : '' ?>><?= htmlspecialchars(Labels::get('application_type', $t['code'])) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="provider" onchange="this.form.submit()">
        <option value="">Todo proveedor</option>
        <?php foreach ($providerNames as $p): ?>
            <option value="<?= htmlspecialchars($p) ?>" <?= $p === $filters['provider'] ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($hasActiveFilters): ?>
        <a class="btn btn-secondary" href="/index.php?r=sla/dashboard/index">Limpiar filtros</a>
    <?php endif; ?>
</form>

<?= Ui::kpiStrip(
    Ui::kpiHero(($kpi['avg_compliance'] ?? Labels::NOT_DEFINED) . ($kpi['avg_compliance'] !== null ? '%' : ''), 'Cumplimiento promedio del ANS'),
    Ui::kpiStat((string) $kpi['total_applications'], 'Aplicaciones', '<path d="M4 4h16v16H4z"></path><path d="M4 10h16M10 4v16"></path>')
    . Ui::kpiStat((string) $kpi['critical_applications'], 'Críticas', '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>', 'danger')
    . Ui::kpiStat((string) $kpi['in_house_applications'], 'Propias', '<path d="M3 9l9-7 9 7"></path><path d="M5 10v10h14V10"></path>')
    . Ui::kpiStat((string) $kpi['third_party_applications'], 'De terceros', '<rect x="4" y="3" width="16" height="18" rx="1"></rect><path d="M9 8h1M9 12h1M9 16h1M14 8h1M14 12h1M14 16h1"></path>')
    . Ui::kpiStat((string) $kpi['saas_applications'], 'SaaS', '<path d="M17 18H6a4 4 0 1 1 .5-7.97A5 5 0 0 1 17 9a3.5 3.5 0 0 1 0 7Z"></path>', 'primary')
    . Ui::kpiStat((string) $kpi['providers_count'], 'Proveedores', '<path d="M3 21h18M5 21V7l7-4 7 4v14"></path>')
    . Ui::kpiStat((string) $kpi['contracts_expiring_soon'], 'Contratos por vencer', '<rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4M8 2v4M3 10h18"></path>', 'warning')
    . Ui::kpiStat((string) $kpi['applications_without_ans'], 'Sin ANS definido', '<rect x="4" y="3" width="16" height="18" rx="1"></rect><path d="M9 9h6M9 13h6M9 17h3"></path>')
    . Ui::kpiStat((string) $kpi['applications_with_ans'], 'Con ANS vigente', '<path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>', 'success')
    . Ui::kpiStat((string) $kpi['applications_with_breaches'], 'Con incumplimientos', '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>', 'danger')
    . Ui::kpiStat(($kpi['avg_availability'] ?? Labels::NOT_DEFINED) . ($kpi['avg_availability'] !== null ? '%' : ''), 'Disponibilidad prom.', '<circle cx="12" cy="12" r="9"></circle><path d="M8 12h2l1.5-3 2 6 1.5-3H18"></path>', 'primary')
) ?>

<div class="two-col">
    <div class="card">
        <p class="card-title">Distribución por criticidad</p>
        <?= Charts::donut(array_combine(
            array_map(fn ($k) => Labels::get('criticality', $k), array_keys($summary['criticality_distribution'])),
            array_values($summary['criticality_distribution'])
        )) ?>
    </div>
    <div class="card">
        <p class="card-title">Distribución por tipo de aplicación</p>
        <?= Charts::horizontalBar(array_combine(
            array_map(fn ($k) => Labels::get('application_type', $k), array_keys($summary['type_distribution'])),
            array_values($summary['type_distribution'])
        ), 'Aplicaciones') ?>
    </div>
</div>

<div class="two-col">
    <div class="card">
        <p class="card-title">Distribución por proveedor</p>
        <?= Charts::horizontalBar($summary['provider_distribution'], 'Aplicaciones') ?>
    </div>
    <div class="card">
        <p class="card-title">Distribución por modalidad</p>
        <?= Charts::horizontalBar(array_combine(
            array_map(fn ($k) => Labels::get('modality', $k), array_keys($summary['modality_distribution'])),
            array_values($summary['modality_distribution'])
        ), 'Aplicaciones') ?>
    </div>
</div>

<div class="card">
    <p class="card-title">Contratos, licencias y certificados próximos a vencer o vencidos</p>
    <div class="table-scroll"><table>
        <thead><tr><th>Aplicación</th><th>Tipo</th><th>Detalle</th><th>Vencimiento</th><th>Alerta</th></tr></thead>
        <tbody>
        <?php foreach ($summary['upcoming_contracts'] as $c): ?>
            <tr>
                <td><?= htmlspecialchars($c['application_name']) ?></td>
                <td><?= htmlspecialchars(Labels::get('contract_type', $c['type'])) ?></td>
                <td><?= htmlspecialchars($c['label'] ?? Labels::NOT_DEFINED) ?></td>
                <td><?= Ui::formatDate($c['expiration_date']) ?></td>
                <td><?= Ui::contractAlertBadge($c['bucket']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($summary['upcoming_contracts'] === []): ?><tr><td colspan="5" class="empty-state">Sin vencimientos próximos</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
