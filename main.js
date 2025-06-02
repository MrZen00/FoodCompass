let map, markers = [], markerLayerGroup, data = null;

// Renderiza una tarjeta de restaurante y la añade a la lista
function renderRestaurantCard(restaurante, idx) {
    const card = document.createElement('div');
    card.className = 'restaurant-card';
    card.dataset.idx = idx;
    card.innerHTML = `
        <div class="restaurant-image">
            <img src="${restaurante.imagenes && restaurante.imagenes[0] ? restaurante.imagenes[0] : ''}" alt="${restaurante.nombre}">
            <div class="restaurant-nav">
                <button class="nav-btn prev" data-idx="${idx}" data-img="0" aria-label="Imagen anterior"><i class="fas fa-chevron-left"></i></button>
                <button class="nav-btn next" data-idx="${idx}" data-img="1" aria-label="Imagen siguiente"><i class="fas fa-chevron-right"></i></button>
            </div>
            <div class="restaurant-badges">
                <span class="payment-badge">PAY</span>
                <span class="cuisine-badge">${(restaurante.tipos_comida && restaurante.tipos_comida.length > 0) ? restaurante.tipos_comida.map(tc => tc.nombre).join(', ') : ''}</span>
            </div>
        </div>
        <div class="restaurant-info">
            <div class="restaurant-header">
                <div>
                    <h2>${restaurante.nombre}</h2>
                    <p class="restaurant-address">${restaurante.ubicacion}</p>
                    <p class="restaurant-price">Precio medio ${restaurante.precio}</p>
                    <p class="restaurant-review">${restaurante.descripcion}</p>
                </div>
                <div class="restaurant-rating">
                    <div class="rating-score">${restaurante.valoraciones}</div>
                </div>
            </div>
        </div>
    `;
    card.addEventListener('mouseenter', () => {
        if (typeof focusMarker === 'function') focusMarker(idx, restaurante);
    });
    card.addEventListener('mouseleave', () => {
        if (typeof unfocusMarker === 'function') unfocusMarker(idx, restaurante);
    });
    // Evento click para abrir la página del restaurante
    card.addEventListener('click', (e) => {
        // Evitar que el click en los botones de navegación de imagen propague
        if (e.target.closest('.nav-btn')) return;
        const slug = restaurante.nombre.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
        window.open(`PaginaRestaurante.html?idx=${restaurante.id}&slug=${slug}`, '_blank');
    });
    const lista = document.querySelector('.restaurants-list');
    if (lista) lista.appendChild(card);
}

// Muestra los restaurantes por sus IDs consultando el backend
async function mostrarRestaurantesPorIds(ids) {
    const lista = document.querySelector('.restaurants-list');
    if (!lista) return;
    try {
        const response = await fetch('../APIS/restaurantes_por_ids.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ ids })
        });
        const restaurantes = await response.json();
        lista.innerHTML = '';
        if (!Array.isArray(restaurantes) || restaurantes.length === 0) {
            lista.innerHTML = '<p>No se encontraron restaurantes para los IDs proporcionados.</p>';
            return;
        }
        restaurantes.forEach((restaurante, idx) => {
            renderRestaurantCard(restaurante, idx);
        });
        if (typeof setupImageNavigation === 'function') setupImageNavigation();
        if (typeof updateMapMarkers === 'function') updateMapMarkers(restaurantes);
    } catch (error) {
        lista.innerHTML = '<p>Error al cargar los restaurantes por IDs.</p>';
        console.error('Error al obtener restaurantes por IDs:', error);
    }
}

// Añade una tarjeta de restaurante al div .restaurants-list si es de la categoría indicada
async function anadirTarjeta(categoria) {
    const lista = document.querySelector('.restaurants-list');
    if (!lista) return;
    try {
        // Consulta al backend filtrando por categoría
        const response = await fetch(`../APIS/restaurantes_por_categoria.php?categoria=${encodeURIComponent(categoria)}`);
        const restaurantes = await response.json();

        lista.innerHTML = '';
        if (!Array.isArray(restaurantes) || restaurantes.length === 0) {
            lista.innerHTML = '<p>No hay restaurantes para esta categoría.</p>';
            return;
        }
        restaurantes.forEach((restaurante, idx) => {
            renderRestaurantCard(restaurante, idx);
        });
        if (typeof setupImageNavigation === 'function') setupImageNavigation();
        if (typeof updateMapMarkers === 'function') updateMapMarkers(restaurantes);
    } catch (error) {
        lista.innerHTML = '<p>Error al cargar los restaurantes.</p>';
        console.error('Error al obtener restaurantes:', error);
        updateMapMarkers([]);
    }
}


// Elimina todas las tarjetas de restaurante del contenedor principal
function limpiarTarjetas() {
    const lista = document.querySelector('.restaurants-list');
    if (lista) {
        lista.querySelectorAll('.restaurant-card').forEach(card => card.remove());
    }
}

let filtroTipoCocina;


