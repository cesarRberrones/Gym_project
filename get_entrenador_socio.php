<?php
include "conexion.php";
header('Content-Type: application/json');

$socio_id = isset($_GET['socio_id']) ? (int)$_GET['socio_id'] : 0;

if ($socio_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de socio inválido']);
    exit();
}

$sql = "SELECT e.ID_Entrenador, u.Nombre as entrenador_nombre
        FROM planes_rutinas pr
        INNER JOIN entrenadores e ON pr.ID_Entrenador = e.ID_Entrenador
        INNER JOIN usuarios u ON e.ID_Usuario = u.ID_Usuario
        WHERE pr.ID_Socio = $socio_id
        ORDER BY pr.Fecha_Asignacion DESC
        LIMIT 1";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'entrenador_id' => $row['ID_Entrenador'],
        'entrenador_nombre' => $row['entrenador_nombre']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No tiene entrenador asignado'
    ]);
}
?>