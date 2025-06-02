<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
session_start();
require '../util/conexion.php';

// Obtener información del usuario
$usuario_id = null;
if (isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];
}

$nombre_usuario = '';
if (isset($_SESSION['usuario'])) {
    $nombre_usuario = $_SESSION['usuario'];
}

else{
    header("Location: https://foodcompass.es/");
}

$restaurante_id = null;
$nombre_restaurante = null;

// Consulta para saber si hay algún restaurante visitado sin valorar
if ($usuario_id !== null) {
    $sql = "SELECT h.restaurante_id, r.nombre AS nombre_restaurante
        FROM historial_visitas h
        JOIN restaurantes r ON h.restaurante_id = r.id
        WHERE h.usuario_id = ? AND (h.valoracion IS NULL OR h.valoracion = 0)
        LIMIT 1";

    $stmt = $_conexion->prepare($sql);
    if ($stmt === false) {
        die("Error en la preparación de la consulta: " . $_conexion->error);
    }
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();

    // Aquí se asignan ambas columnas devueltas
    $stmt->bind_result($restaurante_id, $nombre_restaurante);

    $tiene_pendiente = $stmt->fetch();
    $stmt->close();
} else {
    $tiene_pendiente = false;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Perfil de Usuario</title>
    <link rel="stylesheet" href="../css/perfil.css" />
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<div class="profile-container">

    
    <div class="profile-header">
        <a href="https://foodcompass.es">
  			<img src="../img/foto_perfil.JPG" class="profile-picture" />
		</a>
        <h1 class="profile-name"><?php echo ($nombre_usuario); ?></h1>
        <div class="profile-actions">
            <button class="profile-btn change-password-btn" onclick="window.location.href='cambiar_contrasena.php'">Cambiar Contraseña</button>
            <button class="profile-btn history-btn" onclick="window.location.href='historialUsuario.php'">Historial de Restaurantes</button>
            <button class="profile-btn logout-btn" onclick="window.location.href='cerrarsesion.php'">Cerrar Sesión</button>
            <button id="btn-recomendado" class="profile-btn recomendado-btn">Restaurante recomendado</button>
        
</div>
    </div>
    
    <div class="subscription-plans">
        <div class="plan-card free-plan">
            <h2 class="plan-title">Plan Gratuito</h2>
            <div class="plan-price free-price">€0<span style="font-size: 16px;">/mes</span></div>
            <ul class="plan-features">
                <li>Acceso básico a funciones</li>
                <li>Almacenamiento limitado</li>
                <li>Anuncios</li>
            </ul>
            <button class="plan-button free-button" disabled>Tu plan actual</button>
        </div>
        
        <div class="plan-card premium-plan">
            <h2 class="plan-title">Plan Explorador</h2>
            <div class="plan-price premium-price">€4,99<span style="font-size: 16px;">/mes</span></div>
            <ul class="plan-features">
                <li>Acceso completo a todas las funciones</li>
                <li>Soporte prioritario</li>
                <li>Almacenamiento ilimitado</li>
                <li>Sin anuncios</li>
                <li>Contenido exclusivo</li>
            </ul>
            <button class="plan-button premium-button" onclick="window.location.href='https://buy.stripe.com/test_4gM5kE3dw3txbCegmp5J600'">Actualizar a Premium</button>
        </div>
    </div>

    <?php if ($tiene_pendiente): ?>
        <div class="restaurant-rating">
            <h2 class="rating-title">Valorar Restaurante: <?php echo htmlspecialchars($nombre_restaurante); ?></h2>
            
            <form method="POST" action="../APIS/valoracion.php" id="valoracion-form">
                <input type="hidden" name="restaurante_id" value="<?php echo $restaurante_id; ?>">
                
                <div class="rating-stars">
                    <span class="star" data-value="1">★</span>
                    <span class="star" data-value="2">★</span>
                    <span class="star" data-value="3">★</span>
                    <span class="star" data-value="4">★</span>
                    <span class="star" data-value="5">★</span>
                    <input type="hidden" name="valoracion" id="valoracion-input" value="0" required>
                </div>
                
                <div class="opinion-box">
                    <textarea class="opinion-textarea" name="descripcion" placeholder="Escribe tu opinión (opcional)"></textarea>
                </div>
                
                <button type="submit" class="submit-rating">Enviar Valoración</button>
            </form>
        </div>
    <?php else: ?>
        <p>No tienes restaurantes pendientes por valorar.</p>
    <?php endif; ?>

</div>

<script src="../js/perfil.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let recomendado = null;
    fetch('restauranteRecomendado.php')
        .then(resp => resp.json())
        .then(data => {
            recomendado = data;
        });
    document.getElementById('btn-recomendado').onclick = function() {
        if (recomendado && recomendado.success && recomendado.id && recomendado.nombre) {
            const slug = recomendado.nombre.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
            window.location.href = `../PaginaRestaurante.html?idx=${recomendado.id}&slug=${slug}`;
        } else {
            alert('No hay restaurante recomendado para ti ahora mismo.');
        }
    };
});
</script>
</body>
	<footer class="foodcompass-footer">
    <div class="footer-content">
        <div class="footer-column logo-column">
            <img src="/img/logo.jpg" alt="FoodCompass Logo" class="footer-logo">
            <p class="footer-description">Encuentra tu experiencia gastronómica perfecta con recomendaciones personalizadas.</p>
            <div class="footer-social">
                <a href="https://www.instagram.com/foodcompass09/" class="social-icon" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="https://x.com/FoodCompas91473" class="social-icon" aria-label="X"><i class="fab fa-x"></i></a>
                <a href="https://www.tiktok.com/@foodcompass09" class="social-icon" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
            </div>
        </div>

        <div class="footer-column">
            <h3 class="footer-title">Explorar</h3>
            <ul class="footer-links">
                <li><a href="../restaurante/restaurante.html" class="footer-link">Restaurantes</a></li>
                <li><a href="../util/categorias.html" class="footer-link">Categorías</a></li>
                <li><a href="../util/ciudades.html" class="footer-link">Ciudades</a></li>
            </ul>
        </div>

        <div class="footer-column">
            <h3 class="footer-title">Empresa</h3>
            <ul class="footer-links">
                <li><a href="#" class="footer-link">Sobre nosotros</a></li>
                <li><a href="#" class="footer-link">Blog</a></li>
                <li><a href="mailto:correo@foodcompass.com" class="footer-link">Empleo</a></li>
            </ul>
        </div>

        <div class="footer-column">
            <h3 class="footer-title">Legal</h3>
            <ul class="footer-links">
                <li><a href="../util/terminos.html" class="footer-link">Términos</a></li>
                <li><a href="../util/terminos.html" class="footer-link">Privacidad</a></li>
                <li><a href="mailto:correo@foodcompass.com" class="footer-link">Contacto</a></li>
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="footer-legal">
            <span>© 2025 FoodCompass. Todos los derechos reservados.</span>
            <div class="legal-links">
                <a href="../util/terminos.html" class="legal-link">Aviso Legal</a>
                <span class="separator">|</span>
                <a href="../util/terminos.html" class="legal-link">Política de Privacidad</a>
            </div>
        </div>
    </div>
</footer>
</html>
