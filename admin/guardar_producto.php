<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/autenticacion.php';
require_once __DIR__ . '/../includes/codigos_productos.php';

if (!is_admin()) {
    header('Location: ../index.php');
    exit;
}

function regresarProducto(string $mensaje, bool $correcto = false): void
{
    $_SESSION['mensaje_producto'] = ['texto' => $mensaje, 'correcto' => $correcto];
    header('Location: nuevo_producto.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: nuevo_producto.php');
    exit;
}

if (!verify_csrf($_POST['_csrf'] ?? '')) {
    regresarProducto('La sesión venció. Actualiza la página e intenta nuevamente.');
}

$idCategoria = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);
$nombre = trim((string) ($_POST['nombre'] ?? ''));
$ubicacion = trim((string) ($_POST['ubicacion'] ?? ''));
$precioTexto = trim((string) ($_POST['precio'] ?? ''));

if (!$idCategoria || $nombre === '' || strlen($nombre) > 150 || strlen($ubicacion) > 120
    || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $precioTexto)) {
    regresarProducto('Revisa la categoría, el nombre, la ubicación y el precio de venta.');
}

try {
    $pdo->beginTransaction();
    $stmtCategoria = $pdo->prepare('SELECT id FROM categorias WHERE id = ? FOR UPDATE');
    $stmtCategoria->execute([$idCategoria]);
    if (!$stmtCategoria->fetchColumn()) {
        throw new RuntimeException('La categoría seleccionada no existe.');
    }

    $codigo = siguiente_codigo_producto($pdo, $idCategoria);
    $stmt = $pdo->prepare(
        'INSERT INTO productos (codigo, name, category_id, location, price, stock)
         VALUES (?, ?, ?, ?, ?, 0)'
    );
    $stmt->execute([
        $codigo,
        $nombre,
        $idCategoria,
        $ubicacion !== '' ? $ubicacion : null,
        (float) $precioTexto,
    ]);
    $pdo->commit();

    $_SESSION['mensaje_producto'] = [
        'texto' => 'Producto creado con el código ' . $codigo . '. Su existencia inicial es cero.',
        'correcto' => true,
    ];
    header('Location: nuevo_producto.php?categoria=' . $idCategoria);
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($e instanceof RuntimeException) {
        regresarProducto($e->getMessage());
    }
    if ($e instanceof PDOException && $e->getCode() === '23000') {
        regresarProducto('El código SKU generado ya está registrado. No se creó el producto; vuelve a seleccionar la categoría para generar otro código.');
    }
    error_log('Error al crear producto: ' . $e->getMessage());
    regresarProducto('No se pudo crear el producto. Verifica los datos e intenta de nuevo.');
}
