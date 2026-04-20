<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "conexion.php";
session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

//obtener ID del socio
$id_socio = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_socio == 0) {
    header("Location: socios.php");
    exit();
}

//obtener datos del socio con JOIN
$sql = "SELECT s.*, u.Nombre, u.Email, u.Telefono, u.foto, u.Fecha_Registro,
               (SELECT COUNT(*) FROM socio_membresia WHERE ID_Socio = s.ID_Socios) as total_membresias,
               (SELECT COUNT(*) FROM asistencias WHERE ID_Socio = s.ID_Socios) as total_asistencias,
               (SELECT COUNT(*) FROM reservas_clases WHERE ID_Socio = s.ID_Socios) as total_clases,
               (SELECT SUM(Monto) FROM ventas_pagos WHERE ID_Socio = s.ID_Socios) as total_gastado
        FROM socios s
        INNER JOIN usuarios u ON s.ID_Usuario = u.ID_Usuario
        WHERE s.ID_Socios = $id_socio";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header("Location: socios.php");
    exit();
}

$socio = $result->fetch_assoc();

//obtener membresías del socio
$sql_membresias = "SELECT sm.*, tm.Nombre as membresia_nombre, tm.Duracion_Dias, tm.Precio
                   FROM socio_membresia sm
                   INNER JOIN tipos_membresia tm ON sm.ID_TipoMembresía = tm.ID_TipoMembresía
                   WHERE sm.ID_Socio = $id_socio
                   ORDER BY sm.Fecha_Inicio DESC";
$membresias = $conn->query($sql_membresias);

//obtener ultimas asistencias
$sql_asistencias = "SELECT * FROM asistencias 
                    WHERE ID_Socio = $id_socio 
                    ORDER BY Fecha_Hora DESC 
                    LIMIT 10";
$asistencias = $conn->query($sql_asistencias);

//obtener ultimas clases reservadas
$sql_clases = "SELECT rc.*, c.Nombre as clase_nombre, cp.Fecha, cp.Hora_Inicio
               FROM reservas_clases rc
               INNER JOIN clases_programadas cp ON rc.ID_Clase_Programada = cp.ID_Clase_Programada
               INNER JOIN clases c ON cp.ID_Clase = c.ID_Clase
               WHERE rc.ID_Socio = $id_socio
               ORDER BY cp.Fecha DESC
               LIMIT 5";
$clases = $conn->query($sql_clases);

// Obtener plan/rutina activa del socio
$sql_plan = "SELECT p.*, u.Nombre as entrenador_nombre
             FROM planes_rutinas p
             LEFT JOIN entrenadores e ON p.ID_Entrenador = e.ID_Entrenador
             LEFT JOIN usuarios u ON e.ID_Usuario = u.ID_Usuario
             WHERE p.ID_Socio = $id_socio
             ORDER BY p.Fecha_Asignacion DESC
             LIMIT 1";
$plan_activo = $conn->query($sql_plan)->fetch_assoc();

