<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/autenticacion.php';
require_once __DIR__ . '/../includes/codigos_productos.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['error' => 'No tienes permiso para realizar esta acción.']);
    exit;
}

$idCategoria = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT);
if (!$idCategoria || $idCategoria < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Selecciona una categoría válida.']);
    exit;
}

$stmt = $pdo->prepare('SELECT id FROM categorias WHERE id = ?');
$stmt->execute([$idCategoria]);
if (!$stmt->fetchColumn()) {
    http_response_code(404);
    echo json_encode(['error' => 'La categoría seleccionada no existe.']);
    exit;
}

echo json_encode(['codigo' => siguiente_codigo_producto($pdo, $idCategoria)]);
