<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require('../util/conexion.php');

$sqlRestaurantes = "SELECT * FROM restaurantes ORDER BY valoraciones DESC";
$resultRestaurantes = $_conexion->query($sqlRestaurantes);

$restaurantes = [];

while ($restaurante = $resultRestaurantes->fetch_assoc()) {
    // Procesar imágenes
    $imagenes = json_decode($restaurante['imagenes'], true) ?: [];
    // Consulta para tipos de comida de ESTE restaurante
    $sqlTipos = "SELECT tc.id, tc.nombre 
                 FROM tipos_comida tc
                 INNER JOIN restaurante_tipo_comida rtc ON tc.id = rtc.tipo_comida_id
                 WHERE rtc.restaurante_id = " . $restaurante['id'];
    $resultTipos = $_conexion->query($sqlTipos);
    $tiposComida = [];
    while ($tipo = $resultTipos->fetch_assoc()) {
        $tiposComida[] = $tipo;
    }
    // Estructura final del restaurante
    $restaurantes[] = [
        'id' => $restaurante['id'],
        'nombre' => $restaurante['nombre'],
        'precio' => $restaurante['precio'],
        'valoraciones' => $restaurante['valoraciones'],
        'ubicacion' => $restaurante['ubicacion'],
        'descripcion' => $restaurante['descripcion'],
        'coordenadas' => [
            'latitud' => $restaurante['latitud'],
            'longitud' => $restaurante['longitud']
        ],
        'imagenes' => $imagenes,
        'tipos_comida' => $tiposComida
    ];
}

echo json_encode($restaurantes, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
$_conexion->close();
