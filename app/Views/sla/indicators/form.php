<div class="card" style="max-width:760px;">
    <form method="post" action="/index.php?r=sla/indicators/create">
        <div class="form-grid">
            <div class="form-group">
                <label>Aplicación</label>
                <select name="application_id" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($applications as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Mes reportado</label>
                <input type="date" name="month" required placeholder="Primer día del mes">
            </div>
            <div class="form-group">
                <label>% cumplimiento tiempo de respuesta</label>
                <input type="number" step="0.01" name="response_compliance_pct" required>
            </div>
            <div class="form-group">
                <label>% cumplimiento tiempo de solución</label>
                <input type="number" step="0.01" name="resolution_compliance_pct" required>
            </div>
            <div class="form-group">
                <label>% disponibilidad</label>
                <input type="number" step="0.01" name="availability_pct" required>
            </div>
            <div class="form-group">
                <label>Número de incidentes</label>
                <input type="number" name="incident_count" value="0">
            </div>
            <div class="form-group">
                <label>Incidentes críticos</label>
                <input type="number" name="critical_incident_count" value="0">
            </div>
            <div class="form-group">
                <label>Incidentes reincidentes</label>
                <input type="number" name="recurring_incident_count" value="0">
            </div>
            <div class="form-group">
                <label>Tiempo promedio de respuesta</label>
                <input type="text" name="avg_response_time" placeholder="Ej: 20 min">
            </div>
            <div class="form-group">
                <label>Tiempo promedio de solución</label>
                <input type="text" name="avg_resolution_time" placeholder="Ej: 3 horas">
            </div>
            <div class="form-group">
                <label>Incumplimientos del ANS</label>
                <input type="number" name="breach_count" value="0">
            </div>
            <div class="form-group">
                <label>Escalaciones al proveedor</label>
                <input type="number" name="escalation_count" value="0">
            </div>
        </div>
        <div class="form-actions">
            <button class="btn" type="submit">Guardar</button>
            <a class="btn btn-secondary" href="/index.php?r=sla/indicators/index">Cancelar</a>
        </div>
    </form>
</div>
