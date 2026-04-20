<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 3) {
    header("Location: ../login.php");
    exit();
}

include "../conexion.php";

$mensaje = "";

// Procesar reserva de clase
if (isset($_GET['reservar'])) {
    $id_clase_prog = (int)$_GET['reservar'];
    $id_socio = 0;
    
    // Obtener ID del socio
    $result_socio = $conn->query("SELECT ID_Socios FROM socios WHERE ID_Usuario = " . $_SESSION['usuario_id']);
    if ($result_socio && $row = $result_socio->fetch_assoc()) {
        $id_socio = $row['ID_Socios'];
    }
    
    // Verificar cupo
    $clase = $conn->query("SELECT Cupo_Maximo, (SELECT COUNT(*) FROM reservas_clases WHERE ID_Clase_Programada = $id_clase_prog AND Estado = 'confirmada') as reservados 
                           FROM clases_programadas WHERE ID_Clase_Programada = $id_clase_prog")->fetch_assoc();
    
    if ($clase && $clase['reservados'] < $clase['Cupo_Maximo']) {
        $conn->query("INSERT INTO reservas_clases (ID_Clase_Programada, ID_Socio, Estado) VALUES ($id_clase_prog, $id_socio, 'confirmada')");
        $mensaje = '<div class="alert alert-success">✅ Clase reservada correctamente</div>';
    } else {
        $mensaje = '<div class="alert alert-warning">⚠️ No hay cupo disponible. Clase llena.</div>';
    }
    header("Location: index.php");
    exit();
}

//obtener información del socio
$sql = "SELECT s.*, u.Nombre, u.Email, u.Telefono, u.foto
        FROM socios s
        INNER JOIN usuarios u ON s.ID_Usuario = u.ID_Usuario
        WHERE u.ID_Usuario = " . $_SESSION['usuario_id'];
$result = $conn->query($sql);
$socio = $result->fetch_assoc();

//obtener membresía activa
$sql_mem = "SELECT sm.*, tm.Nombre as membresia_nombre, tm.Duracion_Dias, tm.Precio
            FROM socio_membresia sm
            INNER JOIN tipos_membresia tm ON sm.ID_TipoMembresía = tm.ID_TipoMembresía
            WHERE sm.ID_Socio = " . $socio['ID_Socios'] . " 
            AND sm.Fecha_Fin >= CURDATE()
            ORDER BY sm.Fecha_Fin DESC
            LIMIT 1";
$result_mem = $conn->query($sql_mem);
$membresia = $result_mem->fetch_assoc();

//contar asistencias del mes
$sql_asistencias = "SELECT COUNT(*) as total FROM asistencias 
                    WHERE ID_Socio = " . $socio['ID_Socios'] . "
                    AND MONTH(Fecha_Hora) = MONTH(CURDATE())
                    AND YEAR(Fecha_Hora) = YEAR(CURDATE())";
$asistencias = $conn->query($sql_asistencias)->fetch_assoc();

//próximas clases reservadas
$sql_clases = "SELECT c.Nombre, cp.Fecha, cp.Hora_Inicio
               FROM reservas_clases rc
               INNER JOIN clases_programadas cp ON rc.ID_Clase_Programada = cp.ID_Clase_Programada
               INNER JOIN clases c ON cp.ID_Clase = c.ID_Clase
               WHERE rc.ID_Socio = " . $socio['ID_Socios'] . "
               AND cp.Fecha >= CURDATE()
               ORDER BY cp.Fecha ASC, cp.Hora_Inicio ASC
               LIMIT 3";
$clases = $conn->query($sql_clases);

// Obtener plan de entrenamiento activo del socio
$plan_activo = null;
$ejercicios_plan = [];

$sql_plan = "SELECT p.*, u.Nombre as entrenador_nombre
             FROM planes_rutinas p
             LEFT JOIN entrenadores e ON p.ID_Entrenador = e.ID_Entrenador
             LEFT JOIN usuarios u ON e.ID_Usuario = u.ID_Usuario
             WHERE p.ID_Socio = " . $socio['ID_Socios'] . "
             ORDER BY p.Fecha_Asignacion DESC
             LIMIT 1";
$result_plan = $conn->query($sql_plan);
if ($result_plan && $result_plan->num_rows > 0) {
    $plan_activo = $result_plan->fetch_assoc();
    
    // Obtener ejercicios del plan
    $sql_ejercicios = "SELECT d.*, e.Nombre as ejercicio_nombre, e.Descripcion as ejercicio_desc
                       FROM plan_detalle d
                       INNER JOIN ejercicios e ON d.ID_Ejercicio = e.ID_Ejercicio
                       WHERE d.ID_Plan = " . $plan_activo['ID_Plan'] . "
                       ORDER BY d.Orden";
    $result_ejercicios = $conn->query($sql_ejercicios);
    if ($result_ejercicios) {
        while($row = $result_ejercicios->fetch_assoc()) {
            $ejercicios_plan[] = $row;
        }
    }
}

// Clases disponibles para reservar
$clases_disponibles = $conn->query("SELECT cp.*, c.Nombre as clase_nombre, c.Descripcion,
                                    (SELECT COUNT(*) FROM reservas_clases WHERE ID_Clase_Programada = cp.ID_Clase_Programada AND Estado = 'confirmada') as reservados,
                                    (SELECT COUNT(*) FROM reservas_clases WHERE ID_Clase_Programada = cp.ID_Clase_Programada AND ID_Socio = " . $socio['ID_Socios'] . " AND Estado = 'confirmada') as ya_reservo
                                    FROM clases_programadas cp
                                    INNER JOIN clases c ON cp.ID_Clase = c.ID_Clase
                                    WHERE cp.Fecha >= CURDATE() AND cp.Estado = 'programada'
                                    ORDER BY cp.Fecha ASC, cp.Hora_Inicio ASC
                                    LIMIT 5");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi Perfil - Sistema de Gimnasio</title>
    
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <link href="../css/gym-style.css" rel="stylesheet"/>
</head>
<body>
    <div class="page">
        <header class="navbar navbar-expand-md navbar-gym">
            <div class="container-xl">
                <a href="index.php" class="navbar-brand d-flex align-items-center">
                    <img src="../logo1.png" alt="GYM ADMIN" class="logo-gym me-2">
                    <span style="color: white; font-weight: 600;">GYM SOCIO</span>
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbar-menu">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link active" href="index.php">
                                <i class="ti ti-home me-1"></i> Mi Perfil
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../mi_evaluacion.php">
                                <i class="ti ti-heart-rate-monitor me-1"></i> Mi Evaluación
                            </a>
                        </li>
                        <li class="nav-item">
                            <span class="nav-link text-white">
                                <i class="ti ti-user me-1"></i> <?php echo $_SESSION['usuario_nombre']; ?>
                            </span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../logout.php">
                                <i class="ti ti-logout me-1"></i> Salir
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="page-wrapper">
            <div class="container-xl">
                <?php echo $mensaje; ?>
                
                <!-- carta bienvenida -->
                <div class="welcome-card">
                    <h2><i class="ti ti-dumpling me-2"></i>Bienvenid@, <?php echo $socio['Nombre']; ?></h2>
                    <p class="mb-0"><?php echo date('l, d F Y'); ?> | Panel de Socio</p>
                </div>

                <!-- estatus -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $asistencias['total'] ?? 0; ?></div>
                            <div class="stat-label">Asistencias este mes</div>
                            <i class="ti ti-calendar-check" style="font-size: 24px; color: var(--gym-verde); margin-top: 10px;"></i>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $membresia ? 'Activa' : 'Inactiva'; ?></div>
                            <div class="stat-label">Membresía</div>
                            <i class="ti ti-cards" style="font-size: 24px; color: var(--gym-verde); margin-top: 10px;"></i>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $clases->num_rows; ?></div>
                            <div class="stat-label">Próximas clases</div>
                            <i class="ti ti-calendar" style="font-size: 24px; color: var(--gym-verde); margin-top: 10px;"></i>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Información personal con botón de cambiar foto -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Mi Perfil</h3>
                            </div>
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <?php 
                                    $foto = $socio['foto'] ?? 'default.jpg';
                                    $foto_path = "../uploads/" . $foto;
                                    if (!file_exists($foto_path)) {
                                        $foto_path = "../uploads/default.jpg";
                                    }
                                    ?>
                                    <img src="<?php echo $foto_path; ?>" alt="Foto de perfil" 
                                         class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover; border: 3px solid var(--gym-verde);">
                                </div>
                                <h4><?php echo $socio['Nombre']; ?></h4>
                                <p class="text-muted"><?php echo $socio['Email']; ?></p>
                                <p class="text-muted"><?php echo $socio['Telefono']; ?></p>
                                <p class="text-muted">Socio #<?php echo str_pad($socio['ID_Socios'], 3, '0', STR_PAD_LEFT); ?></p>
                                
                                <!-- Botón para cambiar foto -->
                                <button class="btn btn-sm btn-modificar mt-3" onclick="cambiarFoto(<?php echo $socio['ID_Usuario']; ?>)">
                                    <i class="ti ti-camera me-1"></i>
                                    Cambiar foto
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- membresía actual -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Mi Membresía Actual</h3>
                            </div>
                            <div class="card-body">
                                <?php if ($membresia): ?>
                                    <div class="alert alert-success">
                                        <h4><i class="ti ti-crown me-2"></i> <?php echo $membresia['membresia_nombre']; ?></h4>
                                        <p><strong>Vigente hasta:</strong> <?php echo date('d/m/Y', strtotime($membresia['Fecha_Fin'])); ?></p>
                                        <p><strong>Días restantes:</strong> 
                                            <?php 
                                            $hoy = new DateTime();
                                            $fin = new DateTime($membresia['Fecha_Fin']);
                                            $dias = $hoy->diff($fin)->days;
                                            echo $dias . ' días';
                                            ?>
                                        </p>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="ti ti-alert-triangle me-2"></i>
                                        No tienes una membresía activa. Contacta al administrador.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Clases Disponibles para Reservar -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ti ti-calendar-plus me-2" style="color: var(--gym-verde);"></i>
                                    Clases Disponibles
                                </h3>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-vcenter">
                                    <thead>
                                        <tr><th>Clase</th><th>Fecha</th><th>Hora</th><th>Cupo</th><th></th> </thead>
                                    <tbody>
                                        <?php while($clase = $clases_disponibles->fetch_assoc()): 
                                            $cupo_disponible = $clase['Cupo_Maximo'] - $clase['reservados'];
                                        ?>
                                        <tr>
                                            <td><strong><?php echo $clase['clase_nombre']; ?></strong><br><small><?php echo $clase['Descripcion']; ?></small></td>
                                            <td><?php echo date('d/m/Y', strtotime($clase['Fecha'])); ?></td>
                                            <td><?php echo substr($clase['Hora_Inicio'], 0, 5); ?> - <?php echo substr($clase['Hora_Fin'], 0, 5); ?></td>
                                            <td><span class="badge bg-<?php echo $cupo_disponible > 0 ? 'success' : 'danger'; ?>"><?php echo $cupo_disponible; ?> / <?php echo $clase['Cupo_Maximo']; ?></span></td>
                                            <td>
                                                <?php if ($clase['ya_reservo'] > 0): ?>
                                                    <span class="badge bg-success">✅ Ya reservaste</span>
                                                <?php elseif ($cupo_disponible > 0): ?>
                                                    <a href="?reservar=<?php echo $clase['ID_Clase_Programada']; ?>" class="btn btn-guardar btn-sm" onclick="return confirm('¿Reservar esta clase?')">Reservar</a>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Llena</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- próximas clases -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ti ti-calendar me-2" style="color: var(--gym-verde);"></i>
                                    Mis Próximas Clases
                                </h3>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-vcenter card-table">
                                    <thead>
                                        <tr>
                                            <th>Clase</th>
                                            <th>Fecha</th>
                                            <th>Hora</th>
                                          </thead>
                                    <tbody>
                                        <?php if ($clases && $clases->num_rows > 0): ?>
                                            <?php while($clase = $clases->fetch_assoc()): ?>
                                              <tr>
                                                  <td><?php echo htmlspecialchars($clase['Nombre']); ?></td>
                                                  <td><?php echo date('d/m/Y', strtotime($clase['Fecha'])); ?></td>
                                                  <td><?php echo date('H:i', strtotime($clase['Hora_Inicio'])); ?> hrs</td>
                                              </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                              <tr>
                                                <td colspan="3" class="text-center">No tienes clases reservadas</td>
                                              </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Plan de Entrenamiento Activo -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ti ti-clipboard-list me-2" style="color: var(--gym-verde);"></i>
                                    Mi Plan de Entrenamiento
                                </h3>
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
                                        No tienes un plan de entrenamiento activo. Tu entrenador te asignará uno pronto.
                                    </div>
                                <?php endif; ?>
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
                <form action="../subir_foto.php" method="POST" enctype="multipart/form-data">
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
        
        console.log("Panel de socio cargado correctamente");
    </script>
</body>
</html>