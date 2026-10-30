<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/autenticacion.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!is_admin()) {
    header('Location: ../index.php');
    exit;
}

$idIngreso = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$idIngreso || $idIngreso < 1) {
    http_response_code(400);
    exit('El identificador del ingreso no es válido.');
}

$stmtIngreso = $pdo->prepare("
    SELECT i.*, p.nombre AS proveedor_nombre, p.telefono AS proveedor_telefono,
           p.email AS proveedor_email, p.direccion AS proveedor_direccion,
           u.nombre AS usuario_nombre
    FROM ingresos i
    LEFT JOIN proveedores p ON p.id = i.id_proveedor
    LEFT JOIN usuarios u ON u.id = i.id_usuario
    WHERE i.id = ?
");
$stmtIngreso->execute([$idIngreso]);
$ingreso = $stmtIngreso->fetch();

if (!$ingreso) {
    http_response_code(404);
    exit('No se encontró el ingreso solicitado.');
}

$stmtDetalles = $pdo->prepare("
    SELECT d.*, p.codigo AS producto_codigo, p.name AS producto_nombre
    FROM ingreso_detalles d
    LEFT JOIN productos p ON p.id = d.id_producto
    WHERE d.id_ingreso = ?
    ORDER BY d.id ASC
");
$stmtDetalles->execute([$idIngreso]);
$detalles = $stmtDetalles->fetchAll();

$logoPath = realpath(__DIR__ . '/../assets/img/logo2.jpg');
$logoDataUri = '';
if ($logoPath && is_file($logoPath)) {
    $logoDataUri = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath));
}

$escape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

ob_start();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 32px 38px; }
        body { color: #26352b; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .header { border-bottom: 3px solid #17613c; margin-bottom: 20px; padding-bottom: 14px; }
        .logo { height: 58px; max-width: 220px; }
        .company { color: #17613c; font-size: 18px; font-weight: bold; margin-top: 6px; }
        h1 { color: #17613c; font-size: 17px; margin: 18px 0 5px; }
        .subtitle { color: #68766d; margin-bottom: 18px; }
        .meta { border-collapse: collapse; margin-bottom: 20px; width: 100%; }
        .meta td { border: 1px solid #dce7df; padding: 8px; vertical-align: top; width: 50%; }
        .label { color: #64746a; display: block; font-size: 8px; margin-bottom: 4px; text-transform: uppercase; }
        .items { border-collapse: collapse; width: 100%; }
        .items th { background: #17613c; color: #fff; font-weight: bold; text-align: left; }
        .items th, .items td { border: 1px solid #dce7df; padding: 8px 6px; }
        .number { text-align: right; white-space: nowrap; }
        .total { background: #edf6ef; color: #17613c; font-size: 12px; font-weight: bold; text-align: right; }
        .footer { border-top: 1px solid #dce7df; color: #68766d; font-size: 8px; margin-top: 28px; padding-top: 10px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <?php if ($logoDataUri): ?>
            <img class="logo" src="<?= $logoDataUri ?>" alt="Campo Vello">
        <?php else: ?>
            <div class="company">CAMPO VELLO</div>
        <?php endif; ?>
        <h1>Comprobante de ingreso de material</h1>
        <div class="subtitle">Recepción de productos e incremento de inventario</div>
    </div>

    <table class="meta">
        <tr>
            <td><span class="label">Correlativo</span><strong><?= $escape($ingreso['correlativo']) ?></strong></td>
            <td><span class="label">Fecha de ingreso</span><?= $escape(date('d/m/Y H:i', strtotime($ingreso['fecha_ingreso']))) ?></td>
        </tr>
        <tr>
            <td><span class="label">Proveedor</span><?= $escape($ingreso['proveedor_nombre'] ?? 'No disponible') ?></td>
            <td><span class="label">Factura del proveedor</span><?= $escape($ingreso['numero_factura_proveedor'] ?? '—') ?></td>
        </tr>
        <tr>
            <td><span class="label">Orden de compra</span><?= $escape($ingreso['orden_compra'] ?? '—') ?></td>
            <td><span class="label">Registrado por</span><?= $escape($ingreso['usuario_nombre'] ?? 'No disponible') ?></td>
        </tr>
        <?php if (!empty($ingreso['proveedor_telefono']) || !empty($ingreso['proveedor_email'])): ?>
            <tr>
                <td><span class="label">Contacto del proveedor</span><?= $escape($ingreso['proveedor_telefono'] ?? '—') ?></td>
                <td><span class="label">Correo</span><?= $escape($ingreso['proveedor_email'] ?? '—') ?></td>
            </tr>
        <?php endif; ?>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Código / SKU</th>
                <th>Producto</th>
                <th class="number">Cantidad</th>
                <th class="number">Costo unitario</th>
                <th class="number">Precio de venta</th>
                <th class="number">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($detalles as $detalle): ?>
                <tr>
                    <td><?= $escape($detalle['producto_codigo'] ?? '—') ?></td>
                    <td><?= $escape($detalle['producto_nombre'] ?? 'Producto no disponible') ?></td>
                    <td class="number"><?= (int) $detalle['cantidad'] ?></td>
                    <td class="number">$<?= number_format((float) $detalle['precio_compra'], 2) ?></td>
                    <td class="number">$<?= number_format((float) $detalle['precio_venta_actualizado'], 2) ?></td>
                    <td class="number">$<?= number_format((float) $detalle['subtotal'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$detalles): ?>
                <tr><td colspan="6">No hay productos registrados en este ingreso.</td></tr>
            <?php endif; ?>
            <tr>
                <td class="total" colspan="5">TOTAL DEL INGRESO</td>
                <td class="total">$<?= number_format((float) $ingreso['total'], 2) ?></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">Campo Vello · Comprobante de ingreso <?= $escape($ingreso['correlativo']) ?></div>
</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$nombreArchivo = preg_replace('/[^A-Za-z0-9_-]/', '_', $ingreso['correlativo']);
$dompdf->stream('Ingreso_' . $nombreArchivo . '.pdf', ['Attachment' => false]);
