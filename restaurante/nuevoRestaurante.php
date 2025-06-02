<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Restaurante</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet"> 
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <link href="../css/restauranteReg.css" rel="stylesheet">
    <script src="../js/validarRestaurante.js"></script>
</head>

<?php
error_reporting(E_ALL);
ini_set("display_errors", 1); 
require('../util/conexion.php');

// Clave secreta de reCAPTCHA
$secret_key = "6Leg8j8rAAAAAFq_Ni7FnUgCEyNOZmw-0RZVTP03";

// Variables para mantener valores
$nombre = "";
$tipo_comida = "";
$ubicacion = "";
$descripcion = "";
$correo_value = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Inicializar variables con los valores del formulario
    if (isset($_POST["nombre"])) {
        $nombre = htmlspecialchars($_POST["nombre"]);
    }
    
    if (isset($_POST["tipo"])) {
        $tipo_comida = htmlspecialchars($_POST["tipo"]);
    }
    
    if (isset($_POST["ubicacion"])) {
        $ubicacion = htmlspecialchars($_POST["ubicacion"]);
    }
    
    if (isset($_POST["descripcion"])) {
        $descripcion = htmlspecialchars($_POST["descripcion"]);
    }
    
    if (isset($_POST["correo"])) {
        $correo_value = htmlspecialchars($_POST["correo"]);
    }

    // Validar CAPTCHA
    if (isset($_POST['g-recaptcha-response'])) {
        $captcha_response = $_POST['g-recaptcha-response'];
        $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret_key}&response={$captcha_response}");
        $response_data = json_decode($response);

        if(!$response_data->success) {
            $err_captcha = "Por favor, completa el CAPTCHA.";
        } else {
            // Validar campos del formulario
            if (empty($nombre)) {
                $err_nombre = "El nombre del restaurante es obligatorio";
            } elseif (strlen($nombre) < 3 || strlen($nombre) > 50) {
                $err_nombre = "El nombre debe tener entre 3 y 50 caracteres";
            }
            
            if (empty($tipo_comida)) {
                $err_tipo = "El tipo de comida es obligatorio";
            } elseif (strlen($tipo_comida) < 3 || strlen($tipo_comida) > 30) {
                $err_tipo = "El tipo de comida debe tener entre 3 y 30 caracteres";
            }
            
            if (empty($ubicacion)) {
                $err_ubicacion = "La ubicación es obligatoria";
            } elseif (strlen($ubicacion) < 5 || strlen($ubicacion) > 100) {
                $err_ubicacion = "La ubicación debe tener entre 5 y 100 caracteres";
            }
            
            if (empty($descripcion)) {
                $err_descripcion = "La descripción es obligatoria";
            } elseif (strlen($descripcion) < 10 || strlen($descripcion) > 500) {
                $err_descripcion = "La descripción debe tener entre 10 y 500 caracteres";
            }
            
            if (empty($correo_value)) {
                $err_correo = "El correo electrónico es obligatorio";
            } elseif (!filter_var($correo_value, FILTER_VALIDATE_EMAIL)) {
                $err_correo = "El correo electrónico no es válido";
            } else {
                // Verificar si el correo ya está registrado
                $sql = "SELECT id FROM solicitudes_restaurantes WHERE correo_contacto = ?";
                $stmt = $_conexion->prepare($sql);
                $stmt->bind_param("s", $correo_value);
                $stmt->execute();
                $resultado = $stmt->get_result();
                if ($resultado->num_rows > 0) {
                    $err_correo = "Este correo electrónico ya está registrado";
                }
                $stmt->close();
            }
            
            if (!isset($_POST["terms"])) {
                $err_terms = "Debes aceptar los términos y condiciones";
            }

            // Si no hay errores, proceder con el registro
            if (!isset($err_nombre) && !isset($err_tipo) && !isset($err_ubicacion) && 
                !isset($err_descripcion) && !isset($err_correo) && !isset($err_terms)) {
                
                // Insertar el restaurante en la base de datos
                $sql = "INSERT INTO solicitudes_restaurantes (nombre, tipo_comida, ubicacion, descripcion, correo_contacto) 
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $_conexion->prepare($sql);
                $stmt->bind_param("sssss", $nombre, $tipo_comida, $ubicacion, $descripcion, $correo_value);
                
                if ($stmt->execute()) {
                    // Registro exitoso
                    header("Location: confirmacion.html");
                } else {
                    // Error en la base de datos
                    $err_db = "Error al registrar el restaurante. Por favor, inténtalo de nuevo.";
                }
                $stmt->close();
            }
        }
    } else {
        $err_captcha = "Por favor, completa el CAPTCHA.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Restaurante</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet"> 
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <link href="../css/restauranteReg.css" rel="stylesheet">
    <style>
        .error-text {
            color: #ff3333;
            font-size: 0.8rem;
            margin-top: 0.2rem;
            display: block;
        }
    </style>
</head>

<body>
    <section class="register">
        <form action="#" method="post" class="register--form">
            <h1 class="register--title">Registro de Restaurante</h1>
            
            <div class="register--content">
                <div class="register-box">
                    <i class="ri-restaurant-2-line register-icon"></i>
                    <div class="register--input-box">
                        <input type="text" id="nombre" name="nombre" class="register-input"  placeholder=" " value="<?php echo htmlspecialchars($nombre); ?>">
                        <label for="nombre" class="register--input-label">Nombre del restaurante</label>
                    </div>
                </div>
                <?php if(isset($err_nombre)) echo "<span class='error-text'>$err_nombre</span>" ?>
                
                <div class="register-box">
                    <i class="ri-restaurant-line register-icon"></i>
                    <div class="register--input-box">
                        <input type="text" id="tipo" name="tipo" class="register-input"  placeholder=" " value="<?php echo htmlspecialchars($tipo_comida); ?>">
                        <label for="tipo" class="register--input-label">Tipo de comida</label>
                    </div>
                </div>
                <?php if(isset($err_tipo)) echo "<span class='error-text'>$err_tipo</span>" ?>
                
                <div class="register-box">
                    <i class="ri-map-pin-line register-icon"></i>
                    <div class="register--input-box">
                        <input type="text" id="ubicacion" name="ubicacion" class="register-input"  placeholder=" " value="<?php echo htmlspecialchars($ubicacion); ?>">
                        <label for="ubicacion" class="register--input-label">Ubicación</label>
                    </div>
                </div>
                <?php if(isset($err_ubicacion)) echo "<span class='error-text'>$err_ubicacion</span>" ?>
                
                <div class="register-box">
                    <i class="ri-message-3-line register-icon"></i>
                    <div class="register--input-box">
                        <input type="text" id="descripcion" name="descripcion" class="register-input"  placeholder=" " value="<?php echo htmlspecialchars($descripcion); ?>">
                        <label for="descripcion" class="register--input-label">Descripción</label>
                    </div>
                </div>
                <?php if(isset($err_descripcion)) echo "<span class='error-text'>$err_descripcion</span>" ?>

                <div class="register-box">
                    <i class="ri-mail-line register-icon"></i>
                    <div class="register--input-box">
                        <input type="text" id="correo" name="correo" class="register-input"  placeholder=" " value="<?php echo htmlspecialchars($correo_value); ?>">
                        <label for="correo" class="register--input-label">Correo de contacto</label>
                    </div>
                    
                </div>
            </div>
            <?php if(isset($err_correo)) echo "<span class='error-text'>$err_correo</span>" ?>
            
            <!-- CAPTCHA -->
            <div class="captcha-container">
                <div class="g-recaptcha" data-sitekey="6Leg8j8rAAAAAJ7vFv4p--JtvHd7J-Rw-FhBXEg3"></div>
            </div>
            <?php if(isset($err_captcha)) echo "<span class='error-text'>$err_captcha</span>" ?>
            
            <!-- Términos y condiciones -->
            <div class="terms-container">
                <input type="checkbox" id="terms" name="terms" class="terms-checkbox" >
                <label for="terms" class="terms-label">
                    Acepto los <a href="../util/terminos.html" class="terms-link">términos y condiciones</a>
                </label>
                <?php if(isset($err_terms)) echo "<span class='error-text'>$err_terms</span>" ?>
            </div>
            
            <?php if(isset($err_db)) echo "<span class='error-text'>$err_db</span>" ?>
            
            <button type="submit" class="register--button">
                 Registrar Restaurante
            </button>
        </form>
    </section>
	<script>
    const descripcionInput = document.getElementById('descripcion');
    const tempSpan = document.createElement('span');
    
    // Configura el span fantasma con los mismos estilos que el input
    tempSpan.style.position = 'absolute';
    tempSpan.style.visibility = 'hidden';
    tempSpan.style.whiteSpace = 'nowrap';
    tempSpan.style.font = window.getComputedStyle(descripcionInput).font;
    document.body.appendChild(tempSpan);

    descripcionInput.addEventListener('input', function() {
        tempSpan.textContent = this.value || this.placeholder;
        const width = tempSpan.offsetWidth + 15; // +15px de margen
        
        // Ajusta el ancho con un mínimo de 100px y máximo del contenedor
        this.style.width = `${Math.min(Math.max(100, width), 300)}px`;
    });

    // Dispara el evento al cargar (por si hay valor inicial)
    descripcionInput.dispatchEvent(new Event('input'));
</script>
</body>
</html>