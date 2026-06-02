<?php
// =========================================================================
// 1. CONFIGURACIÓN, SESIÓN Y AUTENTICACIÓN
// =========================================================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/autenticacion.php';
require_once __DIR__ . '/../includes/funciones.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Asegurarse de que solo los administradores puedan entrar
require_login();
if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    header("Location: ../cajero/nueva_factura.php");
    exit;
}

$pdo = getPDO();

// =========================================================================
// 2. CONSULTAS DEL DASHBOARD (MÉTRICAS BASE Y TOTALES DESGLOSADOS)
// =========================================================================

$total_productos = $pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();
$total_usuarios  = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();

// --- TOTALES GENERALES DEL DÍA DE HOY ---
$ventas_hoy = $pdo->query("SELECT SUM(total) FROM facturas WHERE DATE(created_at) = CURRENT_DATE()")->fetchColumn() ?: 0;

// Desglose Efectivo de HOY
$efectivo_hoy = $pdo->query("SELECT SUM(total) FROM facturas WHERE DATE(created_at) = CURRENT_DATE() AND metodo_pago = 'Efectivo'")->fetchColumn() ?: 0;

// Desglose Tarjeta de HOY
$tarjeta_hoy = $pdo->query("SELECT SUM(total) FROM facturas WHERE DATE(created_at) = CURRENT_DATE() AND metodo_pago = 'Tarjeta'")->fetchColumn() ?: 0;


// --- TOTALES GENERALES DEL MES EN CURSO ---
$ventas_mes = $pdo->query("SELECT SUM(total) FROM facturas WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")->fetchColumn() ?: 0;

// Desglose Efectivo del MES
$efectivo_mes = $pdo->query("SELECT SUM(total) FROM facturas WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()) AND metodo_pago = 'Efectivo'")->fetchColumn() ?: 0;

// Desglose Tarjeta del MES
$tarjeta_mes = $pdo->query("SELECT SUM(total) FROM facturas WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()) AND metodo_pago = 'Tarjeta'")->fetchColumn() ?: 0;


// =========================================================================
// 3. LÓGICA Y EXTRACCIÓN DE DATOS PARA LOS GRÁFICOS DINÁMICOS
// =========================================================================

