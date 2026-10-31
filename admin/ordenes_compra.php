<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/autenticacion.php';

if (!is_admin()) {
    header('Location: ../index.php');
    exit;
}

$productos = $pdo->query("
    SELECT p.id, p.codigo, p.name, COALESCE(ultimo.precio_compra, 0) AS ultimo_costo
    FROM productos p
    LEFT JOIN ingreso_detalles ultimo
        ON ultimo.id = (
            SELECT MAX(d.id) FROM ingreso_detalles d WHERE d.id_producto = p.id
        )
    ORDER BY p.name ASC
")->fetchAll();
$proveedores = $pdo->query('SELECT id, nombre FROM proveedores ORDER BY nombre ASC')->fetchAll();
$ordenId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$orden = null;
$detalles = [];
$mensaje = $_SESSION['mensaje_orden'] ?? null;
unset($_SESSION['mensaje_orden']);

if ($ordenId) {
    $stmt = $pdo->prepare("
        SELECT o.*, p.nombre AS proveedor_nombre, u.nombre AS usuario_nombre
        FROM ordenes_compra o
        LEFT JOIN proveedores p ON p.id = o.id_proveedor
        LEFT JOIN usuarios u ON u.id = o.id_usuario
        WHERE o.id = ?
    ");
    $stmt->execute([$ordenId]);
    $orden = $stmt->fetch();
    if ($orden) {
        $stmtDetalles = $pdo->prepare("
            SELECT d.*, p.codigo AS producto_codigo, p.name AS producto_nombre
            FROM orden_compra_detalles d
            LEFT JOIN productos p ON p.id = d.id_producto
            WHERE d.id_orden_compra = ?
            ORDER BY d.id ASC
        ");
        $stmtDetalles->execute([$ordenId]);
        $detalles = $stmtDetalles->fetchAll();
    }
}

$historial = $pdo->query("
    SELECT o.id, o.correlativo, o.fecha_creacion, o.total, p.nombre AS proveedor_nombre
    FROM ordenes_compra o
    LEFT JOIN proveedores p ON p.id = o.id_proveedor
    ORDER BY o.id DESC
    LIMIT 20
")->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Órdenes de compra - Campo Vello</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f3f7f4; color: #23332a; }
        .navbar-custom { background: #17613c; }
        .page-card { border: 1px solid #e3ebe5; border-radius: 16px; box-shadow: 0 8px 26px rgba(28, 62, 41, .07); }
        .form-control, .form-select { min-height: 42px; border-color: #d7e1d9; }
        .btn-green { background: #21834d; border-color: #21834d; color: white; }
        .btn-green:hover { background: #17613c; color: white; }
        .help { color: #6b796e; font-size: .9rem; }
        .product-results { max-height: 280px; overflow-y: auto; }
        .product-result { border: 1px solid #e2ebe4; border-radius: 8px; padding: 10px 12px; }
        .product-result + .product-result { margin-top: 6px; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark navbar-custom px-4">
    <a class="navbar-brand fw-bold" href="dashboard.php">Campo Vello - Admin</a>
    <a class="btn btn-outline-light btn-sm" href="dashboard.php">Volver al panel</a>
</nav>
<main class="container py-4">
    <?php if ($mensaje): ?>
        <div class="alert alert-<?= $mensaje['correcto'] ? 'success' : 'danger' ?>" role="alert">
            <?= htmlspecialchars($mensaje['texto'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($orden): ?>
        <section class="page-card bg-white p-4 p-md-5 mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <h1 class="h3 text-success fw-bold mb-1">Orden <?= htmlspecialchars($orden['correlativo'], ENT_QUOTES, 'UTF-8') ?></h1>
                    <div class="help">Creada <?= htmlspecialchars(date('d/m/Y H:i', strtotime($orden['fecha_creacion'])), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div class="d-flex gap-2">
                    <a class="btn btn-green" href="orden_compra_pdf.php?id=<?= (int) $orden['id'] ?>" target="_blank" rel="noopener">
                        <i class="bi bi-file-earmark-pdf"></i> Ver / imprimir PDF
                    </a>
                    <a class="btn btn-outline-success" href="ordenes_compra.php"><i class="bi bi-plus-lg"></i> Nueva orden</a>
                </div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-4"><span class="help d-block">Proveedor</span><strong><?= htmlspecialchars($orden['proveedor_nombre'] ?? 'No disponible', ENT_QUOTES, 'UTF-8') ?></strong></div>
                <div class="col-md-4"><span class="help d-block">Registrada por</span><strong><?= htmlspecialchars($orden['usuario_nombre'] ?? 'No disponible', ENT_QUOTES, 'UTF-8') ?></strong></div>
                <div class="col-md-4"><span class="help d-block">Estado</span><strong>Pendiente de recepción</strong></div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-success"><tr><th>SKU</th><th>Producto</th><th>Cantidad</th><th>Costo unitario</th><th>Subtotal</th></tr></thead>
                    <tbody>
                    <?php foreach ($detalles as $detalle): ?>
                        <tr>
                            <td><?= htmlspecialchars($detalle['producto_codigo'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($detalle['producto_nombre'] ?? 'Producto no disponible', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int) $detalle['cantidad'] ?></td>
                            <td>$<?= number_format((float) $detalle['costo_unitario'], 2) ?></td>
                            <td>$<?= number_format((float) $detalle['subtotal'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot><tr><td class="text-end fw-bold" colspan="4">Total estimado</td><td class="fw-bold text-success">$<?= number_format((float) $orden['total'], 2) ?></td></tr></tfoot>
                </table>
            </div>
            <?php if (!empty($orden['observaciones'])): ?>
                <p class="mb-0"><strong>Observaciones:</strong> <?= nl2br(htmlspecialchars($orden['observaciones'], ENT_QUOTES, 'UTF-8')) ?></p>
            <?php endif; ?>
        </section>
    <?php elseif ($ordenId): ?>
        <div class="alert alert-warning">No se encontró la orden solicitada.</div>
    <?php else: ?>
        <section class="page-card bg-white p-4 p-md-5 mb-4">
            <h1 class="h3 text-success fw-bold">Crear orden de compra</h1>
            <p class="help mb-4">La orden se guarda como documento pendiente. Crear una orden no modifica las existencias; el inventario solo cambia al registrar una entrada de material.</p>

            <?php if (!$proveedores): ?>
                <div class="alert alert-warning">Primero agrega un proveedor para poder crear la orden.</div>
            <?php else: ?>
                <form action="guardar_orden_compra.php" method="post" id="formOrden">
                    <?= csrf_field() ?>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="proveedor">Proveedor</label>
                            <select class="form-select" name="id_proveedor" id="proveedor" required>
                                <option value="">Selecciona proveedor</option>
                                <?php foreach ($proveedores as $proveedor): ?>
                                    <option value="<?= (int) $proveedor['id'] ?>"><?= htmlspecialchars($proveedor['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="observaciones">Observaciones</label>
                            <input class="form-control" name="observaciones" id="observaciones" maxlength="1000" placeholder="Fecha de entrega u otra nota">
                        </div>
                    </div>

                    <label class="form-label fw-semibold" for="buscarProducto">Buscar producto por código o nombre</label>
                    <input class="form-control mb-2" id="buscarProducto" type="search" autocomplete="off" placeholder="Ej. CAT2-0001 o Maicillo">
                    <div class="product-results mb-4" id="resultadosProductos" aria-live="polite"></div>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-success"><tr><th>SKU</th><th>Producto</th><th>Cantidad</th><th>Costo unitario ($)</th><th>Subtotal</th><th></th></tr></thead>
                            <tbody id="detalleOrden"><tr><td colspan="6" class="text-center text-secondary py-4">Busca y agrega los productos que deseas solicitar.</td></tr></tbody>
                            <tfoot><tr><td colspan="4" class="text-end fw-bold">Total estimado</td><td class="fw-bold text-success" id="totalOrden">$0.00</td><td></td></tr></tfoot>
                        </table>
                    </div>
                    <button class="btn btn-green btn-lg" type="submit"><i class="bi bi-save"></i> Guardar orden de compra</button>
                </form>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <section class="page-card bg-white p-4">
        <h2 class="h5 text-success fw-bold mb-3">Órdenes recientes</h2>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Orden</th><th>Fecha</th><th>Proveedor</th><th>Total</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($historial as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['correlativo'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($item['fecha_creacion'])), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($item['proveedor_nombre'] ?? 'No disponible', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>$<?= number_format((float) $item['total'], 2) ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-success" href="ordenes_compra.php?id=<?= (int) $item['id'] ?>">Ver</a>
                            <a class="btn btn-sm btn-outline-secondary" href="orden_compra_pdf.php?id=<?= (int) $item['id'] ?>" target="_blank" rel="noopener">PDF</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$historial): ?>
                    <tr><td colspan="5" class="text-center text-secondary py-4">Aún no hay órdenes registradas.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
<?php if (!$orden && !$ordenId && $proveedores): ?>
<script>
const catalogo = <?= json_encode($productos, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const campoBusqueda = document.getElementById('buscarProducto');
const resultados = document.getElementById('resultadosProductos');
const tbody = document.getElementById('detalleOrden');
const items = new Map();
const dinero = new Intl.NumberFormat('es-SV', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const htmlSeguro = texto => String(texto ?? '').replace(/[&<>"']/g, caracter => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
})[caracter]);

campoBusqueda.addEventListener('input', () => {
    const consulta = campoBusqueda.value.trim().normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    resultados.replaceChildren();
    if (!consulta) return;
    const encontrados = catalogo.filter(producto =>
        `${producto.codigo} ${producto.name}`.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().includes(consulta)
    ).slice(0, 15);
    if (!encontrados.length) {
        resultados.innerHTML = '<div class="text-secondary small py-2">No se encontraron productos.</div>';
        return;
    }
    encontrados.forEach(producto => {
        const boton = document.createElement('button');
        boton.type = 'button';
        boton.className = 'product-result btn btn-light w-100 text-start';
        boton.innerHTML = `<strong>${htmlSeguro(producto.codigo)}</strong> · ${htmlSeguro(producto.name)} <span class="text-secondary float-end">Último costo: $${dinero.format(Number(producto.ultimo_costo))}</span>`;
        boton.addEventListener('click', () => agregarProducto(producto));
        resultados.appendChild(boton);
    });
});

function agregarProducto(producto) {
    const id = String(producto.id);
    if (items.has(id)) {
        items.get(id).cantidad += 1;
    } else {
        items.set(id, {
            ...producto,
            cantidad: 1,
            costo: Number(producto.ultimo_costo) > 0 ? Number(producto.ultimo_costo) : ''
        });
    }
    campoBusqueda.value = '';
    resultados.replaceChildren();
    dibujarDetalle();
}

function dibujarDetalle() {
    tbody.replaceChildren();
    if (!items.size) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-secondary py-4">Busca y agrega los productos que deseas solicitar.</td></tr>';
        document.getElementById('totalOrden').textContent = '$0.00';
        return;
    }

    let total = 0;
    items.forEach(item => {
        const itemId = String(item.id);
        const subtotal = item.cantidad * (Number(item.costo) || 0);
        total += subtotal;
        const fila = document.createElement('tr');
        fila.innerHTML = `
            <td><input type="hidden" name="producto_id[]" value="${htmlSeguro(itemId)}">${htmlSeguro(item.codigo)}</td>
            <td>${htmlSeguro(item.name)}</td>
            <td><input class="form-control cantidad-orden" type="number" name="cantidad[]" min="1" step="1" value="${item.cantidad}" required></td>
            <td><input class="form-control costo-orden" type="number" name="costo[]" min="0.01" step="0.01" value="${item.costo}" placeholder="Ingresa costo" required></td>
            <td class="subtotal-orden">$${dinero.format(subtotal)}</td>
            <td><button class="btn btn-sm btn-outline-danger quitar-producto" type="button" aria-label="Quitar producto"><i class="bi bi-x-lg"></i></button></td>`;
        fila.querySelector('.cantidad-orden').addEventListener('change', evento => {
            item.cantidad = Math.max(1, parseInt(evento.target.value, 10) || 1);
            evento.target.value = item.cantidad;
            dibujarDetalle();
        });
        fila.querySelector('.costo-orden').addEventListener('change', evento => {
            item.costo = evento.target.value;
            dibujarDetalle();
        });
        fila.querySelector('.quitar-producto').addEventListener('click', () => {
            items.delete(String(item.id));
            dibujarDetalle();
        });
        tbody.appendChild(fila);
    });
    document.getElementById('totalOrden').textContent = `$${dinero.format(total)}`;
}
</script>
<?php endif; ?>
</body>
</html>
