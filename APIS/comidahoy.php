<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require('../util/conexion.php');

// Obtener el día de la semana en español
setlocale(LC_TIME, 'es_ES.UTF-8');
$dias_es = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$timestamp = time();
$dia_semana = $dias_es[(int)date('w', $timestamp)];

// Consulta para obtener los restaurantes abiertos hoy (ahora)
$hora_actual = date('H:i:s');
$sql = "SELECT r.id FROM restaurantes r
    INNER JOIN horarios_restaurante h ON r.id = h.restaurante_id
    WHERE h.dia_semana = ?
      AND h.hora_apertura <= ?
      AND h.hora_cierre >= ?";
$stmt = $_conexion->prepare($sql);
$stmt->bind_param('sss', $dia_semana, $hora_actual, $hora_actual);
$stmt->execute();
$result = $stmt->get_result();

$ids = [];
while ($row = $result->fetch_assoc()) {
    $ids[] = $row['id'];
}

if (empty($ids)) {
    echo json_encode([]);
    exit;
}

// Ahora consulta la información completa de esos restaurantes
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids));
$sqlRest = "SELECT * FROM restaurantes WHERE id IN ($placeholders)";
$stmtRest = $_conexion->prepare($sqlRest);
$stmtRest->bind_param($types, ...$ids);
$stmtRest->execute();
$resultRest = $stmtRest->get_result();

$restaurantes = [];
while ($restaurante = $resultRest->fetch_assoc()) {
    // Procesar imágenes
    $imagenes = json_decode($restaurante['imagenes'], true) ?: [];
    // Consulta para tipos de comida de ESTE restaurante
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
    $restaurante['imagenes'] = $imagenes;
    $restaurante['tipos_comida'] = $tiposComida;
    // Añadir coordenadas igual que en restaurante.php
    // Si ya existen en $restaurante, úsalas; si no, consulta
    if (!isset($restaurante['latitud']) || !isset($restaurante['longitud'])) {
        $sqlCoord = "SELECT latitud, longitud FROM restaurantes WHERE id = ? LIMIT 1";
        $stmtCoord = $_conexion->prepare($sqlCoord);
        $stmtCoord->bind_param('i', $restaurante['id']);
        $stmtCoord->execute();
        $resultCoord = $stmtCoord->get_result();
        $coord = $resultCoord->fetch_assoc();
        $restaurante['latitud'] = $coord['latitud'];
        $restaurante['longitud'] = $coord['longitud'];
    }
    $restaurante['coordenadas'] = [
        'latitud' => $restaurante['latitud'],
        'longitud' => $restaurante['longitud']
    ];
    $restaurantes[] = $restaurante;
}

echo json_encode($restaurantes);
