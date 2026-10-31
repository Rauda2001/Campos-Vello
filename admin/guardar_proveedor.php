<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/autenticacion.php';

if (!is_admin()) {
    header('Location: ../index.php');
    exit;
}

function regresarProveedor(string $mensaje, bool $correcto = false): void
{
    $_SESSION['mensaje_proveedor'] = ['texto' => $mensaje, 'correcto' => $correcto];
    header('Location: gestionar_proveedores.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: gestionar_proveedores.php');
    exit;
}

if (!verify_csrf($_POST['_csrf'] ?? '')) {
    regresarProveedor('La sesión venció. Actualiza la página e intenta nuevamente.');
}

$nombre = trim((string) ($_POST['nombre'] ?? ''));
$telefono = trim((string) ($_POST['telefono'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$direccion = trim((string) ($_POST['direccion'] ?? ''));

if ($nombre === '' || strlen($nombre) > 150 || strlen($telefono) > 20
    || strlen($email) > 100 || strlen($direccion) > 65535
    || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))) {
    regresarProveedor('Revisa el nombre, teléfono, correo y dirección del proveedor.');
}

$stmtExistente = $pdo->prepare('SELECT id FROM proveedores WHERE LOWER(nombre) = LOWER(?) LIMIT 1');
$stmtExistente->execute([$nombre]);
if ($stmtExistente->fetchColumn()) {
    regresarProveedor('Ya existe un proveedor con ese nombre.');
}

try {
    $stmt = $pdo->prepare(
        'INSERT INTO proveedores (nombre, telefono, email, direccion) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([
        $nombre,
        $telefono !== '' ? $telefono : null,
        $email !== '' ? $email : null,
        $direccion !== '' ? $direccion : null,
    ]);
    regresarProveedor('Proveedor agregado correctamente.', true);
} catch (PDOException $e) {
    error_log('Error al guardar proveedor: ' . $e->getMessage());
    regresarProveedor('No se pudo guardar el proveedor. Verifica los datos e intenta de nuevo.');
}
