<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require('../util/conexion.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

$sql = "SELECT 
    u.id AS usuario_id,
    u.usuario,
    r.id AS restaurante_id,
    r.nombre AS restaurante,
    h.valoracion,
    h.descripcion,
    h.fecha_visita
FROM 
    historial_visitas h
JOIN usuarios u ON h.usuario_id = u.id
JOIN restaurantes r ON h.restaurante_id = r.id
WHERE h.usuario_id = ?
ORDER BY h.fecha_visita DESC";

$stmt = $_conexion->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$resultado = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Historial de Visitas</title>
    <style>
        :root {
            --color-primary: #2c3e50;
            --color-secondary: #3498db;
            --color-bg: #f5f7fa;
            --color-card: #ffffff;
            --color-text: #333333;
            --color-border: #e0e0e0;
            --color-bad: #e74c3c;
            --color-medium: #f39c12;
            --color-good: #2ecc71;
            --color-date: #7f8c8d;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: var(--color-bg);
            color: var(--color-text);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        header {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
        }
        
        h1 {
            color: var(--color-primary);
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            position: relative;
            display: inline-block;
        }
        
        h1:after {
            content: "";
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--color-secondary);
            border-radius: 2px;
        }
        
        .subtitle {
            color: #7f8c8d;
            font-size: 1.1rem;
        }
        
        .historial-list {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        
        .visita-card {
            background: var(--color-card);
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }
        
        .visita-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--color-border);
            position: relative;
        }
        
        .restaurante-nombre {
            font-size: 1.4rem;
            font-weight: 700;
            margin: 0 0 0.5rem 0;
            color: var(--color-primary);
        }
        
        .fecha-visita {
            font-size: 0.9rem;
            color: var(--color-date);
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .fecha-visita i {
            margin-right: 0.5rem;
            font-size: 1rem;
        }
        
        .valoracion {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            font-weight: bold;
            font-size: 1.2rem;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
        }
        
        .valoracion.bad {
            background-color: var(--color-bad);
            color: white;
        }
        
        .valoracion.medium {
            background-color: var(--color-medium);
            color: white;
        }
        
        .valoracion.good {
            background-color: var(--color-good);
            color: white;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .usuario-info {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--color-secondary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 1rem;
        }
        
        .username {
            font-weight: 600;
        }
        
        .comentario {
            background: var(--color-bg);
            padding: 1rem;
            border-radius: 8px;
            font-style: italic;
            position: relative;
            margin-top: 1rem;
        }
        
        .comentario:before {
            content: "";
            position: absolute;
            top: -10px;
            left: 20px;
            font-size: 4rem;
            color: var(--color-border);
            line-height: 1;
            z-index: 0;
        }
        
        .no-historial {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--color-card);
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .no-historial-icon {
            font-size: 3rem;
            color: var(--color-secondary);
            margin-bottom: 1rem;
        }
        
        .no-historial h2 {
            color: var(--color-primary);
        }
        
        .no-historial p {
            color: #7f8c8d;
            max-width: 500px;
            margin: 0 auto 1.5rem;
        }
        
        .explore-btn {
            display: inline-block;
            padding: 0.8rem 1.8rem;
            background: var(--color-secondary);
            color: white;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .explore-btn:hover {
            background: var(--color-primary);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Modal estilos rápidos */
        #modal-valoracion {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.4);
            display: flex; align-items: center; justify-content: center;
            z-index: 9999;
        }
        #modal-valoracion .modal-box {
            background: #fff;
            padding: 2em;
            border-radius: 10px;
            min-width: 300px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.2);
        }
        #modal-valoracion .modal-box h3 { margin-top: 0; }
        #modal-valoracion .modal-box label { display:block; margin-bottom:0.5em; }
        #modal-valoracion .modal-box textarea { width:100%; min-height:60px; }
        #modal-valoracion .modal-box input[type="number"] { width:70px; }
        #modal-valoracion .modal-actions { display:flex; justify-content:flex-end; gap:1em; margin-top:1em; }
        .valoracion-success {
            position:fixed; top:30px; left:50%; transform:translateX(-50%);
            background:#4caf50; color:#fff; padding:1em 2em; border-radius:8px; z-index:10000;
            box-shadow:0 2px 8px rgba(0,0,0,0.15); font-size:1.1em;
        }
        .btn-valorar {
            display: inline-block;
            margin-top: 1.2em;
            margin-left: auto;
            background: var(--color-secondary);
            color: #fff;
            border: none;
            border-radius: 22px;
            padding: 0.6em 1.5em;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s, transform 0.2s;
            box-shadow: 0 2px 8px rgba(52,152,219,0.07);
        }
        .btn-valorar:hover {
            background: var(--color-primary);
            color: #fff;
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 6px 18px rgba(44,62,80,0.09);
        }
        .btn-valorar:disabled, .btn-valorado {
            background: #bbb;
            color: #fff;
            cursor: not-allowed;
            opacity: 0.7;
        }
        .card-body {
            display: flex;
            flex-direction: column;
            gap: 0.6em;
            padding: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Mi Historial Gastronómico</h1>
            <p class="subtitle">Todos los restaurantes que has visitado y valorado</p>
        </header>
        
        <?php if ($resultado && $resultado->num_rows > 0): ?>
            <div class="historial-list">
                <?php while ($visita = $resultado->fetch_assoc()): ?>
                    <div class="visita-card">
                        <div class="card-header">
                            <h2 class="restaurante-nombre"><?= htmlspecialchars($visita['restaurante']) ?></h2>
                            <div class="fecha-visita">
                                <i class="far fa-calendar-alt"></i>
                                <?= date('d/m/Y H:i', strtotime($visita['fecha_visita'])) ?>
                            </div>
                            <?php
                            $valoracion = $visita['valoracion'];
                            $colorClass = '';
                            if ($valoracion < 5) {
                                $colorClass = 'bad';
                            } elseif ($valoracion >= 5 && $valoracion <= 7) {
                                $colorClass = 'medium';
                            } else {
                                $colorClass = 'good';
                            }
                            ?>
                            <div class="valoracion <?= $colorClass ?>">
                                <?= $valoracion ?>/10
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="usuario-info">
                                <div class="avatar">
                                    <?= strtoupper(substr($visita['usuario'], 0, 1)) ?>
                                </div>
                                <div class="username">@<?= htmlspecialchars($visita['usuario']) ?></div>
                            </div>
                            <?php if (!empty($visita['descripcion'])): ?>
                                <div class="comentario">
                                    <?= htmlspecialchars($visita['descripcion']) ?>
                                </div>
                            <?php endif; ?>
                            <button class="btn-valorar" data-restaurante="<?= htmlspecialchars($visita['restaurante']) ?>" data-restaurante-id="<?= $visita['restaurante_id'] ?>">Valorar</button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-historial">
                <div class="no-historial-icon">🍽️</div>
                <h2>Tu historial está vacío</h2>
                <p>Aún no has visitado y valorado ningún restaurante. Cuando lo hagas, aparecerán aquí tus experiencias gastronómicas.</p>
                <a href="../index.php" class="explore-btn">Explorar restaurantes</a>
            </div>
        <?php endif; ?>
    </div>
<script>
// Modal y lógica de valoración
function crearModalValoracion(restaurante, restauranteId) {
    if(document.getElementById('modal-valoracion')) return;
    const modal = document.createElement('div');
    modal.id = 'modal-valoracion';
    modal.innerHTML = `
        <div class="modal-box">
            <h3>Valorar restaurante: <span style="color:#2a7">${restaurante}</span></h3>
            <form id="form-valoracion" autocomplete="off">
                <label>Reseña:
                    <textarea name="resena" maxlength="500" required placeholder="Escribe tu opinión..."></textarea>
                </label>
                <label>Valoración (0-10):
                    <input type="number" name="valoracion" min="0" max="10" step="0.1" required>
                </label>
                <div class="modal-actions">
                    <button type="button" id="cerrar-modal-valoracion">Cancelar</button>
                    <button type="submit">Enviar</button>
                </div>
            </form>
        </div>
    `;
    document.body.appendChild(modal);
    document.getElementById('cerrar-modal-valoracion').onclick = () => modal.remove();
    modal.onclick = (e) => { if(e.target===modal) modal.remove(); };
    document.getElementById('form-valoracion').onsubmit = function(ev) {
        ev.preventDefault();
        const formData = new FormData(ev.target);
        const resena = formData.get('resena');
        const valoracion = formData.get('valoracion');
        fetch('valorar_restaurante.php', {
            method: 'POST',
            body: JSON.stringify({
                id_restaurante: restauranteId,
                resena: resena,
                valoracion: valoracion
            }),
            headers: { 'Content-Type': 'application/json' }
        })
        .then(resp => resp.json())
        .then(data => {
            modal.remove();
            if(data.success) {
                // Actualiza la tarjeta en el DOM
                const btn = document.querySelector('.btn-valorar[data-restaurante-id="'+restauranteId+'"]');
                if(btn) {
                    // Desactivar el botón
                    btn.disabled = true;
                    btn.textContent = 'Valorado';
                    btn.classList.add('btn-valorado');
                }
                // Actualizar la valoración
                const card = btn.closest('.visita-card');
                if(card) {
                    // Actualizar el bloque valoración
                    const valoracionDiv = card.querySelector('.valoracion');
                    if(valoracionDiv) {
                        let val = parseFloat(valoracion);
                        let colorClass = '';
                        if(val < 5) colorClass = 'bad';
                        else if(val <= 7) colorClass = 'medium';
                        else colorClass = 'good';
                        valoracionDiv.textContent = val + '/10';
                        valoracionDiv.className = 'valoracion ' + colorClass;
                    }
                    // Actualizar/insertar la reseña
                    let comentario = card.querySelector('.comentario');
                    if(comentario) {
                        comentario.textContent = resena;
                    } else {
                        // Insertar debajo de username
                        const usuarioInfo = card.querySelector('.usuario-info');
                        if(usuarioInfo) {
                            const div = document.createElement('div');
                            div.className = 'comentario';
                            div.textContent = resena;
                            usuarioInfo.insertAdjacentElement('afterend', div);
                        }
                    }
                }
                mostrarValoracionSuccess();
            } else {
                alert(data.message || 'Error al guardar la valoración');
            }
        })
        .catch(() => {
            modal.remove();
            alert('Error de red al guardar la valoración');
        });
    };
}
function mostrarValoracionSuccess() {
    const notif = document.createElement('div');
    notif.className = 'valoracion-success';
    notif.textContent = '¡Has valorado el restaurante!';
    document.body.appendChild(notif);
    setTimeout(()=>notif.remove(), 2500);
}
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-valorar').forEach(btn => {
        btn.onclick = function() {
            crearModalValoracion(
                btn.getAttribute('data-restaurante'),
                btn.getAttribute('data-restaurante-id')
            );
        }
    });
});
</script>
</body>
</html>

<?php
$stmt->close();
?>