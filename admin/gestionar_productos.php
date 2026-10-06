<?php

/**
 * 1. Lógica y Seguridad
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/autenticacion.php';
require_once __DIR__ . '/../includes/funciones.php';

// Redirigir si el usuario no es administrador
if (!is_admin()) {
    header('Location: ../index.php');
    exit;
}

$pdo = getPDO();
$error = '';

// Procesamiento de formulario POST (Agregar, Eliminar, Actualizar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verificación CSRF
    if (!verify_csrf($_POST['_csrf'] ?? '')) {
        $error = 'CSRF inválido';
    } else {
        $action = $_POST['action'] ?? '';

        // Acción: Agregar Producto
        if ($action === 'add') {
            $stmt = $pdo->prepare('INSERT INTO productos (codigo, name, category_id, location, price, stock) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                trim($_POST['codigo']),
                $_POST['name'],
                $_POST['category_id'] !== '' ? $_POST['category_id'] : null, 
                $_POST['location'],
                $_POST['price'],
                $_POST['stock']
            ]);
            header('Location: gestionar_productos.php');
            exit;
        }

        // Acción: Eliminar Producto
        if ($action === 'delete') {
            $stmt = $pdo->prepare('DELETE FROM productos WHERE id=?');
            $stmt->execute([$_POST['id']]);
            header('Location: gestionar_productos.php');
            exit;
        }

        // Acción: Actualizar Producto (Editar)
        if ($action === 'update') {
            $stmt = $pdo->prepare('UPDATE productos SET codigo = ?, name = ?, category_id = ?, location = ?, price = ?, stock = ? WHERE id = ?');
            $stmt->execute([
                trim($_POST['codigo']),
                $_POST['name'],
                $_POST['category_id'] !== '' ? $_POST['category_id'] : null,
                $_POST['location'],
                $_POST['price'],
                $_POST['stock'],
                $_POST['id']
            ]);
            header('Location: gestionar_productos.php');
            exit;
        }
    }
}


// ------------------------------------------------------------------
// LÓGICA DE BÚSQUEDA Y PAGINACIÓN PARA PRODUCTOS
// ------------------------------------------------------------------

// Capturar término de búsqueda
$search_query = $_GET['search'] ?? ''; 
$is_searching = !empty($search_query);
$search_param = '%' . $search_query . '%'; // Parámetro para LIKE

// Variables de Paginación (solo se usan si NO se está buscando)
$limit = 10; // Límite de productos por página
$page = (int) ($_GET['page'] ?? 1); // Página actual, por defecto 1

// --- 1. Contar el total de productos (con o sin filtro de búsqueda) ---
$count_sql = 'SELECT COUNT(id) FROM productos';
$main_sql = '
    SELECT p.*, c.name as category 
    FROM productos p 
    LEFT JOIN categorias c ON p.category_id = c.id';
$params = [];

// AÑADIR FILTRO DE BÚSQUEDA si existe (Busca por Nombre o Código)
if ($is_searching) {
    $count_sql .= ' WHERE name LIKE :search OR codigo LIKE :search';
    $main_sql .= ' WHERE p.name LIKE :search OR p.codigo LIKE :search';
    $params[':search'] = $search_param;
}

$total_products_stmt = $pdo->prepare($count_sql);

// BIND el parámetro de búsqueda si se está usando
if ($is_searching) {
    $total_products_stmt->bindValue(':search', $search_param, PDO::PARAM_STR);
}
$total_products_stmt->execute();
$total_products = $total_products_stmt->fetchColumn();

// --- Lógica de Paginación (SÓLO si NO hay búsqueda) ---
if ($is_searching) {
    // Si se está buscando, no hay paginación:
    $total_pages = 1;
    $offset = 0;
    $limit_clause = ''; // No LIMIT
} else {
    // Si NO se está buscando, aplicamos la paginación:
    $total_pages = ceil($total_products / $limit);

    // Ajustar la página si es inválida
    if ($page < 1) $page = 1;
    if ($page > $total_pages && $total_products > 0) $page = $total_pages;

    // Calcular el punto de inicio (offset)
    $offset = ($page - 1) * $limit;
    
    // Agregar LIMIT y OFFSET a la consulta principal
    $limit_clause = ' LIMIT :limit OFFSET :offset';
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;
}

// ------------------------------------------------------------------
// --- 2. OBTENER PRODUCTOS PARA LA VISTA ACTUAL ---
// ------------------------------------------------------------------
$sql = $main_sql . ' ORDER BY p.id DESC' . $limit_clause;

// Ejecutar la consulta preparada
$stmt = $pdo->prepare($sql);

// BIND todos los parámetros
foreach ($params as $key => $value) {
    $type = PDO::PARAM_STR;
    if ($key === ':limit' || $key === ':offset') {
        $type = PDO::PARAM_INT;
    }
    $stmt->bindValue($key, $value, $type);
}

$stmt->execute();
$products = $stmt->fetchAll();

// Obtener categorías
$categories = $pdo->query('SELECT * FROM categorias')->fetchAll();
?>

<!doctype html>
<html lang="es">

<head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width,initial-scale=1'>
    <title>Gestionar Productos</title>
    <link rel='stylesheet' href='../assets/css/style.css'>

    <style>
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 9999;
            padding: 20px;
        }

        .modal.show {
            display: flex;
        }

        .modal .modal-content {
            background: #fff;
            border-radius: 8px;
            padding: 16px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .modal .close {
            position: absolute;
            right: 8px;
            top: 8px;
            background: transparent;
            border: none;
            font-size: 20px;
            cursor: pointer;
        }

        .form-row {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .form-row input,
        .form-row select {
            padding: 6px 8px;
        }
        
        .action-buttons-group {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            align-items: center;
        }

        .btn-equal-size {
            width: 150px;
            text-align: center;
        }
    </style>
</head>

<body>

    <nav>
        <div style='display:flex;gap:12px;align-items:center'>
            <img src='../assets/img/logo.svg' style='height:36px' alt='logo'>
            <strong>Campo Vello - Admin</strong>
        </div>
        <div>
            <a href='dashboard.php' style='color:#fff' class="btn">Volver al panel</a>
        </div>
    </nav>

    <div class='container'>
        <div class='container'>
            <h2>Productos</h2>

            <?php if (!empty($error)) echo '<div class="alert">' . htmlspecialchars($error) . '</div>'; ?>

            <form method='post' id="add-product-form">
                <?php echo csrf_field(); ?>
                <input type='hidden' name='action' value='add'>

                <div class="form-row">
                    <input name='codigo' placeholder='Código / SKU' pattern="[a-zA-Z0-9\-_]+" required style="width: 120px;">
                    <input name='name' placeholder='Nombre' pattern="[a-zA-Z0-9ñÑáÁéÉíÍóÓúÚ\s\-,()]+" required>
                    <select name='category_id' required>
                        <option value=''>Sin categoría</option>
                        <?php foreach ($categories as $c) : ?>
                            <option value='<?= htmlspecialchars($c['id']) ?>'><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input name='location' pattern="[a-zA-Z0-9ñÑáÁéÉíÍóÓúÚ\s\-,()]+" required placeholder='Ubicación'>
                    <input name='price' type='number' step='0.01' value='' required placeholder='Precio'>
                    <input name='stock' type='number' value='' required placeholder='Stock'>
                </div>
            </form> </br>

            <div class="action-buttons-group">
                <button class='btn btn-equal-size' form="add-product-form">Agregar</button>
                
                <a href="reporte_inventario_pdf.php" target="_blank" class="btn btn-equal-size" style="background-color:#337ab7;">
                    Imprimir inventario
                </a>

                <!-- Campo de búsqueda -->
                <div style="margin-left: auto; display: flex; align-items: center; gap: 8px;">
                    <input type="text" id="search_input" placeholder="Buscar por nombre o código..."
                        value="<?= htmlspecialchars($search_query) ?>" style="width:220px;">
                    <button type="button" class="btn" id="search_button">Buscar</button>
                    <?php if ($is_searching): ?>
                        <a href="gestionar_productos.php" class="btn" style="background-color:#ccc; color:#333;">X</a>
                        <span style="font-size: 0.9em; color: #059669; white-space: nowrap;">(<?= $total_products ?> resultados)</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <table class='table'>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Ubicación</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center;">
                                <?php if ($is_searching): ?>
                                    No se encontraron productos con el término "<?= htmlspecialchars($search_query) ?>"
                                <?php else: ?>
                                    No hay productos para mostrar.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    
                    <?php foreach ($products as $p) : ?>
                        <tr>
                            <!-- white-space: nowrap para que el código quede en una sola línea -->
                            <td style="white-space: nowrap;"><strong><?= htmlspecialchars($p['codigo'] ?? 'N/A') ?></strong></td>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td><?= htmlspecialchars($p['category']) ?></td>
                            <td><?= htmlspecialchars($p['location']) ?></td>
                            <td>$<?= number_format($p['price'], 2) ?></td>
                            <td><?= htmlspecialchars($p['stock']) ?></td>
                            
                            <!-- Flexbox para botones alineados horizontalmente -->
                            <td style="white-space: nowrap;">
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <button class='btn' type="button" onclick="openModal('edit-<?= htmlspecialchars($p['id']) ?>')">Editar</button>

                                    <form method='post' style='display:inline-block; margin:0;' onsubmit="return confirm('¿Eliminar este producto?');">
                                        <?php echo csrf_field(); ?>
                                        <input type='hidden' name='action' value='delete'>
                                        <input type='hidden' name='id' value='<?= htmlspecialchars($p['id']) ?>'>
                                        <button class='btn' style='background:#c62828'>Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <div class="modal" id="edit-<?= htmlspecialchars($p['id']) ?>" aria-hidden="true" role="dialog" aria-labelledby="edit-label-<?= htmlspecialchars($p['id']) ?>">
                            <div class="modal-content">
                                <button class="close" type="button" onclick="closeModal('edit-<?= htmlspecialchars($p['id']) ?>')" aria-label="Cerrar">&times;</button>
                                <h3 id="edit-label-<?= htmlspecialchars($p['id']) ?>">Editar producto (<?= htmlspecialchars($p['codigo'] ?? 'ID #' . $p['id']) ?>)</h3>
                                <form method="post" class="form-row">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($p['id']) ?>">

                                    <label style="flex:1 1 30%">
                                        Código / SKU<br>
                                        <input name="codigo" pattern="[a-zA-Z0-9\-_]+" required value="<?= htmlspecialchars($p['codigo'] ?? '') ?>">
                                    </label>

                                    <label style="flex:1 1 65%">
                                        Nombre<br>
                                        <input name="name" pattern="[a-zA-Z0-9ñÑáÁéÉíÍóÓúÚ\s\-,()]+" required value="<?= htmlspecialchars($p['name']) ?>">
                                    </label>

                                    <label style="flex:1 1 45%">
                                        Categoría<br>
                                        <select name="category_id">
                                            <option value=''>Sin categoría</option>
                                            <?php foreach ($categories as$c) : ?>
                                                <option value="<?= htmlspecialchars($c['id']) ?>" <?= ($p['category_id'] == $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>

                                    <label style="flex:1 1 45%">
                                        Ubicación<br>
                                        <input name="location" pattern="[a-zA-Z0-9ñÑáÁéÉíÍóÓúÚ\s\-,()]+" value="<?= htmlspecialchars($p['location']) ?>">
                                    </label>

                                    <label style="flex:1 1 45%">
                                        Precio<br>
                                        <input name="price" type="number" step="0.01" required value="<?= htmlspecialchars($p['price']) ?>">
                                    </label>

                                    <label style="flex:1 1 45%">
                                        Stock<br>
                                        <input name="stock" type="number" required value="<?= htmlspecialchars($p['stock']) ?>">
                                    </label>

                                    <div style="flex:1 1 100%; display:flex; gap:8px; margin-top:8px;">
                                        <button class="btn" type="submit">Guardar cambios</button>
                                        <button class="btn" type="button" onclick="closeModal('edit-<?= htmlspecialchars($p['id']) ?>')">Cancelar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Lógica de paginación solo si NO se está buscando -->
            <div style="display: flex; justify-content: center; gap: 8px; margin-top: 20px;">
                <?php if (!$is_searching &&$total_pages > 1) : ?>
                    <?php $base_url = 'gestionar_productos.php?page='; ?>

                    <?php if ($page > 1) : ?>
                        <a href="<?= $base_url . ($page - 1) ?>" class="btn">Anterior</a>
                    <?php else : ?>
                        <button class="btn" disabled>Anterior</button>
                    <?php endif; ?>

                    <?php 
                        $start = max(1, $page - 2);$end = min($total_pages,$page + 2);

                        if ($start > 1) {
                            echo '<a href="' . $base_url . '1" class="btn" style="background-color:#eee; color:#333;">1</a>';
                            if ($start > 2) {
                                echo '<span style="padding: 6px 8px;">...</span>';
                            }
                        }
                        
                        for ($i = $start; $i <= $end; $i++) : ?>
                            <a href="<?= $base_url .$i ?>" class="btn" style="<?= $i ==$page ? 'background-color:#000; color:#fff;' : 'background-color:#eee; color:#333;' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php 
                        if ($end <$total_pages) {
                            if ($end <$total_pages - 1) {
                                echo '<span style="padding: 6px 8px;">...</span>';
                            }
                            echo '<a href="' . $base_url . $total_pages . '" class="btn" style="background-color:#eee; color:#333;">' . $total_pages . '</a>';
                        }
                        ?>

                    <?php if ($page <$total_pages) : ?>
                        <a href="<?= $base_url . ($page + 1) ?>" class="btn">Siguiente</a>
                    <?php else : ?>
                        <button class="btn" disabled>Siguiente</button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const searchInput = document.getElementById('search_input');
                const searchButton = document.getElementById('search_button');

                if (searchButton) {
                    searchButton.addEventListener('click', function() {
                        const query = searchInput.value.trim();
                        if (query) {
                            window.location.href = `gestionar_productos.php?search=${encodeURIComponent(query)}`;
                        } else {
                            window.location.href = 'gestionar_productos.php';
                        }
                    });

                    searchInput.addEventListener('keypress', function(e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            searchButton.click();
                        }
                    });
                }
                
                function openModal(id) {
                    const el = document.getElementById(id);
                    if (el) {
                        el.classList.add('show');
                        el.setAttribute('aria-hidden', 'false');
                    }
                }

                function closeModal(id) {
                    const el = document.getElementById(id);
                    if (el) {
                        el.classList.remove('show');
                        el.setAttribute('aria-hidden', 'true');
                    }
                }

                window.openModal = openModal;
                window.closeModal = closeModal;

                window.addEventListener('click', function(e) {
                    if (e.target.classList && e.target.classList.contains('modal')) {
                        e.target.classList.remove('show');
                        e.target.setAttribute('aria-hidden', 'true');
                    }
                });

                window.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        document.querySelectorAll('.modal.show').forEach(function(m) {
                            m.classList.remove('show');
                            m.setAttribute('aria-hidden', 'true');
                        });
                    }
                });
            });
        </script>

        <footer style="
        position: fixed; 
        bottom: 0; 
        width: 100%; 
        text-align: center; 
        padding: 10px 0; 
        background: #f4f4f9; 
        border-top: 1px solid #ddd;
        font-size: 0.85em;
        color: #555;
    ">
        &copy; <?= date('Y') ?> Campo Vello. Todos los derechos reservados.
    </footer>

</body>

</html>