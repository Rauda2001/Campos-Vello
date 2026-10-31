<?php
/**
 * ================================
 * Dashboard de Administrador
 * Campo Vello - Sistema de Facturación
 * ================================
 * Este archivo muestra:
 * - Estadísticas generales
 * - Accesos rápidos para el administrador
 * - Alertas de stock bajo
 */

// ================================
// Importar configuraciones y funciones
// ================================
require_once __DIR__ . '/../includes/config.php';            // Configuración general y conexión
require_once __DIR__ . '/../includes/autenticacion.php';     // Verificación de roles y sesiones
require_once __DIR__ . '/../includes/funciones.php';         // Funciones generales del sistema

// ================================
// Validar que el usuario sea administrador
// ================================
if (!is_admin()) {
    header('Location: ../index.php');   // Si no es admin → redirigir
    exit;
}

// ================================
// Conexión PDO
// ================================
$pdo = getPDO();

// ================================
// Consultas de métricas del sistema
// ================================

// Total de productos registrados
$totalProducts = $pdo->query('SELECT COUNT(*) FROM productos')->fetchColumn();

// Total de clientes registrados
$totalClients = $pdo->query('SELECT COUNT(*) FROM clientes')->fetchColumn();

// Total de facturas generadas
$totalInvoices = $pdo->query('SELECT COUNT(*) FROM facturas')->fetchColumn();