// Obtener ejercicios del plan
$ejercicios_plan = [];
if ($plan_activo) {
    $ejercicios = $conn->query("SELECT d.*, e.Nombre as ejercicio_nombre, e.Descripcion as ejercicio_desc
                                FROM plan_detalle d
                                INNER JOIN ejercicios e ON d.ID_Ejercicio = e.ID_Ejercicio
                                WHERE d.ID_Plan = " . $plan_activo['ID_Plan'] . "
                                ORDER BY d.Orden");
    while($row = $ejercicios->fetch_assoc()) {
        $ejercicios_plan[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detalle del Socio - Sistema de Gimnasio</title>
    
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <link href="css/gym-style.css" rel="stylesheet"/>
</head>
<body>
    <div class="page">
        <!-- HEADER DINÁMICO SEGÚN ROL -->
        <header class="navbar navbar-expand-md navbar-gym">
            <div class="container-xl">
                <a href="index.php" class="navbar-brand d-flex align-items-center">
                    <img src="logo1.png" alt="GYM ADMIN" class="logo-gym me-2">
                    <span style="color: white; font-weight: 600;">
                        <?php if ($_SESSION['rol'] == 1): ?>
                            GYM ADMIN
                        <?php elseif ($_SESSION['rol'] == 2): ?>
                            GYM ENTRENADOR
                        <?php elseif ($_SESSION['rol'] == 3): ?>
                            GYM SOCIO
                        <?php else: ?>
                            GYM CAJA
                        <?php endif; ?>
                    </span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbar-menu">
                    <ul class="navbar-nav ms-auto">
                        <?php if ($_SESSION['rol'] == 1): ?>
                            <!-- Menú ADMIN -->
                            <li class="nav-item"><a class="nav-link" href="index.php"><i class="ti ti-dashboard me-1"></i> Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link" href="membresias.php"><i class="ti ti-cards me-1"></i> Membresías</a></li>
                            <li class="nav-item"><a class="nav-link" href="socios.php"><i class="ti ti-users me-1"></i> Socios</a></li>
                            <li class="nav-item"><a class="nav-link" href="evaluaciones.php"><i class="ti ti-heart-rate-monitor me-1"></i> Evaluaciones</a></li>
                            <li class="nav-item"><a class="nav-link" href="rutinas_planes.php"><i class="ti ti-clipboard-list me-1"></i> Rutinas</a></li>
                            <li class="nav-item"><a class="nav-link" href="clases.php"><i class="ti ti-calendar me-1"></i> Clases</a></li>
                            <li class="nav-item"><a class="nav-link" href="pos.php"><i class="ti ti-shopping-cart me-1"></i> POS</a></li>
                            <li class="nav-item"><a class="nav-link" href="logout.php"><i class="ti ti-logout me-1"></i> Salir</a></li>
                        <?php elseif ($_SESSION['rol'] == 2): ?>
                            <!-- Menú ENTRENADOR -->
                            <li class="nav-item"><a class="nav-link" href="entrenador/index.php"><i class="ti ti-dashboard me-1"></i> Mis Socios</a></li>
                            <li class="nav-item"><a class="nav-link" href="evaluaciones.php"><i class="ti ti-heart-rate-monitor me-1"></i> Evaluaciones</a></li>
                            <li class="nav-item"><a class="nav-link" href="rutinas_planes.php"><i class="ti ti-clipboard-list me-1"></i> Planes</a></li>
                            <li class="nav-item"><a class="nav-link active" href="socio_detalle.php?id=<?php echo $id_socio; ?>"><i class="ti ti-user me-1"></i> Perfil Socio</a></li>
                            <li class="nav-item"><a class="nav-link" href="logout.php"><i class="ti ti-logout me-1"></i> Salir</a></li>
                        <?php elseif ($_SESSION['rol'] == 3): ?>
                            <!-- Menú SOCIO -->
                            <li class="nav-item"><a class="nav-link" href="socio/index.php"><i class="ti ti-home me-1"></i> Mi Perfil</a></li>
                            <li class="nav-item"><a class="nav-link" href="mi_evaluacion.php"><i class="ti ti-heart-rate-monitor me-1"></i> Mi Evaluación</a></li>
                            <li class="nav-item"><a class="nav-link" href="logout.php"><i class="ti ti-logout me-1"></i> Salir</a></li>
                        <?php else: ?>
                            <!-- Menú CAJA -->
                            <li class="nav-item"><a class="nav-link" href="pos.php"><i class="ti ti-shopping-cart me-1"></i> Punto de Venta</a></li>
                            <li class="nav-item"><a class="nav-link" href="logout.php"><i class="ti ti-logout me-1"></i> Salir</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </header>

        <div class="page-wrapper">
            <div class="container-xl">
                <!--título y botón de volver -->
                <div class="page-header d-print-none mb-4">
                    <div class="row align-items-center">
                        <div class="col">
                            <h2 class="page-title">
                                <i class="ti ti-user me-2" style="color: var(--gym-verde);"></i>
                                Detalle del Socio
                            </h2>
                        </div>
                        <div class="col-auto">
    <?php if ($_SESSION['rol'] == 1): ?>
        <a href="socios.php" class="btn btn-modificar">
            <i class="ti ti-arrow-left me-1"></i>
            Volver a Socios
        </a>
    <?php elseif ($_SESSION['rol'] == 2): ?>
        <a href="entrenador/index.php" class="btn btn-modificar">
            <i class="ti ti-arrow-left me-1"></i>
            Volver a Mis Socios
        </a>
    <?php else: ?>
        <a href="socio/index.php" class="btn btn-modificar">
            <i class="ti ti-arrow-left me-1"></i>
            Volver a Mi Perfil
        </a>
    <?php endif; ?>
</div>
                    </div>
                </div>

                <!--tarjeta de perfil -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <!--foto del socio -->
                                <div class="mb-3">
                                    <?php
                                    $foto = $socio['foto'] ?? 'default.jpg';
                                    $foto_path = "uploads/" . $foto;
                                    if (!file_exists($foto_path)) {
                                        $foto_path = "uploads/default.jpg";
                                    }
                                    ?>
                                    <img src="<?php echo $foto_path; ?>" alt="Foto de perfil" 
                                         class="rounded-circle" style="width: 150px; height: 150px; object-fit: cover; border: 3px solid var(--gym-verde);">
                                </div>
                                
                                <h3 class="mb-1"><?php echo htmlspecialchars($socio['Nombre']); ?></h3>
                                <p class="text-muted mb-3">Socio #<?php echo str_pad($socio['ID_Socios'], 3, '0', STR_PAD_LEFT); ?></p>
                                
                                <!--botón para cambiar foto -->
                                <button class="btn btn-sm btn-modificar" onclick="cambiarFoto(<?php echo $socio['ID_Usuario']; ?>)">
                                    <i class="ti ti-camera me-1"></i>
                                    Cambiar foto
                                </button>
                            </div>
                        </div>

                        <!--información personal -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h3 class="card-title">Información Personal</h3>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <th><i class="ti ti-mail me-2"></i>Email:</th>
                                        <td><?php echo htmlspecialchars($socio['Email']); ?></td>
                                    </tr>
                                    <tr>
                                        <th><i class="ti ti-phone me-2"></i>Teléfono:</th>
                                        <td><?php echo htmlspecialchars($socio['Telefono']); ?></td>
                                    </tr>
                                    <tr>
                                        <th><i class="ti ti-cake me-2"></i>Nacimiento:</th>
                                        <td><?php echo date('d/m/Y', strtotime($socio['Fecha_Nacimiento'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th><i class="ti ti-gender-<?php echo $socio['Genero'] == 'M' ? 'male' : ($socio['Genero'] == 'F' ? 'female' : 'other'); ?> me-2"></i>Género:</th>
                                        <td><?php echo $socio['Genero'] == 'M' ? 'Hombre' : ($socio['Genero'] == 'F' ? 'Mujer' : 'Otro'); ?></td>
                                    </tr>
                                    <tr>
                                        <th><i class="ti ti-map-pin me-2"></i>Dirección:</th>
                                        <td><?php echo htmlspecialchars($socio['Direccion'] ?? 'No especificada'); ?></td>
                                    </tr>
                                    <tr>
                                        <th><i class="ti ti-calendar me-2"></i>Registro:</th>
                                        <td><?php echo date('d/m/Y', strtotime($socio['Fecha_Registro'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <!-- tarjetas de estadísticas -->
                        <div class="row">
                            <div class="col-md-3">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="stat-value"><?php echo $socio['total_membresias']; ?></div>
                                        <div class="stat-label">Membresías</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="stat-value"><?php echo $socio['total_asistencias']; ?></div>
                                        <div class="stat-label">Asistencias</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="stat-value"><?php echo $socio['total_clases']; ?></div>
                                        <div class="stat-label">Clases</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="stat-value">$<?php echo number_format($socio['total_gastado'] ?? 0, 0); ?></div>
                                        <div class="stat-label">Gastado</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!--historial de membresías -->
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">Historial de Membresías</h3>
        <?php if ($_SESSION['rol'] == 1): ?>
        <div class="card-actions">
            <button class="btn btn-sm btn-guardar" onclick="asignarMembresia(<?php echo $socio['ID_Socios']; ?>)">
                <i class="ti ti-plus me-1"></i>
                Asignar
            </button>
        </div>
        <?php endif; ?>
    </div>
                            <div class="table-responsive">
                                <table class="table table-vcenter card-table">
                                    <thead>
                                        <tr>
                                            <th>Membresía</th>
                                            <th>Inicio</th>
                                            <th>Fin</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($membresias->num_rows > 0) { 
                                            while($m = $membresias->fetch_assoc()) {
                                                $hoy = date('Y-m-d');
                                                $estado = ($m['Fecha_Fin'] >= $hoy) ? 'Activa' : 'Vencida';
                                                $clase = ($estado == 'Activa') ? 'estado-activo' : 'estado-inactivo';
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($m['membresia_nombre']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($m['Fecha_Inicio'])); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($m['Fecha_Fin'])); ?></td>
                                            <td><span class="<?php echo $clase; ?>"><?php echo $estado; ?></span></td>
                                        </tr>
                                        <?php } 
                                        } else { ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Sin membresías asignadas</td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!--ultimas asistencias -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h3 class="card-title">Últimas Asistencias</h3>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-vcenter card-table">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Hora</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($asistencias->num_rows > 0) { 
                                            while($a = $asistencias->fetch_assoc()) {
                                        ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($a['Fecha_Hora'])); ?></td>
                                            <td><?php echo date('H:i', strtotime($a['Fecha_Hora'])); ?></td>
                                        </tr>
                                        <?php } 
                                        } else { ?>
                                        <tr>
                                            <td colspan="2" class="text-center">Sin asistencias registradas</td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- proximas clases -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h3 class="card-title">Próximas Clases</h3>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-vcenter card-table">
                                    <thead>
                                        <tr>
                                            <th>Clase</th>
                                            <th>Fecha</th>
                                            <th>Hora</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($clases && $clases->num_rows > 0) { 
                                            while($c = $clases->fetch_assoc()) {
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($c['clase_nombre']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($c['Fecha'])); ?></td>
                                            <td><?php echo date('H:i', strtotime($c['Hora_Inicio'])); ?> hrs</td>
                                        </tr>
                                        <?php } 
                                        } else { ?>
                                        <tr>
                                            <td colspan="3" class="text-center">Sin clases reservadas</td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Plan de Entrenamiento Activo -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ti ti-clipboard-list me-2" style="color: var(--gym-verde);"></i>
                                    Plan de Entrenamiento Activo
                                </h3>
                                <?php if ($_SESSION['rol'] == 1 || $_SESSION['rol'] == 2): ?>
                                <div class="card-actions">
                                    <a href="rutinas_planes.php?edit_plan=<?php echo $plan_activo['ID_Plan']; ?>" class="btn btn-sm btn-modificar">
                                        <i class="ti ti-edit me-1"></i> Editar Plan
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <?php if ($plan_activo && isset($plan_activo['Nombre_Plan'])): ?>
                                    <div class="alert alert-info">
                                        <strong><?php echo htmlspecialchars($plan_activo['Nombre_Plan']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($plan_activo['Descripcion'] ?? ''); ?></small><br>
                                        <?php if (isset($plan_activo['Objetivo'])): ?>
                                        <span class="badge bg-success mt-2">
                                            <?php 
                                            if ($plan_activo['Objetivo'] == 'perdida_peso') echo '🏋️ Pérdida de peso';
                                            elseif ($plan_activo['Objetivo'] == 'ganancia_muscular') echo '💪 Ganancia muscular';
                                            else echo '🏃 Resistencia';
                                            ?>
                                        </span>
                                        <?php endif; ?>
                                        <small class="d-block text-muted mt-2">Asignado por: <?php echo htmlspecialchars($plan_activo['entrenador_nombre'] ?? 'No asignado'); ?></small>
                                    </div>
                                    
                                    <?php if (!empty($ejercicios_plan)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-vcenter">
                                            <thead>
                                                <tr><th>Ejercicio</th><th>Series</th><th>Repeticiones</th><th>Descanso</th>
                                            </thead>
                                            <tbody>
                                                <?php foreach($ejercicios_plan as $ej): ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($ej['ejercicio_nombre']); ?></strong><br><small><?php echo htmlspecialchars($ej['ejercicio_desc'] ?? ''); ?></small></td>
                                                    <td><?php echo $ej['Series']; ?></td>
                                                    <td><?php echo $ej['Repeticiones']; ?></td>
                                                    <td><?php echo $ej['Descanso_segundos']; ?> seg</td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php else: ?>
                                    <div class="alert alert-warning">Este plan aún no tiene ejercicios asignados.</div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="alert alert-secondary">
                                        No hay un plan de entrenamiento activo para este socio.
                                        <?php if ($_SESSION['rol'] == 1 || $_SESSION['rol'] == 2): ?>
                                        <a href="rutinas_planes.php" class="btn btn-sm btn-guardar ms-2">Asignar Plan</a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Evaluaciones Físicas y Registro de Comidas -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h3 class="card-title">Evaluaciones Físicas y Registro de Comidas</h3>
                            </div>
                            <div class="card-body">
                                <ul class="nav nav-tabs" id="evaluacionesTab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="evaluaciones-tab" data-bs-toggle="tab" data-bs-target="#evaluaciones" type="button" role="tab">Evaluaciones Físicas</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="comidas-tab" data-bs-toggle="tab" data-bs-target="#comidas" type="button" role="tab">Registro de Comidas</button>
                                    </li>
                                </ul>
                                <div class="tab-content mt-3">
                                    <!-- Pestaña Evaluaciones Físicas -->
                                    <div class="tab-pane fade show active" id="evaluaciones" role="tabpanel">
                                        <?php
                                        $evaluaciones = $conn->query("SELECT * FROM evaluacion_fisica WHERE ID_Socio = $id_socio ORDER BY Fecha_Evaluacion DESC");
                                        if($evaluaciones->num_rows > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-vcenter">
                                                <thead>
                                                    <tr><th>Fecha</th><th>Peso</th><th>Altura</th><th>IMC</th><th>% Grasa</th><th>Cintura</th><th>Observaciones</th>
                                                </thead>
                                                <tbody>
                                                <?php while($e = $evaluaciones->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo date('d/m/Y', strtotime($e['Fecha_Evaluacion'])); ?></td>
                                                    <td><?php echo $e['Peso']; ?> kg</td>
                                                    <td><?php echo $e['Altura']; ?> m</td>
                                                    <td><?php echo $e['IMC']; ?></td>
                                                    <td><?php echo $e['Porcentaje_Grasa'] ?? '—'; ?>%</td>
                                                    <td><?php echo $e['Cintura'] ?? '—'; ?> cm</td>
                                                    <td><?php echo $e['Observaciones'] ?? '—'; ?></td>
                                                </tr>
                                                <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <?php else: ?>
                                        <div class="alert alert-info">No hay evaluaciones registradas.</div>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Pestaña Registro de Comidas -->
                                    <div class="tab-pane fade" id="comidas" role="tabpanel">
                                        <?php
                                        $comidas = $conn->query("SELECT r.*, a.Nombre as alimento_nombre FROM registro_comidas r INNER JOIN alimentos a ON r.ID_Alimento = a.ID_Alimento WHERE r.ID_Socio = $id_socio ORDER BY r.Fecha DESC LIMIT 20");
                                        if($comidas->num_rows > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-vcenter">
                                                <thead>
                                                    <tr><th>Fecha</th><th>Alimento</th><th>Gramos</th><th>Calorías</th><th>Comentario</th>
                                                </thead>
                                                <tbody>
                                                <?php while($c = $comidas->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo date('d/m/Y', strtotime($c['Fecha'])); ?></td>
                                                    <td><?php echo htmlspecialchars($c['alimento_nombre']); ?></td>
                                                    <td><?php echo $c['Porcion_gramos']; ?> g</td>
                                                    <td><?php echo round($c['Calorias_totales']); ?> kcal</td>
                                                    <td><?php echo $c['comentario_entrenador'] ?? '—'; ?></td>
                                                </tr>
                                                <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <?php else: ?>
                                        <div class="alert alert-info">No hay registro de comidas.</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para cambiar foto -->
    <div class="modal fade" id="modalFoto" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cambiar foto de perfil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="subir_foto.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="id_usuario" id="foto_id_usuario">
                        <div class="mb-3">
                            <label class="form-label">Seleccionar imagen</label>
                            <input type="file" class="form-control" name="foto" accept="image/*" required>
                            <small class="form-hint">Formatos permitidos: JPG, PNG, GIF. Máximo 2MB</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-guardar">Subir foto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
    
    <script>
        function cambiarFoto(id_usuario) {
            document.getElementById('foto_id_usuario').value = id_usuario;
            var modal = new bootstrap.Modal(document.getElementById('modalFoto'));
            modal.show();
        }

        function asignarMembresia(id_socio) {
            window.location.href = 'asignar_membresia.php?socio=' + id_socio;
        }
    </script>
</body>
</html>