document.addEventListener('DOMContentLoaded', async function() {
    setupSearchBar();
    map = initMap();
    await cargarRestaurantes();
    setupImageNavigation();
    setupFavoriteButtons();
    setupFilters();

    // Al cargar la página asegura que los filtros están en su estado inicial y renderiza usando el render estándar
    if (!window.currentFilters) window.currentFilters = {
        cocina: null,
        personas: null,
        fechaHora: { fecha: '', hora: '' },
        tag: null
    };
    if (typeof renderFilteredRestaurants === 'function') renderFilteredRestaurants();

    // Acción para el filtro "Todos los filtros"
    const todosFiltros = document.querySelector('.filter-tag span');
    if (todosFiltros && todosFiltros.textContent.trim() === 'Todos los filtros') {
        todosFiltros.parentElement.addEventListener('click', async function() {
            // Limpiar filtros globales
            if (window.currentFilters) {
                for (let key in window.currentFilters) delete window.currentFilters[key];
            }
            // Quitar clase active de todos los filtros
            document.querySelectorAll('.filter-tag').forEach(tag => tag.classList.remove('active'));
            // Poner active sólo en este
            this.classList.add('active');
            // Limpiar tarjetas
            if (typeof limpiarTarjetas === 'function') limpiarTarjetas();
            // Solo limpiar tarjetas y recargar restaurantes, NO reinicializar el mapa
            await cargarRestaurantes();
            setupImageNavigation();
            setupFavoriteButtons();
        });
    }

    // Redirigir a iniciosesion.php al hacer click en el botón de login
    const loginBtn = document.querySelector('.login-button');
    if (loginBtn) {
        loginBtn.addEventListener('click', function() {
            window.location.href = 'iniciosesion.php';
        });
    }
});

async function cargarRestaurantes(orden = '') {
    const debugDiv = document.getElementById('debug-restaurantes') || (() => {
        const d = document.createElement('div');
        d.id = 'debug-restaurantes';
        d.style.background = '#ffe0e0';
        d.style.color = '#a00';
        d.style.padding = '10px';
        d.style.margin = '10px 0';
        d.style.fontWeight = 'bold';
        d.style.display = 'none';
        document.body.prepend(d);
        return d;
    })();
    let response, json;
    try {
        let url = '../APIS/restaurante.php';
        if (orden === 'valoracion_desc') {
            url = '../APIS/restaurantes_mas_valorados.php';
        }
        response = await fetch(url);
        json = await response.json();
        console.log('Respuesta de restaurante.php:', json);
    } catch (e) {
        debugDiv.textContent = 'Error al obtener los datos del backend: ' + e;
        debugDiv.style.display = 'block';
        return;
    }
    data = json;
    if (!Array.isArray(data)) {
        debugDiv.textContent = 'La respuesta del backend NO es un array. Respuesta: ' + JSON.stringify(data);
        debugDiv.style.display = 'block';
        return;
    }
    if (data.length === 0) {
        debugDiv.textContent = 'No se recibieron restaurantes. ¿Seguro que hay datos en la base de datos?';
        debugDiv.style.display = 'block';
    } else {
        debugDiv.style.display = 'none';
    }
    // Si existe el objeto de filtros global, reinícialo
    if (window.currentFilters) window.currentFilters.tag = null;
    // Si existe renderFilteredRestaurants, úsalo para mostrar los restaurantes según filtros
    if (typeof renderFilteredRestaurants === 'function') {
        renderFilteredRestaurants();
        return;
    }
    // Si no existe, renderiza todos los restaurantes (modo legacy)
    const lista = document.querySelector('.restaurants-list');
    lista.innerHTML = '';

    data.forEach((restaurante, idx) => {
        const card = document.createElement('div');
        card.className = 'restaurant-card';
        card.dataset.idx = idx;
        card.innerHTML = `
            <div class="restaurant-image">
                <img src="${restaurante.imagenes[0] || ''}" alt="${restaurante.nombre}">
                <div class="restaurant-nav">
                    <button class="nav-btn prev" data-idx="${idx}" data-img="0" aria-label="Imagen anterior"><i class="fas fa-chevron-left"></i></button>
                    <button class="nav-btn next" data-idx="${idx}" data-img="1" aria-label="Imagen siguiente"><i class="fas fa-chevron-right"></i></button>
                </div>
                <div class="restaurant-badges">
                    <span class="payment-badge">PAY</span>
                    <span class="cuisine-badge">${(restaurante.tipos_comida && restaurante.tipos_comida.length > 0) ? restaurante.tipos_comida.map(tc => tc.nombre).join(', ') : ''}</span>
                </div>
            </div>
            <div class="restaurant-info">
                <div class="restaurant-header">
                    <div>
                        <h2>${restaurante.nombre}</h2>
                        <p class="restaurant-address">${restaurante.ubicacion}</p>
                        <p class="restaurant-price">Precio medio ${restaurante.precio}</p>
                        <p class="restaurant-review">${restaurante.descripcion}</p>
                    </div>
                    <div class="restaurant-rating">
                        <div class="rating-score">${restaurante.valoraciones}</div>
                    </div>
                </div>
            </div>
        `;
        card.addEventListener('click', (e) => {
            // Si el click viene de una flecha, no hacer nada
            if (e.target.closest('.nav-btn')) return;
            // URL amigable: nombre del restaurante en slug
            const slug = restaurante.nombre.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
            window.open(`PaginaRestaurante.html?idx=${restaurante.id}&slug=${slug}`,'_blank');
        });
        card.addEventListener('mouseenter', () => {
            focusMarker(idx, restaurante);
        });
        card.addEventListener('mouseleave', () => {
            unfocusMarker(idx, restaurante);
        });
        lista.appendChild(card);
    });
    // Ejecutar la navegación de imágenes después de renderizar las tarjetas
    setupImageNavigation();
    // Actualizar los marcadores del mapa con los datos cargados
    if (map) {
        addRestaurantMarkers(map);
    }
}

