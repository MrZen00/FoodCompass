<?php
session_start();
header('Content-Type: application/json');

try {
    // Verifica si hay sesión activa
    if (!isset($_SESSION['usuario_id'])) {
        // Devuelve un indicador para redirigir al login
        echo json_encode([
            'success' => false,
            'redirect_to_login' => './usuario/iniciosesion.php?redirect=' . urlencode($_SERVER['HTTP_REFERER'])
        ]);
        exit;
    }

    // Configuración de la base de datos
    $host = 'localhost:3306';
    $usuario = 'test';
    $password = 'Sandro.1?';
    $base_datos = 'foodcomp_';

    // Crear conexión
    $conexion = new mysqli($host, $usuario, $password, $base_datos);

    // Verificar conexión
    if ($conexion->connect_error) {
        throw new Exception('Error de conexión: ' . $conexion->connect_error);
    }

    // Establecer el conjunto de caracteres a UTF-8
    $conexion->set_charset("utf8");

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['restaurante_id'])) {
        throw new Exception('Falta restaurante_id');
    }

    $usuario_id = intval($_SESSION['usuario_id']);
    $restaurante_id = intval($data['restaurante_id']);

    // Insertar la visita con fecha actual
    $stmt = $conexion->prepare("INSERT INTO historial_visitas (usuario_id, restaurante_id, fecha_visita) VALUES (?, ?, NOW())");
    
    if (!$stmt) {
        throw new Exception('Error al preparar la consulta: ' . $conexion->error);
    }

    $stmt->bind_param("ii", $usuario_id, $restaurante_id);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Visita registrada correctamente'
        ]);
    } else {
        throw new Exception('Error al ejecutar la consulta: ' . $stmt->error);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}

$stmt->close();
$conexion->close();
?>