<?php

function siguiente_codigo_producto(PDO $pdo, int $idCategoria): string
{
    $prefijo = 'CAT' . $idCategoria . '-';
    $stmt = $pdo->prepare('SELECT codigo FROM productos WHERE category_id = ?');
    $stmt->execute([$idCategoria]);

    $ultimoNumero = 0;
    $patron = '/^(?:PROD-|'
        . preg_quote($prefijo, '/')
        . ')(\d+)$/i';
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $codigo) {
        if (preg_match($patron, (string) $codigo, $coincidencia)) {
            $ultimoNumero = max($ultimoNumero, (int) $coincidencia[1]);
        }
    }

    $stmtCodigoExistente = $pdo->prepare('SELECT 1 FROM productos WHERE codigo = ? LIMIT 1');
    do {
        $ultimoNumero++;
        $codigo = $prefijo . str_pad((string) $ultimoNumero, 4, '0', STR_PAD_LEFT);
        $stmtCodigoExistente->execute([$codigo]);
    } while ($stmtCodigoExistente->fetchColumn());

    return $codigo;
}
