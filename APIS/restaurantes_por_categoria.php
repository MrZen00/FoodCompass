<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require('../util/conexion.php');

$categoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';

if ($categoria !== '') {
    // 1. Obtener el id del tipo de comida
    $stmt = $_conexion->prepare("SELECT id FROM tipos_comida WHERE nombre = ?");
    $stmt->bind_param("s", $categoria);
    $stmt->execute();
    $stmt->bind_result($tipo_comida_id);
    if ($stmt->fetch()) {
        $stmt->close();
        // 2. Obtener los restaurantes asociados a ese tipo_comida_id
        $orden = isset($_GET['orden']) && $_GET['orden'] === 'valoracion_desc' ? ' ORDER BY r.valoraciones DESC' : '';
$sqlRestaurantes = "SELECT r.* FROM restaurantes r INNER JOIN restaurante_tipo_comida rtc ON r.id = rtc.restaurante_id WHERE rtc.tipo_comida_id = $tipo_comida_id" . $orden;
    } else {
        // No se encontró el tipo de comida
        echo json_encode([]);
        $_conexion->close();
        exit;
    }
} else {
    // Sin filtro, todos los restaurantes
    $sqlRestaurantes = "SELECT * FROM restaurantes";
}

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
