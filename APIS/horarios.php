<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require('../util/conexion.php');

header('Content-Type: application/json');

// Obtener restaurante_id por GET o POST
$restaurante_id = null;
if (isset($_GET['restaurante_id'])) {
    $restaurante_id = intval($_GET['restaurante_id']);
} elseif (isset($_POST['restaurante_id'])) {
    $restaurante_id = intval($_POST['restaurante_id']);
}

if (!$restaurante_id) {
    echo json_encode([]);
    exit;
}

// Consulta SQL solo para ese restaurante
$sql = "SELECT dia_semana, TIME_FORMAT(hora_apertura, '%H:%i') AS hora_apertura, TIME_FORMAT(hora_cierre, '%H:%i') AS hora_cierre FROM horarios_restaurante WHERE restaurante_id = ? ORDER BY FIELD(dia_semana, 'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo')";
$stmt = $_conexion->prepare($sql);
$stmt->bind_param('i', $restaurante_id);
$stmt->execute();
$resultado = $stmt->get_result();
$dias = [];
if ($resultado && $resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        $dia = $fila['dia_semana'];
        if (!isset($dias[$dia])) {
            $dias[$dia] = [
                'dia_semana' => $dia,
                'intervalos' => []
            ];
        }
        $dias[$dia]['intervalos'][] = [
            'hora_apertura' => $fila['hora_apertura'],
            'hora_cierre' => $fila['hora_cierre']
        ];
    }
}
// Reindexar para devolver un array numerico
$horarios = array_values($dias);
echo json_encode($horarios, JSON_UNESCAPED_UNICODE);
$stmt->close();
?>