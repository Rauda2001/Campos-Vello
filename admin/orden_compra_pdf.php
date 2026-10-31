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

$idOrden = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$idOrden || $idOrden < 1) {
    http_response_code(400);
    exit('El identificador de la orden no es válido.');
}

$stmtOrden = $pdo->prepare("
    SELECT o.*, p.nombre AS proveedor_nombre, p.telefono AS proveedor_telefono,
           p.email AS proveedor_email, p.direccion AS proveedor_direccion,
           u.nombre AS usuario_nombre
    FROM ordenes_compra o
    LEFT JOIN proveedores p ON p.id = o.id_proveedor
    LEFT JOIN usuarios u ON u.id = o.id_usuario
    WHERE o.id = ?
");
$stmtOrden->execute([$idOrden]);
$orden = $stmtOrden->fetch();
if (!$orden) {
    http_response_code(404);
    exit('No se encontró la orden solicitada.');
}

$stmtDetalles = $pdo->prepare("
    SELECT d.*, p.codigo AS producto_codigo, p.name AS producto_nombre
    FROM orden_compra_detalles d
    LEFT JOIN productos p ON p.id = d.id_producto
    WHERE d.id_orden_compra = ?
    ORDER BY d.id ASC
");
$stmtDetalles->execute([$idOrden]);
$detalles = $stmtDetalles->fetchAll();
$escape = static fn($valor): string => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');

ob_start();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 35px; }
        body { color: #26352b; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .header { border-bottom: 3px solid #17613c; margin-bottom: 22px; padding-bottom: 14px; }
        .brand { color: #17613c; font-size: 22px; font-weight: bold; }
        h1 { color: #17613c; font-size: 17px; margin: 14px 0 4px; }
        .muted { color: #68766d; }
        .meta, .items { border-collapse: collapse; width: 100%; }
        .meta { margin: 20px 0; }
        .meta td { border: 1px solid #dce7df; padding: 9px; width: 50%; }
        .label { color: #64746a; display: block; font-size: 8px; margin-bottom: 4px; text-transform: uppercase; }
        .items th { background: #17613c; color: #fff; text-align: left; }
        .items th, .items td { border: 1px solid #dce7df; padding: 8px 6px; }
        .number { text-align: right; white-space: nowrap; }
        .total { background: #edf6ef; color: #17613c; font-size: 12px; font-weight: bold; text-align: right; }
        .notice { background: #fff8df; border: 1px solid #eedc9b; color: #6d5713; margin-top: 20px; padding: 10px; }
        .footer { border-top: 1px solid #dce7df; color: #68766d; font-size: 8px; margin-top: 30px; padding-top: 10px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">CAMPO VELLO</div>
        <h1>Orden de compra</h1>
        <div class="muted">Documento de solicitud de productos; no representa una entrada de inventario.</div>
    </div>
    <table class="meta">
        <tr>
            <td><span class="label">Número de orden</span><strong><?= $escape($orden['correlativo']) ?></strong></td>
            <td><span class="label">Fecha de creación</span><?= $escape(date('d/m/Y H:i', strtotime($orden['fecha_creacion']))) ?></td>
        </tr>
        <tr>
            <td><span class="label">Proveedor</span><?= $escape($orden['proveedor_nombre'] ?? 'No disponible') ?></td>
            <td><span class="label">Registrada por</span><?= $escape($orden['usuario_nombre'] ?? 'No disponible') ?></td>
        </tr>
        <?php if (!empty($orden['proveedor_telefono']) || !empty($orden['proveedor_email'])): ?>
        <tr>
            <td><span class="label">Teléfono</span><?= $escape($orden['proveedor_telefono'] ?? '—') ?></td>
            <td><span class="label">Correo</span><?= $escape($orden['proveedor_email'] ?? '—') ?></td>
        </tr>
        <?php endif; ?>
    </table>
    <table class="items">
        <thead><tr><th>SKU</th><th>Producto</th><th class="number">Cantidad</th><th class="number">Costo unitario</th><th class="number">Subtotal</th></tr></thead>
        <tbody>
        <?php foreach ($detalles as $detalle): ?>
            <tr>
                <td><?= $escape($detalle['producto_codigo'] ?? '—') ?></td>
                <td><?= $escape($detalle['producto_nombre'] ?? 'Producto no disponible') ?></td>
                <td class="number"><?= (int) $detalle['cantidad'] ?></td>
                <td class="number">$<?= number_format((float) $detalle['costo_unitario'], 2) ?></td>
                <td class="number">$<?= number_format((float) $detalle['subtotal'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
            <tr><td class="total" colspan="4">TOTAL ESTIMADO</td><td class="total">$<?= number_format((float) $orden['total'], 2) ?></td></tr>
        </tbody>
    </table>
    <?php if (!empty($orden['observaciones'])): ?>
        <p><strong>Observaciones:</strong> <?= nl2br($escape($orden['observaciones'])) ?></p>
    <?php endif; ?>
    <div class="notice"><strong>Importante:</strong> Esta orden no modifica las existencias. El inventario se actualiza al recibir el material y registrar el ingreso correspondiente.</div>
    <div class="footer">Campo Vello · Orden de compra <?= $escape($orden['correlativo']) ?></div>
</body>
</html>
<?php
$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');
$dompdf = new Dompdf($options);
$dompdf->loadHtml(ob_get_clean(), 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$nombreArchivo = preg_replace('/[^A-Za-z0-9_-]/', '_', $orden['correlativo']);
$dompdf->stream('Orden_Compra_' . $nombreArchivo . '.pdf', ['Attachment' => false]);
