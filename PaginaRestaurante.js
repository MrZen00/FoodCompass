document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);
    const idx = parseInt(params.get('idx'));
    const slug = params.get('slug') || '';
    if (isNaN(idx)) return;

    //Buscar mesa: registrar visita (si sesión activa)
    document.getElementById('btn-buscar-mesa').addEventListener('click', function () {
        // Crear el modal del formulario
        if (document.getElementById('modal-reserva')) return; // Evitar múltiples modales
        const modal = document.createElement('div');
        modal.id = 'modal-reserva';
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.width = '100vw';
        modal.style.height = '100vh';
        modal.style.background = 'rgba(0,0,0,0.4)';
        modal.style.display = 'flex';
        modal.style.alignItems = 'center';
        modal.style.justifyContent = 'center';
        modal.style.zIndex = '9999';

        const formBox = document.createElement('div');
        formBox.style.background = '#fff';
        formBox.style.padding = '2em';
        formBox.style.borderRadius = '10px';
        formBox.style.boxShadow = '0 2px 12px rgba(0,0,0,0.2)';
        formBox.innerHTML = `
            <h3>Reservar mesa</h3>
            <form id="form-reserva" style="display:flex;flex-direction:column;gap:1em;min-width:250px;">
                <label>Número de personas:
                    <input type="number" min="1" max="20" name="personas" required style="width:100%">
                </label>
                <label>Fecha:
                    <input type="date" name="fecha" required style="width:100%">
                </label>
                <label>Hora:
                    <input type="time" name="hora" required style="width:100%">
                </label>
                <div style="display:flex;justify-content: flex-end;gap:1em;">
                    <button type="button" id="cerrar-modal-reserva">Cancelar</button>
                    <button type="submit">Enviar</button>
                </div>
            </form>
        `;
        modal.appendChild(formBox);
        document.body.appendChild(modal);

        // Cerrar modal
        document.getElementById('cerrar-modal-reserva').onclick = () => {
            modal.remove();
        };
        modal.onclick = (e) => {
            if (e.target === modal) modal.remove();
        };
        // Enviar formulario
        document.getElementById('form-reserva').onsubmit = async function(ev) {
            ev.preventDefault();
            // Obtener id restaurante de la URL
            const params = new URLSearchParams(window.location.search);
            const idx = parseInt(params.get('idx'));
            if (isNaN(idx)) {
                modal.remove();
                showNotification('error', 'No se pudo identificar el restaurante.');
                return;
            }
            // Enviar reserva
            try {
                const resp = await fetch('usuario/reservar_mesa.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ restaurante_id: idx })
                });
                const data = await resp.json();
                modal.remove();
                if(data.success) {
                    showNotification('success', '¡Reserva realizada! Ahora puedes valorar el restaurante en tu perfil.');
                } else {
                    showNotification('error', data.message || 'No se pudo registrar la reserva.');
                }
            } catch(e) {
                modal.remove();
                showNotification('error', 'Error de red al reservar.');
            }
        };
    });

    // Función para mostrar notificaciones
    function showNotification(type, message) {
        // Eliminar notificaciones anteriores si existen
        const oldNotifications = document.querySelectorAll('.notification');
        oldNotifications.forEach(notif => notif.remove());

        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Mostrar la notificación
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Ocultar después de 3 segundos
        setTimeout(() => {
            notification.classList.remove('show');
            // Eliminar después de la animación
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }

    //Cargar datos del restaurante
    fetch('https://foodcompass.es/APIS/restaurante.php')
        .then(res => res.json())
        .then(data => {
            const restaurante = data.find(r => r.id == idx);
            if (!restaurante) return;

            document.getElementById('nombre-restaurante').textContent = restaurante.nombre;
            document.querySelector('.restaurante-direccion').innerHTML = `<i class='fas fa-map-marker-alt'></i> ${restaurante.ubicacion}`;
            
            if (restaurante.tipos_comida && restaurante.tipos_comida.length > 0) {
                document.querySelector('.restaurante-categoria').textContent = restaurante.tipos_comida[0].nombre;
            } else {
                document.querySelector('.restaurante-categoria').style.display = 'none';
            }

            document.querySelector('.restaurante-precio').textContent = 'Precio medio: ' + restaurante.precio + '€';
            document.querySelector('.restaurante-opiniones').innerHTML = `<i class='fas fa-star'></i> ${restaurante.valoraciones}`;
            document.getElementById('descripcion-texto').textContent = restaurante.descripcion;

            // Carrusel de fotos
            const carruselImg = document.getElementById('carrusel-img-principal');
            const miniaturasDiv = document.getElementById('carrusel-miniaturas');
            let fotos = restaurante.imagenes || [];
            let fotoActual = 0;

            function mostrarFoto(idx) {
                if (!fotos.length) return;
                fotoActual = idx;
                carruselImg.src = fotos[idx];
                carruselImg.style.display = '';
                miniaturasDiv.querySelectorAll('.miniatura-foto').forEach((m, i) => {
                    m.classList.toggle('selected', i === idx);
                });
            }

            miniaturasDiv.innerHTML = '';
            fotos.forEach((src, i) => {
                const mini = document.createElement('img');
                mini.src = src;
                mini.alt = `${restaurante.nombre} miniatura ${i + 1}`;
                mini.className = `miniatura-foto${i === 0 ? ' selected' : ''}`;
                mini.onclick = () => mostrarFoto(i);
                miniaturasDiv.appendChild(mini);
            });

            if (fotos.length > 5) {
                const mas = document.createElement('div');
                mas.className = 'miniaturas-mas';
                mas.textContent = `+${fotos.length - 5} fotos`;
                miniaturasDiv.appendChild(mas);
            }

            document.querySelector('.carrusel-flecha.izq').onclick = () => {
                mostrarFoto((fotoActual - 1 + fotos.length) % fotos.length);
            };
            document.querySelector('.carrusel-flecha.der').onclick = () => {
                mostrarFoto((fotoActual + 1) % fotos.length);
            };
            if (fotos.length) mostrarFoto(0);

            // Opiniones
            cargarOpiniones(restaurante);

            // Horario real desde la API
            fetch(`APIS/horarios.php?restaurante_id=${encodeURIComponent(restaurante.id)}`)
                .then(res => res.json())
                .then(horarios => {
                    let horarioDiv = document.createElement('div');
                    horarioDiv.className = 'restaurante-horario';
                    horarioDiv.innerHTML = `<i class='far fa-clock'></i> <b>Horario:</b>`;
                    if (horarios && horarios.length) {
                        horarioDiv.innerHTML += `<table class='tabla-horario-restaurante'><thead><tr><th>Día</th><th>Horario</th></tr></thead><tbody>` +
                            horarios.map(h => {
                                const intervalos = h.intervalos.map(i => `${i.hora_apertura} - ${i.hora_cierre}`).join(' <span class="horario-separador">|</span> ');
                                return `<tr><td class='horario-dia'>${h.dia_semana}</td><td class='horario-horas'>${intervalos}</td></tr>`;
                            }).join('') +
                            `</tbody></table>`;
                    } else {
                        horarioDiv.innerHTML += " <span class='horario-vacio'>No hay horario disponible.</span>";
                    }
                    document.querySelector('.restaurante-detalles').appendChild(horarioDiv);
                });

            if (restaurante.opciones_dieteticas) {
                let dietDiv = document.createElement('div');
                dietDiv.className = 'restaurante-dietetico';
                dietDiv.innerHTML = `<i class='fa fa-leaf'></i> ${restaurante.opciones_dieteticas}`;
                document.querySelector('.restaurante-detalles').appendChild(dietDiv);
            }
        });

    async function cargarOpiniones(restaurante) {
        const opinionesContainer = document.getElementById('opiniones-container');
        opinionesContainer.innerHTML = '<span>Cargando reseñas...</span>';
        try {
            // Petición a la nueva API pasando restaurante_id
            const resp = await fetch(`APIS/opiniones.php?restaurante_id=${encodeURIComponent(restaurante.id)}`);
            const opiniones = await resp.json();

            if (!opiniones || !opiniones.length) {
                opinionesContainer.innerHTML = '<div class="opiniones-vacio">No hay reseñas disponibles todavía.</div>';
                return;
            }
            opinionesContainer.innerHTML = opiniones.map(descripcion => `
                <div class="opinion-item enhanced-opinion">
                    <div class="opinion-icon">❝</div>
                    <div class="opinion-comentario">${descripcion}</div>
                </div>
            `).join('');
        } catch (e) {
            opinionesContainer.innerHTML = '<div class="opiniones-error">No se pudieron cargar las reseñas.</div>';
        }
    }

    function parseHorario(horario) {
        if (!horario) return {};
        let dias = {};
        let partes = horario.split('|').map(p => p.trim());

        partes.forEach(parte => {
            let [diasStr, horasStr] = parte.split(':');
            if (!horasStr) {
                horasStr = diasStr;
                diasStr = '';
            }
            horasStr = horasStr.trim().replace(/\./g, ':');
            let horas = horasStr.split('-').map(h => h.trim());
            let diasRango = diasStr.split('a').map(d => d.trim());
            let diasSemana = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
            let diasIncluidos = [];

            if (diasRango.length === 2) {
                let i1 = diasSemana.indexOf(diasRango[0]);
                let i2 = diasSemana.indexOf(diasRango[1]);
                if (i1 !== -1 && i2 !== -1 && i2 >= i1) {
                    for(let i = i1; i <= i2; i++) diasIncluidos.push(diasSemana[i]);
                }
            } else if (diasSemana.includes(diasStr)) {
                diasIncluidos.push(diasStr);
            } else if (diasStr === '') {
                diasIncluidos = diasSemana.slice();
            }

            diasIncluidos.forEach(d => {
                if (!dias[d]) dias[d] = [];
                dias[d].push(horas);
            });
        });

        return dias;
    }

    function renderHorarioVisual(diasApertura) {
        const visualDiv = document.createElement('div');
        visualDiv.className = 'horario-visual';
        const diasSemana = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];

        diasSemana.forEach(dia => {
            const dayDiv = document.createElement('div');
            dayDiv.className = 'horario-dia';
            dayDiv.innerHTML = `<span class='horario-dia-nombre'>${dia}</span> `;

            if (diasApertura[dia]) {
                dayDiv.classList.add('abierto');
                dayDiv.innerHTML += diasApertura[dia].map(h => h.join(' - ')).join(' | ');
            } else {
                dayDiv.classList.add('cerrado');
                dayDiv.innerHTML += '<span style="color:#888">Cerrado</span>';
            }
            visualDiv.appendChild(dayDiv);
        });

        document.querySelector('.restaurante-detalles').appendChild(visualDiv);
    }

    function renderCalendario(diasApertura) {
        const calendario = document.getElementById('calendario-reserva');
        const hoy = new Date();
        let mes = hoy.getMonth();
        let year = hoy.getFullYear();
        const diasSemana = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];

        function renderMes(mes, year) {
            calendario.innerHTML = '';
            const table = document.createElement('table');
            table.className = 'tabla-calendario';
            table.innerHTML = '<tr><th>L</th><th>M</th><th>X</th><th>J</th><th>V</th><th>S</th><th>D</th></tr>';

            const firstDay = new Date(year, mes, 1);
            const lastDay = new Date(year, mes + 1, 0);
            let offset = (firstDay.getDay() + 6) % 7;
            let tr = document.createElement('tr');

            for (let i = 0; i < offset; i++) {
                tr.appendChild(document.createElement('td'));
            }

            for (let d = 1; d <= lastDay.getDate(); d++) {
                const date = new Date(year, mes, d);
                const diaNombre = diasSemana[date.getDay()];
                const td = document.createElement('td');
                td.textContent = d;

                if (diasApertura[diaNombre]) {
                    td.className = 'cal-dia-abierto';
                    td.onclick = () => seleccionarDia(date);
                } else {
                    td.className = 'cal-dia-cerrado';
                    td.style.opacity = 0.4;
                }

                if (date.toDateString() === hoy.toDateString()) {
                    td.classList.add('cal-hoy');
                }

                tr.appendChild(td);
                if ((d + offset) % 7 === 0 || d === lastDay.getDate()) {
                    table.appendChild(tr);
                    tr = document.createElement('tr');
                }
            }

            calendario.appendChild(table);

            const nav = document.createElement('div');
            nav.className = 'cal-nav';
            nav.innerHTML = `
                <button class='cal-prev'><i class='fas fa-chevron-left'></i></button>
                <span>${new Date(year, mes).toLocaleDateString('es-ES', {month: 'long', year: 'numeric'})}</span>
                <button class='cal-next'><i class='fas fa-chevron-right'></i></button>
            `;

            nav.querySelector('.cal-prev').onclick = () => {
                mes--;
                if (mes < 0) { mes = 11; year--; }
                renderMes(mes, year);
            };

            nav.querySelector('.cal-next').onclick = () => {
                mes++;
                if (mes > 11) { mes = 0; year++; }
                renderMes(mes, year);
            };

            calendario.prepend(nav);
        }

        function seleccionarDia(date) {
            const horasDiv = document.querySelector('.reserva-horas');
            const diaNombre = diasSemana[date.getDay()];

            document.querySelectorAll('.tabla-calendario td').forEach(td => {
                td.classList.remove('cal-seleccionado');
            });

            const diaSeleccionado = [...document.querySelectorAll('.tabla-calendario td')]
                .find(td => td.textContent == date.getDate());

            if (diaSeleccionado) diaSeleccionado.classList.add('cal-seleccionado');

            horasDiv.innerHTML = '';
            if (diasApertura[diaNombre]) {
                diasApertura[diaNombre].forEach(horasRango => {
                    const [inicio, fin] = horasRango;
                    const [h1, m1] = inicio.split(':').map(Number);
                    const [h2, m2] = fin.split(':').map(Number);
                    const t1 = h1 * 60 + m1, t2 = h2 * 60 + m2;

                    for (let t = t1; t < t2; t += 30) {
                        const h = Math.floor(t / 60), m = t % 60;
                        const hStr = `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`;

                        if (t + 30 <= t2) {
                            const btn = document.createElement('button');
                            btn.className = 'hora-btn';
                            btn.textContent = hStr;
                            horasDiv.appendChild(btn);
                        }
                    }
                });
            } else {
                horasDiv.innerHTML = '<span style="color:#888">Cerrado este día</span>';
            }
        }

        renderMes(mes, year);
    }

    // Tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.querySelectorAll('.tab-content').forEach(tab => tab.style.display = 'none');
            document.getElementById(btn.dataset.tab).style.display = 'block';
        });
    });
});