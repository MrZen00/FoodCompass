<?php
session_start();
require_once '../util/conexion.php';
header('Content-Type: application/json');

$response = ['success' => false];
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode($response);
    exit;
}
$usuario_id = $_SESSION['usuario_id'];

try {
    $sql = "SELECT r.id, r.nombre
        FROM restaurantes r
        JOIN restaurante_tipo_comida rtc ON r.id = rtc.restaurante_id
        JOIN preferencias_usuario p ON rtc.tipo_comida_id = p.tipo_comida_id
        WHERE p.usuario_id = ?
        ORDER BY r.valoraciones DESC, r.id ASC LIMIT 1";
    $stmt = $_conexion->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error en prepare: ' . $_conexion->error);
    }
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $stmt->bind_result($id, $nombre);
    if ($stmt->fetch()) {
        $response = [
            'success' => true,
            'id' => $id,
            'nombre' => $nombre
        ];
    } else {
        $response['error'] = 'No recomendado encontrado';
    }
    $stmt->close();
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}
echo json_encode($response);
