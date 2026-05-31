<?php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/autenticacion.php';
require_once __DIR__ . '/../includes/funciones.php';

if (!is_admin()) {
    header('Location: ../index.php');
    exit;
}

$pdo = getPDO();

// ======================================================
// GENERAR CORRELATIVO
// ======================================================

$stmt = $pdo->query("SELECT MAX(id) as ultimo FROM ingresos_materiales");
$ultimo = $stmt->fetch();

$numero = ($ultimo['ultimo'] ?? 0) + 1;

$correlativo = 'RM-' . str_pad($numero, 6, '0', STR_PAD_LEFT);

// ======================================================
// OBTENER PRODUCTOS
// ======================================================

$productos = $pdo->query("
    SELECT 
        id,
        codigo,
        name,
        price,
        stock
    FROM productos
    ORDER BY name ASC
")->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Ingreso de Materiales</title>

<link rel="stylesheet" href="../assets/css/style.css">

<style>

body{
    background:#f4f6f9;
    font-family:Arial, Helvetica, sans-serif;
}

.container{
    max-width:1400px;
    margin:auto;
    padding:20px;
}

.card{
    background:#fff;
    border-radius:12px;
    padding:20px;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
}

.header-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:15px;
    margin-bottom:20px;
}

.form-group{
    display:flex;
    flex-direction:column;
}

.form-group label{
    margin-bottom:5px;
    font-weight:bold;
    color:#1b5e20;
}

.form-group input{
    padding:10px;
    border:1px solid #ccc;
    border-radius:8px;
}

.table-container{
    overflow:auto;
}

table{
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
}

table th{
    background:#1b5e20;
    color:#fff;
    padding:12px;
    text-align:left;
}

table td{
    padding:10px;
    border-bottom:1px solid #ddd;
}

table input{
    width:100%;
    padding:8px;
    border:1px solid #ccc;
    border-radius:6px;
}

.btn{
    background:#1b5e20;
    color:#fff;
    border:none;
    padding:10px 18px;
    border-radius:8px;
    cursor:pointer;
    font-weight:bold;
}

.btn:hover{
    background:#145a18;
}

.btn-danger{
    background:#c62828;
}

.btn-danger:hover{
    background:#8e0000;
}

.actions{
    margin-top:20px;
    display:flex;
    gap:10px;
}

.total-box{
    margin-top:20px;
    text-align:right;
    font-size:22px;
    font-weight:bold;
    color:#1b5e20;
}

.search-container{
    position:relative;
}

.search-results{
    position:absolute;
    top:100%;
    left:0;
    width:100%;
    background:#fff;
    border:1px solid #ccc;
    max-height:250px;
    overflow-y:auto;
    z-index:999;
    display:none;
}

.search-item{
    padding:10px;
    cursor:pointer;
    border-bottom:1px solid #eee;
}

.search-item:hover{
    background:#e8f5e9;
}

.badge{
    background:#e8f5e9;
    color:#1b5e20;
    padding:4px 8px;
    border-radius:6px;
    font-size:12px;
    font-weight:bold;
}

</style>

</head>

<body>

<nav>

<div style='display:flex;gap:12px;align-items:center;padding:15px;background:#1b5e20;color:white;'>

<img src='../assets/img/logo.svg' style='height:36px' alt='logo'>

<strong>Campo Vello - Ingreso de Materiales</strong>

<div style="margin-left:auto;">
<a href='dashboard.php' class="btn" style="background:white;color:#1b5e20;">
Volver
</a>
</div>

</div>

</nav>

<div class="container">

<div class="card">

<h2 style="color:#1b5e20;margin-bottom:20px;">
📦 Recibo de Materiales
</h2>

<!-- ====================================================== -->
<!-- ENCABEZADO -->
<!-- ====================================================== -->

<div class="header-grid">

<div class="form-group">
<label>Correlativo</label>
<input type="text" value="<?= $correlativo ?>" readonly>
</div>

<div class="form-group">
<label>Proveedor</label>
<input type="text" id="proveedor" placeholder="Nombre del proveedor">
</div>

<div class="form-group">
<label>Fecha</label>
<input type="date" value="<?= date('Y-m-d') ?>">
</div>

<div class="form-group">
<label>Buscar producto</label>

<div class="search-container">

<input 
type="text"
id="buscarProducto"
placeholder="Buscar producto..."
autocomplete="off"
>

<div class="search-results" id="searchResults"></div>

</div>

</div>

