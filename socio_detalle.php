<?php
include "conexion.php";
session_start();

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
$sql_clases = "SELECT rc.*, c.Nombre as clase_nombre, c.Fecha
               FROM reservas_clases rc
               INNER JOIN clases c ON rc.ID_Clase = c.ID_Clase
               WHERE rc.ID_Socio = $id_socio
               ORDER BY c.Fecha DESC
               LIMIT 5";
$clases = $conn->query($sql_clases);
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
        <header class="navbar navbar-expand-md navbar-gym">
            <div class="container-xl">
                <a href="index.php" class="navbar-brand d-flex align-items-center">
                    <img src="logo1.png" alt="GYM ADMIN" class="logo-gym me-2">
                    <span style="color: white; font-weight: 600;">GYM ADMIN</span>
                </a>
                
                <div class="collapse navbar-collapse" id="navbar-menu">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="ti ti-dashboard me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="membresias.php">
                                <i class="ti ti-cards me-1"></i> Membresías
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="socios.php">
                                <i class="ti ti-users me-1"></i> Socios
                            </a>
                        </li>
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
                            <a href="socios.php" class="btn btn-modificar">
                                <i class="ti ti-arrow-left me-1"></i>
                                Volver a Socios
                            </a>
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
                                <div class="card-actions">
                                    <button class="btn btn-sm btn-guardar" onclick="asignarMembresia(<?php echo $socio['ID_Socios']; ?>)">
                                        <i class="ti ti-plus me-1"></i>
                                        Asignar
                                    </button>
                                </div>
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
                                        <?php if ($clases->num_rows > 0) { 
                                            while($c = $clases->fetch_assoc()) {
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($c['clase_nombre']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($c['Fecha'])); ?></td>
                                            <td><?php echo date('H:i', strtotime($c['Fecha'])); ?></td>
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