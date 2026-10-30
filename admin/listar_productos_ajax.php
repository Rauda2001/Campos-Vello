<?php
require_once '../includes/config.php';

$db = $conexion ?? $conn ?? null;
$q = trim($_GET['q'] ?? '');

$productos = [];

if ($db) {
    if (!empty($q)) {
        // Filtra estrictamente por la columna 'codigo' o 'nombre'
        $stmt = $db->prepare("SELECT codigo, nombre, precio, stock FROM productos WHERE codigo LIKE ? OR nombre LIKE ? LIMIT 30");
        $param = "%$q%";
        $stmt->bind_param("ss", $param, $param);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        // Muestra los primeros 30 al abrir el modal
        $res = $db->query("SELECT codigo, nombre, precio, stock FROM productos ORDER BY nombre ASC LIMIT 30");
    }

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $productos[] = $row;
        }
    }
}

header('Content-Type: application/json');
echo json_encode($productos);