</div>

<!-- ====================================================== -->
<!-- TABLA -->
<!-- ====================================================== -->

<div class="table-container">

<table id="tablaProductos">

<thead>

<tr>
<th>Código</th>
<th>Producto</th>
<th>Stock Actual</th>
<th>Cantidad</th>
<th>Costo</th>
<th>Subtotal</th>
<th>Acción</th>
</tr>

</thead>

<tbody>

</tbody>

</table>

</div>

<!-- ====================================================== -->
<!-- TOTAL -->
<!-- ====================================================== -->

<div class="total-box">

TOTAL: $<span id="totalGeneral">0.00</span>

</div>

<!-- ====================================================== -->
<!-- BOTONES -->
<!-- ====================================================== -->

<div class="actions">

<button class="btn">
💾 Guardar ingreso
</button>

<button class="btn" style="background:#1565c0;">
🖨 Generar PDF
</button>

</div>

</div>

</div>

<script>

// ======================================================
// PRODUCTOS DESDE PHP
// ======================================================

const productos = <?= json_encode($productos) ?>;

// ======================================================
// ELEMENTOS
// ======================================================

const buscarInput = document.getElementById('buscarProducto');
const resultados = document.getElementById('searchResults');
const tabla = document.querySelector('#tablaProductos tbody');

let totalGeneral = 0;

// ======================================================
// BUSCADOR
// ======================================================

buscarInput.addEventListener('keyup', function(){

    const texto = this.value.toLowerCase();

    resultados.innerHTML = '';

    if(texto.length < 1){

        resultados.style.display = 'none';
        return;
    }

    const filtrados = productos.filter(p =>
        p.name.toLowerCase().includes(texto) ||
        p.codigo.toLowerCase().includes(texto)
    );

    if(filtrados.length > 0){

        resultados.style.display = 'block';

        filtrados.forEach(producto => {

            const div = document.createElement('div');

            div.classList.add('search-item');

            div.innerHTML = `
                <strong>${producto.codigo}</strong>
                - ${producto.name}
            `;

            div.onclick = () => agregarProducto(producto);

            resultados.appendChild(div);

        });

    }else{

        resultados.style.display = 'none';
    }

});

// ======================================================
// AGREGAR PRODUCTO
// ======================================================

function agregarProducto(producto){

    resultados.style.display = 'none';

    buscarInput.value = '';

    const fila = document.createElement('tr');

    fila.innerHTML = `

        <td>
            <span class="badge">
                ${producto.codigo}
            </span>
        </td>

        <td>${producto.name}</td>

        <td>${producto.stock}</td>

        <td>
            <input 
                type="number"
                value="1"
                min="1"
                class="cantidad"
            >
        </td>

        <td>
            <input 
                type="number"
                value="${producto.price}"
                step="0.01"
                class="costo"
            >
        </td>

        <td class="subtotal">
            ${parseFloat(producto.price).toFixed(2)}
        </td>

        <td>
            <button class="btn btn-danger eliminar">
                X
            </button>
        </td>

    `;

    tabla.appendChild(fila);

    calcularFila(fila);

    // Eventos
    fila.querySelector('.cantidad')
        .addEventListener('input', () => calcularFila(fila));

    fila.querySelector('.costo')
        .addEventListener('input', () => calcularFila(fila));

    fila.querySelector('.eliminar')
        .addEventListener('click', () => {

            fila.remove();
            calcularTotal();

        });

}

// ======================================================
// CALCULAR FILA
// ======================================================

function calcularFila(fila){

    const cantidad = parseFloat(
        fila.querySelector('.cantidad').value
    ) || 0;

    const costo = parseFloat(
        fila.querySelector('.costo').value
    ) || 0;

    const subtotal = cantidad * costo;

    fila.querySelector('.subtotal').innerText =
        subtotal.toFixed(2);

    calcularTotal();

}

// ======================================================
// CALCULAR TOTAL
// ======================================================

function calcularTotal(){

    totalGeneral = 0;

    document.querySelectorAll('.subtotal')
        .forEach(sub => {

            totalGeneral += parseFloat(sub.innerText) || 0;

        });

    document.getElementById('totalGeneral')
        .innerText = totalGeneral.toFixed(2);

}

// ======================================================
// CERRAR BUSCADOR
// ======================================================

document.addEventListener('click', function(e){

    if(!e.target.closest('.search-container')){

        resultados.style.display = 'none';

    }

});

</script>

</body>
</html>