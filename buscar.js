// buscar.js: Lógica para el buscador de restaurantes

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('.search-input input[type="text"]');
    console.log('Input de búsqueda detectado:', searchInput);
    const searchBtn = document.querySelector('.search-button');
    const lista = document.querySelector('.restaurants-list');

    searchBtn.addEventListener('click', function() {
        const termino = searchInput.value;
        buscarRestaurantes(termino);
    });

    async function buscarRestaurantes(termino) {
        if (!termino || termino.trim() === '') return;
        lista.innerHTML = '<p>Buscando restaurantes...</p>';
        try {
            const resp = await fetch(`APIS/buscar_restaurantes.php?q=${encodeURIComponent(termino)}`);
            const text = await resp.text();
            console.log('Respuesta cruda del PHP:', text);
            let restaurantes;
            try {
                restaurantes = JSON.parse(text);
            } catch (jsonErr) {
                console.error('No se pudo convertir a JSON:', jsonErr);
                lista.innerHTML = '<p>Error: Respuesta del servidor no es JSON válido.</p>';
                return;
            }
            // Limpiar tarjetas existentes
            if (typeof limpiarTarjetas === 'function') {
                limpiarTarjetas();
            } else {
                lista.innerHTML = '';
            }
            if (!Array.isArray(restaurantes) || restaurantes.length === 0) {
                lista.innerHTML = '<p>No se encontraron restaurantes para esa búsqueda.</p>';
                return;
            }
            restaurantes.forEach((restaurante, idx) => {
                if (typeof renderRestaurantCard === 'function') {
                    renderRestaurantCard(restaurante, idx);
                }
            });
            if (typeof setupImageNavigation === 'function') setupImageNavigation();
            // Actualizar marcadores del mapa con los resultados de búsqueda
            if (typeof updateMapMarkers === 'function') updateMapMarkers(restaurantes);
        } catch (e) {
            lista.innerHTML = '<p>Error al buscar restaurantes.</p>';
            console.error('Error en la búsqueda:', e);
        }
    }
});