function initMap() {
    const malagaCentro = [36.7213, -4.4213];
    map = L.map('map').setView(malagaCentro, 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(map);
    // NO añadir marcadores aquí
    return map;
}

function addRestaurantMarkers(map) {
    if (!data) return;
    if (markerLayerGroup) markerLayerGroup.clearLayers();
    markers = [];
    markerLayerGroup = L.layerGroup().addTo(map);
    data.forEach((restaurante, idx) => {
        let coords = [36.7213, -4.4213];
        if (restaurante.coordenadas && restaurante.coordenadas.latitud && restaurante.coordenadas.longitud) {
            coords = [parseFloat(restaurante.coordenadas.latitud), parseFloat(restaurante.coordenadas.longitud)];
        }
        const customIcon = L.divIcon({
            className: 'custom-marker',
            html: `<div class="marker-circle" data-marker-idx="${idx}"><span class="marker-rating">${restaurante.valoraciones}</span></div>`,
            iconSize: [36, 36]
        });
        const marker = L.marker(coords, {icon: customIcon}).addTo(markerLayerGroup);
        marker.on('mouseover', () => focusMarker(idx, restaurante));
        marker.on('mouseout', () => unfocusMarker(idx, restaurante));
        marker.on('click', () => {
            const slug = restaurante.nombre.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
            window.open(`PaginaRestaurante.html?idx=${restaurante.id}&slug=${slug}`,'_blank');
        });
        markers.push(marker);
    });
    // Ajustar el mapa a los bounds de los marcadores
    if (markers.length > 0) {
        const group = new L.featureGroup(markers);
        map.fitBounds(group.getBounds().pad(0.2));
    }
    // Enable click on marker circle
    if (!window._markerCircleClickListenerAdded) {
        window._markerCircleClickListenerAdded = true;
        document.getElementById('map').addEventListener('click', function(ev) {
            const circle = ev.target.closest('.marker-circle');
            if (circle && typeof circle.dataset.markerIdx !== 'undefined') {
                const idx = parseInt(circle.dataset.markerIdx);
                if (markers[idx]) {
                    markers[idx].fire('click');
                }
            }
        });
    }
}

function focusMarker(idx, restaurante) {
    // Unhighlight ALL markers before highlighting the selected one
    markers.forEach((m, i) => {
        if (m) unhighlightMarker(i, restauranteListForMarkers && restauranteListForMarkers[i] ? restauranteListForMarkers[i] : null);
    });
    if (!markers[idx]) return;
    highlightMarker(idx, restauranteListForMarkers && restauranteListForMarkers[idx] ? restauranteListForMarkers[idx] : restaurante);
    markers[idx].setTooltipContent(`<strong>${restaurante.nombre}</strong><br>Puntuación: ${restaurante.valoraciones}`);
    markers[idx].openTooltip();
    highlightCard(idx);
}

function unfocusMarker(idx, restaurante) {
    if (!markers[idx]) return;
    unhighlightMarker(idx, restauranteListForMarkers && restauranteListForMarkers[idx] ? restauranteListForMarkers[idx] : restaurante);
    markers[idx].closeTooltip();
    unhighlightCard(idx);
    // Cerrar todos los tooltips menos el seleccionado
    markers.forEach((m, i) => {
        if (m && i !== idx) m.closeTooltip();
    });
}

function highlightMarker(idx, restaurante) {
    if (!markers[idx]) return;
    // Usar los datos correctos para cada marcador
    const valoraciones = restaurante && restaurante.valoraciones ? restaurante.valoraciones : '';
    markers[idx].setIcon(L.divIcon({
        className: 'custom-marker highlight',
        html: `<div class="marker-circle highlight" data-marker-idx="${idx}"><span class="marker-rating">${valoraciones}</span></div>`,
        iconSize: [44, 44]
    }));
}

function unhighlightMarker(idx, restaurante) {
    if (!markers[idx]) return;
    // Usar los datos correctos para cada marcador
    const valoraciones = restaurante && restaurante.valoraciones ? restaurante.valoraciones : '';
    markers[idx].setIcon(L.divIcon({
        className: 'custom-marker',
        html: `<div class="marker-circle" data-marker-idx="${idx}"><span class="marker-rating">${valoraciones}</span></div>`,
        iconSize: [36, 36]
    }));
}

function highlightCard(idx) {
    const card = document.querySelector(`.restaurant-card[data-idx="${idx}"]`);
    if (card) card.classList.add('highlight');
}

function unhighlightCard(idx) {
    const card = document.querySelector(`.restaurant-card[data-idx="${idx}"]`);
    if (card) card.classList.remove('highlight');
}

function setupImageNavigation() {
    document.querySelectorAll('.restaurant-card').forEach(card => {
        const idx = parseInt(card.dataset.idx);
        const restaurante = data[idx];
        let currentImg = 0;
        const img = card.querySelector('.restaurant-image img');
        const prevBtn = card.querySelector('.nav-btn.prev');
        const nextBtn = card.querySelector('.nav-btn.next');
        if (!img || !prevBtn || !nextBtn || !restaurante.imagenes) return;
        prevBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            currentImg = (currentImg - 1 + restaurante.imagenes.length) % restaurante.imagenes.length;
            img.src = restaurante.imagenes[currentImg];
        });
        nextBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            currentImg = (currentImg + 1) % restaurante.imagenes.length;
            img.src = restaurante.imagenes[currentImg];
        });
    });
}


