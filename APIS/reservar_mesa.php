<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}
$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['restaurante_id'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}
$id_usuario = $_SESSION['usuario_id'];
$id_restaurante = intval($input['restaurante_id']);
require_once('../util/conexion.php');
// Comprobar si ya existe la visita
$stmt = $_conexion->prepare('SELECT 1 FROM historial_visitas WHERE usuario_id=? AND restaurante_id=?');
$stmt->bind_param('ii', $id_usuario, $id_restaurante);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Ya existe la visita']);
    exit();
}
$stmt->close();
// Insertar nueva visita
$fecha = date('Y-m-d H:i:s');
$stmt = $_conexion->prepare('INSERT INTO historial_visitas (usuario_id, restaurante_id, fecha_visita) VALUES (?, ?, ?)');
$stmt->bind_param('iis', $id_usuario, $id_restaurante, $fecha);
$ok = $stmt->execute();
$stmt->close();
if ($ok) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al registrar la visita']);
}
