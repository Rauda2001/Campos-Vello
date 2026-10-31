<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/autenticacion.php';
require_once '../includes/config.php';

// Usamos únicamente la variable $pdo definida en tu config.php
$lista_proveedores = [];
if (isset($pdo) &&$pdo instanceof PDO) {
    try {
        $stmt_p =$pdo->query("SELECT id, nombre FROM proveedores ORDER BY nombre ASC");
        $lista_proveedores =$stmt_p->fetchAll();
    } catch (PDOException $e) {$lista_proveedores = [];
    }
}

$ingresoIdActual = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$ingresoActual = null;
$detallesIngresoActual = [];
$idIngresoAnterior = null;
$idIngresoSiguiente = null;

$idUltimoIngreso = $pdo->query('SELECT MAX(id) FROM ingresos')->fetchColumn();

if ($ingresoIdActual) {
    $stmtIngreso = $pdo->prepare("
        SELECT i.*, p.nombre AS proveedor_nombre, u.nombre AS usuario_nombre
        FROM ingresos i
        LEFT JOIN proveedores p ON p.id = i.id_proveedor
        LEFT JOIN usuarios u ON u.id = i.id_usuario
        WHERE i.id = ?
    ");
    $stmtIngreso->execute([$ingresoIdActual]);
    $ingresoActual = $stmtIngreso->fetch();

    if ($ingresoActual) {
        $stmtDetallesIngreso = $pdo->prepare("
            SELECT d.*, p.codigo AS producto_codigo, p.name AS producto_nombre
            FROM ingreso_detalles d
            LEFT JOIN productos p ON p.id = d.id_producto
            WHERE d.id_ingreso = ?
            ORDER BY d.id ASC
        ");
        $stmtDetallesIngreso->execute([$ingresoIdActual]);
        $detallesIngresoActual = $stmtDetallesIngreso->fetchAll();

        $stmtAnterior = $pdo->prepare('SELECT MAX(id) FROM ingresos WHERE id < ?');
        $stmtAnterior->execute([$ingresoIdActual]);
        $idIngresoAnterior = $stmtAnterior->fetchColumn() ?: null;

        $stmtSiguiente = $pdo->prepare('SELECT MIN(id) FROM ingresos WHERE id > ?');
        $stmtSiguiente->execute([$ingresoIdActual]);
        $idIngresoSiguiente = $stmtSiguiente->fetchColumn() ?: null;
    }
} else {
    $idIngresoAnterior = $idUltimoIngreso ?: null;
}

// Generar correlativo automático (ING-YYYY-XXXX)
$correlativo = 'ING-' . date('Y') . '-0001';
if (isset($pdo) &&$pdo instanceof PDO) {
    try {
        $stmt_ing =$pdo->query("SELECT MAX(id) AS ultimo FROM ingresos");
        $row_ing =$stmt_ing->fetch();
        $ultimo_id =$row_ing['ultimo'] ?? 0;
        $correlativo = 'ING-' . date('Y') . '-' . str_pad($ultimo_id + 1, 4, '0', STR_PAD_LEFT);
    } catch (PDOException $e) {
        // Mantiene correlativo por defecto
    }
}

$stmt_prod = $pdo->query("
    SELECT
        p.id,
        p.codigo,
        p.name AS nombre,
        COALESCE(c.name, 'Sin categoría') AS categoria,
        p.location AS ubicacion,
        p.price AS precio,
        p.stock
    FROM productos p
    LEFT JOIN categorias c ON c.id = p.category_id
    ORDER BY p.name ASC
");
$productos = $stmt_prod->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Productos e Ingresos - Campo Vello</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { background: #f3f7f4; color: #23332a; }
        .navbar-custom { background: #17613c; box-shadow: 0 3px 14px rgba(20, 54, 35, .14); }
        .navbar-brand { letter-spacing: .2px; }
        .page-card { border: 1px solid #e3ebe5; border-radius: 16px; box-shadow: 0 8px 26px rgba(28, 62, 41, .07) !important; }
        .page-heading { display: flex; align-items: flex-start; gap: 14px; margin-bottom: 26px; }
        .page-heading-icon { display: grid; place-items: center; width: 46px; height: 46px; flex: 0 0 46px; border-radius: 12px; background: #e8f4ec; color: #17613c; font-size: 1.35rem; }
        .page-heading h1 { margin: 0 0 5px; color: #17613c; font-size: clamp(1.35rem, 2.5vw, 1.8rem); font-weight: 700; }
        .page-heading p { margin: 0; color: #66756a; }
        .section-title { color: #243b2c; font-weight: 700; }
        .form-label { color: #34493a; }
        .form-control, .form-select { min-height: 42px; border-color: #d7e1d9; border-radius: 8px; }
        .form-control:focus, .form-select:focus { border-color: #4a9b69; box-shadow: 0 0 0 .2rem rgba(46, 125, 70, .14); }
        .btn-green { background: #21834d; border-color: #21834d; color: white; }
        .btn-green:hover { background: #17613c; border-color: #17613c; color: white; }
        .table-header-green th { background: #e8f2eb; color: #234b32; border-bottom-color: #d5e4d9; }
        .table > :not(caption) > * > * { padding: .8rem .75rem; }
        .table tbody tr:hover { background: #f6faf7; }
        .search-help { color: #6b796e; font-size: .88rem; }
        .inventory-search-results { max-height: 400px; overflow-y: auto; }
        .empty-search { color: #6b796e; padding: 1.5rem !important; text-align: center; }
        .stock-badge { min-width: 42px; }
        .receipt-history { padding: 16px; border: 1px solid #e0ebe3; border-radius: 12px; background: #f8fbf9; }
        .receipt-detail { padding: 18px; border: 1px solid #e0ebe3; border-radius: 12px; background: #fff; }
        .detail-label { display: block; margin-bottom: 4px; color: #748176; font-size: .82rem; }
        .admin-tools { display: flex; flex-wrap: wrap; gap: 8px; margin: -8px 0 24px; }
        @media (max-width: 767.98px) {
            .page-heading { margin-bottom: 20px; }
            .receipt-actions { align-items: stretch !important; flex-direction: column; gap: 10px; }
            .receipt-actions .btn { width: 100%; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom px-4 mb-4">
    <a class="navbar-brand fw-bold" href="#">Campo Vello - Admin</a>
    <div class="ms-auto">
        <a href="dashboard.php" class="btn btn-outline-light btn-sm">Volver al panel</a>
    </div>
</nav>

<div class="container bg-white p-4 p-md-5 mb-4 page-card">
    <?php if (!empty($_SESSION['mensaje_ingreso'])): ?>
        <?php $mensajeIngreso = $_SESSION['mensaje_ingreso']; unset($_SESSION['mensaje_ingreso']); ?>
        <div class="alert alert-<?= $mensajeIngreso['correcto'] ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($mensajeIngreso['texto'], ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>
    <?php if ($ingresoIdActual && !$ingresoActual): ?>
        <div class="alert alert-warning" role="alert">No se encontró el ingreso solicitado.</div>
    <?php endif; ?>

    <section class="receipt-history mb-4" aria-labelledby="receiptHistoryTitle">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h2 class="section-title h5 mb-1" id="receiptHistoryTitle">Historial de ingresos</h2>
                <p class="search-help mb-0">
                    <?= $ingresoActual
                        ? 'Ingreso seleccionado: ' . htmlspecialchars($ingresoActual['correlativo'], ENT_QUOTES, 'UTF-8')
                        : 'Navega por las recepciones registradas y abre su comprobante.' ?>
                </p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a class="btn btn-outline-success <?= !$idIngresoAnterior ? 'disabled' : '' ?>"
                   href="<?= $idIngresoAnterior ? 'gestionar_productos.php?id=' . (int) $idIngresoAnterior : '#' ?>"
                   aria-label="Ver ingreso anterior" <?= !$idIngresoAnterior ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
                    <i class="bi bi-chevron-left"></i>
                </a>
                <a class="btn btn-outline-success <?= !$idIngresoSiguiente ? 'disabled' : '' ?>"
                   href="<?= $idIngresoSiguiente ? 'gestionar_productos.php?id=' . (int) $idIngresoSiguiente : '#' ?>"
                   aria-label="Ver ingreso siguiente" <?= !$idIngresoSiguiente ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
                    <i class="bi bi-chevron-right"></i>
                </a>
                <a class="btn btn-light border" href="gestionar_productos.php">
                    <i class="bi bi-plus-lg"></i> Nuevo ingreso
                </a>
                <?php if ($ingresoActual): ?>
                    <a class="btn btn-green" href="ingreso_pdf.php?id=<?= (int) $ingresoActual['id'] ?>" target="_blank" rel="noopener">
                        <i class="bi bi-file-earmark-pdf"></i> Ver / imprimir PDF
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if ($ingresoActual): ?>
        <section class="receipt-detail mb-4" aria-label="Detalle del ingreso seleccionado">
            <div class="row g-3 mb-3">
                <div class="col-sm-6 col-lg-3">
                    <span class="detail-label">Correlativo</span>
                    <strong><?= htmlspecialchars($ingresoActual['correlativo'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <span class="detail-label">Proveedor</span>
                    <strong><?= htmlspecialchars($ingresoActual['proveedor_nombre'] ?? 'No disponible', ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <span class="detail-label">Factura del proveedor</span>
                    <strong><?= htmlspecialchars($ingresoActual['numero_factura_proveedor'] ?? '—', ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <span class="detail-label">Fecha de ingreso</span>
                    <strong><?= htmlspecialchars(date('d/m/Y H:i', strtotime($ingresoActual['fecha_ingreso'])), ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-header-green">
                        <tr>
                            <th>Código / SKU</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Costo de compra</th>
                            <th>Precio de venta</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detallesIngresoActual as $detalle): ?>
                            <tr>
                                <td><?= htmlspecialchars($detalle['producto_codigo'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($detalle['producto_nombre'] ?? 'Producto no disponible', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) $detalle['cantidad'] ?></td>
                                <td>$<?= number_format((float) $detalle['precio_compra'], 2) ?></td>
                                <td>$<?= number_format((float) $detalle['precio_venta_actualizado'], 2) ?></td>
                                <td>$<?= number_format((float) $detalle['subtotal'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$detallesIngresoActual): ?>
                            <tr><td colspan="6" class="empty-search">Este ingreso no tiene productos registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="text-end fw-bold">Total del ingreso</td>
                            <td class="fw-bold text-success">$<?= number_format((float) $ingresoActual['total'], 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <div class="page-heading">
        <div class="page-heading-icon"><i class="bi bi-box-arrow-in-down"></i></div>
        <div>
            <h1>Ingreso de material</h1>
            <p>Registra la recepción de productos y actualiza sus existencias.</p>
        </div>
    </div>
    <div class="admin-tools">
        <a class="btn btn-outline-success" href="nuevo_producto.php"><i class="bi bi-plus-lg"></i> Nuevo producto</a>
        <a class="btn btn-outline-success" href="gestionar_proveedores.php"><i class="bi bi-truck"></i> Proveedores</a>
        <a class="btn btn-outline-success" href="ordenes_compra.php"><i class="bi bi-clipboard-check"></i> Órdenes de compra</a>
        <a class="btn btn-outline-success" href="inventario.php"><i class="bi bi-file-earmark-pdf"></i> Inventario / PDF</a>
    </div>

    <form id="formIngreso" action="guardar_ingreso.php" method="POST">
        <?= csrf_field(); ?>

        <!-- ENCABEZADO DE RECEPCIÓN -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label fw-bold">Correlativo:</label>
                <input type="text" class="form-control bg-light" name="correlativo" value="<?= htmlspecialchars($correlativo) ?>" readonly>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Proveedor:</label>
                <select class="form-select" name="id_proveedor" required>
                    <option value="">Seleccione Proveedor...</option>
                    <?php if (!empty($lista_proveedores)): ?>
                        <?php foreach ($lista_proveedores as$p): ?>
                            <option value="<?= htmlspecialchars($p['id']) ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>No se encontraron proveedores en la base de datos</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Orden de Compra:</label>
                <input type="text" class="form-control" name="orden_compra" placeholder="Ej: OC-4589">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">N° Factura Proveedor:</label>
                <input type="text" class="form-control" name="num_factura" placeholder="Ej: F-102938" required>
            </div>
        </div>

        <hr>

        <!-- BUSCADOR CON BOTÓN PARA MODAL DE INVENTARIO -->
        <h5 class="section-title mb-2">Agregar productos a la recepción</h5>
        <p class="search-help mb-3">Escanea un SKU y presiona Enter, o busca por código o nombre.</p>
        <div class="row mb-3">
            <div class="col-lg-7 d-flex flex-column flex-sm-row gap-2">
                <input type="text" id="buscarCodigo" class="form-control" placeholder="Escribe o escanea un código SKU" autocomplete="off" aria-label="Código SKU del producto">
                <button type="button" class="btn btn-green text-nowrap" data-bs-toggle="modal" data-bs-target="#modalBuscarInventario">
                    <i class="bi bi-search"></i> Buscar en Inventario
                </button>
            </div>
        </div>

        <div class="table-responsive mb-3">
            <table class="table table-bordered align-middle">
                <thead class="table-success">
                    <tr>
                        <th style="width: 15%;">Código / SKU</th>
                        <th style="width: 30%;">Producto</th>
                        <th style="width: 10%;">Cantidad</th>
                        <th style="width: 15%;">Costo Compra ($)</th>
                        <th style="width: 15%;">Precio Venta ($)</th>
                        <th style="width: 10%;">Subtotal ($)</th>
                        <th style="width: 5%;">Acción</th>
                    </tr>
                </thead>
                <tbody id="listaDetalle">
                    <!-- Filas agregadas dinámicamente -->
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-end fw-bold fs-5">TOTAL COMPRA:</td>
                        <td class="fw-bold fs-5 text-success" id="totalTexto">$0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center receipt-actions">
            <input type="hidden" name="total_ingreso" id="totalIngreso" value="0.00">
            <button type="button" class="btn btn-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir Inventario</button>
            <button type="submit" class="btn btn-green btn-lg"><i class="bi bi-save"></i> Registrar Entrada e Incrementar Inventario</button>
        </div>
    </form>
</div>

<!-- CATÁLOGO DE PRODUCTOS EN SECCIÓN INFERIOR -->
<div class="container bg-white p-4 p-md-5 mb-5 page-card">
    <h2 class="section-title h4 mb-1">Productos en inventario</h2>
    <p class="search-help mb-3">Consulta los códigos, precios y existencias actuales.</p>
    <div class="table-responsive">
        <table class="table table-hover align-middle border">
            <thead class="table-header-green">
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Ubicación</th>
                    <th>Precio</th>
                    <th>Stock</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($productos)): ?>
                    <?php foreach ($productos as$prod): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($prod['codigo']) ?></td>
                            <td><?= htmlspecialchars($prod['nombre']) ?></td>
                            <td><?= htmlspecialchars($prod['categoria'] ?? 'Sin categoría') ?></td>
                            <td><?= htmlspecialchars($prod['ubicacion'] ?? 'N/A') ?></td>
                            <td>$<?= number_format($prod['precio'], 2) ?></td>
                            <td><span class="badge bg-<?= $prod['stock'] > 5 ? 'success' : 'danger' ?> stock-badge"><?= (int) $prod['stock'] ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center text-muted">No hay productos en inventario.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL BÚSQUEDA DE PRODUCTOS -->
<div class="modal fade" id="modalBuscarInventario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header navbar-custom text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-box-seam"></i> Seleccionar Producto del Inventario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-2">
                    <input type="search" id="filtroModal" class="form-control" placeholder="Buscar por código SKU o nombre del producto" autocomplete="off" aria-label="Buscar productos en inventario">
                    <button class="btn btn-success" type="button" onclick="filtrarProductosModal()">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                </div>
                <p class="search-help mb-3">Puedes escribir parte del código o del nombre.</p>

                <div class="table-responsive inventory-search-results">
                    <table class="table table-hover align-middle border">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Código / SKU</th>
                                <th>Producto</th>
                                <th>Stock Actual</th>
                                <th>Precio Venta</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tablaModalProductos">
                            <?php if (!empty($productos)): ?>
                                <?php foreach ($productos as$p): ?>
                                    <tr class="fila-producto-modal"
                                        data-codigo="<?= htmlspecialchars((string) $p['codigo'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-nombre="<?= htmlspecialchars((string) $p['nombre'], ENT_QUOTES, 'UTF-8') ?>">
                                        <td class="fw-bold"><?= htmlspecialchars((string) $p['codigo'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) $p['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><span class="badge bg-<?= $p['stock'] > 5 ? 'success' : 'danger' ?>"><?= (int) $p['stock'] ?></span></td>
                                        <td>$<?= number_format($p['precio'], 2) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-success btn-seleccionar-producto"
                                                    data-codigo="<?= htmlspecialchars((string) $p['codigo'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-nombre="<?= htmlspecialchars((string) $p['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-precio="<?= (float) $p['precio'] ?>">
                                                <i class="bi bi-plus-lg"></i> Agregar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="empty-search">No hay productos disponibles en el inventario.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Objeto JS con todos los productos procesados por PHP directamente
const catalogoProductos = <?= json_encode($productos, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

function normalizarBusqueda(valor) {
    return String(valor ?? '').trim().normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase();
}

function escaparHtml(valor) {
    return String(valor ?? '').replace(/[&<>"']/g, caracter => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    })[caracter]);
}

// Enfocar buscador al abrir modal
$('#modalBuscarInventario').on('shown.bs.modal', function () {
    $('#filtroModal').val('').focus();
    filtrarProductosModal();
});

// Filtrado instantáneo en el navegador (Sin AJAX)
$('#filtroModal').on('input', function() {
    filtrarProductosModal();
});

function filtrarProductosModal() {
    const query = normalizarBusqueda($('#filtroModal').val());
    let filasEncontradas = 0;

    $('.fila-producto-modal').each(function() {
        const codigo = normalizarBusqueda(this.dataset.codigo);
        const nombre = normalizarBusqueda(this.dataset.nombre);

        if (codigo.includes(query) || nombre.includes(query)) {
            $(this).show();
            filasEncontradas++;
        } else {
            $(this).hide();
        }
    });

    $('#sinResultadosModal').remove();
    if (filasEncontradas === 0 && $('.fila-producto-modal').length > 0) {
        $('#tablaModalProductos').append('<tr id="sinResultadosModal"><td colspan="5" class="empty-search">No se encontraron productos coincidentes.</td></tr>');
    }
}

// Búsqueda directa por SKU desde el input principal (Sin AJAX)
$('#buscarCodigo').on('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const codigoOriginal = $(this).val().trim();
        const codigoIngresado = normalizarBusqueda(codigoOriginal);

        if (codigoIngresado !== '') {
            const prod = catalogoProductos.find(p => normalizarBusqueda(p.codigo) === codigoIngresado);
            if (prod) {
                agregarFila(prod.codigo, prod.nombre, 1, 0.00, parseFloat(prod.precio));
                $('#buscarCodigo').val('');
            } else {
                alert('El código "' + codigoOriginal + '" no existe en el inventario.');
            }
        }
    }
});

$('#tablaModalProductos').on('click', '.btn-seleccionar-producto', function() {
    seleccionarProducto(this.dataset.codigo, this.dataset.nombre, parseFloat(this.dataset.precio));
});

function seleccionarProducto(codigo, nombre, precioVenta) {
    agregarFila(codigo, nombre, 1, 0.00, precioVenta);
    var modalEl = document.getElementById('modalBuscarInventario');
    var modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
}

function agregarFila(codigo, nombre, cantidad, costo, precioVenta) {
    const precioNumerico = Number.isFinite(Number(precioVenta)) ? Number(precioVenta) : 0;
    let subtotal = cantidad * costo;
    let fila = `
        <tr>
            <td><input type="hidden" name="codigo[]" value="${escaparHtml(codigo)}">${escaparHtml(codigo)}</td>
            <td>${escaparHtml(nombre)}</td>
            <td><input type="number" class="form-control cantidad" name="cantidad[]" value="${cantidad}" min="1" onchange="recalcular()" onkeyup="recalcular()"></td>
            <td><input type="number" step="0.01" class="form-control costo" name="costo[]" value="${costo.toFixed(2)}" onchange="recalcular()" onkeyup="recalcular()"></td>
            <td><input type="number" step="0.01" class="form-control" name="precio_venta[]" value="${precioNumerico.toFixed(2)}"></td>
            <td class="subtotal fw-bold">$${subtotal.toFixed(2)}</td>
            <td><button type="button" class="btn btn-danger btn-sm" onclick="$(this).closest('tr').remove(); recalcular();">X</button></td>
        </tr>
    `;
    $('#listaDetalle').append(fila);
    recalcular();
}

function recalcular() {
    let total = 0;
    $('#listaDetalle tr').each(function() {
        let cant = parseFloat($(this).find('.cantidad').val()) || 0;
        let costo = parseFloat($(this).find('.costo').val()) || 0;
        let sub = cant * costo;
        $(this).find('.subtotal').text('$' + sub.toFixed(2));
        total += sub;
    });
    $('#totalTexto').text('$' + total.toFixed(2));$('#totalIngreso').val(total.toFixed(2));
}
</script>

</body>
</html>