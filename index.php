    <?php
        error_reporting(E_ALL);
        ini_set("display_errors", 1);
        require('./util/conexion.php');
		require_once('./usuario/check_login.php');
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="Food Compas te guía a los mejores sitios para comer en Málaga. Opiniones, recomendaciones y secretos gastronómicos locales en un solo lugar.">
    <title>Buscador de Restaurantes</title>
    <!-- Añadir Leaflet CSS y JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <link rel="stylesheet" href="estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
	<link rel="icon" type="image/png" href="/img/favicon.png">
</head>
<body>
	    <div id="toast-container">
        <div id="toast" class="toast"></div>
    	</div>
	   
    <header>
        <div class="header-container">
            <div class="logo">
                <img src="/img/logo.jpg" alt="Logo">
            </div>
            <div class="header-links">
                <a href="/restaurante/nuevoRestaurante.php">REGISTRAR MI RESTAURANTE</a>
            </div>
        </div>
    </header>

    <div class="search-container">
        <div class="search-box">
            <div class="location-input">
                <i class="fas fa-map-marker-alt"></i>
                <input type="text" placeholder="Ciudad, España" value="Málaga, España" disabled>
            </div>
            <div class="search-input">
                <i class="fas fa-search"></i>
                <input type="text" id="search-input" placeholder="Tipo de cocina, nombre del restaurante...">
            </div>
            <button name="busqueda" class="search-button">BÚSQUEDA</button>
        </div>
        <div class="user-actions">
    <?php if(isset($_SESSION['usuario'])): ?>
        <a href="usuario/perfilUsuario.php" class="profile-link">
            <img src="img/foto_perfil.JPG" alt="Foto de perfil" class="profile-pic">
        </a>
    <?php else: ?>
        <button name="inicioSesion" class="login-button" onclick="window.location.href='usuario/iniciosesion.php'">
            <i class="fas fa-user"></i> INICIAR SESIÓN
        </button>
    <?php endif; ?>
</div>
    </div>
        


    <div class="filters-container">
        <div class="filter-options">
            <div class="filter-option">
                <i class="far fa-calendar"></i>
                <span>Fecha</span>
                <i class="fas fa-chevron-down"></i>
            </div>
        </div>
        <div class="filter-tags">
            <div class="filter-tag active">
                <i class="fas fa-filter"></i>
                <span>Todos los filtros</span>
            </div>
            <div class="filter-tag" id="comidahoy">
                <i class="fas fa-utensils"></i>
                <span>Comida hoy</span>
            </div>
            <div class="filter-tag">
                <i class="fas fa-star"></i>
                <span>Mejor valorados</span>
            </div>
            <div class="filter-tag">
                <span>Tipo de cocina</span>
                <i class="fas fa-chevron-down"></i>
            </div>

        </div>
    </div>

    <main>
        <div class="content-container">
            <div class="results-header">
                <h1>Los mejores restaurantes de la zona</h1>
            </div>

            <div class="restaurants-list">
              
            </div>
        </div>
        <div class="map-container">
            <!-- Contenedor del mapa con ID específico -->
            <div id="map" style="width: 100%; height: 100%;"></div>
        </div>
    </main>

    <script src="main.js"></script>
	<script src="buscar.js"></script>
	<script src="comerhoy.js"></script>


	
	<?php 
        //Mostramos el mensaje de exito por Pantalla (Si exste la sesion Show Toast)
        if (isset($_SESSION['show_toast']) && $_SESSION['show_toast']): ?>
        <?php
        if(isset($_SESSION["usuario"])){
        echo "<h1>" . htmlspecialchars($_SESSION["usuario"]) . "</h1>";
        }
        ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toast = document.getElementById('toast');
            toast.textContent = '<?php echo addslashes($_SESSION['toast_message']); ?>';
            toast.style.display = 'block';
            
            // Eliminar el toast despues de la animación
            setTimeout(function() {
                toast.style.display = 'none';
            }, 3000);
            
            // Limpiar la sesión
            <?php 
            unset($_SESSION['show_toast']);
            unset($_SESSION['toast_message']); 
            ?>
        });
    </script>
    <?php endif; ?>
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
                <li><a href="/restaurante/restaurante.html" class="footer-link">Restaurantes</a></li>
                <li><a href="/util/categorias.html" class="footer-link">Categorías</a></li>
                <li><a href="/util/ciudades.html" class="footer-link">Ciudades</a></li>
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
                <li><a href="/util/terminos.html" class="footer-link">Términos</a></li>
                <li><a href="/util/terminos.html" class="footer-link">Privacidad</a></li>
                <li><a href="mailto:correo@foodcompass.com" class="footer-link">Contacto</a></li>
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="footer-legal">
            <span>© 2025 FoodCompass. Todos los derechos reservados.</span>
            <div class="legal-links">
                <a href="/util/terminos.html" class="legal-link">Aviso Legal</a>
                <span class="separator">|</span>
                <a href="/util/terminos.html" class="legal-link">Política de Privacidad</a>
            </div>
        </div>
    </div>
</footer>

</html>
