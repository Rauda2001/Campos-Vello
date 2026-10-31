<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/autenticacion.php';

if (!is_admin()) {
    header('Location: ../index.php');
    exit;
}

function regresarOrden(string $mensaje): void
{
    $_SESSION['mensaje_orden'] = ['texto' => $mensaje, 'correcto' => false];
    header('Location: ordenes_compra.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ordenes_compra.php');
    exit;
}
if (!verify_csrf($_POST['_csrf'] ?? '')) {
    regresarOrden('La sesión venció. Actualiza la página e intenta nuevamente.');
}

$idProveedor = filter_var($_POST['id_proveedor'] ?? null, FILTER_VALIDATE_INT);
$productoIds = $_POST['producto_id'] ?? [];
$cantidades = $_POST['cantidad'] ?? [];
$costos = $_POST['costo'] ?? [];
$observaciones = trim((string) ($_POST['observaciones'] ?? ''));

if (!is_array($productoIds) || !is_array($cantidades) || !is_array($costos)) {
    regresarOrden('El detalle de la orden no es válido.');
}
$productoIds = array_values($productoIds);
$cantidades = array_values($cantidades);
$costos = array_values($costos);

if (!$idProveedor || count($productoIds) < 1 || count($productoIds) !== count($cantidades)
    || count($productoIds) !== count($costos) || strlen($observaciones) > 1000) {
    regresarOrden('Selecciona un proveedor y agrega productos con cantidades y costos válidos.');
}

$items = [];
$total = 0.0;
foreach ($productoIds as $indice => $idProductoTexto) {
    $idProducto = filter_var($idProductoTexto, FILTER_VALIDATE_INT);
    $cantidad = filter_var($cantidades[$indice], FILTER_VALIDATE_INT);
    $costoTexto = trim((string) $costos[$indice]);
    if (!$idProducto || !$cantidad || $cantidad < 1
        || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $costoTexto)
        || (float) $costoTexto <= 0) {
        regresarOrden('Revisa los productos, las cantidades y el costo unitario de la orden.');
    }

    $subtotal = round($cantidad * (float) $costoTexto, 2);
    $total += $subtotal;
    $items[] = [
        'id' => $idProducto,
        'cantidad' => $cantidad,
        'costo' => (float) $costoTexto,
        'subtotal' => $subtotal,
    ];
}

if (!is_finite($total) || $total > 99999999.99) {
    regresarOrden('El total de la orden excede el monto permitido.');
}

try {
    $pdo->beginTransaction();
    $stmtProveedor = $pdo->prepare('SELECT id FROM proveedores WHERE id = ?');
    $stmtProveedor->execute([$idProveedor]);
    if (!$stmtProveedor->fetchColumn()) {
        throw new RuntimeException('El proveedor seleccionado no existe.');
    }

    $stmtProducto = $pdo->prepare('SELECT id FROM productos WHERE id = ?');
    foreach ($items as $item) {
        $stmtProducto->execute([$item['id']]);
        if (!$stmtProducto->fetchColumn()) {
            throw new RuntimeException('Uno de los productos seleccionados ya no está disponible.');
        }
    }

    $stmtOrden = $pdo->prepare(
        'INSERT INTO ordenes_compra (correlativo, id_proveedor, id_usuario, total, observaciones)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmtOrden->execute([
        'TMP-' . bin2hex(random_bytes(16)),
        $idProveedor,
        (int) current_user()['id'],
        $total,
        $observaciones !== '' ? $observaciones : null,
    ]);

    $idOrden = (int) $pdo->lastInsertId();
    $correlativo = 'OC-' . date('Y') . '-' . str_pad((string) $idOrden, 5, '0', STR_PAD_LEFT);
    $pdo->prepare('UPDATE ordenes_compra SET correlativo = ? WHERE id = ?')
        ->execute([$correlativo, $idOrden]);

    $stmtDetalle = $pdo->prepare(
        'INSERT INTO orden_compra_detalles (id_orden_compra, id_producto, cantidad, costo_unitario, subtotal)
         VALUES (?, ?, ?, ?, ?)'
    );
    foreach ($items as $item) {
        $stmtDetalle->execute([
            $idOrden,
            $item['id'],
            $item['cantidad'],
            $item['costo'],
            $item['subtotal'],
        ]);
    }

    $pdo->commit();
    $_SESSION['mensaje_orden'] = [
        'texto' => 'Orden ' . $correlativo . ' guardada. No se modificaron las existencias.',
        'correcto' => true,
    ];
    header('Location: ordenes_compra.php?id=' . $idOrden);
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($e instanceof RuntimeException) {
        regresarOrden($e->getMessage());
    }
    error_log('Error al guardar orden de compra: ' . $e->getMessage());
    regresarOrden('No se pudo guardar la orden. No se modificaron las existencias.');
}
