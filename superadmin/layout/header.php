<?php
// superadmin/layout/header.php
// Cabecera HTML del panel de Super Administrador (diseño independiente — paleta índigo)
require_once __DIR__ . '/../../config/db.php';

// Cabeceras de seguridad HTTP
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

$_sa_nombre = htmlspecialchars($_SESSION['super_admin_nombre'] ?? 'Super Admin');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pagina_titulo) ? htmlspecialchars($pagina_titulo) . ' — ' : '' ?>FarmaCloud · Super Admin</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,300;0,14..32,400;0,14..32,500;0,14..32,600;0,14..32,700;0,14..32,800&display=swap" rel="stylesheet">

    <style>
    /* ────────────────────────────────────────────
       RESET & TOKENS — Panel Super Admin
       Paleta: Índigo / Violeta — diferenciada del panel de farmacia (verde)
    ──────────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        --sa-primario:        #6366f1;
        --sa-primario-hover:  #4f46e5;
        --sa-primario-claro:  #eef2ff;
        --sa-primario-oscuro: #3730a3;
        --sa-secundario:      #8b5cf6;
        --sa-accent:          #a78bfa;

        --sa-bg:              #f8faff;
        --sa-bg-card:         #ffffff;
        --sa-sidebar-bg:      #0f172a;
        --sa-sidebar-text:    rgba(255,255,255,0.75);
        --sa-sidebar-active:  rgba(99,102,241,0.18);
        --sa-sidebar-border:  rgba(255,255,255,0.07);

        --sa-texto:           #0f172a;
        --sa-texto-sec:       #64748b;
        --sa-borde:           #e2e8f0;
        --sa-shadow:          0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.04);
        --sa-shadow-md:       0 4px 6px -1px rgba(0,0,0,0.08), 0 2px 4px -2px rgba(0,0,0,0.04);

        --sa-radio:           10px;
        --sa-radio-lg:        14px;

        --sa-exito:           #10b981;
        --sa-peligro:         #ef4444;
        --sa-advertencia:     #f59e0b;
    }

    html, body { height: 100%; }

    body {
        font-family: 'Inter', system-ui, sans-serif;
        font-size: 0.9375rem;
        color: var(--sa-texto);
        background: var(--sa-bg);
        line-height: 1.5;
        -webkit-font-smoothing: antialiased;
    }

    /* ── LAYOUT ── */
    .sa-app { display: flex; min-height: 100vh; }

    /* ── SIDEBAR ── */
    .sa-sidebar {
        width: 256px;
        flex-shrink: 0;
        background: var(--sa-sidebar-bg);
        display: flex;
        flex-direction: column;
        position: fixed;
        top: 0; left: 0; bottom: 0;
        z-index: 100;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: rgba(255,255,255,0.1) transparent;
    }

    .sa-sidebar-header {
        padding: 24px 20px 20px;
        border-bottom: 1px solid var(--sa-sidebar-border);
    }

    .sa-brand {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 16px;
    }

    .sa-brand-icon {
        width: 36px; height: 36px;
        background: linear-gradient(135deg, var(--sa-primario), var(--sa-secundario));
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        color: #fff;
        flex-shrink: 0;
    }
    .sa-brand-icon svg { width: 18px; height: 18px; }

    .sa-brand-name {
        font-size: 1rem;
        font-weight: 700;
        color: #fff;
        letter-spacing: -0.02em;
    }
    .sa-brand-tag {
        font-size: 0.65rem;
        font-weight: 600;
        color: var(--sa-accent);
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .sa-user-card {
        background: rgba(255,255,255,0.05);
        border: 1px solid var(--sa-sidebar-border);
        border-radius: 10px;
        padding: 10px 12px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .sa-user-avatar {
        width: 32px; height: 32px;
        background: linear-gradient(135deg, var(--sa-primario), var(--sa-secundario));
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700;
        font-size: 0.85rem;
        color: #fff;
        flex-shrink: 0;
    }
    .sa-user-nombre {
        font-size: 0.82rem;
        font-weight: 600;
        color: #fff;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sa-user-rol {
        font-size: 0.68rem;
        color: var(--sa-accent);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    /* ── NAV ── */
    .sa-nav { flex: 1; padding: 16px 12px; }
    .sa-nav-section { margin-bottom: 4px; }
    .sa-nav-label {
        font-size: 0.65rem;
        font-weight: 700;
        color: rgba(255,255,255,0.3);
        text-transform: uppercase;
        letter-spacing: 0.1em;
        padding: 12px 10px 6px;
    }

    .sa-nav-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 10px;
        border-radius: 8px;
        color: var(--sa-sidebar-text);
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 500;
        transition: background 0.15s, color 0.15s;
        margin-bottom: 2px;
    }
    .sa-nav-item svg { width: 18px; height: 18px; flex-shrink: 0; }
    .sa-nav-item:hover {
        background: rgba(255,255,255,0.07);
        color: #fff;
    }
    .sa-nav-item.active {
        background: var(--sa-sidebar-active);
        color: var(--sa-accent);
        border: 1px solid rgba(99,102,241,0.3);
    }

    .sa-sidebar-footer {
        padding: 12px;
        border-top: 1px solid var(--sa-sidebar-border);
    }

    /* ── MAIN ── */
    .sa-main {
        flex: 1;
        margin-left: 256px;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    /* ── TOPBAR ── */
    .sa-topbar {
        background: var(--sa-bg-card);
        border-bottom: 1px solid var(--sa-borde);
        padding: 0 32px;
        height: 64px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: sticky;
        top: 0;
        z-index: 50;
        box-shadow: var(--sa-shadow);
    }

    .sa-topbar-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .sa-topbar-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: linear-gradient(135deg, var(--sa-primario-claro), #ede9fe);
        color: var(--sa-primario);
        border: 1px solid rgba(99,102,241,0.2);
        border-radius: 20px;
        padding: 4px 12px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }
    .sa-topbar-badge svg { width: 12px; height: 12px; }

    .sa-topbar-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--sa-texto);
        letter-spacing: -0.02em;
    }

    .sa-topbar-right {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .sa-topbar-clock {
        font-size: 0.8rem;
        color: var(--sa-texto-sec);
        font-variant-numeric: tabular-nums;
        background: var(--sa-bg);
        border: 1px solid var(--sa-borde);
        border-radius: 8px;
        padding: 6px 12px;
    }

    /* ── CONTENT ── */
    .sa-content {
        flex: 1;
        padding: 32px;
    }

    /* ── PAGE HEADER ── */
    .sa-page-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 28px;
        gap: 16px;
        flex-wrap: wrap;
    }
    .sa-page-header h1 {
        font-size: 1.6rem;
        font-weight: 800;
        color: var(--sa-texto);
        letter-spacing: -0.03em;
        margin-bottom: 4px;
    }
    .sa-page-subtitle {
        font-size: 0.875rem;
        color: var(--sa-texto-sec);
    }
    .sa-page-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }

    /* ── CARDS ── */
    .sa-card {
        background: var(--sa-bg-card);
        border: 1px solid var(--sa-borde);
        border-radius: var(--sa-radio-lg);
        box-shadow: var(--sa-shadow);
        overflow: hidden;
    }
    .sa-card-header {
        padding: 18px 24px;
        border-bottom: 1px solid var(--sa-borde);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .sa-card-title {
        font-size: 0.975rem;
        font-weight: 700;
        color: var(--sa-texto);
        letter-spacing: -0.01em;
    }
    .sa-card-body { padding: 24px; }

    /* ── KPI GRID ── */
    .sa-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 18px;
        margin-bottom: 28px;
    }
    .sa-kpi-card {
        background: var(--sa-bg-card);
        border: 1px solid var(--sa-borde);
        border-radius: var(--sa-radio-lg);
        padding: 22px 24px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: var(--sa-shadow);
        transition: transform 0.15s, box-shadow 0.15s;
    }
    .sa-kpi-card:hover { transform: translateY(-2px); box-shadow: var(--sa-shadow-md); }

    .sa-kpi-icon {
        width: 48px; height: 48px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .sa-kpi-icon svg { width: 22px; height: 22px; }
    .sa-kpi-icon-indigo { background: var(--sa-primario-claro); color: var(--sa-primario); }
    .sa-kpi-icon-purple { background: #ede9fe; color: #7c3aed; }
    .sa-kpi-icon-green  { background: #d1fae5; color: #059669; }
    .sa-kpi-icon-red    { background: #fee2e2; color: #dc2626; }

    .sa-kpi-label {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--sa-texto-sec);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 4px;
    }
    .sa-kpi-valor {
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--sa-texto);
        letter-spacing: -0.04em;
        line-height: 1;
    }

    /* ── TABLA ── */
    .sa-table-wrap { overflow-x: auto; }
    .sa-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }
    .sa-table th {
        text-align: left;
        padding: 11px 16px;
        background: var(--sa-bg);
        color: var(--sa-texto-sec);
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        border-bottom: 1px solid var(--sa-borde);
        white-space: nowrap;
    }
    .sa-table td {
        padding: 13px 16px;
        border-bottom: 1px solid var(--sa-borde);
        vertical-align: middle;
        color: var(--sa-texto);
    }
    .sa-table tbody tr:last-child td { border-bottom: none; }
    .sa-table tbody tr:hover td { background: var(--sa-bg); }

    /* ── BADGES ── */
    .sa-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 0.72rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .sa-badge-activo   { background: #d1fae5; color: #065f46; }
    .sa-badge-inactivo { background: #fee2e2; color: #991b1b; }
    .sa-badge-admin    { background: var(--sa-primario-claro); color: var(--sa-primario-oscuro); }

    /* ── BOTONES ── */
    .sa-btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 18px;
        border-radius: var(--sa-radio);
        font-size: 0.875rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        text-decoration: none;
        font-family: inherit;
        transition: background 0.15s, transform 0.1s, box-shadow 0.15s;
        letter-spacing: -0.01em;
    }
    .sa-btn svg { width: 16px; height: 16px; }
    .sa-btn-primario {
        background: var(--sa-primario);
        color: #fff;
        box-shadow: 0 4px 12px rgba(99,102,241,0.35);
    }
    .sa-btn-primario:hover {
        background: var(--sa-primario-hover);
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(99,102,241,0.4);
    }
    .sa-btn-ghost {
        background: transparent;
        color: var(--sa-texto-sec);
        border: 1px solid var(--sa-borde);
    }
    .sa-btn-ghost:hover {
        background: var(--sa-bg);
        color: var(--sa-texto);
        border-color: #cbd5e1;
    }
    .sa-btn-peligro {
        background: #fee2e2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }
    .sa-btn-peligro:hover { background: #fecaca; }

    .sa-btn-sm { padding: 6px 12px; font-size: 0.8rem; }

    /* ── FORMS ── */
    .sa-form-group { margin-bottom: 20px; }
    .sa-form-label {
        display: block;
        font-size: 0.83rem;
        font-weight: 600;
        color: var(--sa-texto);
        margin-bottom: 7px;
    }
    .sa-form-label span { color: var(--sa-peligro); margin-left: 2px; }
    .sa-form-input, .sa-form-select, .sa-form-textarea {
        width: 100%;
        padding: 10px 14px;
        border: 1.5px solid var(--sa-borde);
        border-radius: var(--sa-radio);
        font-size: 0.9rem;
        color: var(--sa-texto);
        background: var(--sa-bg);
        font-family: inherit;
        outline: none;
        transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
    }
    .sa-form-input:focus, .sa-form-select:focus, .sa-form-textarea:focus {
        border-color: var(--sa-primario);
        background: #fff;
        box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
    }
    .sa-form-textarea { min-height: 90px; resize: vertical; }
    .sa-form-hint {
        font-size: 0.78rem;
        color: var(--sa-texto-sec);
        margin-top: 5px;
    }
    .sa-form-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    .sa-form-grid-3 {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 16px;
    }

    /* ── ALERTAS ── */
    .sa-alerta {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 13px 16px;
        border-radius: var(--sa-radio);
        font-size: 0.875rem;
        margin-bottom: 20px;
        font-weight: 500;
    }
    .sa-alerta svg { width: 18px; height: 18px; flex-shrink: 0; margin-top: 1px; }
    .sa-alerta-exito  { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
    .sa-alerta-error  { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    .sa-alerta-info   { background: #ede9fe; color: #4c1d95; border: 1px solid #c4b5fd; }

    /* ── DIVIDER ── */
    .sa-divider { border: none; border-top: 1px solid var(--sa-borde); margin: 24px 0; }

    /* ── EMPTY STATE ── */
    .sa-empty {
        text-align: center;
        padding: 60px 24px;
        color: var(--sa-texto-sec);
    }
    .sa-empty svg { width: 48px; height: 48px; margin: 0 auto 16px; opacity: 0.35; }
    .sa-empty-title { font-size: 1rem; font-weight: 700; color: var(--sa-texto); margin-bottom: 6px; }
    .sa-empty-msg { font-size: 0.875rem; }

    /* ── RESPONSIVE ── */
    @media (max-width: 768px) {
        .sa-sidebar { display: none; }
        .sa-main { margin-left: 0; }
        .sa-content { padding: 20px 16px; }
        .sa-topbar { padding: 0 16px; }
        .sa-form-grid-2, .sa-form-grid-3 { grid-template-columns: 1fr; }
    }

    <?php if (isset($extra_css_raw)) echo $extra_css_raw; ?>
    </style>
    <?php if (isset($extra_css)): echo $extra_css; endif; ?>
</head>
<body>
<div class="sa-app">

    <!-- Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="sa-main">

        <!-- Topbar -->
        <header class="sa-topbar">
            <div class="sa-topbar-left">
                <span class="sa-topbar-badge">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                    Super Admin
                </span>
                <span class="sa-topbar-title"><?= isset($pagina_titulo) ? htmlspecialchars($pagina_titulo) : 'Panel de Control' ?></span>
            </div>
            <div class="sa-topbar-right">
                <div class="sa-topbar-clock" id="sa-reloj">--:--:--</div>
            </div>
        </header>

        <!-- Contenido de la página -->
        <main class="sa-content">
        <!-- Fin header.php superadmin -->
