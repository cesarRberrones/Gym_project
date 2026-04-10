<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 3) {
    header("Location: ../login.php");
    exit();
}

include "../conexion.php";

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
$sql_clases = "SELECT c.Nombre, c.Fecha, c.Cupo_Maximo
               FROM reservas_clases rc
               INNER JOIN clases c ON rc.ID_Clase = c.ID_Clase
               WHERE rc.ID_Socio = " . $socio['ID_Socios'] . "
               AND c.Fecha >= CURDATE()
               ORDER BY c.Fecha ASC
               LIMIT 3";
$clases = $conn->query($sql_clases);
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
                    <span style="color: white; font-weight: 600;">GYM USER</span>
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
                <!-- carta bienvenida -->
                <div class="welcome-card">
                    <h2><i class="ti ti-dumpling me-2"></i>Bienvenido, <?php echo $socio['Nombre']; ?></h2>
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
                    <!-- Información personal -->
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

                        <!-- próximas clases -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h3 class="card-title">Mis Próximas Clases</h3>
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
                                        <?php if ($clases->num_rows > 0): ?>
                                            <?php while($clase = $clases->fetch_assoc()): ?>
                                             <tr>
                                                 <td><?php echo htmlspecialchars($clase['Nombre']); ?></td>
                                                 <td><?php echo date('d/m/Y', strtotime($clase['Fecha'])); ?></td>
                                                 <td><?php echo date('H:i', strtotime($clase['Fecha'])); ?></td>
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
    <script>
        console.log("Panel de socio cargado correctamente");
    </script>
</body>
</html>