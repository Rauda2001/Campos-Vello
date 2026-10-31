<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/autenticacion.php';

if (!is_admin()) {
    header('Location: ../index.php');
    exit;
}

$categorias = $pdo->query('SELECT id, name FROM categorias ORDER BY name ASC')->fetchAll();
$categoriaId = filter_input(INPUT_GET, 'categoria', FILTER_VALIDATE_INT);
$categoriaId = $categoriaId !== false && $categoriaId !== null ? $categoriaId : 0;
$soloBajoStock = ($_GET['bajo_stock'] ?? '') === '1';
$categoriaNombre = 'Todas las categorías';

if ($categoriaId > 0) {
    $stmtCategoria = $pdo->prepare('SELECT name FROM categorias WHERE id = ?');
    $stmtCategoria->execute([$categoriaId]);
    $categoriaNombre = $stmtCategoria->fetchColumn();
    if ($categoriaNombre === false) {
        http_response_code(404);
        exit('La categoría seleccionada no existe.');
    }
}

$sql = "
    SELECT p.codigo, p.name, p.location, p.price, p.stock,
           c.name AS categoria,
           ultimo.precio_compra AS ultimo_costo
    FROM productos p
    LEFT JOIN categorias c ON c.id = p.category_id
    LEFT JOIN ingreso_detalles ultimo
        ON ultimo.id = (
            SELECT MAX(d.id) FROM ingreso_detalles d WHERE d.id_producto = p.id
        )
";
if ($categoriaId > 0) {
    $condiciones = ['p.category_id = ?'];
    $parametros = [$categoriaId];
    if ($soloBajoStock) {
        $condiciones[] = 'p.stock < 5';
    }
    $stmt = $pdo->prepare($sql . ' WHERE ' . implode(' AND ', $condiciones) . ' ORDER BY p.name ASC');
    $stmt->execute($parametros);
    $productos = $stmt->fetchAll();
} else {
    $filtroStock = $soloBajoStock ? ' WHERE p.stock < 5' : '';
    $productos = $pdo->query($sql . $filtroStock . ' ORDER BY p.name ASC')->fetchAll();
}

$valorTotal = 0.0;
$sinCosto = 0;
foreach ($productos as $producto) {
    if ($producto['ultimo_costo'] === null) {
        if ((int) $producto['stock'] > 0) {
            $sinCosto++;
        }
    }
    $costoValoracion = $producto['ultimo_costo'] === null
        ? (float) $producto['price']
        : (float) $producto['ultimo_costo'];
    $valorTotal += $costoValoracion * (int) $producto['stock'];
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventario - Campo Vello</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background: #f3f7f4; color: #23332a; }
        .navbar-custom { background: #17613c; }
        .page-card { border: 1px solid #e3ebe5; border-radius: 16px; box-shadow: 0 8px 26px rgba(28, 62, 41, .07); }
        .btn-green { background: #21834d; border-color: #21834d; color: white; }
        .btn-green:hover { background: #17613c; color: white; }
        .metric { background: #f6faf7; border: 1px solid #e2ebe4; border-radius: 12px; padding: 16px; height: 100%; }
        .metric span { color: #6b796e; display: block; font-size: .9rem; }
        .metric strong { color: #17613c; display: block; font-size: 1.35rem; margin-top: 5px; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark navbar-custom px-4">
    <a class="navbar-brand fw-bold" href="dashboard.php">Campo Vello - Admin</a>
    <a class="btn btn-outline-light btn-sm" href="dashboard.php">Volver al panel</a>
</nav>
<main class="container py-4">
    <section class="page-card bg-white p-4 p-md-5">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h1 class="h3 text-success fw-bold mb-1"><?= $soloBajoStock ? 'Productos con bajo stock' : 'Inventario valorizado' ?></h1>
                <p class="text-secondary mb-0"><?= $soloBajoStock ? 'Productos con menos de 5 unidades en existencia.' : 'Se usa el último costo de compra; si no existe, el precio de venta se muestra como estimación.' ?></p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <?php if ($soloBajoStock): ?>
                    <a class="btn btn-outline-success" href="inventario.php<?= $categoriaId > 0 ? '?categoria=' . (int) $categoriaId : '' ?>">Ver inventario completo</a>
                <?php else: ?>
                    <a class="btn btn-outline-warning" href="inventario.php?bajo_stock=1">Ver bajo stock</a>
                <?php endif; ?>
                    <a class="btn btn-green" href="reporte_inventario_pdf.php?categoria=<?= (int) $categoriaId ?>&bajo_stock=<?= $soloBajoStock ? '1' : '0' ?>" target="_blank" rel="noopener">
                        Ver / imprimir PDF
                </a>
            </div>
        </div>
        <form class="row g-2 align-items-end mb-4" method="get">
            <?php if ($soloBajoStock): ?><input type="hidden" name="bajo_stock" value="1"><?php endif; ?>
            <div class="col-md-6 col-lg-4">
                <label class="form-label fw-semibold" for="categoria">Filtrar por categoría</label>
                <select class="form-select" id="categoria" name="categoria">
                    <option value="0" <?= $categoriaId === 0 ? 'selected' : '' ?>>Todas las categorías</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?= (int) $categoria['id'] ?>" <?= $categoriaId === (int) $categoria['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($categoria['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto"><button class="btn btn-outline-success" type="submit">Ver inventario</button></div>
        </form>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-4"><div class="metric"><span>Categoría</span><strong class="fs-5"><?= htmlspecialchars($categoriaNombre, ENT_QUOTES, 'UTF-8') ?></strong></div></div>
            <div class="col-sm-6 col-lg-4"><div class="metric"><span>Productos listados</span><strong><?= count($productos) ?></strong></div></div>
            <div class="col-sm-6 col-lg-4"><div class="metric"><span>Valor estimado del inventario</span><strong>$<?= number_format($valorTotal, 2) ?></strong></div></div>
        </div>
        <?php if ($sinCosto > 0): ?>
            <div class="alert alert-warning">En <?= $sinCosto ?> producto(s) se utilizó el precio de venta como estimación porque no hay costo de compra registrado.</div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-success"><tr><th>SKU</th><th>Producto</th><th>Categoría</th><th>Ubicación</th><th>Base de valoración</th><th>Precio venta</th><th>Existencia</th><th>Valor estimado</th></tr></thead>
                <tbody>
                <?php foreach ($productos as $producto): ?>
                    <?php
                    $costoValoracion = $producto['ultimo_costo'] === null
                        ? (float) $producto['price']
                        : (float) $producto['ultimo_costo'];
                    ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($producto['codigo'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($producto['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($producto['categoria'] ?? 'Sin categoría', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($producto['location'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            $<?= number_format($costoValoracion, 2) ?>
                                <?php if ($producto['ultimo_costo'] === null): ?>
                                <span class="badge text-bg-warning">Estimado por precio de venta</span>
                            <?php else: ?>
                                <span class="badge text-bg-success">Último costo de compra</span>
                            <?php endif; ?>
                        </td>
                        <td>$<?= number_format((float) $producto['price'], 2) ?></td>
                        <td><?= (int) $producto['stock'] ?></td>
                        <td>$<?= number_format($costoValoracion * (int) $producto['stock'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$productos): ?>
                    <tr><td class="text-center text-secondary py-4" colspan="8">No hay productos para esta selección.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
