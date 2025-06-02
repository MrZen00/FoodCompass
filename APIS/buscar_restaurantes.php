<?php
header('Content-Type: application/json');
require('../util/conexion.php');

// Recibe el término de búsqueda por GET o POST
$palabra = isset($_GET['q']) ? $_GET['q'] : (isset($_POST['q']) ? $_POST['q'] : '');
$palabra = trim($palabra);

if ($palabra === '') {
    echo json_encode([]);
    exit;
}

// Prepara la consulta
$sql = "
    SELECT r.*
    FROM restaurantes r
    LEFT JOIN restaurante_tipo_comida rtc ON r.id = rtc.restaurante_id
    LEFT JOIN tipos_comida tc ON rtc.tipo_comida_id = tc.id
    WHERE r.nombre LIKE ? OR tc.nombre LIKE ?
    GROUP BY r.id
    LIMIT 0, 25
";
$stmt = $_conexion->prepare($sql);
$like = '%' . $palabra . '%';
$stmt->bind_param('ss', $like, $like);
$stmt->execute();
$result = $stmt->get_result();

$restaurantes = [];
while ($restaurante = $result->fetch_assoc()) {
    // Decodifica imágenes si existe, si no, array vacío
    $imagenes = isset($restaurante['imagenes']) ? json_decode($restaurante['imagenes'], true) ?: [] : [];
    
    // Obtiene los tipos de comida para cada restaurante
    $sqlTipos = "SELECT tc.id, tc.nombre 
                 FROM tipos_comida tc
                 INNER JOIN restaurante_tipo_comida rtc ON tc.id = rtc.tipo_comida_id
                 WHERE rtc.restaurante_id = ?";
    $stmtTipos = $_conexion->prepare($sqlTipos);
    $stmtTipos->bind_param('i', $restaurante['id']);
    $stmtTipos->execute();
    $resultTipos = $stmtTipos->get_result();
    $tiposComida = [];
    while ($tipo = $resultTipos->fetch_assoc()) {
        $tiposComida[] = $tipo;
    }
    $stmtTipos->close();

    // Asegura que todas las claves existen y no da warnings
    // Si falta latitud o longitud, consulta la tabla restaurantes
    if (!isset($restaurante['latitud']) || !isset($restaurante['longitud'])) {
        $sqlCoord = "SELECT latitud, longitud FROM restaurantes WHERE id = ? LIMIT 1";
        $stmtCoord = $_conexion->prepare($sqlCoord);
        $stmtCoord->bind_param('i', $restaurante['id']);
        $stmtCoord->execute();
        $resultCoord = $stmtCoord->get_result();
        $coord = $resultCoord->fetch_assoc();
        $restaurante['latitud'] = $coord['latitud'];
        $restaurante['longitud'] = $coord['longitud'];
    }
    $restaurantes[] = [
        'id' => $restaurante['id'],
        'nombre' => $restaurante['nombre'],
        'precio' => $restaurante['precio'],
        'valoraciones' => $restaurante['valoraciones'],
        'ubicacion' => $restaurante['ubicacion'],
        'descripcion' => $restaurante['descripcion'],
        'categoria' => $restaurante['categoria'] ?? null,
        'coordenadas' => [
            'latitud' => $restaurante['latitud'],
            'longitud' => $restaurante['longitud']
        ],
        'imagenes' => $imagenes,
        'tipos_comida' => $tiposComida
    ];
}
$stmt->close();

// Devuelve solo JSON puro
echo json_encode($restaurantes, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
$_conexion->close();