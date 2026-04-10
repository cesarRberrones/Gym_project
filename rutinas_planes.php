<?php
include "conexion.php";
session_start();

$mensaje_alerta = "";

//PROCESAR EL FORMULARIO PARA GUARDAR UN PLAN
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar_plan'])) {
    $id_socio = $_POST['id_socio'];
    $id_entrenador = $_POST['id_entrenador'];
    $nombre_plan = $_POST['nombre_plan'];
    $descripcion = $_POST['descripcion'];
    $fecha_actual = date('Y-m-d'); 

    $sql_insert = "INSERT INTO planes_rutinas (ID_Socio, ID_Entrenador, Nombre_Plan, Descripcion, Fecha_Asignacion) 
                   VALUES ('$id_socio', '$id_entrenador', '$nombre_plan', '$descripcion', '$fecha_actual')";

    if ($conn->query($sql_insert) === TRUE) {
        $mensaje_alerta = '<div class="alert alert-success alert-dismissible" role="alert">
                            <div class="d-flex">
                                <div><i class="ti ti-check icon alert-icon"></i></div>
                                <div>¡Plan asignado exitosamente!</div>
                            </div>
                            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                           </div>';
    } else {
        $mensaje_alerta = '<div class="alert alert-danger alert-dismissible" role="alert">
                            <div class="d-flex">
                                <div><i class="ti ti-alert-circle icon alert-icon"></i></div>
                                <div>Error al guardar: ' . $conn->error . '</div>
                            </div>
                            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                           </div>';
    }
}

//PROCESAR LA ELIMINACIÓN DE UN PLAN
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar_plan'])) {
    $id_plan_eliminar = $_POST['id_plan_eliminar'];
    
    //Consulta SQL para borrar el plan específico
    $sql_delete = "DELETE FROM planes_rutinas WHERE ID_Plan = '$id_plan_eliminar'";
    
    if ($conn->query($sql_delete) === TRUE) {
        $mensaje_alerta = '<div class="alert alert-success alert-dismissible" role="alert">
                            <div class="d-flex">
                                <div><i class="ti ti-check icon alert-icon"></i></div>
                                <div>¡Plan eliminado correctamente!</div>
                            </div>
                            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                           </div>';
    } else {
        $mensaje_alerta = '<div class="alert alert-danger alert-dismissible" role="alert">
                            <div class="d-flex">
                                <div><i class="ti ti-alert-circle icon alert-icon"></i></div>
                                <div>Error al eliminar: ' . $conn->error . '</div>
                            </div>
                            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                           </div>';
    }
}

//OBTENER DATOS PARA LOS MENÚS DESPLEGABLES
$socios_query = $conn->query("SELECT s.ID_Socios, u.Nombre FROM socios s INNER JOIN usuarios u ON s.ID_Usuario = u.ID_Usuario");
$entrenadores_query = $conn->query("SELECT e.ID_Entrenador, u.Nombre FROM entrenadores e INNER JOIN usuarios u ON e.ID_Usuario = u.ID_Usuario");

$plantillas_query = $conn->query("SELECT DISTINCT Nombre_Plan, Descripcion FROM planes_rutinas");
$lista_plantillas = [];
if($plantillas_query) {
    while($row = $plantillas_query->fetch_assoc()) { 
        $lista_plantillas[] = $row; 
    }
}

//CONSULTA PARA LA TABLA DEL HISTORIAL
$query_planes = "SELECT 
                    pr.ID_Plan, 
                    pr.Nombre_Plan, 
                    pr.Descripcion, 
                    pr.Fecha_Asignacion,
                    us.Nombre AS NombreSocio, 
                    ue.Nombre AS NombreEntrenador 
                 FROM planes_rutinas pr
                 INNER JOIN socios s ON pr.ID_Socio = s.ID_Socios
                 INNER JOIN usuarios us ON s.ID_Usuario = us.ID_Usuario
                 INNER JOIN entrenadores e ON pr.ID_Entrenador = e.ID_Entrenador
                 INNER JOIN usuarios ue ON e.ID_Usuario = ue.ID_Usuario
                 ORDER BY pr.Fecha_Asignacion DESC";

