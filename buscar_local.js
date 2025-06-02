// buscar_local.js: Búsqueda local en los datos ya cargados (array data)
// Usa mostrarRestaurantesPorIds(ids) para mostrar los resultados

function buscarPorCamposLocales(term, data) {
    if (!term || !data || !Array.isArray(data)) return;
    const normalizar = str => (str || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    const t = normalizar(term);
    // Busca en nombre, categoria, descripcion, ubicacion, tipos_comida
    const ids = data.filter(r => {
        if (!r) return false;
        if (normalizar(r.nombre).includes(t)) return true;
        if (normalizar(r.categoria).includes(t)) return true;
        if (normalizar(r.descripcion).includes(t)) return true;
        if (normalizar(r.ubicacion).includes(t)) return true;
        if (Array.isArray(r.tipos_comida) && r.tipos_comida.some(tc => normalizar(tc.nombre).includes(t))) return true;
        return false;
    }).map(r => r.id);
    if (ids.length > 0 && typeof mostrarRestaurantesPorIds === 'function') {
        mostrarRestaurantesPorIds(ids);
    } else {
        const lista = document.querySelector('.restaurants-list');
        if (lista) lista.innerHTML = '<p>No se encontraron restaurantes locales para esa búsqueda.</p>';
    }
}
