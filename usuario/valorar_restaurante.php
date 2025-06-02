<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}
$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['id_restaurante'], $input['resena'], $input['valoracion'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}
$id_usuario = $_SESSION['usuario_id'];
$id_restaurante = intval($input['id_restaurante']);
$resena = trim($input['resena']);
$valoracion = floatval($input['valoracion']);
if ($valoracion < 0) $valoracion = 0;
if ($valoracion > 10) $valoracion = 10;
require_once('../util/conexion.php');
$stmt = $_conexion->prepare('UPDATE historial_visitas SET descripcion=?, valoracion=? WHERE usuario_id=? AND restaurante_id=?');
$stmt->bind_param('sdii', $resena, $valoracion, $id_usuario, $id_restaurante);
$ok = $stmt->execute();
$stmt->close();
if ($ok) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar la valoración']);
}
