<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesion</title>
    <!-- ICONOS -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet" />

    <!-- ESTILOS -->
    <link rel="stylesheet" href="../css/stylesLog.css"/>

    <!-- JS -->
    <script src="../js/validarLogin.js"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php
error_reporting(E_ALL);
ini_set("display_errors", 1); 
require('../util/conexion.php');

// Clave secreta de reCAPTCHA
$secret_key = "6Leg8j8rAAAAAFq_Ni7FnUgCEyNOZmw-0RZVTP03";

// Inicializar variables para mantener los valores del formulario
$usuario_value = "";
$contrasena_value = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["usuario"])) {
        $usuario_value = htmlspecialchars($_POST["usuario"]);
    }

    if (isset($_POST["contrasena"])) {
        $contrasena_value = htmlspecialchars($_POST["contrasena"]);
    }

    // Verificar si se marcó "Recordar contraseña"
    $recordar = false;
    if (isset($_POST['remember'])) {
        $recordar = true;
    }
        
    // Validar CAPTCHA primero
    if(isset($_POST['g-recaptcha-response'])) {
        $captcha_response = $_POST['g-recaptcha-response'];
        
        // Verificar con Google
        $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret_key}&response={$captcha_response}");
        $response_data = json_decode($response);
        
        if(!$response_data->success) {
            $err_captcha = "Por favor, completa el CAPTCHA.";
        } else {
            // CAPTCHA válido, procesamos login
            $usuario = $_POST["usuario"];
            $contrasena = $_POST["contrasena"];

            // Evitamos SQL injection
            $stmt = $_conexion->prepare("SELECT id, usuario, contrasena FROM usuarios WHERE usuario = ?");
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($resultado->num_rows == 0) {
                $err_usuario = "El usuario no existe";
            } else {
                $info_usuario = $resultado->fetch_assoc();
                $acceso_concedido = password_verify($contrasena, $info_usuario["contrasena"]);
                if($acceso_concedido) {
                    session_start();
                    $_SESSION["usuario_id"] = $info_usuario["id"];
                    $_SESSION["usuario"] = $usuario;
                    $_SESSION['show_toast'] = true;
                    $_SESSION['toast_message'] = "¡Bienvenido, $usuario!";

                    // Implementación para "Recordar contraseña" (PRODUCCIÓN)
                    if ($recordar) {
                        // Generar token seguro
                        $token = bin2hex(random_bytes(32));
                        $expiracion = time() + (30 * 24 * 60 * 60); // 30 días
                        
                        // Actualizar token en la base de datos
                        $sql_update = "UPDATE usuarios SET token_remember = ?, token_expira = ? WHERE id = ?";
                        $stmt_update = $_conexion->prepare($sql_update);
                        $stmt_update->bind_param("sii", $token, $expiracion, $info_usuario['id']);
                        $stmt_update->execute();
                        
                        // Configurar cookie para PRODUCCIÓN (foodcompass.es)
                        setcookie(
                            'remember_token',
                            $token,
                            [
                                'expires' => $expiracion,
                                'path' => '/',
                                'domain' => 'foodcompass.es', // Dominio real
                                'secure' => true,             // Solo HTTPS
                                'httponly' => true,           // No accesible por JS
                                'samesite' => 'Strict'        // Mayor seguridad
                            ]
                        );
                    }

                    header("Location: ../index.php");
                    exit;
                } else {
                    $err_contrasena = "La contraseña es incorrecta";
                }
            }
        }
    } else {
        $err_captcha = "Por favor, completa el CAPTCHA.";
    }
}
?>
</head>
<body>

<section class="login">
    <img src="../img/hola.avif" alt="Login-image" class="login--image" />

    <form action="" method="post" class="login--form">
      <h1 class="login--title">Iniciar sesión</h1>

      <div class="login--content">
        <div class="login-box">
          <i class="ri-user-3-line login-icon"></i>

          <div class="login--input-box">
            <input type="text" name="usuario" class="login-input" id="login-input-text" placeholder=" " 
                   value="<?php echo $usuario_value; ?>" required />
            <label for="" class="login--input-label">Usuario</label>
          </div>
        </div>
        <?php if(isset($err_usuario)) echo "<span class='error-text php-error'>$err_usuario</span>" ?>

        <div class="login-box">
          <i class="ri-lock-2-line login-icon"></i>

          <div class="login--input-box">
            <input type="password" name="contrasena" class="login-input" id="login-input-pass" placeholder=" " 
                   value="<?php echo $contrasena_value; ?>" required />
            <label for="" class="login--input-label">Contraseña</label>
            <i class="ri-eye-off-line login--eye" id="login--eye"></i>
          </div>
        </div>
      </div>
      <?php if(isset($err_contrasena)) echo "<span class='error-text php-error'>$err_contrasena</span>" ?>

      <div class="captcha-container">
        <div class="g-recaptcha" data-sitekey="6Leg8j8rAAAAAJ7vFv4p--JtvHd7J-Rw-FhBXEg3"></div>
        <?php if(isset($err_captcha)) echo "<span class='error-text php-error'>$err_captcha</span>" ?>
      </div>

      <div class="remember-me">
        <input type="checkbox" id="remember" name="remember" class="remember-checkbox">
        <label for="remember">Recordar contraseña</label>
      </div>


      <button type="submit" class="login--button">Iniciar Sesión</button>

      <p class="login-register">
        ¿No tienes una cuenta? &nbsp;<a href="registro.php" class="register-link">Regístrate</a>
      </p>
    </form>
  </section>

</body>
</html>