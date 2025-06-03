<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
require('../util/conexion.php');

// Verificar si el administrador está logueado
$admin_id = null;
if (isset($_SESSION['admin_id'])) {
    $admin_id = $_SESSION['admin_id'];
}

$admin_usuario = '';
if (isset($_SESSION['admin_usuario'])) {
    $admin_usuario = $_SESSION['admin_usuario'];
}

// Si no hay sesión de administrador, redirigir al login
if (!$admin_id) {
    header("Location: https://foodcompass.es/admin/adminLog.php");
    exit(); // Importante terminar la ejecución después de redirigir
}

// Inicializar variables
$mensaje = '';
$error = '';
$restaurantes = [];
$restaurante_editar = null;

// Procesar acciones (añadir, editar, eliminar)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];
        
        $nombre = $_POST['nombre'] ?? '';
        $precio = $_POST['precio'] ?? '';
        $valoraciones = !empty($_POST['valoraciones']) ? $_POST['valoraciones'] : null;
        $ubicacion = $_POST['ubicacion'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $latitud = !empty($_POST['latitud']) ? $_POST['latitud'] : null;
        $longitud = !empty($_POST['longitud']) ? $_POST['longitud'] : null;

        // Validar valoración (1-10)
        if ($valoraciones !== null && ($valoraciones < 1 || $valoraciones > 10)) {
            $error = "La valoración debe estar entre 1 y 10";
        } else {
            try {
                if ($accion == 'añadir') {
                    $stmt = $_conexion->prepare("INSERT INTO restaurantes (nombre, precio, valoraciones, ubicacion, descripcion, latitud, longitud) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sdsssss", $nombre, $precio, $valoraciones, $ubicacion, $descripcion, $latitud, $longitud);
                    $stmt->execute();
                    $mensaje = "Restaurante añadido correctamente.";
                }

                if ($accion == 'editar') {
                    $id = $_POST['id'];
                    $stmt = $_conexion->prepare("UPDATE restaurantes SET nombre = ?, precio = ?, valoraciones = ?, ubicacion = ?, descripcion = ?, latitud = ?, longitud = ? WHERE id = ?");
                    $stmt->bind_param("sdsssssi", $nombre, $precio, $valoraciones, $ubicacion, $descripcion, $latitud, $longitud, $id);
                    $stmt->execute();
                    $mensaje = "Restaurante actualizado correctamente.";
                }

                if ($accion == 'eliminar') {
                    $id = $_POST['id'];
                    
                    try {
                        // Primero eliminar horarios
                        $stmt = $_conexion->prepare("DELETE FROM horarios_restaurante WHERE restaurante_id = ?");
                        $stmt->bind_param("i", $id);
                        $stmt->execute();
                        
                        // Luego eliminar el restaurante
                        $stmt = $_conexion->prepare("DELETE FROM restaurantes WHERE id = ?");
                        $stmt->bind_param("i", $id);
                        $stmt->execute();
                        
                        $mensaje = "Restaurante eliminado correctamente.";
                    } catch (Exception $e) {
                        $error = "Error al eliminar restaurante: " . $e->getMessage();
                    }
                }
            } catch (Exception $e) {
                $error = "Error al ejecutar acción: " . $e->getMessage();
            }
        }
    }
}

// Obtener lista de restaurantes
$query = "SELECT * FROM restaurantes ORDER BY nombre";
$result = $_conexion->query($query);
if ($result) {
    $restaurantes = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
} else {
    $error = "Error al obtener restaurantes: " . $_conexion->error;
}

// Obtener datos para editar
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $id = $_GET['editar'];
    $stmt = $_conexion->prepare("SELECT * FROM restaurantes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $restaurante_editar = $result->fetch_assoc();
    $stmt->close();
}