$planes_resultado = $conn->query($query_planes);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Planes y Rutinas - Sistema de Gimnasio</title>
    
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
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
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
                            <a class="nav-link" href="socios.php">
                                <i class="ti ti-users me-1"></i> Socios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="entrenadores.php">
                                <i class="ti ti-run me-1"></i> Entrenadores
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="clases.php">
                                <i class="ti ti-calendar me-1"></i> Clases
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="Pagos.php">
                                <i class="ti ti-credit-card me-1"></i> Pagos/Caja
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reportes.php">
                                <i class="ti ti-chart-bar me-1"></i> Reportes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="rutinas_planes.php">
                                <i class="ti ti-clipboard-list me-1"></i> Planes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="ti ti-logout me-1"></i> Salir
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="page-wrapper">
            <div class="container-xl mt-4">
                
                <?php echo $mensaje_alerta; ?>

                <div class="row row-cards">
                    
                    <div class="col-12">
                        <form method="POST" action="" class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="ti ti-plus me-2" style="color: var(--gym-verde);"></i> Asignar Nuevo Plan</h3>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label required">Socio</label>
                                        <select name="id_socio" class="form-select" required>
                                            <option value="">Seleccione a quién asignar el plan...</option>
                                            <?php if($socios_query) while($socio = $socios_query->fetch_assoc()): ?>
                                                <option value="<?php echo $socio['ID_Socios']; ?>"><?php echo htmlspecialchars($socio['Nombre']); ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label required">Entrenador a cargo</label>
                                        <select name="id_entrenador" class="form-select" required>
                                            <option value="">Seleccione el entrenador responsable...</option>
                                            <?php if($entrenadores_query) while($entrenador = $entrenadores_query->fetch_assoc()): ?>
                                                <option value="<?php echo $entrenador['ID_Entrenador']; ?>"><?php echo htmlspecialchars($entrenador['Nombre']); ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>

                                    <div class="col-12 mt-4">
                                        <label class="form-label text-primary">¿Desea usar un plan existente? (Opcional)</label>
                                        <select id="select_plantilla" class="form-select" onchange="autocompletarPlan()">
                                            <option value="">No, quiero escribir un plan completamente nuevo...</option>
                                            <?php foreach($lista_plantillas as $index => $plantilla): ?>
                                                <option value="<?php echo $index; ?>"><?php echo htmlspecialchars($plantilla['Nombre_Plan']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label required">Nombre del Plan</label>
                                        <input type="text" name="nombre_plan" id="input_nombre" class="form-control" placeholder="Ej. Rutina Hipertrofia Básica" required>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label required">Descripción y Detalles</label>
                                        <textarea name="descripcion" id="input_descripcion" class="form-control" rows="3" placeholder="Especifique los ejercicios, repeticiones o la rutina general..." required></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" name="guardar_plan" class="btn btn-primary">
                                    <i class="ti ti-device-floppy me-2"></i> Asignar Plan
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="ti ti-clipboard-list me-2" style="color: var(--gym-verde);"></i> Historial de Planes y Rutinas</h3>
                            </div>
                            <div class="table-responsive">
                                <table class="table card-table table-vcenter text-nowrap datatable">
                                    <thead>
                                        <tr>
                                            <th>N° Plan</th>
                                            <th>Nombre del Plan</th>
                                            <th>Socio Asignado</th>
                                            <th>Entrenador a Cargo</th>
                                            <th>Descripción</th>
                                            <th>Fecha de Asignación</th>
                                            <th class="text-end">Acciones</th>
                                         </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($planes_resultado && $planes_resultado->num_rows > 0): ?>
                                            <?php while($plan = $planes_resultado->fetch_assoc()): ?>
                                                 <tr>
                                                    <td><span class="text-muted">#<?php echo str_pad($plan['ID_Plan'], 4, "0", STR_PAD_LEFT); ?></span></td>
                                                    <td class="fw-bold text-primary"><?php echo htmlspecialchars($plan['Nombre_Plan']); ?></td>
                                                    <td>
                                                        <div class="d-flex py-1 align-items-center">
                                                            <div class="flex-fill">
                                                                <div class="font-weight-medium"><?php echo htmlspecialchars($plan['NombreSocio']); ?></div>
                                                                <div class="text-muted"><small>Socio</small></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex py-1 align-items-center">
                                                            <div class="flex-fill">
                                                                <div class="font-weight-medium"><?php echo htmlspecialchars($plan['NombreEntrenador']); ?></div>
                                                                <div class="text-muted"><small>Entrenador</small></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="text-truncate d-inline-block" style="max-width: 250px;" title="<?php echo htmlspecialchars($plan['Descripcion']); ?>">
                                                            <?php echo htmlspecialchars($plan['Descripcion']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('d/m/Y', strtotime($plan['Fecha_Asignacion'])); ?></td>
                                                    
                                                    <td class="text-end">
                                                        <form method="POST" action="" style="display:inline-block;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar el plan de <?php echo htmlspecialchars($plan['NombreSocio']); ?>? Esta acción no se puede deshacer.');">
                                                            <input type="hidden" name="id_plan_eliminar" value="<?php echo $plan['ID_Plan']; ?>">
                                                            
                                                            <button type="submit" name="eliminar_plan" class="btn btn-danger btn-sm text-white">
                                                                <i class="ti ti-trash me-1"></i> Eliminar
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                             <tr>
                                                <td colspan="7" class="text-center py-4 empty">
                                                    <div class="empty-icon"><i class="ti ti-clipboard-x"></i></div>
                                                    <p class="empty-title">No hay planes de rutinas asignados aún.</p>
                                                </td>
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
        const planes_existentes = <?php echo json_encode($lista_plantillas); ?>;

        function autocompletarPlan() {
            const select = document.getElementById("select_plantilla");
            const inputNombre = document.getElementById("input_nombre");
            const inputDesc = document.getElementById("input_descripcion");

            if (select.value !== "") {
                const indiceElegido = select.value;
                const planElegido = planes_existentes[indiceElegido];
                inputNombre.value = planElegido.Nombre_Plan;
                inputDesc.value = planElegido.Descripcion;
            } else {
                inputNombre.value = '';
                inputDesc.value = '';
            }
        }
    </script>

    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>