<?php
error_reporting(E_ALL);
ini_set("display_errors", 1); 
require('../util/conexion.php');

$secret_key = "6Leg8j8rAAAAAFq_Ni7FnUgCEyNOZmw-0RZVTP03";

// Inicializar variables
$usuario_value = "";
$contrasena_value = "";
$err_usuario = "";
$err_contrasena = "";
$err_captcha = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario_value = htmlspecialchars($_POST["usuario"] ?? "");
    $contrasena_value = htmlspecialchars($_POST["contrasena"] ?? "");
    $recordar = isset($_POST['remember']);

    if (isset($_POST['g-recaptcha-response'])) {
        $captcha_response = $_POST['g-recaptcha-response'];
        $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret_key}&response={$captcha_response}");
        $response_data = json_decode($response);

        if (!$response_data->success) {
            $err_captcha = "Por favor, completa el CAPTCHA.";
        } else {
            // CAPTCHA válido, procesar login
            $usuario = $_POST["usuario"];
            $contrasena = $_POST["contrasena"];

            $stmt = $_conexion->prepare("SELECT id, usuario, contrasena FROM admin WHERE usuario = ?");
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($resultado->num_rows === 0) {
                $err_usuario = "Usuario no encontrado o sin permisos.";
            } else {
                $info_admin = $resultado->fetch_assoc();

                // Comparación directa sin hash
                if ($contrasena === $info_admin['contrasena']) {
                    session_start();
                    $_SESSION['admin_id'] = $info_admin['id'];
                    $_SESSION['admin_usuario'] = $info_admin['usuario'];

                    header("Location: editarRestaurantes.php");
                    exit;
                } else {
                    $err_contrasena = "Contraseña incorrecta.";
                }
            }

            $stmt->close();
        }
    } else {
        $err_captcha = "Por favor, completa el CAPTCHA.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Acceso Administrador</title>

  <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet"/>
  <link rel="stylesheet" href="../css/stylesLog.css"/>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>

<section class="login">
  <img src="../img/hola.avif" alt="Login-image" class="login--image" />

  <form action="" method="post" class="login--form">
    <h1 class="login--title">Acceso Administrador</h1>

    <div class="login--content">
      <div class="login-box">
        <i class="ri-user-3-line login-icon"></i>
        <div class="login--input-box">
          <input type="text" name="usuario" class="login-input" placeholder=" " 
                 value="<?php echo $usuario_value; ?>" required />
          <label class="login--input-label">Usuario Admin</label>
        </div>
      </div>
      <?php if (!empty($err_usuario)) echo "<span class='error-text php-error'>$err_usuario</span>"; ?>

      <div class="login-box">
        <i class="ri-lock-2-line login-icon"></i>
        <div class="login--input-box">
          <input type="password"  name="contrasena" class="login-input" placeholder=" " 
                 value="<?php echo $contrasena_value; ?>" required />
          <label class="login--input-label">Contraseña</label>
          <i class="ri-eye-off-line login--eye" id="login--eye"></i>
        </div>
      </div>
      <?php if (!empty($err_contrasena)) echo "<span class='error-text php-error'>$err_contrasena</span>"; ?>
    </div>

    <div class="captcha-container">
      <div class="g-recaptcha" data-sitekey="6Leg8j8rAAAAAJ7vFv4p--JtvHd7J-Rw-FhBXEg3"></div>
      <?php if (!empty($err_captcha)) echo "<span class='error-text php-error'>$err_captcha</span>"; ?>
    </div>

    <button type="submit" class="login--button">Acceder al Panel</button>
  </form>
</section>

</body>
</html>