function setupFavoriteButtons() {
    const favoriteButtons = document.querySelectorAll('.favorite-btn');
    favoriteButtons.forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            if (!getCookie('session')) {
                alert('Necesitas tener la sesión iniciada para poder dar favoritos');
                return;
            }
            const restaurantId = btn.getAttribute('data-id');
            if (!restaurantId) return;
            btn.disabled = true; // Evita dobles clicks
            try {
                const response = await fetch('../APIS/favorito.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: restaurantId })
                });
                const result = await response.json();
                if (result.success) {
                    // Alterna el icono (relleno/vacío)
                    const icon = btn.querySelector('i');
                    if (result.favorited) {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                    } else {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                    }
                } else {
                    alert(result.message || 'Error al marcar como favorito');
                }
            } catch (err) {
                alert('Error de conexión al marcar favorito');
            }
            btn.disabled = false;
        });
    });
}

// Función auxiliar para obtener cookies por nombre
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

function setupSearchBar() {
    const searchInput = document.querySelector('.search-input input');
    const searchButton = document.querySelector('.search-button');
    if (!window.currentFilters) window.currentFilters = {};
    // Buscar al escribir
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            window.currentFilters.search = searchInput.value;
            if (typeof renderFilteredRestaurants === 'function') {
                renderFilteredRestaurants();
            }
        });
    }
    // Buscar al hacer click en botón
    if (searchButton) {
        searchButton.addEventListener('click', function(e) {
            e.preventDefault();
            window.currentFilters.search = searchInput.value;
            if (typeof renderFilteredRestaurants === 'function') {
                renderFilteredRestaurants();
            }
        });
    }
}

