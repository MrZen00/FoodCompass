<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require('../util/conexion.php');

header('Content-Type: application/json');

// Obtener usuario_id de la sesión
if (!isset($_SESSION['usuario_id'])) {
    // Usuario no logueado, devolver array vacío o error
    echo json_encode([]);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Consulta SQL filtrada por usuario_id
$sql = "SELECT 
    u.id AS usuario_id,
    u.usuario,
    r.id AS restaurante_id,
    r.nombre AS restaurante,
    h.valoracion,
    h.descripcion
FROM 
    historial_visitas h
JOIN usuarios u ON h.usuario_id = u.id
JOIN restaurantes r ON h.restaurante_id = r.id
WHERE h.usuario_id = ?
ORDER BY h.fecha_visita DESC";

// Preparar y ejecutar la consulta segura
$stmt = $_conexion->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado && $resultado->num_rows > 0) {
    $datos = [];
    while ($fila = $resultado->fetch_assoc()) {
        $datos[] = $fila;
    }
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([]);
}

$stmt->close();
?>
