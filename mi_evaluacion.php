<?php
include "conexion.php";
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 3) {
    header("Location: login.php");
    exit();
}

$mensaje = "";
$pestana_activa = isset($_GET['tab']) ? $_GET['tab'] : 'evaluaciones';

// Obtener ID del socio logueado
$sql_socio = "SELECT ID_Socios FROM socios WHERE ID_Usuario = " . $_SESSION['usuario_id'];
$result = $conn->query($sql_socio);
$socio = $result->fetch_assoc();
$id_socio = $socio['ID_Socios'];

// ==================== REGISTRO DE COMIDAS (solo socio) ====================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion_comida'])) {
    $id_alimento = (int)$_POST['id_alimento'];
    $fecha = $_POST['fecha_comida'];
    $gramos = (float)$_POST['gramos'];
    
    $alimento = $conn->query("SELECT Calorias_por_100g FROM alimentos WHERE ID_Alimento = $id_alimento")->fetch_assoc();
    $calorias = ($gramos * $alimento['Calorias_por_100g']) / 100;
    
    $sql = "INSERT INTO registro_comidas (ID_Socio, ID_Alimento, Fecha, Porcion_gramos, Calorias_totales) 
            VALUES ($id_socio, $id_alimento, '$fecha', $gramos, $calorias)";
    
    if ($conn->query($sql)) {
        $mensaje = '<div class="alert alert-success">Comida registrada correctamente</div>';
    } else {
        $mensaje = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
    }
}