// Gráfico 1: Top 10 Productos Más Vendidos (Últimos 12 Meses)
$top_productos = $pdo->query("
    SELECT p.name as producto, SUM(i.quantity) as total_vendido 
    FROM invoice_items i 
    JOIN productos p ON i.product_id = p.id 
    JOIN facturas f ON i.invoice_id = f.id
    WHERE f.created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
    GROUP BY p.id 
    ORDER BY total_vendido DESC 
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

$max_unidades = 1;
if (!empty($top_productos)) {
    foreach ($top_productos as $tp) {
        if ($tp['total_vendido'] > $max_unidades) {
            $max_unidades = $tp['total_vendido'];
        }
    }
}

// Gráfico 2: Ingresos Totales por Mes (Últimos 12 Meses)
$ingresos_mensuales = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as mes_anio,
        DATE_FORMAT(created_at, '%b %y') as mes_nombre,
        SUM(total) as total_mes
    FROM facturas
    WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY mes_anio ASC
")->fetchAll(PDO::FETCH_ASSOC);

$max_ingreso_mes = 3000; 
if (!empty($ingresos_mensuales)) {
    foreach ($ingresos_mensuales as $im) {
        if ($im['total_mes'] > $max_ingreso_mes) {
            $max_ingreso_mes = $im['total_mes'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Campo Vello</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f3f5f7;
            font-family: Arial, Helvetica, sans-serif;
            color: #333;
        }
        .header-admin {
            background: linear-gradient(135deg, #1f7a1f, #2f8b2f);
            color: white;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        }
        .header-izquierda h1 { margin: 0; font-size: 22px; }
        .header-izquierda p { margin: 4px 0 0 0; font-size: 13px; opacity: 0.9; }
        .btn-volver {
            color: white;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
        }
        .btn-volver:hover { opacity: 0.8; }
        .contenedor {
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .titulo-seccion {
            font-size: 24px;
            color: #1f7a1f;
            margin-top: 0;
            margin-bottom: 25px;
            font-weight: bold;
        }
        
        /* Contenedores Grid del Dashboard */
        .grid-dashboard-principales {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .subtitulo-bloque {
            font-size: 16px;
            color: #444;
            margin: 25px 0 15px 0;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-left: 4px solid #1f7a1f;
            padding-left: 10px;
        }

        .grid-balances {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }
        
        .tarjeta-metrica {
            background: white;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border-left: 5px solid #2f8b2f;
        }
        .tarjeta-metrica.efectivo { border-left-color: #2e7d32; }
        .tarjeta-metrica.tarjeta { border-left-color: #1565c0; }
        .tarjeta-metrica.hoy { border-left-color: #ef6c00; }
        
        .tarjeta-metrica h3 {
            margin: 0;
            color: #666;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .tarjeta-metrica .valor {
            margin-top: 12px;
            font-size: 26px;
            font-weight: bold;
            color: #222;
        }

        /* CONFIGURACIÓN GRÁFICOS NATIVOS */
        .seccion-graficas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 25px;
        }
        .card-grafica {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .card-grafica h4 {
            margin-top: 0;
            margin-bottom: 25px;
            color: #1f7a1f;
            font-size: 15px;
            text-align: center;
            font-weight: bold;
        }

        /* Gráfico de Barras CSS (Top Productos) */
        .grafico-barras-container { display: flex; flex-direction: column; gap: 12px; }
        .fila-barra { display: flex; align-items: center; font-size: 12px; }
        .label-producto {
            width: 140px; text-align: right; padding-right: 15px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #555;
        }
        .contenedor-barra-gris { flex-grow: 1; background: #f0f0f0; height: 20px; border-radius: 4px; overflow: hidden; position: relative; }
        .barra-azul-llena { background: #2b6cb0; height: 100%; border-radius: 4px; transition: width 0.4s; }
        .valor-unidades { position: absolute; right: 8px; top: 2px; font-weight: bold; font-size: 11px; color: #333; }

        /* Gráfico de Línea/Tendencia CSS (Ingresos Mensuales) */
        .grafico-linea-container {
            position: relative; height: 220px; border-bottom: 2px solid #ccc; border-left: 2px solid #ccc;
            margin-left: 40px; margin-bottom: 20px; display: flex; justify-content: space-around; align-items: flex-end; padding: 0 10px;
        }
        .punto-mes { display: flex; flex-direction: column; align-items: center; position: relative; flex-grow: 1; }
        .barra-tendencia-mes { background: linear-gradient(to top, #3182ce, #63b3ed); width: 35px; border-top-left-radius: 6px; border-top-right-radius: 6px; position: relative; min-height: 5px; }
        .monto-flotante { position: absolute; top: -22px; left: 50%; transform: translateX(-50%); font-size: 10px; font-weight: bold; color: #2d3748; white-space: nowrap; }
        .eje-x-label { position: absolute; bottom: -24px; font-size: 11px; color: #666; font-weight: bold; }
        .eje-y-leyenda { position: absolute; left: -45px; font-size: 11px; color: #888; }
        
        footer { margin-top: 50px; text-align: center; padding: 20px; color: #777; font-size: 13px; border-top: 1px solid #ddd; }
    </style>
</head>
<body>

    <div class="header-admin">
        <div class="header-izquierda">
            <h1>Campo Vello - Admin</h1>
            <p>Panel de Control Gerencial e Inversiones</p>
        </div>
        <a href="dashboard.php" class="btn-volver">Volver al panel</a>
    </div>

    <div class="contenedor">
        <h2 class="titulo-seccion">Dashboard General</h2>

        <div class="grid-dashboard-principales">
            <div class="tarjeta-metrica">
                <h3>Total Productos</h3>
                <div class="valor"><?php echo $total_productos; ?></div>
            </div>
            <div class="tarjeta-metrica">
                <h3>Total Usuarios</h3>
                <div class="valor"><?php echo $total_usuarios; ?></div>
            </div>
            <div class="tarjeta-metrica hoy">
                <h3>Total Ventas Hoy</h3>
                <div class="valor">$ <?php echo number_format($ventas_hoy, 2); ?></div>
            </div>
            <div class="tarjeta-metrica">
                <h3>Total Ventas Mes</h3>
                <div class="valor">$ <?php echo number_format($ventas_mes, 2); ?></div>
            </div>
        </div>

        <div class="subtitulo-bloque">Caja y Arqueo del Día (Hoy)</div>
        <div class="grid-balances">
            <div class="tarjeta-metrica efectivo">
                <h3>💵 Efectivo Recibido Hoy</h3>
                <div class="valor" style="color: #2e7d32;">$ <?php echo number_format($efectivo_hoy, 2); ?></div>
            </div>
            <div class="tarjeta-metrica tarjeta">
                <h3>💳 Tarjeta Procesada Hoy</h3>
                <div class="valor" style="color: #1565c0;">$ <?php echo number_format($tarjeta_hoy, 2); ?></div>
            </div>
        </div>

        <div class="subtitulo-bloque">Rendimiento Financiero del Mes</div>
        <div class="grid-balances">
            <div class="tarjeta-metrica efectivo">
                <h3>💵 Total Efectivo Mensual</h3>
                <div class="valor" style="color: #2e7d32;">$ <?php echo number_format($efectivo_mes, 2); ?></div>
            </div>
            <div class="tarjeta-metrica tarjeta">
                <h3>💳 Total Tarjeta Mensual</h3>
                <div class="valor" style="color: #1565c0;">$ <?php echo number_format($tarjeta_mes, 2); ?></div>
            </div>
        </div>

        <div class="seccion-graficas">
            
            <div class="card-grafica">
                <h4>Top 10 Productos Más Vendidos (Últimos 12 Meses)</h4>
                <div class="grafico-barras-container">
                    <?php if (empty($top_productos)): ?>
                        <div style="text-align:center; color:#999; padding:40px;">No hay registros de productos vendidos.</div>
                    <?php else: ?>
                        <?php foreach ($top_productos as $item): 
                            $porcentaje = ($item['total_vendido'] / $max_unidades) * 100;
                        ?>
                            <div class="fila-barra">
                                <div class="label-producto" title="<?php echo htmlspecialchars($item['producto']); ?>">
                                    <?php echo htmlspecialchars($item['producto']); ?>
                                </div>
                                <div class="contenedor-barra-gris">
                                    <div class="barra-azul-llena" style="width: <?php echo $porcentaje; ?>%;"></div>
                                    <span class="valor-unidades"><?php echo number_format($item['total_vendido']); ?> unds</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-grafica">
                <h4>Ingresos Totales por Mes (Últimos 12 Meses)</h4>
                <div class="grafico-linea-container">
                    <div class="eje-y-leyenda" style="top: 0;">$<?php echo number_format($max_ingreso_mes); ?></div>
                    <div class="eje-y-leyenda" style="top: 50%;">$<?php echo number_format($max_ingreso_mes / 2); ?></div>
                    <div class="eje-y-leyenda" style="bottom: 0;">$0.00</div>

                    <?php if (empty($ingresos_mensuales)): ?>
                        <div style="text-align:center; color:#999; width: 100%; padding-top:60px;">Sin transacciones financieras.</div>
                    <?php else: ?>
                        <?php foreach ($ingresos_mensuales as $mes): 
                            $altura_proporcional = ($mes['total_mes'] / $max_ingreso_mes) * 100;
                        ?>
                            <div class="punto-mes">
                                <div class="barra-tendencia-mes" style="height: <?php echo max($altura_proporcional, 4); ?>px;">
                                    <div class="monto-flotante">$<?php echo number_format($mes['total_mes'], 0); ?></div>
                                </div>
                                <div class="eje-x-label"><?php echo htmlspecialchars($mes['mes_nombre']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <footer>
        © <?php echo date('Y'); ?> Campo Vello. Todos los derechos reservados.
    </footer>

</body>
</html>