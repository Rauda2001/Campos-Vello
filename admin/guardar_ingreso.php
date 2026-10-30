<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/autenticacion.php';

if (!is_admin()) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: gestionar_productos.php');
    exit;
}

function volverAIngreso(string $mensaje, bool $correcto = false, ?int $ingresoId = null): void
{
    $_SESSION['mensaje_ingreso'] = [
        'texto' => $mensaje,
        'correcto' => $correcto,
    ];
    $destino = 'gestionar_productos.php';
    if ($ingresoId !== null) {
        $destino .= '?id=' . $ingresoId;
    }
    header('Location: ' . $destino);
    exit;
}

if (!verify_csrf($_POST['_csrf'] ?? '')) {
    volverAIngreso('La sesión del formulario venció. Actualiza la página e intenta de nuevo.');
}

$idProveedor = filter_var($_POST['id_proveedor'] ?? null, FILTER_VALIDATE_INT);
$numeroFactura = trim((string) ($_POST['num_factura'] ?? ''));
$ordenCompra = trim((string) ($_POST['orden_compra'] ?? ''));
$codigos = $_POST['codigo'] ?? [];
$cantidades = $_POST['cantidad'] ?? [];
$costos = $_POST['costo'] ?? [];
$preciosVenta = $_POST['precio_venta'] ?? [];

if (is_array($codigos) && is_array($cantidades) && is_array($costos) && is_array($preciosVenta)) {
    $codigos = array_values($codigos);
    $cantidades = array_values($cantidades);
    $costos = array_values($costos);
    $preciosVenta = array_values($preciosVenta);
}

if (!$idProveedor || $numeroFactura === '') {
    volverAIngreso('Selecciona un proveedor e ingresa el número de factura.');
}

if (!is_array($codigos) || !is_array($cantidades) || !is_array($costos) || !is_array($preciosVenta)
    || count($codigos) === 0
    || count($codigos) !== count($cantidades)
    || count($codigos) !== count($costos)
    || count($codigos) !== count($preciosVenta)) {
    volverAIngreso('Agrega al menos un producto y revisa sus cantidades y precios.');
}

$detalles = [];
$total = 0.0;

foreach ($codigos as $indice => $codigo) {
    $codigo = trim((string) $codigo);
    $cantidad = filter_var($cantidades[$indice], FILTER_VALIDATE_INT);
    $costoTexto = trim((string) $costos[$indice]);
    $precioTexto = trim((string) $preciosVenta[$indice]);

    if ($codigo === '' || $cantidad === false || $cantidad < 1
        || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $costoTexto)
        || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $precioTexto)) {
        volverAIngreso('Hay productos con código, cantidad o precio inválido. Revisa el detalle e intenta de nuevo.');
    }

    $costo = (float) $costoTexto;
    $precioVenta = (float) $precioTexto;
    $subtotal = round($cantidad * $costo, 2);
    $total += $subtotal;

    $detalles[] = [
        'codigo' => $codigo,
        'cantidad' => $cantidad,
        'costo' => $costo,
        'precio_venta' => $precioVenta,
        'subtotal' => $subtotal,
    ];
}

if (!is_finite($total) || $total > 99999999.99) {
    volverAIngreso('El total de la recepción excede el monto permitido.');
}

$usuario = current_user();

try {
    $pdo->beginTransaction();

    $stmtProveedor = $pdo->prepare('SELECT id FROM proveedores WHERE id = ?');
    $stmtProveedor->execute([$idProveedor]);
    if (!$stmtProveedor->fetchColumn()) {
        throw new RuntimeException('El proveedor seleccionado ya no está disponible.');
    }

    $correlativoTemporal = 'TMP-' . bin2hex(random_bytes(16));
    $stmtIngreso = $pdo->prepare(
        'INSERT INTO ingresos (correlativo, id_proveedor, orden_compra, numero_factura_proveedor, id_usuario, total)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmtIngreso->execute([
        $correlativoTemporal,
        $idProveedor,
        $ordenCompra !== '' ? $ordenCompra : null,
        $numeroFactura,
        (int) $usuario['id'],
        $total,
    ]);

    $idIngreso = (int) $pdo->lastInsertId();
    $correlativo = 'ING-' . date('Y') . '-' . str_pad((string) $idIngreso, 4, '0', STR_PAD_LEFT);
    $pdo->prepare('UPDATE ingresos SET correlativo = ? WHERE id = ?')
        ->execute([$correlativo, $idIngreso]);

    $stmtProducto = $pdo->prepare('SELECT id FROM productos WHERE codigo = ? FOR UPDATE');
    $stmtDetalle = $pdo->prepare(
        'INSERT INTO ingreso_detalles
            (id_ingreso, id_producto, cantidad, precio_compra, precio_venta_actualizado, subtotal)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmtActualizarProducto = $pdo->prepare(
        'UPDATE productos SET stock = stock + ?, price = ? WHERE id = ?'
    );

    foreach ($detalles as $detalle) {
        $stmtProducto->execute([$detalle['codigo']]);
        $idProducto = $stmtProducto->fetchColumn();

        if (!$idProducto) {
            throw new RuntimeException('El producto con código ' . $detalle['codigo'] . ' ya no existe.');
        }

        $stmtDetalle->execute([
            $idIngreso,
            (int) $idProducto,
            $detalle['cantidad'],
            $detalle['costo'],
            $detalle['precio_venta'],
            $detalle['subtotal'],
        ]);
        $stmtActualizarProducto->execute([
            $detalle['cantidad'],
            $detalle['precio_venta'],
            (int) $idProducto,
        ]);
    }

    $pdo->commit();
    volverAIngreso('La entrada ' . $correlativo . ' se registró correctamente y el inventario fue actualizado.', true, $idIngreso);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($e instanceof RuntimeException) {
        volverAIngreso($e->getMessage());
    }

    error_log('Error al registrar ingreso de material: ' . $e->getMessage());
    volverAIngreso('No se pudo registrar la entrada. No se modificó el inventario; verifica los datos e intenta de nuevo.');
}
