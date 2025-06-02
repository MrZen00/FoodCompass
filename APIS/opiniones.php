<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require('../util/conexion.php');

header('Content-Type: application/json');

// Obtener restaurante_id por GET o POST
$restaurante_id = null;
if (isset($_GET['restaurante_id'])) {
    $restaurante_id = intval($_GET['restaurante_id']);
} elseif (isset($_POST['restaurante_id'])) {
    $restaurante_id = intval($_POST['restaurante_id']);
}

header('Content-Type: application/json');

if (!$restaurante_id) {
    echo json_encode([]);
    exit;
}

// Consulta SQL para obtener solo las descripciones de las reseñas de un restaurante concreto
$sql = "SELECT h.descripcion FROM historial_visitas h WHERE h.restaurante_id = ? AND h.descripcion IS NOT NULL AND h.descripcion <> '' ORDER BY h.fecha_visita DESC";
$stmt = $_conexion->prepare($sql);
$stmt->bind_param('i', $restaurante_id);
$stmt->execute();
$resultado = $stmt->get_result();

$datos = [];
if ($resultado && $resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        $datos[] = $fila['descripcion'];
    }
}
echo json_encode($datos, JSON_UNESCAPED_UNICODE);
$stmt->close();
?>