// ==================== OBTENER DATOS ====================
// Evaluaciones del socio
$evaluaciones = $conn->query("SELECT e.*, eu.Nombre as entrenador_nombre
                              FROM evaluacion_fisica e
                              LEFT JOIN entrenadores ent ON e.ID_Entrenador = ent.ID_Entrenador
                              LEFT JOIN usuarios eu ON ent.ID_Usuario = eu.ID_Usuario
                              WHERE e.ID_Socio = $id_socio
                              ORDER BY e.Fecha_Evaluacion DESC");

// Última evaluación
$ultima = $conn->query("SELECT * FROM evaluacion_fisica WHERE ID_Socio = $id_socio ORDER BY Fecha_Evaluacion DESC LIMIT 1")->fetch_assoc();

// Comidas de hoy
$fecha_hoy = date('Y-m-d');
$comidas_hoy = $conn->query("SELECT r.*, a.Nombre as alimento_nombre, a.Calorias_por_100g 
                             FROM registro_comidas r 
                             INNER JOIN alimentos a ON r.ID_Alimento = a.ID_Alimento 
                             WHERE r.ID_Socio = $id_socio AND r.Fecha = '$fecha_hoy'
                             ORDER BY r.ID_Registro DESC");

$total_calorias_hoy = 0;
if ($comidas_hoy->num_rows > 0) {
    $suma = $conn->query("SELECT SUM(Calorias_totales) as total FROM registro_comidas WHERE ID_Socio = $id_socio AND Fecha = '$fecha_hoy'");
    $total_calorias_hoy = $suma->fetch_assoc()['total'] ?? 0;
}

// Lista de alimentos para el select
$alimentos = $conn->query("SELECT ID_Alimento, Nombre, Calorias_por_100g FROM alimentos ORDER BY Nombre");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi Evaluación Física - Sistema de Gimnasio</title>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <link href="css/gym-style.css" rel="stylesheet"/>
</head>
<body>
    <div class="page">
        <header class="navbar navbar-expand-md navbar-gym">
            <div class="container-xl">
                <a href="socio/index.php" class="navbar-brand d-flex align-items-center">
                    <img src="logo1.png" alt="GYM ADMIN" class="logo-gym me-2">
                    <span style="color: white; font-weight: 600;">GYM SOCIO</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbar-menu">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="socio/index.php"><i class="ti ti-home me-1"></i> Mi Perfil</a></li>
                        <li class="nav-item"><a class="nav-link active" href="mi_evaluacion.php"><i class="ti ti-heart-rate-monitor me-1"></i> Mi Evaluación</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php"><i class="ti ti-logout me-1"></i> Salir</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="page-wrapper">
            <div class="container-xl">
                <?php echo $mensaje; ?>
                
                <h2 class="page-title">Mi Seguimiento</h2>

                <!-- Pestañas -->
                <ul class="nav nav-tabs mb-4">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $pestana_activa == 'evaluaciones' ? 'active' : ''; ?>" 
                           href="?tab=evaluaciones">
                            <i class="ti ti-activity me-1"></i> Evaluaciones Físicas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $pestana_activa == 'comidas' ? 'active' : ''; ?>" 
                           href="?tab=comidas">
                            <i class="ti ti-apple me-1"></i> Registro de Comidas
                        </a>
                    </li>
                </ul>

                <!-- PESTAÑA: EVALUACIONES FÍSICAS -->
                <?php if ($pestana_activa == 'evaluaciones'): ?>
                    <?php if ($ultima): ?>
                    <div class="alert alert-success">
                        <strong>Última evaluación:</strong> <?php echo date('d/m/Y', strtotime($ultima['Fecha_Evaluacion'])); ?>
                    </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Historial de Evaluaciones</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter">
                                <thead>
                                    <tr><th>Fecha</th><th>Peso</th><th>Altura</th><th>IMC</th><th>% Grasa</th><th>Entrenador</th><th>Observaciones</th> </thead>
                                <tbody>
                                    <?php if ($evaluaciones->num_rows > 0): while($e = $evaluaciones->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($e['Fecha_Evaluacion'])); ?></td>
                                        <td><?php echo $e['Peso']; ?> kg</td>
                                        <td><?php echo $e['Altura']; ?> m</td>
                                        <td><?php echo $e['IMC']; ?></td>
                                        <td><?php echo $e['Porcentaje_Grasa'] ?? '—'; ?>%</td>
                                        <td><?php echo $e['entrenador_nombre'] ?? 'No asignado'; ?></td>
                                        <td><?php echo $e['Observaciones'] ?? '—'; ?></td>
                                     </tr>
                                    <?php endwhile; else: ?>
                                     <tr><td colspan="7" class="text-center">No hay evaluaciones registradas</td></tr>
                                    <?php endif; ?>
                                </tbody>
                             </table>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- PESTAÑA: REGISTRO DE COMIDAS -->
                <?php if ($pestana_activa == 'comidas'): ?>
                    <!-- Formulario para registrar comida -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Registrar Comida</h3>
                        </div>
                        <form method="POST" action="mi_evaluacion.php?tab=comidas">
                            <input type="hidden" name="accion_comida" value="1">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Fecha</label>
                                        <input type="date" name="fecha_comida" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Alimento</label>
                                        <select name="id_alimento" class="form-select" required>
                                            <option value="">Seleccionar...</option>
                                            <?php while($a = $alimentos->fetch_assoc()): ?>
                                                <option value="<?php echo $a['ID_Alimento']; ?>">
                                                    <?php echo htmlspecialchars($a['Nombre']); ?> (<?php echo $a['Calorias_por_100g']; ?> cal/100g)
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Cantidad (gramos)</label>
                                        <input type="number" step="10" name="gramos" class="form-control" placeholder="Ej: 150" required>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-guardar">Registrar Comida</button>
                            </div>
                        </form>
                    </div>

                    <!-- Listado de comidas de hoy -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h3 class="card-title">Comidas de Hoy</h3>
                            <div class="card-actions">
                                <span class="badge bg-green">Total: <?php echo round($total_calorias_hoy); ?> kcal</span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter">
                                <thead>
                                     <tr><th>Alimento</th><th>Gramos</th><th>Calorías</th><th>Comentario</th></tr>
                                </thead>
                                <tbody>
                                    <?php if ($comidas_hoy->num_rows > 0): while($c = $comidas_hoy->fetch_assoc()): ?>
                                     <tr>
                                         <td><?php echo htmlspecialchars($c['alimento_nombre']); ?></td>
                                         <td><?php echo $c['Porcion_gramos']; ?> g</td>
                                         <td><?php echo round($c['Calorias_totales']); ?> kcal</td>
                                         <td><?php echo $c['comentario_entrenador'] ?? '—'; ?></td>
                                     </tr>
                                    <?php endwhile; else: ?>
                                     <tr><td colspan="4" class="text-center">No hay comidas registradas hoy</td></tr>
                                    <?php endif; ?>
                                </tbody>
                             </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
</body>
</html>