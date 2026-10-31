<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/autenticacion.php';
require_once __DIR__ . '/../includes/codigos_productos.php';

if (!is_admin()) {
    header('Location: ../index.php');
    exit;
}

$categorias = $pdo->query('SELECT id, name FROM categorias ORDER BY name')->fetchAll();
$categoriaSeleccionada = filter_input(INPUT_GET, 'categoria', FILTER_VALIDATE_INT);
$codigoSugerido = '';

if ($categoriaSeleccionada) {
    $stmtCategoria = $pdo->prepare('SELECT id FROM categorias WHERE id = ?');
    $stmtCategoria->execute([$categoriaSeleccionada]);
    if ($stmtCategoria->fetchColumn()) {
        $codigoSugerido = siguiente_codigo_producto($pdo, $categoriaSeleccionada);
    } else {
        $categoriaSeleccionada = null;
    }
}

$mensaje = $_SESSION['mensaje_producto'] ?? null;
unset($_SESSION['mensaje_producto']);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nuevo producto - Campo Vello</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f3f7f4; color: #23332a; }
        .navbar-custom { background: #17613c; }
        .page-card { max-width: 850px; border: 1px solid #e3ebe5; border-radius: 16px; box-shadow: 0 8px 26px rgba(28, 62, 41, .07); }
        .form-label { color: #34493a; font-weight: 600; }
        .form-control, .form-select { min-height: 43px; border-color: #d7e1d9; }
        .btn-green { background: #21834d; border-color: #21834d; color: white; }
        .btn-green:hover { background: #17613c; color: white; }
        .help { color: #6b796e; font-size: .9rem; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark navbar-custom px-4">
    <a class="navbar-brand fw-bold" href="dashboard.php">Campo Vello - Admin</a>
    <a class="btn btn-outline-light btn-sm" href="gestionar_productos.php">Volver a ingresos</a>
</nav>
<main class="container py-4">
    <section class="page-card bg-white p-4 p-md-5 mx-auto">
        <h1 class="h3 text-success fw-bold mb-2">Agregar producto al catálogo</h1>
        <p class="help mb-4">El producto nuevo comenzará con existencia cero. Registra la mercancía desde Ingreso de material.</p>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?= $mensaje['correcto'] ? 'success' : 'danger' ?>" role="alert">
                <?= htmlspecialchars($mensaje['texto'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (!$categorias): ?>
            <div class="alert alert-warning">No hay categorías registradas. Crea una categoría antes de agregar productos.</div>
        <?php else: ?>
            <form action="guardar_producto.php" method="post">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="categoria">Categoría</label>
                        <select class="form-select" id="categoria" name="category_id" required>
                            <option value="">Selecciona una categoría</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= (int) $categoria['id'] ?>" <?= $categoriaSeleccionada === (int) $categoria['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($categoria['name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="codigo">Código SKU generado</label>
                        <input class="form-control bg-light" id="codigo" name="codigo_preview" value="<?= htmlspecialchars($codigoSugerido, ENT_QUOTES, 'UTF-8') ?>" placeholder="Se genera al elegir categoría" readonly>
                        <div class="help mt-1">Formato CAT + ID de categoría + consecutivo.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="nombre">Nombre del producto</label>
                        <input class="form-control" id="nombre" name="nombre" maxlength="150" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ubicacion">Ubicación</label>
                        <input class="form-control" id="ubicacion" name="ubicacion" maxlength="120">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="precio">Precio de venta ($)</label>
                        <input class="form-control" id="precio" name="precio" type="number" min="0" max="99999999.99" step="0.01" value="0.00" required>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button class="btn btn-green" type="submit"><i class="bi bi-box-seam"></i> Guardar producto</button>
                    <a class="btn btn-outline-secondary" href="gestionar_productos.php">Cancelar</a>
                </div>
            </form>
        <?php endif; ?>
    </section>
</main>
<script>
const categoria = document.getElementById('categoria');
const codigo = document.getElementById('codigo');

categoria?.addEventListener('change', async () => {
    codigo.value = '';
    if (!categoria.value) return;

    codigo.value = 'Generando...';
    try {
        const respuesta = await fetch(`siguiente_codigo_producto.php?category_id=${encodeURIComponent(categoria.value)}`, {
            headers: { Accept: 'application/json' }
        });
        const datos = await respuesta.json();
        if (!respuesta.ok) throw new Error(datos.error || 'No se pudo generar el código.');
        codigo.value = datos.codigo;
    } catch (error) {
        codigo.value = '';
        alert(error.message);
    }
});
</script>
</body>
</html>
