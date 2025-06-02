<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <!-- ICONOS -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet" />

    <!-- ESTILOS -->
    <link rel="stylesheet" href="../css/stylesReg.css" />

    <!-- JS -->
    <script src="../js/validarRegistro.js"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php
error_reporting(E_ALL);
ini_set("display_errors", 1); 
require('../util/conexion.php');

// Clave secreta de reCAPTCHA
$secret_key = "6Leg8j8rAAAAAFq_Ni7FnUgCEyNOZmw-0RZVTP03";

// Variables para mantener valores
$usuario = "";
$correo_value = "";
$contrasena_value = "";
$confirmar_value = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = "";
    $correo_value = "";
    $contrasena_value = "";
    $confirmar_value = "";
    $preferencias = [];

    if (isset($_POST["usuario"])) {
        $usuario = htmlspecialchars($_POST["usuario"]);
    }

    if (isset($_POST["correo"])) {
        $correo_value = htmlspecialchars($_POST["correo"]);
    }

    if (isset($_POST["contrasena"])) {
        $contrasena_value = htmlspecialchars($_POST["contrasena"]);
    }

    if (isset($_POST["confirm_password"])) {
      $contrasena_value = htmlspecialchars($_POST["confirm_password"]);
  }

    if (isset($_POST["preferencias"])) {
        foreach ($_POST["preferencias"] as $preferencia) {
            $preferencias[] = htmlspecialchars($preferencia);
        }
    }

    if (isset($_POST['g-recaptcha-response'])) {
        $captcha_response = $_POST['g-recaptcha-response'];
        $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret_key}&response={$captcha_response}");
        $response_data = json_decode($response);

        if(!$response_data->success) {
            $err_captcha = "Por favor, completa el CAPTCHA.";
        } else {
            if ($usuario == "") {
                $err_usuario = "El usuario es obligatorio";
            } elseif (strlen($usuario) < 4 || strlen($usuario) > 10) {
                $err_usuario = "El usuario debe tener entre 4 y 10 caracteres";
            } else {
                $sql = "SELECT usuario FROM usuarios WHERE usuario = ?";
                $stmt = $_conexion->prepare($sql);
                $stmt->bind_param("s", $usuario);
                $stmt->execute();
                $resultado = $stmt->get_result();
                if ($resultado->num_rows > 0) {
                    $err_usuario = "El usuario ya existe";
                }
                $stmt->close();
            }

            if ($contrasena_value == "") {
                $err_contrasena = "La contraseña es obligatoria";
            } elseif (strlen($contrasena_value) < 4 || strlen($contrasena_value) > 10) {
                $err_contrasena = "La contraseña debe tener entre 4 y 10 caracteres";
            } else {
              $patron = "/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[a-zA-Z]).{4,}$/";
              if(!preg_match($patron, $contrasena_value)){
                $err_contrasena = "La contraseña debe tener al menos una minúscula, una mayúscula y un número";
              }
            }

            if ($confirmar_value == ""){
                $err_confirmar = "Campo obligatorio";
            } elseif ($confirmar_value !== $contrasena_value) {
              $err_confirmar = "Las contraseñas deben coincidir";
            }

            if ($correo_value == "") {
                $err_correo = "El correo electrónico es obligatorio";
            } elseif (!filter_var($correo_value, FILTER_VALIDATE_EMAIL)) {
                $err_correo = "El correo electrónico no es válido";
            } else {
                $sql = "SELECT usuario FROM usuarios WHERE correo = ?";
                $stmt = $_conexion->prepare($sql);
                $stmt->bind_param("s", $correo_value);
                $stmt->execute();
                $resultado = $stmt->get_result();
                if ($resultado->num_rows > 0) {
                    $err_correo = "El correo electrónico ya está registrado";
                }
                $stmt->close();
            }

            if (!isset($_POST["terminos"])) {
                $err_terminos = "Debes aceptar los términos y condiciones";
            }

            if (!isset($err_usuario) && !isset($err_contrasena) && !isset($err_correo) && !isset($err_terminos)) {
                $contrasena_cifrada = password_hash($contrasena_value, PASSWORD_DEFAULT);
                $sql = "INSERT INTO usuarios (usuario, contrasena, correo) VALUES (?, ?, ?)";
                $stmt = $_conexion->prepare($sql);
                $stmt->bind_param("sss", $usuario, $contrasena_cifrada, $correo_value);
                $stmt->execute();
                $usuario_id = $stmt->insert_id;
                $stmt->close();

                if (!empty($preferencias)) {
                    $sql = "INSERT INTO preferencias_usuario (usuario_id, tipo_comida_id) VALUES (?, ?)";
                    $stmt = $_conexion->prepare($sql);
                    foreach ($preferencias as $tipo_comida_id) {
                        $tipo_comida_id = intval($tipo_comida_id);
                        $stmt->bind_param("ii", $usuario_id, $tipo_comida_id);
                        $stmt->execute();
                    }
                    $stmt->close();
                } else {
                    $default = 1;
                    $sql = "INSERT INTO preferencias_usuario (usuario_id, tipo_comida_id) VALUES (?, ?)";
                    $stmt = $_conexion->prepare($sql);
                    $stmt->bind_param("ii", $usuario_id, $default);
                    $stmt->execute();
                    $stmt->close();
                }

                session_start();
                $_SESSION['usuario_id'] = $usuario_id;
                $_SESSION['usuario'] = $usuario;
                $_SESSION['show_toast'] = true;
                $_SESSION['toast_message'] = "¡Registro completado con éxito!";
                header("Location: ../index.php");
                exit();
            }
        }
    } else {
        $err_captcha = "Por favor, completa el CAPTCHA.";
    }
}
?>
</head>
<body>