// Productos con stock bajo (<5)
$lowStock = $pdo->query('SELECT COUNT(*) FROM productos WHERE stock < 5')->fetchColumn();
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body.dashboard-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: #f4f7f5;
            color: #23332a;
        }
        .dashboard-nav {
            min-height: 72px;
            padding: 12px clamp(18px, 4vw, 56px);
            background: #17613c;
            box-shadow: 0 3px 14px rgba(20, 54, 35, .14);
        }
        .dashboard-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }
        .dashboard-brand img {
            display: block;
            width: 145px;
            height: 38px;
            object-fit: cover;
            object-position: left center;
            border-radius: 6px;
        }
        .dashboard-brand-divider {
            width: 1px;
            height: 30px;
            background: rgba(255, 255, 255, .3);
        }
        .dashboard-brand strong { font-size: .98rem; letter-spacing: .01em; }
        .dashboard-logout {
            background: #fff;
            color: #17613c;
            font-weight: 600;
        }
        .dashboard-logout:hover { background: #eaf3ed; color: #124a2e; }
        .dashboard-main {
            width: min(1120px, calc(100% - 36px));
            flex: 1;
            margin: 38px auto 54px;
        }
        .dashboard-welcome {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
        }
        .dashboard-eyebrow {
            margin: 0 0 7px;
            color: #568168;
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
        }
        .dashboard-welcome h1 {
            margin: 0;
            color: #1c3b2a;
            font-size: clamp(1.7rem, 3vw, 2.25rem);
            font-weight: 700;
            letter-spacing: -.035em;
        }
        .dashboard-subtitle { margin: 8px 0 0; color: #6a796f; }
        .dashboard-date {
            padding: 9px 13px;
            border: 1px solid #e0e9e2;
            border-radius: 999px;
            background: #fff;
            color: #5d6d62;
            font-size: .88rem;
        }
        .dashboard-panel {
            padding: clamp(20px, 3vw, 34px);
            border: 1px solid #e3ebe5;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 12px 34px rgba(28, 62, 41, .07);
        }
        .dashboard-section-heading {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 16px;
        }
        .dashboard-section-heading h2 {
            margin: 0;
            color: #253c2e;
            font-size: 1.05rem;
            font-weight: 700;
        }
        .dashboard-section-heading span { color: #849087; font-size: .86rem; }
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 28px;
        }
        .dashboard-stat {
            display: flex;
            align-items: center;
            gap: 14px;
            min-height: 92px;
            padding: 17px;
            border: 1px solid #e7eee8;
            border-radius: 13px;
            background: #fbfdfb;
        }
        .dashboard-stat-icon, .dashboard-action-icon {
            display: grid;
            flex: 0 0 auto;
            place-items: center;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #eaf4ed;
            color: #21834d;
        }
        .dashboard-stat-icon svg, .dashboard-action-icon svg {
            width: 22px;
            height: 22px;
            fill: none;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 1.8;
        }
        .dashboard-stat-label { display: block; color: #718076; font-size: .84rem; }
        .dashboard-stat-value {
            display: block;
            margin-top: 2px;
            color: #203c2a;
            font-size: 1.55rem;
            font-weight: 700;
            line-height: 1.2;
        }
        .dashboard-alert {
            display: flex;
            align-items: center;
            gap: 11px;
            margin: -8px 0 25px;
            padding: 13px 15px;
            border: 1px solid #f1dfab;
            border-radius: 11px;
            background: #fff9e9;
            color: #765b19;
            font-size: .92rem;
        }
        .dashboard-alert-link {
            flex: 0 0 auto;
            margin-left: auto;
            padding: 7px 11px;
            border: 1px solid #e8d18c;
            border-radius: 8px;
            background: #fffdf6;
            color: #70540f;
            font-size: .84rem;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
        }
        .dashboard-alert-link:hover { background: #fff; color: #503b09; }
        .dashboard-alert svg {
            flex: 0 0 auto;
            width: 20px;
            height: 20px;
            fill: none;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 1.8;
        }
        .dashboard-group + .dashboard-group { margin-top: 27px; }
        .dashboard-actions {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }
        .dashboard-action {
            display: flex;
            align-items: center;
            gap: 13px;
            min-height: 82px;
            padding: 15px;
            border: 1px solid #e5ece6;
            border-radius: 12px;
            background: #fff;
            color: #263d2e;
            text-decoration: none;
            transition: border-color .16s ease, box-shadow .16s ease, transform .16s ease;
        }
        .dashboard-action:hover {
            transform: translateY(-2px);
            border-color: #b7d5c0;
            box-shadow: 0 8px 18px rgba(33, 99, 60, .09);
            color: #17613c;
        }
        .dashboard-action-icon { width: 42px; height: 42px; border-radius: 11px; }
        .dashboard-action-copy { min-width: 0; }
        .dashboard-action-copy strong { display: block; font-size: .92rem; }
        .dashboard-action-copy span { display: block; margin-top: 3px; color: #7a887e; font-size: .78rem; line-height: 1.35; }
        .dashboard-footer {
            position: static;
            width: 100%;
            margin-top: auto;
            padding: 15px 20px;
            border-top: 1px solid #e1e8e2;
            background: #fff;
            color: #738076;
            font-size: .84rem;
            text-align: center;
        }
        @media (max-width: 760px) {
            .dashboard-stats { gap: 9px; }
            .dashboard-stat { align-items: flex-start; flex-direction: column; gap: 10px; padding: 13px; }
            .dashboard-actions { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 520px) {
            .dashboard-nav { padding-inline: 14px; }
            .dashboard-brand { gap: 9px; }
            .dashboard-brand img { width: 112px; height: 32px; }
            .dashboard-brand strong { font-size: .83rem; }
            .dashboard-logout { padding: 7px 9px; font-size: .8rem; }
            .dashboard-main { width: calc(100% - 24px); margin-top: 25px; }
            .dashboard-stats { grid-template-columns: 1fr; }
            .dashboard-stat { align-items: center; flex-direction: row; }
            .dashboard-actions { grid-template-columns: 1fr; }
            .dashboard-date { display: none; }
            .dashboard-alert { align-items: flex-start; flex-wrap: wrap; }
            .dashboard-alert-link { margin-left: 30px; }
        }
    </style>
</head>

<body class="dashboard-page">
    <nav class="dashboard-nav">
        <div class="dashboard-brand">
            <img src="../assets/img/logo.svg" alt="Campo Vello">
            <span class="dashboard-brand-divider" aria-hidden="true"></span>
            <strong>Administración</strong>
        </div>
        <a href="../logout.php" class="btn dashboard-logout">Cerrar sesión</a>
    </nav>

    <main class="dashboard-main">
        <header class="dashboard-welcome">
            <div>
                <p class="dashboard-eyebrow">Centro de control</p>
                <h1>Panel de administrador</h1>
                <p class="dashboard-subtitle">Resumen general y herramientas de Campo Vello.</p>
            </div>
            <div class="dashboard-date"><?= htmlspecialchars(date('d/m/Y'), ENT_QUOTES, 'UTF-8') ?></div>
        </header>

        <section class="dashboard-panel" aria-label="Resumen y accesos de administración">
            <div class="dashboard-section-heading">
                <h2>Resumen del sistema</h2>
                <span>Indicadores actuales</span>
            </div>

            <div class="dashboard-stats">
                <article class="dashboard-stat">
                    <span class="dashboard-stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M3 7.5 12 3l9 4.5-9 4.5-9-4.5Z"/><path d="M3 7.5v9l9 4.5 9-4.5v-9M12 12v9"/></svg>
                    </span>
                    <span><span class="dashboard-stat-label">Productos</span><strong class="dashboard-stat-value"><?= number_format((int) $totalProducts) ?></strong></span>
                </article>
                <article class="dashboard-stat">
                    <span class="dashboard-stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M16 20v-1.5a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4V20"/><circle cx="9.5" cy="7" r="4"/><path d="M17 11a4 4 0 1 0 0-8M21 20v-1.5a4 4 0 0 0-3-3.87"/></svg>
                    </span>
                    <span><span class="dashboard-stat-label">Clientes</span><strong class="dashboard-stat-value"><?= number_format((int) $totalClients) ?></strong></span>
                </article>
                <article class="dashboard-stat">
                    <span class="dashboard-stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M7 3h8l5 5v13H7z"/><path d="M15 3v6h5M10 14h7M10 18h7"/></svg>
                    </span>
                    <span><span class="dashboard-stat-label">Facturas emitidas</span><strong class="dashboard-stat-value"><?= number_format((int) $totalInvoices) ?></strong></span>
                </article>
            </div>

            <?php if ($lowStock > 0): ?>
                <div class="dashboard-alert" role="status">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.3 4.3 2.8 17.5A1.7 1.7 0 0 0 4.3 20h15.4a1.7 1.7 0 0 0 1.5-2.5L13.7 4.3a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/></svg>
                    <span><strong><?= number_format((int) $lowStock) ?> producto(s)</strong> tienen existencias menores a 5. Revisa el inventario para evitar faltantes.</span>
                    <a class="dashboard-alert-link" href="inventario.php?bajo_stock=1">Ver productos</a>
                </div>
            <?php endif; ?>

            <section class="dashboard-group" aria-labelledby="inventoryTools">
                <div class="dashboard-section-heading">
                    <h2 id="inventoryTools">Inventario y compras</h2>
                    <span>Productos, proveedores y recepciones</span>
                </div>
                <div class="dashboard-actions">
                    <a class="dashboard-action" href="gestionar_productos.php">
                        <span class="dashboard-action-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 7.5 12 3l9 4.5-9 4.5-9-4.5Z"/><path d="M3 7.5v9l9 4.5 9-4.5v-9M12 12v9"/></svg></span>
                        <span class="dashboard-action-copy"><strong>Ingresos de material</strong><span>Registrar recepciones y revisar historial</span></span>
                    </a>
                    <a class="dashboard-action" href="nuevo_producto.php">
                        <span class="dashboard-action-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/><circle cx="12" cy="12" r="9"/></svg></span>
                        <span class="dashboard-action-copy"><strong>Agregar producto</strong><span>Crear SKU y asignar categoría</span></span>
                    </a>
                    <a class="dashboard-action" href="gestionar_proveedores.php">
                        <span class="dashboard-action-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 7h11v11H3zM14 11h4l3 3v4h-7"/><circle cx="7.5" cy="19" r="2"/><circle cx="17.5" cy="19" r="2"/></svg></span>
                        <span class="dashboard-action-copy"><strong>Proveedores</strong><span>Registrar y consultar proveedores</span></span>
                    </a>
                    <a class="dashboard-action" href="ordenes_compra.php">
                        <span class="dashboard-action-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 3h10v4H7zM5 5H3v16h18V5h-2M7 12l3 3 7-7"/></svg></span>
                        <span class="dashboard-action-copy"><strong>Órdenes de compra</strong><span>Preparar solicitudes sin afectar stock</span></span>
                    </a>
                    <a class="dashboard-action" href="inventario.php">
                        <span class="dashboard-action-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 4h16v16H4zM8 8h8M8 12h8M8 16h5"/></svg></span>
                        <span class="dashboard-action-copy"><strong>Inventario y reportes</strong><span>Consultar existencias y exportar PDF</span></span>
                    </a>
                </div>
            </section>

            <section class="dashboard-group" aria-labelledby="systemTools">
                <div class="dashboard-section-heading">
                    <h2 id="systemTools">Administración del sistema</h2>
                    <span>Accesos de gestión</span>
                </div>
                <div class="dashboard-actions">
                    <a class="dashboard-action" href="gestionar_usuarios.php">
                        <span class="dashboard-action-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="4"/><path d="M2.5 21v-1a6.5 6.5 0 0 1 13 0v1M17 8h5M19.5 5.5v5"/></svg></span>
                        <span class="dashboard-action-copy"><strong>Usuarios</strong><span>Administrar accesos y roles</span></span>
                    </a>
                    <a class="dashboard-action" href="reportes.php">
                        <span class="dashboard-action-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 19V5M4 19h17M8 16v-4M13 16V8M18 16v-7"/></svg></span>
                        <span class="dashboard-action-copy"><strong>Reportes</strong><span>Consultar actividad y resultados</span></span>
                    </a>
                </div>
            </section>
        </section>
    </main>

    <footer class="dashboard-footer">
        &copy; <?= date('Y') ?> Campo Vello. Todos los derechos reservados.
    </footer>
</body>
</html>
