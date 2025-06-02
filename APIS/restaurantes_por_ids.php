<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require('../util/conexion.php');

// Recibe un array de ids por POST (JSON)
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$ids = isset($data['ids']) ? $data['ids'] : [];

if (!is_array($ids) || empty($ids)) {
    echo json_encode(['error' => 'No se recibieron IDs válidos']);
    exit;
}

// Prepara la consulta segura
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$sql = "SELECT * FROM restaurantes WHERE id IN ($placeholders)";
$stmt = $_conexion->prepare($sql);
$stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
$stmt->execute();
$result = $stmt->get_result();

$restaurantes = [];
while ($restaurante = $result->fetch_assoc()) {
    $imagenes = json_decode($restaurante['imagenes'], true) ?: [];
    $sqlTipos = "SELECT tc.id, tc.nombre 
                 FROM tipos_comida tc
                 INNER JOIN restaurante_tipo_comida rtc ON tc.id = rtc.tipo_comida_id
                 WHERE rtc.restaurante_id = ?";
    $stmtTipos = $_conexion->prepare($sqlTipos);
    $stmtTipos->bind_param('i', $restaurante['id']);
    $stmtTipos->execute();
    $resultTipos = $stmtTipos->get_result();
    $tiposComida = [];
    while ($tipo = $resultTipos->fetch_assoc()) {
        $tiposComida[] = $tipo;
    }
    $stmtTipos->close();
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
$stmt->close();

// Respuesta JSON
echo json_encode($restaurantes, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
$_conexion->close();