// Configuración del formulario
$form_titulo = $restaurante_editar ? 'Editar' : 'Añadir';
$form_accion = $restaurante_editar ? 'editar' : 'añadir';
$boton_texto = $restaurante_editar ? 'Actualizar' : 'Añadir';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Restaurantes</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 20px;
        }
        h1 {
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        .mensaje {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
        }
        .exito {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #3498db;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #e9f7fe;
        }
        .formulario {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #e1e1e1;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        input[type="text"], 
        input[type="number"], 
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border 0.3s;
        }
        input[type="text"]:focus, 
        input[type="number"]:focus, 
        textarea:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 5px rgba(52,152,219,0.3);
        }
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
            margin-right: 10px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background-color: #2980b9;
        }
        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }
        .btn-danger:hover {
            background-color: #c0392b;
        }
        .btn-warning {
            background-color: #f39c12;
            color: white;
        }
        .btn-warning:hover {
            background-color: #d35400;
        }
        .btn-success {
            background-color: #2ecc71;
            color: white;
        }
        .btn-success:hover {
            background-color: #27ae60;
        }
        .acciones {
            white-space: nowrap;
        }
        .acciones form {
            display: inline-block;
        }
        .ubicacion-corta {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Administración de Restaurantes</h1>

        <?php if (!empty($mensaje)): ?>
            <div class='mensaje exito'><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class='mensaje error'><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="formulario">
            <h2><?php echo $form_titulo; ?> Restaurante</h2>
            <form method="post" id="form-restaurante">
                <input type="hidden" name="accion" value="<?php echo $form_accion; ?>">
                <?php if ($restaurante_editar): ?>
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($restaurante_editar['id']); ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" required 
                           value="<?php echo htmlspecialchars($restaurante_editar['nombre'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="precio">Precio (cualquier número entero):</label>
                    <input type="number" id="precio" name="precio" min="1" step="1" required 
                           value="<?php echo htmlspecialchars($restaurante_editar['precio'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="valoraciones">Valoraciones (1-10):</label>
                    <input type="number" id="valoraciones" name="valoraciones" min="1" max="10" step="0.1" 
                           value="<?php echo htmlspecialchars($restaurante_editar['valoraciones'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="ubicacion">Ubicación:</label>
                    <input type="text" id="ubicacion" name="ubicacion" required 
                           value="<?php echo htmlspecialchars($restaurante_editar['ubicacion'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion" rows="4" required><?php 
                        echo htmlspecialchars($restaurante_editar['descripcion'] ?? ''); 
                    ?></textarea>
                </div>

                <div class="form-group">
                    <label for="latitud">Latitud:</label>
                    <input type="text" id="latitud" name="latitud" 
                           value="<?php echo htmlspecialchars($restaurante_editar['latitud'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="longitud">Longitud:</label>
                    <input type="text" id="longitud" name="longitud" 
                           value="<?php echo htmlspecialchars($restaurante_editar['longitud'] ?? ''); ?>">
                </div>

                <button type="submit" class="btn btn-primary"><?php echo $boton_texto; ?></button>
                <?php if ($restaurante_editar): ?>
                    <a href="https://foodcompass.es/admin/editarRestaurantes.php" class="btn btn-warning" id="btn-cancelar">Cancelar</a>
                <?php endif; ?>
            </form>
        </div>

        <h2>Lista de Restaurantes</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Precio</th>
                    <th>Valoración</th>
                    <th>Ubicación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($restaurantes)): ?>
                    <tr><td colspan="6" style="text-align: center;">No hay restaurantes registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($restaurantes as $r): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['id']); ?></td>
                            <td><?php echo htmlspecialchars($r['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($r['precio']); ?></td>
                            <td>
                                <?php if ($r['valoraciones'] !== null): ?>
                                    <?php echo htmlspecialchars($r['valoraciones']); ?>
                                    <small>(<?php echo str_repeat('★', round($r['valoraciones'])); ?>)</small>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="ubicacion-corta" title="<?php echo htmlspecialchars($r['ubicacion']); ?>">
                                <?php echo htmlspecialchars(mb_strimwidth($r['ubicacion'], 0, 30, '...')); ?>
                            </td>
                            <td class="acciones">
                                <a href="?editar=<?php echo htmlspecialchars($r['id']); ?>" class="btn btn-success">Editar</a>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($r['id']); ?>">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('¿Estás seguro de eliminar este restaurante?')">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        // Limpiar formulario al cancelar
        document.getElementById('btn-cancelar')?.addEventListener('click', function() {
            document.getElementById('form-restaurante').reset();
        });
    </script>
</body>
</html>