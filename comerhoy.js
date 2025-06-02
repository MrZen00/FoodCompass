// Funcionalidad para el filtro "Comida hoy"
async function cargarRestaurantesComidaHoy() {
    try {
		console.log("Bien");
        const response = await fetch('APIS/comidahoy.php');
        const restaurantes = await response.json();
        // Limpiar tarjetas existentes
        limpiarTarjetas();
        // Añadir nuevas tarjetas
        let mostrados = [];
        if (Array.isArray(restaurantes) && restaurantes.length > 0) {
            restaurantes.forEach((restaurante, idx) => {
                renderRestaurantCard(restaurante, idx);
                mostrados.push(restaurante);
            });
        } else {
            const lista = document.querySelector('.restaurants-list');
            if (lista) lista.innerHTML = '<p>No hay restaurantes abiertos ahora mismo.</p>';
        }
        // Reasigna lógica de navegación e interacción
        setupImageNavigation && setupImageNavigation();
        setupFavoriteButtons && setupFavoriteButtons();
        // --- Renderizado de marcadores centralizado ---
        if (typeof updateMapMarkers === 'function') updateMapMarkers(mostrados);
        // Reasigna lógica de navegación e interacción
        setupImageNavigation && setupImageNavigation();
        setupFavoriteButtons && setupFavoriteButtons();
    } catch (error) {
        alert('Error al cargar restaurantes abiertos hoy');
        console.error(error);
		console.log("Mal");
    }
}

// Asigna el evento de click al filtro 'Comida hoy' si existe

   document.addEventListener('DOMContentLoaded', function() {
    const filtroComidaHoy = document.getElementById('comidahoy');
    if (filtroComidaHoy) {
        filtroComidaHoy.addEventListener('click', cargarRestaurantesComidaHoy);
    }
});
