<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/autenticacion.php';

if (!is_admin()) {
    header('Location: ../index.php');
    exit;
}

$mensaje = $_SESSION['mensaje_proveedor'] ?? null;
unset($_SESSION['mensaje_proveedor']);
$proveedores = $pdo->query('SELECT id, nombre, telefono, email, direccion FROM proveedores ORDER BY nombre ASC')->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Proveedores - Campo Vello</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background: #f3f7f4; color: #23332a; }
        .navbar-custom { background: #17613c; }
        .page-card { border: 1px solid #e3ebe5; border-radius: 16px; box-shadow: 0 8px 26px rgba(28, 62, 41, .07); }
        .form-control { min-height: 42px; border-color: #d7e1d9; }
        .btn-green { background: #21834d; border-color: #21834d; color: white; }
        .btn-green:hover { background: #17613c; color: white; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark navbar-custom px-4">
    <a class="navbar-brand fw-bold" href="dashboard.php">Campo Vello - Admin</a>
    <a class="btn btn-outline-light btn-sm" href="dashboard.php">Volver al panel</a>
</nav>
<main class="container py-4">
    <div class="row g-4">
        <section class="col-lg-5">
            <div class="page-card bg-white p-4">
                <h1 class="h4 text-success fw-bold">Agregar proveedor</h1>
                <p class="text-secondary">Guarda los datos de las empresas a las que compras.</p>
                <?php if ($mensaje): ?>
                    <div class="alert alert-<?= $mensaje['correcto'] ? 'success' : 'danger' ?>" role="alert">
                        <?= htmlspecialchars($mensaje['texto'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
                <form action="guardar_proveedor.php" method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="nombre">Nombre del proveedor</label>
                        <input class="form-control" id="nombre" name="nombre" maxlength="150" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="telefono">Teléfono</label>
                        <input class="form-control" id="telefono" name="telefono" maxlength="20">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="email">Correo electrónico</label>
                        <input class="form-control" id="email" name="email" type="email" maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="direccion">Dirección</label>
                        <textarea class="form-control" id="direccion" name="direccion" rows="3"></textarea>
                    </div>
                    <button class="btn btn-green" type="submit">Guardar proveedor</button>
                </form>
            </div>
        </section>
        <section class="col-lg-7">
            <div class="page-card bg-white p-4">
                <h2 class="h4 text-success fw-bold mb-3">Proveedores registrados</h2>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-success"><tr><th>Proveedor</th><th>Teléfono</th><th>Correo</th></tr></thead>
                        <tbody>
                        <?php foreach ($proveedores as $proveedor): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($proveedor['nombre'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <?php if ($proveedor['direccion']): ?>
                                        <div class="small text-secondary"><?= htmlspecialchars($proveedor['direccion'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($proveedor['telefono'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($proveedor['email'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$proveedores): ?>
                            <tr><td class="text-center text-secondary py-4" colspan="3">No hay proveedores registrados.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</main>
</body>
</html>
