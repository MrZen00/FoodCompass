<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña</title>
    <!-- ICONOS -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet" />
    <!-- ESTILOS -->
    <link rel="stylesheet" href="../css/styles.css" />
    <!-- JS -->
    <script src="../js/validarCambioContrasena.js"></script>
    <?php
        error_reporting(E_ALL);
        ini_set("display_errors", 1); 
        session_start();
        require('../util/conexion.php');
        
        // Verificar si el usuario está logueado
        if (!isset($_SESSION['usuario'])) {
            header('Location: iniciosesion.php');
            exit;
        }

        // Variables para mensajes de error/éxito
        $error = '';
        $success = '';
        
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Obtener datos del formulario
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // Validaciones básicas
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $error = "Todos los campos son obligatorios";
            } elseif ($new_password !== $confirm_password) {
                $error = "Las nuevas contraseñas no coinciden";
            } elseif (strlen($new_password) < 4 || strlen($new_password) > 10) {
                $error = "La contraseña debe tener entre 4 y 10 caracteres";
            } else {
                try {
                    // Obtener usuario actual
                    $usuario = $_SESSION['usuario'];
                    $stmt = $_conexion->prepare("SELECT id, contrasena FROM usuarios WHERE usuario = ?");
                    $stmt->bind_param("s", $usuario);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows === 0) {
                        $error = "Usuario no encontrado";
                    } else {
                        $user = $result->fetch_assoc();
                        
                        // Verificar contraseña actual
                        if (!password_verify($current_password, $user['contrasena'])) {
                            $error = "La contraseña actual es incorrecta";
                        } else {
                            // Actualizar contraseña
                            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                            $update_stmt = $_conexion->prepare("UPDATE usuarios SET contrasena = ? WHERE id = ?");
                            $update_stmt->bind_param("si", $new_password_hash, $user['id']);
                            
                            if ($update_stmt->execute()) {
                                $_SESSION['show_toast'] = true;
                                $_SESSION['toast_message'] = "¡Contraseña cambiada con éxito!";
                                header("Location: ../index.php");
                                exit();
                            } else {
                                $error = "Error al actualizar la contraseña";
                            }
                        }
                    }
                } catch (Exception $e) {
                    $error = "Error en el proceso: " . $e->getMessage();
                }
            }
        }
    ?>
</head>
<body>

<section class="register">
    <img src="../img/hola.avif" alt="Cambiar contraseña" class="register--image" />

    <form action="" method="post" class="register--form">
      <h1 class="register--title">Cambiar contraseña</h1>

      <div class="register--content">
        <!-- Contraseña Actual -->
        <div class="register-box">
          <i class="ri-lock-2-line register-icon"></i>
          <div class="register--input-box">
            <input type="password" name="current_password" class="register-input" placeholder=" " required />
            <label class="register--input-label">Contraseña actual</label>
          </div>
        </div>

        <!-- Nueva Contraseña -->
        <div class="register-box">
          <i class="ri-lock-password-line register-icon"></i>
          <div class="register--input-box">
            <input type="password" name="new_password" class="register-input" id="new-password" placeholder=" " required />
            <label class="register--input-label">Nueva contraseña</label>
            <i class="ri-eye-off-line register--eye" id="register--eye"></i>
          </div>
        </div>

        <!-- Confirmar Nueva Contraseña -->
        <div class="register-box">
          <i class="ri-lock-password-line register-icon"></i>
          <div class="register--input-box">
            <input type="password" name="confirm_password" class="register-input" placeholder=" " required />
            <label class="register--input-label">Confirmar nueva contraseña</label>
          </div>
        </div>

        <?php if(!empty($error)): ?>
            <div class="error-text" style="margin-bottom: 20px;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <button type="submit" class="register--button">Cambiar contraseña</button>

        <p class="register-login">
          <a href="../perfilUsuario.html">Volver al perfil</a>
        </p>
      </div>
    </form>
  </section>

  <script>
    // Mostrar/ocultar contraseña
    const passwordInput = document.getElementById('new-password');
    const eyeIcon = document.getElementById('register--eye');
    
    eyeIcon.addEventListener('click', () => {
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.classList.remove('ri-eye-off-line');
        eyeIcon.classList.add('ri-eye-line');
      } else {
        passwordInput.type = 'password';
        eyeIcon.classList.remove('ri-eye-line');
        eyeIcon.classList.add('ri-eye-off-line');
      }
    });
  </script>
</body>
</html>