<?php
/** @var string $content */
$pageTitle = $pageTitle ?? 'BacklogFlow';
$activeModule = $activeModule ?? 'projects-dashboard';

$subnav = [
    'projects' => [
        ['key' => 'projects-dashboard', 'label' => 'Dashboard', 'href' => 'projects/dashboard/index'],
        ['key' => 'projects-developers', 'label' => 'Desarrolladores', 'href' => 'projects/developers/index'],
        ['key' => 'projects-projects', 'label' => 'Proyectos', 'href' => 'projects/projects/index'],
        ['key' => 'projects-backlog', 'label' => 'Backlog', 'href' => 'projects/backlog/index'],
        ['key' => 'projects-sprints', 'label' => 'Sprints', 'href' => 'projects/sprints/index'],
        ['key' => 'projects-ideas', 'label' => 'Ideas', 'href' => 'projects/ideas/index'],
        ['key' => 'projects-activities', 'label' => 'Actividades', 'href' => 'projects/activities/index'],
        ['key' => 'projects-management', 'label' => 'Seguimiento gerencial', 'href' => 'projects/management/index'],
        ['key' => 'projects-catalogs', 'label' => 'Catálogos', 'href' => 'projects/catalogs/index'],
    ],
    'sla' => [
        ['key' => 'sla-dashboard', 'label' => 'Dashboard', 'href' => 'sla/dashboard/index'],
        ['key' => 'sla-applications', 'label' => 'Aplicaciones', 'href' => 'sla/applications/index'],
        ['key' => 'sla-providers', 'label' => 'Proveedores', 'href' => 'sla/providers/index'],
        ['key' => 'sla-supporttypes', 'label' => 'Tipos y matriz de soporte', 'href' => 'sla/support-types/index'],
        ['key' => 'sla-contracts', 'label' => 'Contratos y vencimientos', 'href' => 'sla/contracts/index'],
        ['key' => 'sla-indicators', 'label' => 'Indicadores', 'href' => 'sla/indicators/index'],
    ],
];
$currentGroup = str_starts_with($activeModule, 'sla') ? 'sla' : 'projects';

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?> · BacklogFlow</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="/assets/js/chart.umd.min.js"></script>
    <script src="/assets/js/charts-init.js"></script>
    <script>
        (function () {
            var stored = localStorage.getItem('bf-theme');
            if (stored) document.documentElement.setAttribute('data-theme', stored);
        })();
    </script>
</head>
<body>
<div class="app-shell">
    <div class="sidebar-backdrop" data-sidebar-backdrop></div>
    <aside class="sidebar" id="bf-sidebar">
        <div class="sidebar-brand">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <rect x="3" y="3" width="8" height="8" rx="2" fill="#2563eb"/>
                <rect x="13" y="3" width="8" height="8" rx="2" fill="#059669"/>
                <rect x="3" y="13" width="8" height="8" rx="2" fill="#059669" fill-opacity="0.55"/>
                <rect x="13" y="13" width="8" height="8" rx="2" fill="#2563eb" fill-opacity="0.55"/>
            </svg>
            BacklogFlow
        </div>

        <div class="sidebar-group">
            <div class="sidebar-group-title">Módulos</div>
            <a href="/index.php?r=projects/dashboard/index" class="<?= $currentGroup === 'projects' ? 'active' : '' ?>">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="3" width="7" height="7" rx="1.5"></rect><rect x="3" y="14" width="7" height="7" rx="1.5"></rect><rect x="14" y="14" width="7" height="7" rx="1.5"></rect></svg>
                Proyectos
            </a>
            <a href="/index.php?r=sla/dashboard/index" class="<?= $currentGroup === 'sla' ? 'active' : '' ?>">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2 3 6v6c0 5 3.8 8.7 9 10 5.2-1.3 9-5 9-10V6l-9-4Z"></path></svg>
                ANS de Aplicaciones
            </a>
        </div>

        <div class="sidebar-hint">
            Usa las pestañas de arriba o los accesos rápidos del dashboard para moverte dentro de cada módulo.
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-heading">
                <button type="button" class="sidebar-toggle" id="bf-sidebar-toggle" aria-label="Abrir menú" aria-expanded="false" aria-controls="bf-sidebar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"></path></svg>
                </button>
                <div>
                    <p class="topbar-eyebrow"><?= $currentGroup === 'sla' ? 'ANS de Aplicaciones' : 'Proyectos' ?></p>
                    <h1><?= htmlspecialchars($pageTitle) ?></h1>
                </div>
            </div>
            <button type="button" class="theme-toggle" id="bf-theme-toggle" aria-label="Cambiar tema claro/oscuro" title="Cambiar tema claro/oscuro">
                <svg id="bf-theme-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="4"></circle>
                    <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"></path>
                </svg>
            </button>
        </header>
        <nav class="subnav" aria-label="Secciones del módulo">
            <?php foreach ($subnav[$currentGroup] as $item): ?>
                <a href="/index.php?r=<?= $item['href'] ?>" class="subnav-pill <?= $activeModule === $item['key'] ? 'active' : '' ?>" <?= $activeModule === $item['key'] ? 'data-active-pill' : '' ?>><?= htmlspecialchars($item['label']) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="page-body">
            <?php if ($flash): ?>
                <div class="flash-banner flash-<?= htmlspecialchars($flash['type']) ?>" role="status">
                    <?php if ($flash['type'] === 'success'): ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                    <?php else: ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    <?php endif; ?>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                    <button type="button" class="flash-dismiss" data-close-flash aria-label="Cerrar aviso">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
                    </button>
                </div>
            <?php endif; ?>
            <?= $content ?>
        </div>
    </main>
