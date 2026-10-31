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

date_default_timezone_set('America/El_Salvador');
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
           c.name AS categoria, ultimo.precio_compra AS ultimo_costo
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
    $stmtProductos = $pdo->prepare($sql . ' WHERE ' . implode(' AND ', $condiciones) . ' ORDER BY p.name ASC');
    $stmtProductos->execute($parametros);
    $productos = $stmtProductos->fetchAll();
} else {
    $filtroStock = $soloBajoStock ? ' WHERE p.stock < 5' : '';
    $productos = $pdo->query($sql . $filtroStock . ' ORDER BY p.name ASC')->fetchAll();
}

$totalInventario = 0.0;
$productosSinCosto = 0;
foreach ($productos as $producto) {
    if ($producto['ultimo_costo'] === null) {
        if ((int) $producto['stock'] > 0) {
            $productosSinCosto++;
        }
    }
    $costoValoracion = $producto['ultimo_costo'] === null
        ? (float) $producto['price']
        : (float) $producto['ultimo_costo'];
    $totalInventario += $costoValoracion * (int) $producto['stock'];
}

$escape = static fn($valor): string => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');
$dompdf = new Dompdf($options);

ob_start();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 30px 28px; }
        body { color: #26352b; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        .header { background: #17613c; color: #fff; padding: 18px 20px; }
        .brand { font-size: 19px; font-weight: bold; }
        .subtitle { color: #e4f1e7; font-size: 9px; margin-top: 4px; }
        h1 { color: #17613c; font-size: 16px; margin: 20px 0 5px; }
        .meta { color: #58675d; margin: 0 0 14px; }
        .summary { background: #edf6ef; border: 1px solid #d5e5d9; margin-bottom: 14px; padding: 10px; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #17613c; color: #fff; text-align: left; }
        th, td { border: 1px solid #dce7df; padding: 6px 5px; }
        tr:nth-child(even) { background: #f7faf8; }
        .number { text-align: right; white-space: nowrap; }
        .center { text-align: center; }
        .total { background: #edf6ef; color: #17613c; font-size: 11px; font-weight: bold; }
        .note { color: #68766d; font-size: 8px; margin-top: 12px; }
        .footer { border-top: 1px solid #dce7df; color: #68766d; margin-top: 20px; padding-top: 8px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">CAMPO VELLO</div>
        <div class="subtitle">Sistema de inventario y facturación</div>
    </div>
    <h1><?= $soloBajoStock ? 'Reporte de productos con bajo stock' : 'Reporte de inventario' ?></h1>
    <p class="meta">Categoría: <strong><?= $escape($categoriaNombre) ?></strong><?= $soloBajoStock ? ' · Existencias menores a 5' : '' ?> · Generado: <?= $escape(date('d/m/Y H:i:s')) ?></p>
    <div class="summary">
        Productos: <strong><?= count($productos) ?></strong>
        &nbsp;&nbsp;|&nbsp;&nbsp;
        Valor estimado del inventario: <strong>$<?= number_format($totalInventario, 2) ?></strong>
    </div>
    <p class="note">La valoración usa el último costo de compra; cuando no existe, usa el precio de venta como estimación.</p>

    <table>
        <thead>
            <tr>
                <th>SKU</th>
                <th>Producto</th>
                <th>Categoría</th>
                <th>Ubicación</th>
                <th>Base de valoración</th>
                <th class="center">Existencia</th>
                <th class="number">Valor a costo</th>
                <th class="number">Precio de venta</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($productos as $producto): ?>
            <?php
            $costo = $producto['ultimo_costo'] === null ? null : (float) $producto['ultimo_costo'];
            $costoValoracion = $costo === null ? (float) $producto['price'] : $costo;
            $valorExistencia = $costoValoracion * (int) $producto['stock'];
            ?>
            <tr>
                <td><?= $escape($producto['codigo'] ?? '—') ?></td>
                <td><?= $escape($producto['name'] ?? '') ?></td>
                <td><?= $escape($producto['categoria'] ?? 'Sin categoría') ?></td>
                <td><?= $escape($producto['location'] ?? '—') ?></td>
                <td><?= $costo === null ? 'Precio de venta (estimado)' : 'Último costo: $' . number_format($costo, 2) ?></td>
                <td class="center"><?= (int) $producto['stock'] ?></td>
                <td class="number">$<?= number_format($valorExistencia, 2) ?></td>
                <td class="number">$<?= number_format((float) $producto['price'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$productos): ?>
            <tr><td class="center" colspan="8">No hay productos para la categoría seleccionada.</td></tr>
        <?php endif; ?>
            <tr>
                <td class="total number" colspan="6">VALOR ESTIMADO DEL INVENTARIO</td>
                <td class="total number">$<?= number_format($totalInventario, 2) ?></td>
                <td class="total"></td>
            </tr>
        </tbody>
    </table>
    <?php if ($productosSinCosto > 0): ?>
        <p class="note">Nota: <?= $productosSinCosto ?> producto(s) con existencias no tienen compras registradas; para esos productos se utilizó el precio de venta como estimación.</p>
    <?php endif; ?>
    <div class="footer">Campo Vello · Reporte generado el <?= $escape(date('d/m/Y H:i:s')) ?></div>
</body>
</html>
<?php
$dompdf->loadHtml(ob_get_clean(), 'UTF-8');
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream('Inventario_' . date('Ymd_His') . '.pdf', ['Attachment' => false]);