function setupFilters() {
    // Filtros de la barra superior
    const filterOptions = document.querySelectorAll('.filter-option');
    const filterTags = document.querySelectorAll('.filter-tag');

    filterTags.forEach(tag => {
    // Acción para el filtro 'Mejor valorados'
    const span = tag.querySelector('span');
    if (span && span.textContent.trim() === 'Mejor valorados') {
        tag.addEventListener('click', async function() {
            // Limpiar filtros globales
            if (window.currentFilters) {
                for (let key in window.currentFilters) delete window.currentFilters[key];
            }
            // Quitar clase active de todos los filtros
            document.querySelectorAll('.filter-tag').forEach(t => t.classList.remove('active'));
            // Poner active sólo en este
            this.classList.add('active');
            // Limpiar tarjetas
            if (typeof limpiarTarjetas === 'function') limpiarTarjetas();
            // Solo limpiar tarjetas y recargar restaurantes ordenados, NO reinicializar el mapa
            await cargarRestaurantes('valoracion_desc');
            setupImageNavigation();
            setupFavoriteButtons();
        });
    }
    // Event listeners para los filtros superiores (filter-option)
    filterOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Cerrar cualquier dropdown abierto
            if (typeof removeDropdowns === 'function') removeDropdowns();
            // Deseleccionar todos los filtros activos
            filterTags.forEach(t => t.classList.remove('active'));
            filterOptions.forEach(o => o.classList.remove('active'));
            // Limpiar filtro de fecha/hora al cambiar de filtro superior
            if (window.currentFilters) {
                window.currentFilters.fechaHora = { fecha: '', hora: '' };
            }
            // Marcar este filtro como activo
            this.classList.add('active');
            const span = this.querySelector('span');
            if (
                span && (
                    span.textContent.trim() === 'Fecha' ||
                    span.textContent.trim() === 'Hora'
                )
            ) {
                // Al seleccionar fecha/hora, limpia 'Comida hoy' y 'Mejor valorados'
                if (window.currentFilters) {
                    if (window.currentFilters.tag === 2 || window.currentFilters.tag === 3) {
                        window.currentFilters.tag = null;
                        // Quitar la clase 'active' de los tags correspondientes
                        filterTags.forEach(t => {
                            const s = t.querySelector('span');
                            if (s && (s.textContent.trim() === 'Comida hoy' || s.textContent.trim() === 'Mejor valorados')) t.classList.remove('active');
                        });
                    }
                }
                if (typeof createFechaHoraDropdown === 'function') {
                    createFechaHoraDropdown(this);
                }
            } else if (span && span.textContent.trim() === 'Pers.') {
                if (typeof createPersDropdown === 'function') {
                    createPersDropdown(this);
                }
            } else if (span && span.textContent.trim() === 'Tipo de cocina') {
                if (typeof createCocinaDropdown === 'function') {
                    createCocinaDropdown(this);
                }
            }
        });
    });
        tag.addEventListener('click', async function() {
            // Cerrar cualquier dropdown abierto
            if (typeof removeDropdowns === 'function') removeDropdowns();
            // Deseleccionar todos los filtros activos
            filterTags.forEach(t => t.classList.remove('active'));
            filterOptions.forEach(o => o.classList.remove('active'));
            // Limpiar filtro de fecha/hora al cambiar de filtro tag
            if (window.currentFilters) {
                window.currentFilters.fechaHora = { fecha: '', hora: '' };
            }
            // Marcar este filtro como activo
            this.classList.add('active');

            const span = this.querySelector('span');
            if (span && span.textContent.trim() === 'Todos los filtros') {
                // Limpiar filtros globales
                if (window.currentFilters) {
                    for (let key in window.currentFilters) delete window.currentFilters[key];
                }
                // Limpiar tarjetas y recargar todos los restaurantes
                if (typeof limpiarTarjetas === 'function') limpiarTarjetas();
                await cargarRestaurantes();
            } else {
                // Limpiar todos los filtros antes de activar uno nuevo
                if (window.currentFilters) {
                    for (let key in window.currentFilters) delete window.currentFilters[key];
                }
                if (span && span.textContent.trim() === 'Ofertas especiales') {
                    window.currentFilters.tag = 1;
                } else if (span && span.textContent.trim() === 'Comida hoy') {
                    // Limpiar filtro de fecha/hora y 'Mejor valorados' si seleccionas 'Comida hoy'
                    if (window.currentFilters) {
                        window.currentFilters.fechaHora = { fecha: '', hora: '' };
                        if (window.currentFilters.tag === 3) {
                            // Quitar la clase 'active' de 'Mejor valorados'
                            filterTags.forEach(t => {
                                const s = t.querySelector('span');
                                if (s && s.textContent.trim() === 'Mejor valorados') t.classList.remove('active');
                            });
                        }
                    }
                    window.currentFilters.tag = 2;
                } else if (span && span.textContent.trim() === 'Mejor valorados') {
                    // Limpiar filtro de fecha/hora y 'Comida hoy' si seleccionas 'Mejor valorados'
                    if (window.currentFilters) {
                        window.currentFilters.fechaHora = { fecha: '', hora: '' };
                        if (window.currentFilters.tag === 2) {
                            // Quitar la clase 'active' de 'Comida hoy'
                            filterTags.forEach(t => {
                                const s = t.querySelector('span');
                                if (s && s.textContent.trim() === 'Comida hoy') t.classList.remove('active');
                            });
                        }
                    }
                    window.currentFilters.tag = 3;
                } else if (span && span.textContent.trim() === 'Tipo de cocina') {
                    if (typeof createCocinaDropdown === 'function') {
                        createCocinaDropdown(this);
                        return;
                    }
                } else if (
                    span && (
                        span.textContent.trim() === 'Fecha' ||
                        span.textContent.trim() === 'Hora'
                    )
                ) {
                    // Limpiar filtro 'Comida hoy' si seleccionas fecha/hora
                    if (window.currentFilters && window.currentFilters.tag === 2) {
                        window.currentFilters.tag = null;
                        // Quitar la clase 'active' del tag 'Comida hoy'
                        filterTags.forEach(t => {
                            const s = t.querySelector('span');
                            if (s && s.textContent.trim() === 'Comida hoy') t.classList.remove('active');
                        });
                    }
                    if (typeof createFechaHoraDropdown === 'function') {
                        createFechaHoraDropdown(this);
                        return;
                    }
                }
                if (typeof renderFilteredRestaurants === 'function') renderFilteredRestaurants();
            }
        });
    });

    // ... (resto de setupFilters original)
    console.log('setupFilters: filterOptions:', filterOptions);
    console.log('setupFilters: filterTags:', filterTags);
    const filtersContainer = document.querySelector('.filters-container');
    // Opciones de ejemplo para los desplegables
    const cocinaOptions = [
        'Andaluz',
        'Argentino',
        'Hamburguesas',
        'Internacional',
        'Italiano',
        'Japonés',
        'Mediterráneo',
        'Mexicano',
        'Peruano',
        'Tapas',
        'Vietnamita',
        'Español'
    ];
    // Estado de filtros seleccionados
    window.currentFilters = {
        cocina: null,
        personas: null,
        fechaHora: { fecha: '', hora: '' },
        tag: null
    };

    // Dropdowns personalizados
    function createCocinaDropdown(target) {
        removeDropdowns();
        const cocinaDropdown = document.createElement('div');
        cocinaDropdown.className = 'dropdown-menu dropdown-cocina';
        cocinaDropdown.innerHTML = cocinaOptions.map(opt => `<div class=\"dropdown-item\" data-cocina=\"${opt}\">${opt}</div>`).join('');
        document.body.appendChild(cocinaDropdown);
        positionDropdownBelow(target, cocinaDropdown);
        cocinaDropdown.addEventListener('click', async function(e) {
            if (e.target.classList.contains('dropdown-item')) {
                currentFilters.cocina = e.target.dataset.cocina;
                limpiarTarjetas();
                await anadirTarjeta(e.target.dataset.cocina);
                // Actualizar marcadores tras filtrar por tipo de cocina
                if (typeof updateMapMarkers === 'function') {
                    const cards = document.querySelectorAll('.restaurant-card');
                    const restaurantesFiltrados = Array.from(cards).map(card => {
                        const idx = card.dataset.idx;
                        // Aquí asumimos que renderRestaurantCard recibe el restaurante real, pero si no, deberíamos guardar la lista
                        // Si tienes acceso a la lista filtrada, pásala directamente
                        // Aquí solo por robustez:
                        return window.ultimoFiltrado && window.ultimoFiltrado[idx] ? window.ultimoFiltrado[idx] : null;
                    }).filter(r => r);
                    if (restaurantesFiltrados.length) updateMapMarkers(restaurantesFiltrados);
                }
                closeAllDropdowns();
            }
        });
    }
    function createPersDropdown(target) {
        removeDropdowns();
        const persDropdown = document.createElement('div');
        persDropdown.className = 'dropdown-menu dropdown-pers';
        persDropdown.innerHTML = `<input type=\"number\" min=\"1\" max=\"20\" value=\"2\" class=\"input-pers\" style=\"width:60px;\">`;
        document.body.appendChild(persDropdown);
        positionDropdownBelow(target, persDropdown);
        persDropdown.querySelector('.input-pers').addEventListener('change', function(e) {
            currentFilters.personas = parseInt(e.target.value);
            renderFilteredRestaurants();
            closeAllDropdowns();
        });
    }
    // Nuevo filtro unificado de fecha y hora
    function createFechaHoraDropdown(target) {
        removeDropdowns();
        const fechaHoraDropdown = document.createElement('div');
        fechaHoraDropdown.className = 'dropdown-menu dropdown-fecha-hora';
        fechaHoraDropdown.innerHTML = `
            <input type="date" class="input-fecha"> <input type="time" class="input-hora">
        `;
        document.body.appendChild(fechaHoraDropdown);
        positionDropdownBelow(target, fechaHoraDropdown);
        const inputFecha = fechaHoraDropdown.querySelector('.input-fecha');
        const inputHora = fechaHoraDropdown.querySelector('.input-hora');
        inputFecha.addEventListener('change', function() {
            if (inputFecha.value && inputHora.value) {
                currentFilters.fechaHora = {
                    fecha: inputFecha.value,
                    hora: inputHora.value
                };
                renderFilteredRestaurants();
            }
        });
        inputHora.addEventListener('change', function() {
            if (inputFecha.value && inputHora.value) {
                currentFilters.fechaHora = {
                    fecha: inputFecha.value,
                    hora: inputHora.value
                };
                renderFilteredRestaurants();
            }
        });
    }

    // Renderizado filtrado
    async function renderFilteredRestaurants() {
        if (!data) return;
        const lista = document.querySelector('.restaurants-list');
        lista.innerHTML = '';
        let filtered = data;
        // Filtrado por búsqueda de texto
        if (window.currentFilters && window.currentFilters.search && window.currentFilters.search.trim() !== '') {
            const searchValue = normalizar(window.currentFilters.search.trim());
            filtered = filtered.filter(r => {
                const tipos = (r.tipos_comida || []).map(tc => normalizar(tc.nombre)).join(' ');
                return normalizar(r.nombre).includes(searchValue) || tipos.includes(searchValue);
            });
        }
        // Filtrado por tag
        if (currentFilters.tag !== null) {
            if (currentFilters.tag === 1) { // Ofertas especiales
                filtered = filtered.filter(r => r.ofertas && r.ofertas.length > 0);
            } else if (currentFilters.tag === 2) { // Comida hoy
                filtered = filtered.filter(r => r.comidaHoy);
            } else if (currentFilters.tag === 3) { // Mejor valorados
                filtered = filtered.filter(r => r.valoraciones >= 4.5);
                filtered = filtered.sort((a, b) => b.valoraciones - a.valoraciones);
            }
            // Tag 0 = Todos los filtros (no filtra nada)
        }
        // Filtrado por cocina
        if (currentFilters.cocina) {
            function normalizar(str) {
                return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
            }
            filtered = filtered.filter(r => r.categoria && normalizar(r.categoria) === normalizar(currentFilters.cocina));
        }
        // Filtrado por personas (ejemplo: si tienes campo personasMin/personasMax en el json)
        if (currentFilters.personas) {
            filtered = filtered.filter(r => !r.personasMin || currentFilters.personas >= r.personasMin);
        }
        // Unificación de filtros: si hay fecha y hora, primero filtra por backend, luego aplica los demás filtros en frontend
        if (currentFilters.fechaHora && currentFilters.fechaHora.fecha && currentFilters.fechaHora.hora) {
            console.log('Filtrando por fecha y hora:', currentFilters.fechaHora);
            fetch(`../APIS/restaurantes_fecha.php?fecha=${encodeURIComponent(currentFilters.fechaHora.fecha)}&hora=${encodeURIComponent(currentFilters.fechaHora.hora)}`)
                .then(res => {
                    if (!res.ok) {
                        throw new Error('HTTP status ' + res.status);
                    }
                    return res.json();
                })
                .then(async restaurantes => {
                    console.log('Respuesta del backend (restaurantes_fecha.php):', restaurantes);
                    window.dataFechaHora = restaurantes; // Para inspección manual
                    let filtered = restaurantes;
                    // Si NO hay ningún filtro adicional, muestra todos los restaurantes devueltos por el backend
                    if (!window.currentFilters.search && currentFilters.tag === null && !currentFilters.cocina && !currentFilters.personas) {
                        lista.innerHTML = '';
                        if (!Array.isArray(filtered) || filtered.length === 0) {
                            lista.innerHTML = '<p>No hay restaurantes abiertos en esa fecha y hora.</p>';
                            updateMapMarkers([]);
                            return;
                        }
                        // Extrae los IDs de los restaurantes devueltos por el filtro
                        const ids = filtered.map(r => r.id);
                        if (ids.length === 0) {
                            lista.innerHTML = '<p>No hay restaurantes abiertos en esa fecha y hora.</p>';
                            updateMapMarkers([]);
                            return;
                        }
                        await mostrarRestaurantesPorIds(ids);
                        // NOTA: updateMapMarkers se debe llamar dentro de mostrarRestaurantesPorIds si se requiere
                        return;
                    }
                    // Filtrado por búsqueda de texto
                    if (window.currentFilters && window.currentFilters.search && window.currentFilters.search.trim() !== '') {
                        const searchValue = normalizar(window.currentFilters.search.trim());
                        filtered = filtered.filter(r => {
                            const tipos = (r.tipos_comida || []).map(tc => normalizar(tc.nombre)).join(' ');
                            return normalizar(r.nombre).includes(searchValue) || tipos.includes(searchValue);
                        });
                    }
                    // Filtrado por tag
                    if (currentFilters.tag !== null) {
                        if (currentFilters.tag === 1) { // Ofertas especiales
                            filtered = filtered.filter(r => r.ofertas && r.ofertas.length > 0);
                        } else if (currentFilters.tag === 2) { // Comida hoy
                            filtered = filtered.filter(r => r.comidaHoy);
                        } else if (currentFilters.tag === 3) { // Mejor valorados
                            filtered = filtered.filter(r => r.valoraciones >= 4.5);
                        }
                        // Tag 0 = Todos los filtros (no filtra nada)
                    }
                    // Filtrado por cocina
                    if (currentFilters.cocina) {
                        function normalizar(str) {
                            return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
                        }
                        filtered = filtered.filter(r => r.categoria && normalizar(r.categoria) === normalizar(currentFilters.cocina));
                    }
                    // Filtrado por personas
                    if (currentFilters.personas) {
                        filtered = filtered.filter(r => !r.personasMin || currentFilters.personas >= r.personasMin);
                    }
                    lista.innerHTML = '';
                    if (!Array.isArray(filtered) || filtered.length === 0) {
                        lista.innerHTML = '<p>No hay restaurantes abiertos en esa fecha y hora con los filtros seleccionados.</p>';
                        updateMapMarkers([]);
                        return;
                    }
                    filtered.forEach((restaurante, idx) => {
                        renderRestaurantCard(restaurante, idx);
                    });
                    setupImageNavigation();
                    updateMapMarkers(filtered);
                })
                .catch(err => {
                    console.error('Error al filtrar por fecha y hora:', err);
                    if (window.dataFechaHora) {
                        console.log('Última respuesta de restaurantes_fecha.php:', window.dataFechaHora);
                    }
                    lista.innerHTML = '<p>Error al filtrar por fecha y hora.</p>';
                    updateMapMarkers([]);
                });
            return;
        }
        // Renderizado normal si no hay filtro de fecha/hora
        filtered.forEach((restaurante, idx) => {
            renderRestaurantCard(restaurante, idx);
        });
        setupImageNavigation();
        updateMapMarkers(filtered);
    }

    // Actualiza los marcadores del mapa según los restaurantes filtrados
    function updateMapMarkers(restaurantesFiltrados) {
        if (!map || !markerLayerGroup) return;
        markerLayerGroup.clearLayers();
        markers = [];
        restaurantesFiltrados.forEach((restaurante, idx) => {
            const coords = restaurante.coordenadas || [36.7213, -4.4213];
            const customIcon = L.divIcon({
                className: 'custom-marker',
                html: `<div class=\"marker-circle\" data-marker-idx=\"${idx}\"><span class=\"marker-rating\">${restaurante.valoraciones}</span></div>`,
                iconSize: [36, 36]
            });
            const marker = L.marker(coords, {icon: customIcon}).addTo(markerLayerGroup);
            marker.on('mouseover', () => focusMarker(idx, restaurante));
            marker.on('mouseout', () => unfocusMarker(idx, restaurante));
            marker.on('click', () => {
                const slug = restaurante.nombre.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
                window.open(`PaginaRestaurante.html?idx=${restaurante.id}&slug=${slug}`,'_blank');
            });
            markers.push(marker);
        });
    }

    // Utilidad para posicionar el menú justo debajo del filtro, siempre visible
    function positionDropdownBelow(target, dropdown) {
        const rect = target.getBoundingClientRect();
        dropdown.style.display = 'block';
        dropdown.style.position = 'absolute';
        dropdown.style.left = rect.left + 'px';
        dropdown.style.top = (rect.bottom + window.scrollY) + 'px';
        dropdown.style.zIndex = 9999;
        dropdown.style.background = '#fff';
        dropdown.style.boxShadow = '0 2px 8px rgba(0,0,0,0.15)';
        dropdown.style.borderRadius = '8px';
        dropdown.style.padding = '8px 0';
        dropdown.style.minWidth = rect.width + 'px';
        dropdown.style.maxHeight = '260px';
        dropdown.style.overflowY = 'auto';
    }
    // Cerrar dropdowns al hacer click fuera
    document.addEventListener('click', function(e) {
        // Solo cerrar si el click no es en el menú ni en el filtro
        if (!e.target.closest('.filter-option') && !e.target.closest('.filter-tag') && !e.target.closest('.dropdown-menu')) {
            closeAllDropdowns();
        }
    });
    function closeAllDropdowns() {
        removeDropdowns();
    }
    function removeDropdowns() {
        document.querySelectorAll('.dropdown-menu').forEach(d => d.remove());
    }

    // Cierra dropdowns al hacer click fuera de cualquier dropdown o filtro
    document.addEventListener('mousedown', function(e) {
        if (!e.target.closest('.dropdown-menu') &&
            !e.target.closest('.filter-option') &&
            !e.target.closest('.filter-tag')) {
            removeDropdowns();
        }
    });
    // Filtrado por cocina
    if (currentFilters.cocina) {
        function normalizar(str) {
            return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
        }
        filtered = filtered.filter(r => r.categoria && normalizar(r.categoria) === normalizar(currentFilters.cocina));
    }
    
    if (currentFilters.personas) {
        filtered = filtered.filter(r => !r.personasMin || currentFilters.personas >= r.personasMin);
    }

    filtered.forEach((restaurante, idx) => {
        renderRestaurantCard(restaurante, idx);
    });
    setupImageNavigation();
    // Actualizar marcadores del mapa
    updateMapMarkers(filtered);
}

