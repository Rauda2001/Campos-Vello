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

require_login();

$pdo = getPDO();

// =========================================================================
// LÓGICA DE NAVEGACIÓN Y DETALLE (Estilo SAP)
// =========================================================================
$factura_id_actual = isset($_GET['id']) ? intval($_GET['id']) : null;
$factura_guardada = null;
$detalles_guardados = [];

$id_primero  = $pdo->query("SELECT MIN(id) FROM facturas")->fetchColumn();
$id_ultimo   = $pdo->query("SELECT MAX(id) FROM facturas")->fetchColumn();
$id_anterior = null;
$id_siguiente = null;

$siguiente_id_factura = $id_ultimo ? ($id_ultimo + 1) : 1;

if ($factura_id_actual) {
    $stmt = $pdo->prepare("
        SELECT f.*, c.name AS cliente_nombre 
        FROM facturas f 
        LEFT JOIN clientes c ON f.client_id = c.id 
        WHERE f.id = ?
    ");
    $stmt->execute([$factura_id_actual]);
    $factura_guardada = $stmt->fetch();

    if ($factura_guardada) {
        $stmtDet = $pdo->prepare("
            SELECT i.*, p.name AS producto_nombre 
            FROM invoice_items i 
            LEFT JOIN productos p ON i.product_id = p.id 
            WHERE i.invoice_id = ?
        ");
        $stmtDet->execute([$factura_id_actual]);
        $detalles_guardados = $stmtDet->fetchAll();

        $id_anterior  = $pdo->query("SELECT MAX(id) FROM facturas WHERE id < $factura_id_actual")->fetchColumn();
        $id_siguiente = $pdo->query("SELECT MIN(id) FROM facturas WHERE id > $factura_id_actual")->fetchColumn();
    }
} else {
    $id_anterior = $id_ultimo; 
    $id_siguiente = null; 
}

// =========================================================================
// LÓGICA PARA GUARDAR LA FACTURA CON EL MÉTODO DE PAGO
// =========================================================================
$error_mensaje = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$factura_id_actual) {

    $cliente_id  = $_POST['cliente_id'] ?? null;
    $metodo_pago = $_POST['metodo_pago'] ?? 'Efectivo'; 
    $subtotal    = $_POST['subtotal'] ?? 0;
    $iva         = $_POST['iva'] ?? 0;
    $total       = $_POST['total'] ?? 0;
    $user_id     = $_SESSION['user']['id'] ?? null; 

    $codigos      = $_POST['codigo'] ?? [];
    $cantidades   = $_POST['cantidad'] ?? [];
    $precios      = $_POST['precio'] ?? [];

    if (!empty($cliente_id) && !empty($codigos) && count($codigos) > 0) {
        try {
            $pdo->beginTransaction();

            $stmtFactura = $pdo->prepare("
                INSERT INTO facturas (user_id, client_id, metodo_pago, subtotal, iva_amount, total, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmtFactura->execute([$user_id, $cliente_id, $metodo_pago, $subtotal, $iva, $total]);
            
            $factura_id = $pdo->lastInsertId();

            $stmtDetalle = $pdo->prepare("
                INSERT INTO invoice_items (invoice_id, product_id, quantity, price) 
                VALUES (?, ?, ?, ?)
            ");

            $stmtStock = $pdo->prepare("
                UPDATE productos 
                SET stock = stock - ? 
                WHERE id = ?
            ");

            foreach ($codigos as $index => $codigo) {
                if (empty($codigo)) continue;

                $cantidad = floatval($cantidades[$index]);
                $precio   = floatval($precios[$index]);

                $stmtDetalle->execute([$factura_id, $codigo, $cantidad, $precio]);
                $stmtStock->execute([$cantidad, $codigo]);
            }

            $pdo->commit();

            header("Location: nueva_factura.php?id=" . $factura_id);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error_mensaje = "Error en la base de datos al guardar: " . $e->getMessage();
        }
    } else {
        $error_mensaje = "Por favor, selecciona un cliente y asegúrate de añadir al menos un producto válido.";
    }
}

// =========================================================================
// CONSULTAS PARA RENDERIZAR LA PÁGINA
// =========================================================================
$clientes = $pdo->query("SELECT * FROM clientes ORDER BY name ASC")->fetchAll();

$productos = $pdo->query("
    SELECT p.*, c.name AS categoria
    FROM productos p
    LEFT JOIN categorias c ON p.category_id = c.id
    ORDER BY p.id DESC
")->fetchAll();

$fecha_actual = $factura_guardada ? date('d/m/Y', strtotime($factura_guardada['created_at'])) : date('d/m/Y');
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Factura</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        *{
            box-sizing:border-box;
        }

        body{
            margin:0;
            background:#f3f5f7;
            font-family:Arial, Helvetica, sans-serif;
            color:#333;
        }

        .contenedor{
            padding:20px;
        }

        .barra-sap {
            background: linear-gradient(135deg, #1f7a1f, #2f8b2f);
            border: 1px solid #196119;
            padding: 8px 15px;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        
        .btn-sap {
            background: #ffffff;
            border: none;
            border-radius: 6px;
            padding: 6px 14px;
            cursor: pointer;
            font-weight: bold;
            font-size: 13px;
            color: #1f7a1f;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s, opacity 0.2s;
            height: 34px;
            outline: none;
        }
        .btn-sap:hover:not(.disabled) { 
            background: #e6f2e6; 
        }
        .btn-sap.disabled { 
            opacity: 0.4; 
            cursor: not-allowed; 
            pointer-events: none; 
        }
        
        .btn-crear-sap {
            background: #ffffff;
            color: #1f7a1f;
            border: none;
            font-weight: bold;
            border-radius: 6px;
            padding: 6px 16px;
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            height: 34px;
            transition: background 0.2s, transform 0.1s;
            outline: none;
        }
        
        .separador-sap { 
            width: 1px; 
            height: 22px; 
            background: rgba(255, 255, 255, 0.3); 
            margin: 0 8px; 
        }

        .header-factura{
            background:linear-gradient(135deg,#1f7a1f,#2f8b2f);
            color:white;
            border-radius:16px;
            padding:18px 22px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:20px;
            box-shadow:0 4px 10px rgba(0,0,0,0.08);
        }

        .header-izquierda{
            display:flex;
            align-items:center;
            gap:15px;
        }

        .logo-box img{
            height:52px;
        }

        .titulo h1{
            margin:0;
            font-size:26px;
            font-weight:bold;
        }

        .titulo p{
            margin-top:4px;
            font-size:13px;
            opacity:0.95;
        }

        .header-derecha{
            display:flex;
            align-items:center;
            gap:20px;
        }

        .volver-panel{
            color:white;
            text-decoration:none;
            font-size:14px;
            font-weight:600;
        }

        .campo-header{
            display:flex;
            flex-direction:column;
        }

        .campo-header label{
            font-size:13px;
            font-weight:bold;
            margin-bottom:5px;
        }

        .btn-clientes{
            height:38px;
            border:none;
            border-radius:8px;
            padding:0 16px;
            background:white;
            color:#1f7a1f;
            font-weight:bold;
            cursor:pointer;
            font-size:13px;
            outline: none;
        }
        
        .btn-clientes:focus, select#metodo_pago:focus, .btn-inventario-arriba:focus {
            box-shadow: 0 0 0 3px #ffeb3b;
        }

        .btn-inventario-arriba {
            background-color: #2f8b2f;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            margin-bottom: 15px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
            outline: none;
        }
        .btn-inventario-arriba:hover:not(:disabled) {
            background-color: #1f5c1f;
        }

        .card{
            background:white;
            border-radius:16px;
            padding:18px;
            box-shadow:0 4px 12px rgba(0,0,0,0.08);
        }

        table{
            width:100%;
            border-collapse:collapse;
        }

        thead{
            background:#2f7f2f;
            color:white;
        }

        thead th{
            padding:12px;
            text-align:left;
            font-size:13px;
        }

        tbody td{
            border:1px solid #ddd;
            padding:10px;
            font-size:13px;
            background:white;
        }
        
        tr.fila-activa {
            background-color: #d1ebd1 !important;
        }
        tr.fila-activa td {
            background-color: #d1ebd1 !important;
        }

        .codigo-input,
        .descripcion-input,
        .cantidades-input,
        .precio-input{
            width:100%;
            height:36px;
            border:1px solid #ccc;
            border-radius:6px;
            padding:6px 10px;
            font-size:13px;
            outline:none;
            background: #f9f9f9;
        }

        .btn-eliminar{
            width:34px;
            height:34px;
            border:none;
            border-radius:6px;
            background:#d32f2f;
            color:white;
            cursor:pointer;
            font-size:14px;
            font-weight:bold;
        }

        .footer-factura{
            margin-top:25px;
            display:flex;
            justify-content:flex-end;
        }

        .resumen{
            width:320px;
            background:#f4faf2;
            border:1px solid #cde3c5;
            border-radius:14px;
            padding:18px;
        }

        .resumen h2{
            margin-top:0;
            margin-bottom:18px;
            color:#1f7a1f;
            font-size:20px;
        }

        .linea-resumen{
            display:flex;
            justify-content:space-between;
            margin-bottom:12px;
            font-size:14px;
        }

        .total-final{
            border-top:2px solid #ccc;
            padding-top:12px;
            margin-top:12px;
            font-size:18px;
            font-weight:bold;
            color:#1f7a1f;
         }

        .btn-guardar{
            width:100%;
            margin-top:18px;
            height:45px;
            border:none;
            border-radius:10px;
            background:#1565c0;
            color:white;
            font-size:15px;
            font-weight:bold;
            cursor:pointer;
        }

        .btn-imprimir-pdf {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width:100%;
            margin-top:18px;
            height:45px;
            border:none;
            border-radius:10px;
            background:#e65100;
            color:white;
            font-size:15px;
            font-weight:bold;
            text-decoration: none;
        }

        .modal{
            display:none;
            position:fixed;
            inset:0;
            background:rgba(0,0,0,0.5);
            z-index:999;
            justify-content:center;
            align-items:center;
        }

        .modal-content{
            width:90%;
            max-width:1100px;
            background:white;
            border-radius:14px;
            padding:18px;
            max-height:85vh;
            overflow:auto;
        }

        .submodal-cantidad {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .submodal-cantidad-content {
            background: white;
            padding: 25px;
            border-radius: 16px;
            width: 380px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .input-submodal-cant {
            width: 80px;
            height: 40px;
            font-size: 18px;
            text-align: center;
            margin: 15px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        .modal-header{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:15px;
        }

        .modal-header h2{
            margin:0;
            color:#1f7a1f;
            font-size:20px;
        }

        .cerrar-modal{
            width:34px;
            height:34px;
            border:none;
            border-radius:6px;
            background:red;
            color:white;
            cursor:pointer;
            font-weight:bold;
        }

        .btn-seleccionar{
            border:none;
            border-radius:6px;
            background:#2f8b2f;
            color:white;
            padding:8px 12px;
            cursor:pointer;
            font-size:12px;
            font-weight:bold;
        }

        .busqueda-box{
            position:relative;
            width:100%;
            margin-bottom:15px;
        }

        .input-busqueda{
            width:100%;
            height:38px;
            border:1px solid #ccc;
            border-radius:8px;
            padding:8px 12px;
            font-size:13px;
            outline:none;
        }

        footer{
            margin-top:25px;
            background:linear-gradient(135deg,#1f7a1f,#2f8b2f);
            color:white;
            text-align:center;
            padding:15px;
            font-size:13px;
        }
    </style>
</head>

<body>

<div class="contenedor">

    <div class="barra-sap">
        <a href="nueva_factura.php?id=<?= $id_primero ?>" class="btn-sap <?= ($factura_id_actual == $id_primero || !$id_primero) ? 'disabled' : '' ?>">|◀</a>
        <a href="nueva_factura.php?id=<?= $id_anterior ?>" class="btn-sap <?= !$id_anterior ? 'disabled' : '' ?>">◀</a>
        <a href="nueva_factura.php?id=<?= $id_siguiente ?>" class="btn-sap <?= !$id_siguiente ? 'disabled' : '' ?>">▶</a>
        <a href="nueva_factura.php?id=<?= $id_ultimo ?>" class="btn-sap <?= ($factura_id_actual == $id_ultimo || !$id_ultimo || !$factura_id_actual) ? 'disabled' : '' ?>">▶|</a>
        <div class="separador-sap"></div>
        <a href="nueva_factura.php" class="btn-crear-sap">Crear nueva factura</a>
        <span style="margin-left:auto; font-size:13px; font-weight:bold; color:#ffffff;">
            <?= $factura_id_actual ? "Factura Histórica: #$factura_id_actual" : "Factura #$siguiente_id_factura" ?>
        </span>
    </div>

    <?php if ($error_mensaje): ?>
        <div style="background:#f8d7da; color:#721c24; padding:15px; border-radius:8px; margin-bottom:20px; font-weight:bold;">
            <?= htmlspecialchars($error_mensaje) ?>
        </div>
    <?php endif; ?>

    <div class="header-factura">
        <div class="header-izquierda">
            <div class="logo-box">
                <img src="../assets/img/logo.svg" alt="Logo">
            </div>
            <div class="titulo">
                <h1><?= $factura_guardada ? 'Visualizando Factura' : 'Nueva Factura' ?></h1>
                <p>Sistema de Facturación Campo Vello</p>
            </div>
        </div>

        <div class="header-derecha">
            <div class="campo-header">
                <label>Cliente <span style="color: #ffeb3b; font-size: 11px;">[Alt + C]</span></label>
                <button type="button" class="btn-clientes" id="btnDisparadorClientes" onclick="abrirModalClientes()" <?= $factura_guardada ? 'disabled' : '' ?>>
                    <?= $factura_guardada ? htmlspecialchars($factura_guardada['cliente_nombre']) : 'Seleccionar Cliente' ?>
                </button>
            </div>

            <div class="campo-header">
                <label>Método de Pago <span style="color: #ffeb3b; font-size: 11px;">[Alt + P]</span></label>
                <?php if ($factura_guardada): ?>
                    <input type="text" value="<?= htmlspecialchars($factura_guardada['metodo_pago'] ?? 'Efectivo') ?>" readonly style="width: 160px; height: 38px; border-radius: 8px; text-align: center; font-weight: bold;">
                <?php else: ?>
                    <select name="metodo_pago" id="metodo_pago" form="formFactura" style="width: 160px; height: 38px; border-radius: 8px; font-weight: bold; outline: none;">
                        <option value="Efectivo">💵 Efectivo</option>
                        <option value="Tarjeta">💳 Tarjeta</option>
                    </select>
                <?php endif; ?>
            </div>

            <div class="campo-header">
                <label>Fecha</label>
                <input type="text" value="<?= $fecha_actual ?>" readonly style="width: 110px; height: 38px; border-radius:8px; border:none; text-align:center;">
            </div>

            <a href="ventas.php" class="volver-panel">Volver al panel</a>
        </div>
    </div>

    <?php if (!$factura_guardada): ?>
        <button type="button" class="btn-inventario-arriba" id="btnAbrirInventario" onclick="abrirBuscadorCentralizado()">
            🔍 Agregar Producto / Buscar en Inventario
        </button>
    <?php endif; ?>

    <div class="card">
        <form method="POST" action="nueva_factura.php" id="formFactura">
            <?= csrf_field() ?>
            <input type="hidden" name="cliente_id" id="cliente_id" value="<?= $factura_guardada ? $factura_guardada['client_id'] : '' ?>">

            <table id="tablaFactura">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="15%">Código</th>
                        <th width="40%">Descripción</th>
                        <th width="10%">Cantidad</th>
                        <th width="13%">Precio</th>
                        <th width="13%">Total</th>
                        <th width="4%">Acción</th>
                    </tr>
                </thead>
                <tbody id="bodyFactura">
                    <?php if ($factura_guardada): ?>
                        <?php foreach ($detalles_guardados as $index => $item): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><input type="text" class="codigo-input" name="codigo[]" value="<?= $item['product_id'] ?>" readonly></td>
                                <td><input type="text" class="descripcion-input" name="descripcion[]" value="<?= htmlspecialchars($item['producto_nombre'] ?? '') ?>" readonly></td>
                                <td><input type="number" class="cantidades-input" name="cantidad[]" value="<?= $item['quantity'] ?>" readonly></td>
                                <td><input type="text" class="precio-input" name="precio[]" value="<?= $item['price'] ?>" readonly></td>
                                <td class="total-fila"><?= number_format($item['quantity'] * $item['price'], 2) ?></td>
                                <td><button type="button" class="btn-eliminar" disabled>X</button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="footer-factura">
                <div class="resumen">
                    <h2>Resumen</h2>
                    <div class="linea-resumen">
                        <span>Subtotal:</span>
                        <strong>$ <span id="subtotal"><?= $factura_guardada ? number_format($factura_guardada['subtotal'], 2) : '0.00' ?></span></strong>
                    </div>
                    <div class="linea-resumen">
                        <span>IVA (13%):</span>
                        <strong>$ <span id="iva"><?= $factura_guardada ? number_format($factura_guardada['iva_amount'], 2) : '0.00' ?></span></strong>
                    </div>
                    <div class="linea-resumen total-final">
                        <span>Total:</span>
                        <strong>$ <span id="total"><?= $factura_guardada ? number_format($factura_guardada['total'], 2) : '0.00' ?></span></strong>
                    </div>

                    <input type="hidden" name="subtotal" id="inputSubtotal" value="<?= $factura_guardada ? $factura_guardada['subtotal'] : '0.00' ?>">
                    <input type="hidden" name="iva" id="inputIva" value="<?= $factura_guardada ? $factura_guardada['iva_amount'] : '0.00' ?>">
                    <input type="hidden" name="total" id="inputTotal" value="<?= $factura_guardada ? $factura_guardada['total'] : '0.00' ?>">

                    <?php if (!$factura_guardada): ?>
                        <button type="submit" class="btn-guardar">Guardar Factura</button>
                    <?php else: ?>
                        <a href="generar_pdf.php?id=<?= $factura_id_actual ?>" target="_blank" class="btn-imprimir-pdf">🖨️ Abrir PDF / Imprimir</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="modalClientes">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Seleccionar Cliente</h2>
            <button type="button" class="cerrar-modal" onclick="cerrarModalClientes()">X</button>
        </div>
        <div class="busqueda-box">
            <input type="text" id="buscarCliente" placeholder="Buscar cliente..." class="input-busqueda" onkeydown="manejarTecladoClientes(event)">
        </div>
        <table>
            <thead>
                <tr><th>ID</th><th>Nombre</th><th>Seleccionar</th></tr>
            </thead>
            <tbody id="tablaClientes">
                <?php foreach($clientes as $c): ?>
                    <tr id="fila_c_<?= $c['id'] ?>" onclick='seleccionarCliente(<?= json_encode($c['id']) ?>, <?= json_encode($c['name']) ?>)'>
                        <td><?= $c['id'] ?></td>
                        <td><?= htmlspecialchars($c['name']) ?></td>
                        <td><button type="button" class="btn-seleccionar">Seleccionar</button></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal" id="modalProductos">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Seleccionar Producto</h2>
            <button type="button" class="cerrar-modal" onclick="cerrarModal()">X</button>
        </div>
        <div class="busqueda-box">
            <input type="text" id="buscarProducto" placeholder="Buscar por nombre o código..." class="input-busqueda" onkeydown="manejarTecladoProductos(event)">
        </div>
        <table>
            <thead>
                <tr><th>ID</th><th>Nombre</th><th>Categoría</th><th>Ubicación</th><th>Precio</th><th>Stock</th><th>Seleccionar</th></tr>
            </thead>
            <tbody id="tablaProductos">
                <?php foreach($productos as $p): ?>
                    <tr id="fila_p_<?= $p['id'] ?>" onclick='prepararCantidad(<?= json_encode($p['id']) ?>, <?= json_encode($p['name']) ?>, <?= json_encode($p['price']) ?>, <?= $p['stock'] ?>)'>
                        <td><?= $p['id'] ?></td>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td><?= htmlspecialchars($p['categoria']) ?></td>
                        <td><?= htmlspecialchars($p['location']) ?></td>
                        <td>$<?= number_format($p['price'], 2) ?></td>
                        <td><?= $p['stock'] ?></td>
                        <td><button type="button" class="btn-seleccionar">Seleccionar</button></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="submodal-cantidad" id="submodalCantidad">
    <div class="submodal-cantidad-content">
        <h3 id="lblProdNombre" style="color:#1f7a1f; margin-top:0;"></h3>
        <p id="lblProdStock" style="font-size:13px; color:#666;"></p>
        <input type="number" id="txtSubmodalCant" class="input-submodal-cant" value="" min="1" onkeydown="manejarTecladoSubmodal(event)">
        <div style="margin-top:15px; display:flex; gap:10px; justify-content:center;">
            <button type="button" onclick="cerrarSubmodalCant()" style="background:#666; color:white; border:none; padding:8px 16px; border-radius:6px; cursor:pointer;">Cancelar</button>
            <button type="button" id="btnAceptarSubmodal" onclick="confirmarAdicionProducto()" style="background:#2f8b2f; color:white; border:none; padding:8px 16px; border-radius:6px; cursor:pointer; font-weight:bold;">Aceptar</button>
        </div>
    </div>
</div>

<footer>
    © <?= date('Y') ?> Campo Vello. Todos los derechos reservados.
</footer>

<script>
let indiceActivoCli = -1;
let indiceActivoProd = -1;
let prodTemporal = null;

const elementosCiclo = ["btnDisparadorClientes", "metodo_pago", "btnAbrirInventario"];

document.addEventListener("keydown", function(e) {
    if (e.key === "Tab") {
        let activeId = document.activeElement.id;
        if (elementosCiclo.includes(activeId)) {
            e.preventDefault();
            let indexActual = elementosCiclo.indexOf(activeId);
            let siguienteIndex = (indexActual + 1) % elementosCiclo.length;
            document.getElementById(elementosCiclo[siguienteIndex]).focus();
        }
    }
});

// =========================================================================
// CONTROL DEL MENÚ DE PRODUCTOS (Navegación con Flechas y Enter)
// =========================================================================
function abrirBuscadorCentralizado() {
    let inputBuscar = document.getElementById("buscarProducto");
    inputBuscar.value = "";
    
    let filas = document.querySelectorAll("#tablaProductos tr");
    filas.forEach((f, idx) => {
        f.style.display = "";
        if(idx === 0) {
            f.classList.add("fila-activa");
        } else {
            f.classList.remove("fila-activa");
        }
    });
    
    indiceActivoProd = 0; 
    document.getElementById("modalProductos").style.display = "flex";
    setTimeout(() => inputBuscar.focus(), 100);
}

function cerrarModal() {
    document.getElementById("modalProductos").style.display = "none";
}

function prepararCantidad(id, nombre, precio, stock) {
    prodTemporal = { id, nombre, precio };
    document.getElementById("lblProdNombre").innerText = nombre;
    document.getElementById("lblProdStock").innerText = "Disponible en Stock: " + stock;
    
    let inputCant = document.getElementById("txtSubmodalCant");
    inputCant.value = ""; 
    
    document.getElementById("submodalCantidad").style.display = "flex";
    setTimeout(() => {
        inputCant.focus();
    }, 100);
}

function cerrarSubmodalCant() {
    document.getElementById("submodalCantidad").style.display = "none";
    prodTemporal = null;
}

function confirmarAdicionProducto() {
    if (!prodTemporal) return;
    
    let cantidadValor = document.getElementById("txtSubmodalCant").value;
    let cantidad = parseFloat(cantidadValor) || 1; 
    let tbody = document.getElementById("bodyFactura");
    let numeroFila = tbody.rows.length + 1;

    let filaHtml = `
        <tr>
            <td>${numeroFila}</td>
            <td><input type="text" class="codigo-input" name="codigo[]" value="${prodTemporal.id}" readonly></td>
            <td><input type="text" class="descripcion-input" name="descripcion[]" value="${prodTemporal.nombre}" readonly></td>
            <td><input type="number" class="cantidades-input" name="cantidad[]" value="${cantidad}" onchange="calcularFila(this)"></td>
            <td><input type="text" class="precio-input" name="precio[]" value="${parseFloat(prodTemporal.precio).toFixed(2)}" readonly></td>
            <td class="total-fila">${(cantidad * prodTemporal.precio).toFixed(2)}</td>
            <td><button type="button" class="btn-eliminar" onclick="eliminarFila(this)">X</button></td>
        </tr>
    `;

    tbody.insertAdjacentHTML("beforeend", filaHtml);
    calcularTotales();
    
    cerrarSubmodalCant();
    cerrarModal();
    
    document.getElementById("btnAbrirInventario").focus();
}

function manejarTecladoProductos(e) {
    let filasVisibles = Array.from(document.querySelectorAll("#tablaProductos tr")).filter(f => f.style.display !== "none");
    if (filasVisibles.length === 0) return;

    if (e.key === "ArrowDown") {
        e.preventDefault();
        filasVisibles.forEach(f => f.classList.remove("fila-activa"));
        indiceActivoProd = (indiceActivoProd + 1) % filasVisibles.length;
        filasVisibles[indiceActivoProd].classList.add("fila-activa");
        filasVisibles[indiceActivoProd].scrollIntoView({ block: "nearest" });
    } else if (e.key === "ArrowUp") {
        e.preventDefault();
        filasVisibles.forEach(f => f.classList.remove("fila-activa"));
        indiceActivoProd = (indiceActivoProd - 1 + filasVisibles.length) % filasVisibles.length;
        filasVisibles[indiceActivoProd].classList.add("fila-activa");
        filasVisibles[indiceActivoProd].scrollIntoView({ block: "nearest" });
    } else if (e.key === "Enter") {
        e.preventDefault();
        if (indiceActivoProd >= 0 && indiceActivoProd < filasVisibles.length) {
            filasVisibles[indiceActivoProd].click(); 
        }
    }
}

document.getElementById("buscarProducto").addEventListener("input", function() {
    let filtro = this.value.toLowerCase();
    let primeraFilaAsignada = false;
    
    document.querySelectorAll("#tablaProductos tr").forEach(f => {
        let cumple = f.innerText.toLowerCase().includes(filtro);
        f.style.display = cumple ? "" : "none";
        f.classList.remove("fila-activa");
        
        if (cumple && !primeraFilaAsignada) {
            f.classList.add("fila-activa");
            primeraFilaAsignada = true;
        }
    });
    indiceActivoProd = 0; 
});

function manejarTecladoSubmodal(e) {
    if (e.key === "Enter") {
        e.preventDefault();
        confirmarAdicionProducto();
    }
}

// =========================================================================
// PROCESOS AUXILIARES DE FACTURACIÓN (Totales, Clientes)
// =========================================================================
function calcularFila(input) {
    let fila = input.closest("tr");
    let cantidad = parseFloat(input.value) || 0;
    let precio = parseFloat(fila.querySelector(".precio-input").value) || 0;
    fila.querySelector(".total-fila").innerText = (cantidad * precio).toFixed(2);
    calcularTotales();
}

function calcularTotales() {
    let subtotal = 0;
    document.querySelectorAll(".total-fila").forEach(td => {
        subtotal += parseFloat(td.innerText) || 0;
    });

    let iva = subtotal * 0.13;
    let total = subtotal + iva;

    document.getElementById("subtotal").innerText = subtotal.toFixed(2);
    document.getElementById("iva").innerText = iva.toFixed(2);
    document.getElementById("total").innerText = total.toFixed(2);

    document.getElementById("inputSubtotal").value = subtotal.toFixed(2);
    document.getElementById("inputIva").value = iva.toFixed(2);
    document.getElementById("inputTotal").value = total.toFixed(2);
}

function eliminarFila(btn) {
    btn.closest("tr").remove();
    document.querySelectorAll("#bodyFactura tr").forEach((fila, index) => {
        fila.cells[0].innerText = index + 1;
    });
    calcularTotales();
}

function abrirModalClientes() {
    let inputBuscarCliente = document.getElementById("buscarCliente");
    inputBuscarCliente.value = "";
    
    let filas = document.querySelectorAll("#tablaClientes tr");
    filas.forEach((f, idx) => {
        f.style.display = "";
        if(idx === 0) {
            f.classList.add("fila-activa");
        } else {
            f.classList.remove("fila-activa");
        }
    });
    
    indiceActivoCli = 0;
    document.getElementById("modalClientes").style.display = "flex";
    setTimeout(() => inputBuscarCliente.focus(), 100);
}

function cerrarModalClientes() {
    document.getElementById("modalClientes").style.display = "none";
}

function seleccionarCliente(id, nombre) {
    document.getElementById("cliente_id").value = id;
    document.getElementById("btnDisparadorClientes").innerText = nombre;
    cerrarModalClientes();
    document.getElementById("metodo_pago").focus();
}

function manejarTecladoClientes(e) {
    let filasVisibles = Array.from(document.querySelectorAll("#tablaClientes tr")).filter(f => f.style.display !== "none");
    if (filasVisibles.length === 0) return;

    if (e.key === "ArrowDown") {
        e.preventDefault();
        filasVisibles.forEach(f => f.classList.remove("fila-activa"));
        indiceActivoCli = (indiceActivoCli + 1) % filasVisibles.length;
        filasVisibles[indiceActivoCli].classList.add("fila-activa");
        filasVisibles[indiceActivoCli].scrollIntoView({ block: "nearest" });
    } else if (e.key === "ArrowUp") {
        e.preventDefault();
        filasVisibles.forEach(f => f.classList.remove("fila-activa"));
        indiceActivoCli = (indiceActivoCli - 1 + filasVisibles.length) % filasVisibles.length;
        filasVisibles[indiceActivoCli].classList.add("fila-activa");
        filasVisibles[indiceActivoCli].scrollIntoView({ block: "nearest" });
    } else if (e.key === "Enter") {
        e.preventDefault();
        if (indiceActivoCli >= 0 && indiceActivoCli < filasVisibles.length) {
            filasVisibles[indiceActivoCli].click();
        }
    }
}

document.getElementById("buscarCliente").addEventListener("input", function() {
    let filtro = this.value.toLowerCase();
    let primeraFilaAsignada = false;
    
    document.querySelectorAll("#tablaClientes tr").forEach(f => {
        let cumple = f.innerText.toLowerCase().includes(filtro);
        f.style.display = cumple ? "" : "none";
        f.classList.remove("fila-activa");
        
        if (cumple && !primeraFilaAsignada) {
            f.classList.add("fila-activa");
            primeraFilaAsignada = true;
        }
    });
    indiceActivoCli = 0;
});

window.addEventListener("keydown", function(e) {
    if (e.altKey && (e.key === "c" || e.key === "C")) {
        e.preventDefault();
        if (!document.getElementById("btnDisparadorClientes").disabled) abrirModalClientes();
    }
    if (e.altKey && (e.key === "p" || e.key === "P")) {
        let selectPago = document.getElementById("metodo_pago");
        if (selectPago) { e.preventDefault(); selectPago.focus(); }
    }
}, true);
</script>

</body>
</html>