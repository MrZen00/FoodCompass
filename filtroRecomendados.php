<?php
session_start();
include_once 'util/conexion.php';

// Solo muestra el filtro si el usuario tiene la sesión iniciada
if (isset($_SESSION['usuario'])) {
    $usuario_id = $_SESSION['usuario']; // Asegúrate de que este valor corresponda al id del usuario

    // Consulta para obtener el restaurante con mayor valoración según la preferencia del usuario
    $sql = "SELECT r.*
            FROM preferencias_usuario pu
            JOIN restaurante_tipo_comida rtc ON pu.tipo_comida_id = rtc.tipo_comida_id
            JOIN restaurantes r ON rtc.restaurante_id = r.id
            WHERE pu.usuario_id = ?
            ORDER BY r.valoraciones DESC
            LIMIT 1";

    if ($stmt = $_conexion->prepare($sql)) {
        $stmt->bind_param('i', $usuario_id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        if ($restaurante = $resultado->fetch_assoc()) {
            // Muestra el filtro y el restaurante recomendado
            echo '<div class="filter-tag" id="filtro-recomendados" data-id="' . htmlspecialchars($restaurante['id']) . '"><i class="fas fa-thumbs-up"></i> <span>Recomendado: ' . htmlspecialchars($restaurante['nombre']) . ' (Valoración: ' . htmlspecialchars($restaurante['valoraciones']) . ')</span></div>';
        } else {
            // Si no hay restaurante recomendado
            echo '<div class="filter-tag" id="filtro-recomendados"><i class="fas fa-thumbs-up"></i> <span>Sin recomendación disponible</span></div>';
        }
        $stmt->close();
    } else {
        echo '<div class="filter-tag" id="filtro-recomendados"><i class="fas fa-thumbs-up"></i> <span>Error en la consulta</span></div>';
    }
} else {
    // No muestra nada si no hay sesión
    echo '';
}
?>