</div>

<dialog id="bf-confirm-modal" class="modal modal-confirm">
    <div class="modal-body">
        <p id="bf-confirm-message">¿Confirmas esta acción?</p>
        <div class="form-actions">
            <a id="bf-confirm-action" class="btn btn-danger" href="#">Sí, eliminar</a>
            <button type="button" class="btn btn-secondary" data-close-modal>Cancelar</button>
        </div>
    </div>
</dialog>

<script>
    (function () {
        var root = document.documentElement;
        var btn = document.getElementById('bf-theme-toggle');
        var icon = document.getElementById('bf-theme-icon');
        var moonPath = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>';
        var sunPath = '<circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"></path>';

        function isDark() {
            var explicit = root.getAttribute('data-theme');
            if (explicit) return explicit === 'dark';
            return window.matchMedia('(prefers-color-scheme: dark)').matches;
        }

        function syncIcon() {
            icon.innerHTML = isDark() ? moonPath : sunPath;
        }

        btn.addEventListener('click', function () {
            var next = isDark() ? 'light' : 'dark';
            root.setAttribute('data-theme', next);
            localStorage.setItem('bf-theme', next);
            syncIcon();
        });

        syncIcon();
    })();
</script>
<script>
    (function () {
        var sidebar = document.getElementById('bf-sidebar');
        var toggle = document.getElementById('bf-sidebar-toggle');
        var backdrop = document.querySelector('[data-sidebar-backdrop]');

        function closeSidebar() {
            sidebar.classList.remove('open');
            backdrop.classList.remove('open');
            toggle.setAttribute('aria-expanded', 'false');
        }

        toggle.addEventListener('click', function () {
            var open = sidebar.classList.toggle('open');
            backdrop.classList.toggle('open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        backdrop.addEventListener('click', closeSidebar);
        sidebar.querySelectorAll('a').forEach(function (a) { a.addEventListener('click', closeSidebar); });

        // Keep the active subnav pill visible within its horizontally-scrolling strip.
        var activePill = document.querySelector('[data-active-pill]');
        if (activePill) activePill.scrollIntoView({ block: 'nearest', inline: 'center' });
    })();
</script>
<script>
    (function () {
        // Any element with data-open-modal="dialog-id" opens that <dialog> as a modal.
        document.addEventListener('click', function (e) {
            var opener = e.target.closest('[data-open-modal]');
            if (opener) {
                var dialog = document.getElementById(opener.getAttribute('data-open-modal'));
                if (dialog && typeof dialog.showModal === 'function') dialog.showModal();
                return;
            }

            // Any element with data-close-modal closes its nearest <dialog>; if it isn't
            // inside one (the standalone, non-modal form page), fall back to a normal link.
            var closer = e.target.closest('[data-close-modal]');
            if (closer) {
                var owningDialog = closer.closest('dialog');
                if (owningDialog) {
                    owningDialog.close();
                } else if (closer.dataset.fallbackHref) {
                    window.location.href = closer.dataset.fallbackHref;
                }
                return;
            }

            // Themed replacement for the native confirm() popup, used by every "Eliminar" action.
            var deleteTrigger = e.target.closest('[data-confirm-delete]');
            if (deleteTrigger) {
                var confirmDialog = document.getElementById('bf-confirm-modal');
                document.getElementById('bf-confirm-message').textContent =
                    deleteTrigger.dataset.confirmMessage || '¿Confirmas esta acción?';
                document.getElementById('bf-confirm-action').href = deleteTrigger.dataset.confirmDelete;
                confirmDialog.showModal();
                return;
            }

            var dismissFlash = e.target.closest('[data-close-flash]');
            if (dismissFlash) {
                dismissFlash.closest('.flash-banner').remove();
            }
        });

        // Clicking a dialog's own backdrop area (not its content) closes it.
        document.querySelectorAll('dialog.modal').forEach(function (dialog) {
            dialog.addEventListener('click', function (e) {
                if (e.target === dialog) dialog.close();
            });
        });

        // Auto-dismiss the flash banner after a few seconds.
        var flashBanner = document.querySelector('.flash-banner');
        if (flashBanner) {
            setTimeout(function () { flashBanner.remove(); }, 6000);
        }

        // Native validation bubbles follow the browser's own language, not the page's —
        // set explicit Spanish messages so an all-Spanish app doesn't show English errors.
        document.querySelectorAll('input[required], select[required], textarea[required]').forEach(function (el) {
            el.addEventListener('invalid', function () {
                el.setCustomValidity(el.validity.valueMissing ? 'Este campo es obligatorio.' : 'Ingresa un valor válido.');
            });
            ['input', 'change'].forEach(function (evt) {
                el.addEventListener(evt, function () { el.setCustomValidity(''); });
            });
        });
    })();
</script>
<script src="/assets/js/table-tools.js"></script>
</body>
</html>
