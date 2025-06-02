<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../util/conexion.php';

// Comprobar que el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    // No está logueado, redirigir a login o perfil
    header('Location: ../perfil.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Recoger datos del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $restaurante_id = isset($_POST['restaurante_id']) ? intval($_POST['restaurante_id']) : 0;
    $valoracion = isset($_POST['valoracion']) ? intval($_POST['valoracion']) : 0;
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';

    // Validar datos básicos
    if ($restaurante_id > 0 && $valoracion > 0) {
        // Multiplicar valoracion x2
        $valoracion_final = $valoracion * 2;

        // Preparar consulta para actualizar valoracion y descripcion en historial_visitas
        $sql = "UPDATE historial_visitas SET valoracion = ?, descripcion = ? WHERE usuario_id = ? AND restaurante_id = ?";

        if ($stmt = $_conexion->prepare($sql)) {
            $stmt->bind_param("isii", $valoracion_final, $descripcion, $usuario_id, $restaurante_id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $_SESSION['mensaje'] = "Valoración enviada correctamente.";
            } else {
                $_SESSION['mensaje'] = "No se pudo actualizar la valoración o ya estaba valorado.";
            }

            $stmt->close();
        } else {
            $_SESSION['mensaje'] = "Error en la consulta a la base de datos.";
        }
    } else {
        $_SESSION['mensaje'] = "Datos inválidos en la valoración.";
    }
} else {
    $_SESSION['mensaje'] = "Método no permitido.";
}

// Redirigir al perfil o donde quieras
header('Location: ../usuario/perfilUsuario.php');
exit;
?>
