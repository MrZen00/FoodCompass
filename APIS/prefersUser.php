<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require('../util/conexion.php');

header('Content-Type: application/json');

// Consulta SQL
$sql = "SELECT  
    usuarios.id,
    usuarios.usuario,
    tipos_comida.nombre
FROM 
    usuarios,
    preferencias_usuario,
    tipos_comida
WHERE 
    usuarios.id = preferencias_usuario.usuario_id
    AND preferencias_usuario.tipo_comida_id = tipos_comida.id
    ORDER BY usuarios.id ASC";

// Ejecutar la consulta
$resultado = $_conexion->query($sql);

// Procesar resultados
if ($resultado && $resultado->num_rows > 0) {
    $datos = [];
    while ($fila = $resultado->fetch_assoc()) {
        $datos[] = $fila;
    }
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([]);
}
?>