<section class="register">
    <img src="../img/hola.avif" alt="Register-image" class="register--image" />

    <form action="" method="post" class="register--form">
      <h1 class="register--title">Crear cuenta</h1>

      <div class="register--content">
        <!-- Usuario -->
        <div class="register-box">
          <i class="ri-user-3-line register-icon"></i>
          <div class="register--input-box">
            <input type="text" name="usuario" class="register-input" placeholder=" " 
                   value="<?php echo htmlspecialchars($usuario); ?>" />
            <label class="register--input-label">Usuario</label>
          </div>
        </div>
        <?php if(isset($err_usuario)) echo "<span class='error-text'>$err_usuario</span>" ?>

        <!-- Correo -->
        <div class="register-box">
          <i class="ri-mail-line register-icon"></i>
          <div class="register--input-box">
            <input type="email" name="correo" class="register-input" placeholder=" " 
                   value="<?php echo htmlspecialchars($correo_value); ?>" />
            <label class="register--input-label">Correo electrónico</label>
          </div>
        </div>
        <?php if(isset($err_correo)) echo "<span class='error-text'>$err_correo</span>" ?>

        <!-- Contraseña -->
        <div class="register-box">
          <i class="ri-lock-2-line register-icon"></i>
          <div class="register--input-box">
            <input type="password" name="contrasena" class="register-input" id="register-input-pass" placeholder=" " 
                   value="<?php echo htmlspecialchars($contrasena_value); ?>"  />
            <label class="register--input-label">Contraseña</label>
            <i class="ri-eye-off-line register--eye" id="register--eye"></i>
          </div>
        </div>
        <?php if(isset($err_contrasena)) echo "<span class='error-text'>$err_contrasena</span>" ?>

        <!-- Confirmar Nueva Contraseña -->
            <div class="register-box">
          <i class="ri-lock-password-line register-icon"></i>
          <div class="register--input-box">
            <input type="password" name="confirm_password" class="register-input" placeholder=" "
            value="<?php echo htmlspecialchars($confirmar_value); ?>"  />
            <label class="register--input-label">Confirmar contraseña</label>
          </div>
        </div>
        <?php if(isset($err_confirmar)) echo "<span class='error-text'>$err_confirmar</span>" ?>
        
        <!-- Preferencias (manteniendo tu código original) -->
        <div class="register-box">
          <i class="ri-restaurant-line register-icon"></i>
          <div class="register--input-box">
            <label class="register--input-label" for="preferencias">¿Qué tipo de comida te gusta?</label>
            <br><br>
            <select name="preferencias[]" id="preferencias" multiple class="register--select">
              <option value="1">Andaluz</option>
              <option value="2">Argentino</option>
              <option value="3">Hamburguesas</option>
              <option value="4">Internacional</option>
              <option value="5">Italiano</option>
              <option value="6">Japonés</option>
              <option value="7">Mediterráneo</option>
              <option value="8">Mexicano</option>
              <option value="9">Peruano</option>
              <option value="10">Tapas</option>
              <option value="11">Vietnamita</option>
            </select>
          </div>
        </div>

        <div class="captcha-container">
          <div class="g-recaptcha" data-sitekey="6Leg8j8rAAAAAJ7vFv4p--JtvHd7J-Rw-FhBXEg3"></div>
          <?php if(isset($err_captcha)) echo "<span class='error-text'>$err_captcha</span>" ?>
        </div>
        <div class="terms-container">
          <input type="checkbox" name="terminos" id="terminos" class="terms-checkbox">
          <label for="terminos" class="terms-label">Acepto los <a href="terminos.php" target="_blank">términos y condiciones</a></label>
          <?php if(isset($err_terminos)) echo "<span class='error-text'>$err_terminos</span>" ?>
      </div>

        <button type="submit" class="register--button">Registrarse</button>

        <p class="register-login">
          ¿Ya tienes una cuenta? &nbsp;<a href="iniciosesion.php">Inicia sesión</a>
        </p>
    </form>
  </section>
</body>
</html>