// Actualiza los marcadores del mapa según los restaurantes filtrados
function updateMapMarkers(restaurantesFiltrados) {
    if (!map || !markerLayerGroup) return;
    markerLayerGroup.clearLayers();
    markers = [];
    restauranteListForMarkers = restaurantesFiltrados;
    restaurantesFiltrados.forEach((restaurante, idx) => {
        let coords = [36.7213, -4.4213];
        if (
            restaurante.coordenadas &&
            typeof restaurante.coordenadas.latitud !== 'undefined' && restaurante.coordenadas.latitud !== null &&
            typeof restaurante.coordenadas.longitud !== 'undefined' && restaurante.coordenadas.longitud !== null
        ) {
            coords = [parseFloat(restaurante.coordenadas.latitud), parseFloat(restaurante.coordenadas.longitud)];
        }
        const customIcon = L.divIcon({
            className: 'custom-marker',
            html: `<div class=\"marker-circle\" data-marker-idx=\"${idx}\"><span class=\"marker-rating\">${restaurante.valoraciones}</span></div>`,
            iconSize: [36, 36]
        });
        const marker = L.marker(coords, {icon: customIcon}).addTo(markerLayerGroup);
        marker.on('mouseover', () => focusMarker(idx, restaurante));
        marker.on('mouseout', () => unfocusMarker(idx, restaurante));
        marker.on('click', () => {
            const slug = restaurante.nombre.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
            window.open(`PaginaRestaurante.html?idx=${restaurante.id}&slug=${slug}`,'_blank');
        });
        markers.push(marker);
    });
}

function positionDropdownBelow(target, dropdown) {
    const rect = target.getBoundingClientRect();
    dropdown.style.display = 'block';
    dropdown.style.position = 'absolute';
    dropdown.style.left = rect.left + 'px';
    dropdown.style.top = (rect.bottom + window.scrollY) + 'px';
    dropdown.style.zIndex = 9999;
    dropdown.style.background = '#fff';
    dropdown.style.boxShadow = '0 2px 8px rgba(0,0,0,0.15)';
    dropdown.style.borderRadius = '8px';
    dropdown.style.padding = '8px 0';
    dropdown.style.minWidth = rect.width + 'px';
    dropdown.style.maxHeight = '260px';
    dropdown.style.overflowY = 'auto';
}