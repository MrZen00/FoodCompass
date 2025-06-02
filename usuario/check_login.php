<?php
session_start();
require(__DIR__ . '/../util/conexion.php');

// Verificar si hay una sesión activa
if (!isset($_SESSION['usuario'])) {
    // Verificar si existe cookie de recordar
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        
        // Buscar usuario con este token
        $sql = "SELECT id, usuario, token_expira FROM usuarios WHERE token_remember = ?";
        $stmt = $_conexion->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows > 0) {
            $fila = $resultado->fetch_assoc();
            
            // Verificar si el token no ha expirado
            if ($fila['token_expira'] > time()) {
                // Iniciar sesión automáticamente
                $_SESSION['usuario_id'] = $fila['id'];
                $_SESSION['usuario'] = $fila['usuario'];
                
                // Opcional: Regenerar el token para mayor seguridad
                $nuevo_token = bin2hex(random_bytes(32));
                $nueva_expiracion = time() + (30 * 24 * 60 * 60);
                
                $sql_update = "UPDATE usuarios SET token_remember = ?, token_expira = ? WHERE id = ?";
                $stmt_update = $_conexion->prepare($sql_update);
                $stmt_update->bind_param("sii", $nuevo_token, $nueva_expiracion, $fila['id']);
                $stmt_update->execute();
                
                // Actualizar la cookie (CONFIGURACIÓN PARA PRODUCCIÓN)
                setcookie(
                    'remember_token',
                    $nuevo_token,
                    [
                        'expires' => $nueva_expiracion,
                        'path' => '/',
                        'domain' => 'foodcompass.es',  // Dominio real
                        'secure' => true,              // Solo HTTPS
                        'httponly' => true,            // No accesible por JS
                        'samesite' => 'Strict'         // Mayor seguridad
                    ]
                );
            } else {
                // Token expirado, borrar cookie (CONFIGURACIÓN PARA PRODUCCIÓN)
                setcookie(
                    'remember_token',
                    '',
                    [
                        'expires' => time() - 3600,
                        'path' => '/',
                        'domain' => 'foodcompass.es',
                        'secure' => true,
                        'httponly' => true
                    ]
                );
            }
        }
    }
}
?>