<?php
include "conexion.php";
session_start();

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] != 1 && $_SESSION['rol'] != 2 && $_SESSION['rol'] != 3)) {
    header("Location: login.php");
    exit();
}

$mensaje = "";
$es_admin = ($_SESSION['rol'] == 1);
$es_entrenador = ($_SESSION['rol'] == 2);
$es_socio = ($_SESSION['rol'] == 3);

// Obtener ID del entrenador si es entrenador
$id_entrenador = 0;
if ($es_entrenador) {
    $result = $conn->query("SELECT ID_Entrenador FROM entrenadores WHERE ID_Usuario = " . $_SESSION['usuario_id']);
    if ($result && $row = $result->fetch_assoc()) {
        $id_entrenador = $row['ID_Entrenador'];
    }
}

// Obtener ID del socio si es socio
$id_socio_logueado = 0;
if ($es_socio) {
    $result = $conn->query("SELECT ID_Socios FROM socios WHERE ID_Usuario = " . $_SESSION['usuario_id']);
    if ($result && $row = $result->fetch_assoc()) {
        $id_socio_logueado = $row['ID_Socios'];
    }
}

// ==================== CRUD PLANES ====================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['crear_plan']) && !$es_socio) {
        $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
        $descripcion = mysqli_real_escape_string($conn, $_POST['descripcion']);
        $objetivo = $_POST['objetivo'];
        $id_socio = (int)$_POST['id_socio'];
        $id_entrenador_plan = $es_admin ? (int)$_POST['id_entrenador'] : $id_entrenador;
        
        $sql = "INSERT INTO planes_rutinas (Nombre, Descripcion, Objetivo, ID_Socio, ID_Entrenador, Fecha_Asignacion) 
                VALUES ('$nombre', '$descripcion', '$objetivo', $id_socio, $id_entrenador_plan, CURDATE())";
        
        if ($conn->query($sql)) {
            $id_plan = $conn->insert_id;
            $mensaje = '<div class="alert alert-success">Plan creado. Ahora agrega ejercicios.</div>';
            header("Location: rutinas_planes.php?edit_plan=$id_plan");
            exit();
        } else {
            $mensaje = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
    }
    
    if (isset($_POST['editar_plan']) && !$es_socio) {
        $id_plan = (int)$_POST['id_plan'];
        $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
        $descripcion = mysqli_real_escape_string($conn, $_POST['descripcion']);
        $objetivo = $_POST['objetivo'];
        
        $sql = "UPDATE planes_rutinas SET Nombre='$nombre', Descripcion='$descripcion', Objetivo='$objetivo' WHERE ID_Plan=$id_plan";
        if ($conn->query($sql)) {
            $mensaje = '<div class="alert alert-success">Plan actualizado</div>';
        } else {
            $mensaje = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
    }
    
    if (isset($_POST['eliminar_plan']) && $es_admin) {
        $id_plan = (int)$_POST['id_plan'];
        $conn->query("DELETE FROM plan_detalle WHERE ID_Plan = $id_plan");
        $conn->query("DELETE FROM planes_rutinas WHERE ID_Plan = $id_plan");
        $mensaje = '<div class="alert alert-success">Plan eliminado</div>';
    }
    
    // Agregar ejercicio al plan
    if (isset($_POST['agregar_ejercicio']) && !$es_socio) {
        $id_plan = (int)$_POST['id_plan'];
        $id_ejercicio = (int)$_POST['id_ejercicio'];
        $series = (int)$_POST['series'];
        $repeticiones = (int)$_POST['repeticiones'];
        $descanso = (int)$_POST['descanso'];
        $orden = (int)$_POST['orden'];
        
        $sql = "INSERT INTO plan_detalle (ID_Plan, ID_Ejercicio, Series, Repeticiones, Descanso_segundos, Orden) 
                VALUES ($id_plan, $id_ejercicio, $series, $repeticiones, $descanso, $orden)";
        if ($conn->query($sql)) {
            $mensaje = '<div class="alert alert-success">Ejercicio agregado</div>';
        } else {
            $mensaje = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
    }
    
    if (isset($_POST['eliminar_ejercicio']) && !$es_socio) {
        $id_detalle = (int)$_POST['id_detalle'];
        $conn->query("DELETE FROM plan_detalle WHERE ID_Detalle = $id_detalle");
        $mensaje = '<div class="alert alert-success">Ejercicio eliminado</div>';
    }
}

// ==================== OBTENER DATOS SEGÚN ROL ====================
if ($es_admin) {
    // Admin ve todos los planes
    $planes = $conn->query("SELECT p.*, u.Nombre as socio_nombre, eu.Nombre as entrenador_nombre 
                            FROM planes_rutinas p
                            LEFT JOIN socios s ON p.ID_Socio = s.ID_Socios
                            LEFT JOIN usuarios u ON s.ID_Usuario = u.ID_Usuario
                            LEFT JOIN entrenadores e ON p.ID_Entrenador = e.ID_Entrenador
                            LEFT JOIN usuarios eu ON e.ID_Usuario = eu.ID_Usuario
                            ORDER BY p.Fecha_Asignacion DESC");
} elseif ($es_entrenador) {
    // Entrenador ve solo sus planes
    $planes = $conn->query("SELECT p.*, u.Nombre as socio_nombre, eu.Nombre as entrenador_nombre 
                            FROM planes_rutinas p
                            LEFT JOIN socios s ON p.ID_Socio = s.ID_Socios
                            LEFT JOIN usuarios u ON s.ID_Usuario = u.ID_Usuario
                            LEFT JOIN entrenadores e ON p.ID_Entrenador = e.ID_Entrenador
                            LEFT JOIN usuarios eu ON e.ID_Usuario = eu.ID_Usuario
                            WHERE p.ID_Entrenador = $id_entrenador
                            ORDER BY p.Fecha_Asignacion DESC");
} else {
    // Socio ve solo sus planes
    $planes = $conn->query("SELECT p.*, eu.Nombre as entrenador_nombre 
                            FROM planes_rutinas p
                            LEFT JOIN entrenadores e ON p.ID_Entrenador = e.ID_Entrenador
                            LEFT JOIN usuarios eu ON e.ID_Usuario = eu.ID_Usuario
                            WHERE p.ID_Socio = $id_socio_logueado
                            ORDER BY p.Fecha_Asignacion DESC");
}

$socios = $conn->query("SELECT s.ID_Socios, u.Nombre FROM socios s INNER JOIN usuarios u ON s.ID_Usuario = u.ID_Usuario ORDER BY u.Nombre");
$entrenadores = $conn->query("SELECT e.ID_Entrenador, u.Nombre FROM entrenadores e INNER JOIN usuarios u ON e.ID_Usuario = u.ID_Usuario");
$ejercicios = $conn->query("SELECT * FROM ejercicios ORDER BY Nombre");

$edit_plan = isset($_GET['edit_plan']) ? (int)$_GET['edit_plan'] : 0;
$detalle_plan = [];
if ($edit_plan) {
    $detalle = $conn->query("SELECT d.*, e.Nombre as ejercicio_nombre 
                             FROM plan_detalle d 
                             INNER JOIN ejercicios e ON d.ID_Ejercicio = e.ID_Ejercicio 
                             WHERE d.ID_Plan = $edit_plan 
                             ORDER BY d.Orden");
    while($row = $detalle->fetch_assoc()) {
        $detalle_plan[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rutinas y Planes - Sistema de Gimnasio</title>
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
                    <span style="color: white; font-weight: 600;">
                        <?php if ($es_admin): ?>
                            GYM ADMIN
                        <?php elseif ($es_entrenador): ?>
                            GYM ENTRENADOR
                        <?php else: ?>
                            GYM SOCIO
                        <?php endif; ?>
                    </span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbar-menu">
                    <ul class="navbar-nav ms-auto">
                        <?php if ($es_admin): ?>
                            <!-- Menú ADMIN -->
                            <li class="nav-item"><a class="nav-link" href="index.php"><i class="ti ti-dashboard me-1"></i> Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link" href="membresias.php"><i class="ti ti-cards me-1"></i> Membresías</a></li>
                            <li class="nav-item"><a class="nav-link" href="socios.php"><i class="ti ti-users me-1"></i> Socios</a></li>
                            <li class="nav-item"><a class="nav-link" href="evaluaciones.php"><i class="ti ti-heart-rate-monitor me-1"></i> Evaluaciones</a></li>
                            <li class="nav-item"><a class="nav-link active" href="rutinas_planes.php"><i class="ti ti-clipboard-list me-1"></i> Rutinas</a></li>
                            <li class="nav-item"><a class="nav-link" href="clases.php"><i class="ti ti-calendar me-1"></i> Clases</a></li>
                            <li class="nav-item"><a class="nav-link" href="pos.php"><i class="ti ti-shopping-cart me-1"></i> POS</a></li>
                            <li class="nav-item"><a class="nav-link" href="logout.php"><i class="ti ti-logout me-1"></i> Salir</a></li>
                        <?php elseif ($es_entrenador): ?>
                            <!-- Menú ENTRENADOR -->
                            <li class="nav-item"><a class="nav-link" href="entrenador/index.php"><i class="ti ti-dashboard me-1"></i> Mis Socios</a></li>
                            <li class="nav-item"><a class="nav-link" href="evaluaciones.php"><i class="ti ti-heart-rate-monitor me-1"></i> Evaluaciones</a></li>
                            <li class="nav-item"><a class="nav-link active" href="rutinas_planes.php"><i class="ti ti-clipboard-list me-1"></i> Rutinas</a></li>
                            <li class="nav-item"><a class="nav-link" href="logout.php"><i class="ti ti-logout me-1"></i> Salir</a></li>
                        <?php else: ?>
                            <!-- Menú SOCIO -->
                            <li class="nav-item"><a class="nav-link" href="socio/index.php"><i class="ti ti-home me-1"></i> Mi Perfil</a></li>
                            <li class="nav-item"><a class="nav-link" href="mi_evaluacion.php"><i class="ti ti-heart-rate-monitor me-1"></i> Mi Evaluación</a></li>
                            <li class="nav-item"><a class="nav-link active" href="rutinas_planes.php"><i class="ti ti-clipboard-list me-1"></i> Mis Planes</a></li>
                            <li class="nav-item"><a class="nav-link" href="logout.php"><i class="ti ti-logout me-1"></i> Salir</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </header>

        <div class="page-wrapper">
            <div class="container-xl">
                <?php echo $mensaje; ?>
                
                <div class="page-header d-print-none mb-4">
                    <div class="row align-items-center">
                        <div class="col">
                            <h2 class="page-title"><i class="ti ti-clipboard-list me-2" style="color: var(--gym-verde);"></i> Rutinas y Planes</h2>
                        </div>
                    </div>
                </div>

                <!-- Pestañas (solo admin/entrenador pueden crear/editar) -->
                <ul class="nav nav-tabs mb-4">
                    <li class="nav-item">
                        <a class="nav-link <?php echo !$edit_plan ? 'active' : ''; ?>" data-bs-toggle="tab" href="#lista">Planes</a>
                    </li>
                    <?php if (!$es_socio && $edit_plan): ?>
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#editar">Editar Plan</a>
                    </li>
                    <?php endif; ?>
                    <?php if (!$es_socio): ?>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#nuevo">Nuevo Plan</a>
                    </li>
                    <?php endif; ?>
                </ul>

                <div class="tab-content">
                    <!-- ==================== LISTA DE PLANES ==================== -->
                    <div class="tab-pane fade <?php echo !$edit_plan ? 'show active' : ''; ?>" id="lista">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Planes Asignados</h3>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-vcenter">
                                    <thead>
                                        <tr><th>ID</th><th>Plan</th><?php if (!$es_socio): ?><th>Socio</th><?php endif; ?><th>Entrenador</th><th>Objetivo</th><th>Fecha</th><?php if (!$es_socio): ?><th>Acciones</th><?php endif; ?> </thead>
                                    <tbody>
                                        <?php while($p = $planes->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $p['ID_Plan']; ?></td>
                                            <td><strong><?php echo $p['Nombre']; ?></strong><br><small><?php echo $p['Descripcion']; ?></small></td>
                                            <?php if (!$es_socio): ?>
                                            <td><?php echo $p['socio_nombre']; ?></td>
                                            <?php endif; ?>
                                            <td><?php echo $p['entrenador_nombre']; ?></td>
                                            <td>
                                                <?php 
                                                $obj_texto = '';
                                                if ($p['Objetivo'] == 'perdida_peso') $obj_texto = '🏋️ Pérdida de peso';
                                                elseif ($p['Objetivo'] == 'ganancia_muscular') $obj_texto = '💪 Ganancia muscular';
                                                else $obj_texto = '🏃 Resistencia';
                                                echo $obj_texto;
                                                ?>
                                              </td>
                                            <td><?php echo date('d/m/Y', strtotime($p['Fecha_Asignacion'])); ?></td>
                                            <?php if (!$es_socio): ?>
                                            <td>
                                                <a href="rutinas_planes.php?edit_plan=<?php echo $p['ID_Plan']; ?>" class="btn btn-modificar btn-sm">Editar</a>
                                                <?php if ($es_admin): ?>
                                                <form method="POST" style="display:inline-block;">
                                                    <input type="hidden" name="id_plan" value="<?php echo $p['ID_Plan']; ?>">
                                                    <button type="submit" name="eliminar_plan" class="btn btn-eliminar btn-sm" onclick="return confirm('¿Eliminar este plan?')">Eliminar</button>
                                                </form>
                                                <?php endif; ?>
                                              </td>
                                            <?php else: ?>
                                            <td>Ver solo</td>
                                            <?php endif; ?>
                                         </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== EDITAR PLAN (con ejercicios) ==================== -->
                    <?php if (!$es_socio && $edit_plan): ?>
                    <div class="tab-pane fade show active" id="editar">
                        <?php 
                        $plan_edit = $conn->query("SELECT * FROM planes_rutinas WHERE ID_Plan = $edit_plan")->fetch_assoc();
                        ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h3 class="card-title">Editar Plan: <?php echo $plan_edit['Nombre']; ?></h3>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="id_plan" value="<?php echo $edit_plan; ?>">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Nombre</label>
                                            <input type="text" name="nombre" class="form-control" value="<?php echo $plan_edit['Nombre']; ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Objetivo</label>
                                            <select name="objetivo" class="form-select" required>
                                                <option value="perdida_peso" <?php echo $plan_edit['Objetivo'] == 'perdida_peso' ? 'selected' : ''; ?>>Pérdida de peso</option>
                                                <option value="ganancia_muscular" <?php echo $plan_edit['Objetivo'] == 'ganancia_muscular' ? 'selected' : ''; ?>>Ganancia muscular</option>
                                                <option value="resistencia" <?php echo $plan_edit['Objetivo'] == 'resistencia' ? 'selected' : ''; ?>>Resistencia</option>
                                            </select>
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Descripción</label>
                                            <textarea name="descripcion" class="form-control" rows="2"><?php echo $plan_edit['Descripcion']; ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" name="editar_plan" class="btn btn-guardar">Guardar Cambios</button>
                                </div>
                            </form>
                        </div>

                        <!-- Agregar ejercicios al plan -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h3 class="card-title">Agregar Ejercicio</h3>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="id_plan" value="<?php echo $edit_plan; ?>">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Ejercicio</label>
                                            <select name="id_ejercicio" class="form-select" required>
                                                <option value="">Seleccionar...</option>
                                                <?php 
                                                $ejercicios->data_seek(0);
                                                while($e = $ejercicios->fetch_assoc()): ?>
                                                    <option value="<?php echo $e['ID_Ejercicio']; ?>"><?php echo $e['Nombre']; ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">Series</label>
                                            <input type="number" name="series" class="form-control" value="3" required>
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">Repeticiones</label>
                                            <input type="number" name="repeticiones" class="form-control" value="12" required>
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">Descanso (seg)</label>
                                            <input type="number" name="descanso" class="form-control" value="60">
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">Orden</label>
                                            <input type="number" name="orden" class="form-control" value="0">
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" name="agregar_ejercicio" class="btn btn-guardar">Agregar Ejercicio</button>
                                </div>
                            </form>
                        </div>

                        <!-- Lista de ejercicios del plan -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Ejercicios del Plan</h3>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-vcenter">
                                    <thead>
                                        <tr><th>Ejercicio</th><th>Series</th><th>Repeticiones</th><th>Descanso</th><th>Orden</th><th>Acciones</th> </thead>
                                    <tbody>
                                        <?php foreach($detalle_plan as $d): ?>
                                        <tr>
                                            <td><?php echo $d['ejercicio_nombre']; ?></td>
                                            <td><?php echo $d['Series']; ?></td>
                                            <td><?php echo $d['Repeticiones']; ?></td>
                                            <td><?php echo $d['Descanso_segundos']; ?> seg</td>
                                            <td><?php echo $d['Orden']; ?></td>
                                            <td>
                                                <form method="POST" style="display:inline-block;">
                                                    <input type="hidden" name="id_detalle" value="<?php echo $d['ID_Detalle']; ?>">
                                                    <button type="submit" name="eliminar_ejercicio" class="btn btn-eliminar btn-sm" onclick="return confirm('¿Eliminar este ejercicio?')">Eliminar</button>
                                                </form>
                                              </td>
                                         </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ==================== CREAR NUEVO PLAN (solo admin/entrenador) ==================== -->
                    <?php if (!$es_socio): ?>
                    <div class="tab-pane fade" id="nuevo">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Crear Nuevo Plan</h3>
                            </div>
                            <form method="POST">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Nombre del Plan</label>
                                            <input type="text" name="nombre" class="form-control" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Objetivo</label>
                                            <select name="objetivo" class="form-select" required>
                                                <option value="perdida_peso">Pérdida de peso</option>
                                                <option value="ganancia_muscular">Ganancia muscular</option>
                                                <option value="resistencia">Resistencia</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Socio</label>
                                            <select name="id_socio" class="form-select" required>
                                                <option value="">Seleccionar socio...</option>
                                                <?php 
                                                $socios->data_seek(0);
                                                while($s = $socios->fetch_assoc()): ?>
                                                    <option value="<?php echo $s['ID_Socios']; ?>"><?php echo $s['Nombre']; ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <?php if ($es_admin): ?>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Entrenador</label>
                                            <select name="id_entrenador" class="form-select" required>
                                                <option value="">Seleccionar entrenador...</option>
                                                <?php 
                                                $entrenadores->data_seek(0);
                                                while($e = $entrenadores->fetch_assoc()): ?>
                                                    <option value="<?php echo $e['ID_Entrenador']; ?>"><?php echo $e['Nombre']; ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <?php else: ?>
                                        <input type="hidden" name="id_entrenador" value="<?php echo $id_entrenador; ?>">
                                        <?php endif; ?>
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Descripción</label>
                                            <textarea name="descripcion" class="form-control" rows="2"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" name="crear_plan" class="btn btn-guardar">Crear Plan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
</body>
</html>