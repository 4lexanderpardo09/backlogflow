<?php
use App\Helpers\Labels;

/** @var array|null $application @var array|null $ownership @var array $types @var array $criticalityLevels
 *  @var array $modalities @var array $providers @var array $supportTypesCatalog @var array $assignedSupportTypes */
$assignedSupportTypes = $assignedSupportTypes ?? [];
$a = $application ?? [];
$o = $ownership ?? [];
$action = $application === null ? '/index.php?r=sla/applications/create' : '/index.php?r=sla/applications/edit/' . $application['id'];
?>
<div class="card" style="max-width:900px;">
    <form method="post" action="<?= $action ?>">
        <p class="card-title">Identificación</p>
        <div class="form-grid">
            <div class="form-group">
                <label>Nombre de la aplicación</label>
                <input type="text" name="name" required value="<?= htmlspecialchars($a['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Nombre comercial</label>
                <input type="text" name="commercial_name" value="<?= htmlspecialchars($a['commercial_name'] ?? '') ?>">
            </div>
            <div class="form-group full">
                <label>Descripción</label>
                <textarea name="description"><?= htmlspecialchars($a['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Proceso de negocio que soporta</label>
                <input type="text" name="business_process" value="<?= htmlspecialchars($a['business_process'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Área usuaria</label>
                <input type="text" name="requesting_area" value="<?= htmlspecialchars($a['requesting_area'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Responsable funcional</label>
                <input type="text" name="functional_owner" value="<?= htmlspecialchars($a['functional_owner'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Responsable técnico</label>
                <input type="text" name="technical_owner" value="<?= htmlspecialchars($a['technical_owner'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Tipo de aplicación</label>
                <select name="application_type_id" required>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= (int) ($a['application_type_id'] ?? 0) === (int) $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars(Labels::get('application_type', $t['code'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Categoría</label>
                <input type="text" name="category" value="<?= htmlspecialchars($a['category'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Criticidad</label>
                <select name="criticality_id" required>
                    <?php foreach ($criticalityLevels as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= (int) ($a['criticality_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars(Labels::get('criticality', $c['code'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Estado</label>
                <select name="status">
                    <?php foreach (['active' => 'Activa', 'in_implementation' => 'En implementación', 'retired' => 'Retirada'] as $code => $label): ?>
                        <option value="<?= $code ?>" <?= ($a['status'] ?? 'active') === $code ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Número aproximado de usuarios</label>
                <input type="number" name="approx_users" value="<?= htmlspecialchars((string) ($a['approx_users'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label>Ubicación / URL</label>
                <input type="text" name="url" value="<?= htmlspecialchars($a['url'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Ambiente</label>
                <input type="text" name="environment" value="<?= htmlspecialchars($a['environment'] ?? '') ?>">
            </div>
            <div class="form-group full">
                <label>Observaciones</label>
                <textarea name="notes"><?= htmlspecialchars($a['notes'] ?? '') ?></textarea>
            </div>
        </div>

        <p class="card-title" style="margin-top:22px;">Propiedad y modalidad de contratación</p>
        <div class="form-grid">
            <div class="form-group">
                <label>Propiedad</label>
                <select name="ownership">
                    <option value="in_house" <?= ($o['ownership'] ?? '') === 'in_house' ? 'selected' : '' ?>>Desarrollo interno</option>
                    <option value="outsourced" <?= ($o['ownership'] ?? '') === 'outsourced' ? 'selected' : '' ?>>Desarrollo/proveedor externo</option>
                </select>
            </div>
            <div class="form-group">
                <label>Modalidad</label>
                <select name="modality_id">
                    <option value="">Sin definir</option>
                    <?php foreach ($modalities as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= (int) ($o['modality_id'] ?? 0) === (int) $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars(Labels::get('modality', $m['code'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Tipo de licencia</label>
                <input type="text" name="license_type" value="<?= htmlspecialchars($o['license_type'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Modelo de adquisición</label>
                <input type="text" name="acquisition_model" value="<?= htmlspecialchars($o['acquisition_model'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Fecha de adquisición</label>
                <input type="date" name="acquisition_date" value="<?= htmlspecialchars($o['acquisition_date'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Fecha de inicio del contrato</label>
                <input type="date" name="contract_start_date" value="<?= htmlspecialchars($o['contract_start_date'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Fecha de vencimiento</label>
                <input type="date" name="expiration_date" value="<?= htmlspecialchars($o['expiration_date'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Renovación automática</label>
                <select name="auto_renewal">
                    <option value="0" <?= (int) ($o['auto_renewal'] ?? 0) === 0 ? 'selected' : '' ?>>No</option>
                    <option value="1" <?= (int) ($o['auto_renewal'] ?? 0) === 1 ? 'selected' : '' ?>>Sí</option>
                </select>
            </div>
            <div class="form-group">
                <label>Número de licencias</label>
                <input type="number" name="license_count" value="<?= htmlspecialchars((string) ($o['license_count'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label>Costo aproximado</label>
                <input type="number" step="0.01" name="approx_cost" value="<?= htmlspecialchars((string) ($o['approx_cost'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label>Periodicidad del pago</label>
                <input type="text" name="payment_frequency" value="<?= htmlspecialchars($o['payment_frequency'] ?? '') ?>">
            </div>
        </div>

        <p class="card-title" style="margin-top:22px;">Proveedor</p>
        <div class="form-grid">
            <div class="form-group">
                <label>Proveedor (vacío = desarrollo interno)</label>
                <select name="provider_id">
                    <option value="">Desarrollo interno / Área de Sistemas</option>
                    <?php foreach ($providers as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= (int) ($application['provider_id'] ?? 0) === (int) $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Número de contrato</label>
                <input type="text" name="contract_number" value="<?= htmlspecialchars($application['contract_number'] ?? '') ?>">
            </div>
        </div>

        <p class="card-title" style="margin-top:22px;">Tipos de soporte (matriz de soporte)</p>
        <p style="color:var(--color-muted-foreground);font-size:12.5px;margin-top:0;">
            Marca los tipos de soporte que presta <em>esta</em> aplicación y el nivel de escalamiento que le
            corresponde aquí (tú decides qué significa cada número, ej. 1 = primera línea, 2 = especialista, 3 =
            proveedor...), y abre "Datos de contacto" para registrar a quién escalar para ese tipo puntual.
            ¿Falta un tipo o quieres eliminar/renombrar uno del catálogo?
            <a href="/index.php?r=sla/support-types/index" target="_blank">Gestionar catálogo de tipos de soporte</a>.
        </p>
        <?php if ($supportTypesCatalog === []): ?>
            <p class="empty-state">Aún no hay tipos de soporte en el catálogo.
                <a href="/index.php?r=sla/support-types/index" target="_blank">Crea el primero aquí</a>.</p>
        <?php else: ?>
        <div class="table-scroll"><table>
            <thead><tr><th style="width:36px;"></th><th>Tipo de soporte</th><th>Nivel</th><th>Contacto para escalar</th></tr></thead>
            <tbody>
            <?php foreach ($supportTypesCatalog as $st): ?>
                <?php
                $typeId = (int) $st['id'];
                $assigned = $assignedSupportTypes[$typeId] ?? null;
                $isAssigned = $assigned !== null;
                $level = $assigned['level'] ?? (int) $st['level'];
                ?>
                <tr>
                    <td><input type="checkbox" name="support_type_ids[]" value="<?= $typeId ?>" <?= $isAssigned ? 'checked' : '' ?>></td>
                    <td><?= htmlspecialchars($st['name']) ?></td>
                    <td>
                        <input type="number" name="level_<?= $typeId ?>" min="1" step="1" style="width:70px;" value="<?= (int) $level ?>">
                    </td>
                    <td>
                        <details<?= $isAssigned && ($assigned['responsible'] ?? $assigned['contact'] ?? null) ? ' open' : '' ?>>
                            <summary style="cursor:pointer;font-size:12.5px;">Datos de contacto</summary>
                            <div class="form-grid" style="margin-top:8px;min-width:420px;">
                                <div class="form-group">
                                    <label>Responsable</label>
                                    <input type="text" name="responsible_<?= $typeId ?>" value="<?= htmlspecialchars($assigned['responsible'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label>Canal</label>
                                    <input type="text" name="channel_<?= $typeId ?>" placeholder="GLPI, correo, teléfono..." value="<?= htmlspecialchars($assigned['channel'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label>Horario</label>
                                    <input type="text" name="hours_<?= $typeId ?>" value="<?= htmlspecialchars($assigned['hours'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label>Tiempo máx. de escalamiento</label>
                                    <input type="text" name="max_escalation_time_<?= $typeId ?>" value="<?= htmlspecialchars($assigned['max_escalation_time'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label>Contacto</label>
                                    <input type="text" name="contact_<?= $typeId ?>" value="<?= htmlspecialchars($assigned['contact'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label>Correo</label>
                                    <input type="email" name="email_<?= $typeId ?>" value="<?= htmlspecialchars($assigned['email'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label>Teléfono</label>
                                    <input type="text" name="phone_<?= $typeId ?>" value="<?= htmlspecialchars($assigned['phone'] ?? '') ?>">
                                </div>
                                <div class="form-group full">
                                    <label>Notas del procedimiento</label>
                                    <textarea name="procedure_notes_<?= $typeId ?>"><?= htmlspecialchars($assigned['procedure_notes'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </details>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>

        <div class="form-actions">
            <button class="btn" type="submit">Guardar</button>
            <a class="btn btn-secondary" href="/index.php?r=sla/applications/index">Cancelar</a>
        </div>
    </form>
</div>
