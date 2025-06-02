// Muestra el filtro 'Recomendados' solo si el usuario tiene sesión iniciada y permite filtrar la lista para mostrar solo el restaurante recomendado

document.addEventListener('DOMContentLoaded', function() {
    fetch('filtroRecomendados.php')
        .then(response => response.text())
        .then(html => {
            if (html.trim() !== '') {
                // Agrega el filtro al contenedor de filtros si el usuario tiene sesión
                const filtroContainer = document.getElementById('filtros');
                if (filtroContainer) {
                    filtroContainer.insertAdjacentHTML('beforeend', html);
                    const filtroRecomendados = document.getElementById('filtro-recomendados');
                    if (filtroRecomendados) {
                        filtroRecomendados.addEventListener('click', function() {
                            const recomendadoId = this.getAttribute('data-id');
                            console.log('[FiltroRecomendados] ID recomendado:', recomendadoId);
                            console.log('[FiltroRecomendados] window.data:', window.data);
                            if (!recomendadoId) {
                                alert('No se encontró el ID del restaurante recomendado.');
                                return;
                            }
                            if (window.data && Array.isArray(window.data)) {
                                const recomendado = window.data.find(r => String(r.id) === String(recomendadoId));
                                console.log('[FiltroRecomendados] Restaurante encontrado:', recomendado);
                                if (recomendado) {
                                    const lista = document.querySelector('.restaurants-list');
                                    if (lista) {
                                        lista.innerHTML = '';
                                        // Usa la función global si existe
                                        if (typeof renderRestaurantCard === 'function') {
                                            renderRestaurantCard(recomendado, 0);
                                        } else {
                                            // Renderizado simple si no existe la función global
                                            const card = document.createElement('div');
                                            card.className = 'restaurant-card';
                                            card.innerHTML = `<h3>${recomendado.nombre}</h3>`;
                                            lista.appendChild(card);
                                        }
                                    }
                                } else {
                                    alert('No se encontró el restaurante recomendado en la lista.');
                                }
                            } else {
                                alert('No hay datos de restaurantes cargados.');
                            }
                        });
                    }
                }
            }
        });
});

// Función para renderizar una tarjeta de restaurante (ajusta los campos según tu estructura)
function renderRestaurantCard(restaurante, idx) {
    const card = document.createElement('div');
    card.className = 'restaurant-card';
    card.dataset.idx = idx;
    card.innerHTML = `
        <div class="restaurant-image">
            <img src="${restaurante.imagenes && restaurante.imagenes[0] ? restaurante.imagenes[0] : ''}" alt="${restaurante.nombre}">
            <div class="restaurant-nav">
                <button class="nav-btn prev" data-idx="${idx}" data-img="0"><i class="fas fa-chevron-left"></i></button>
                <button class="nav-btn next" data-idx="${idx}" data-img="0"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
        <div class="restaurant-info">
            <h3>${restaurante.nombre}</h3>
            <div class="restaurant-meta">
                <span class="restaurant-rating"><i class="fas fa-star"></i> ${restaurante.valoraciones}</span>
                <span class="restaurant-type">${restaurante.tipos_comida ? restaurante.tipos_comida.map(tc => tc.nombre).join(', ') : ''}</span>
            </div>
            <div class="restaurant-location">${restaurante.ubicacion || ''}</div>
            <div class="restaurant-desc">${restaurante.descripcion || ''}</div>
        </div>
    `;
    return card;
}
