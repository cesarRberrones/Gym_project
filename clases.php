<?php
include "conexion.php";
session_start();

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] != 1 && $_SESSION['rol'] != 2)) {
    header("Location: login.php");
    exit();
}

$mensaje = "";
$es_admin = ($_SESSION['rol'] == 1);
$id_entrenador_logueado = 0;

// Si es entrenador, obtener su ID
if ($_SESSION['rol'] == 2) {
    $result = $conn->query("SELECT ID_Entrenador FROM entrenadores WHERE ID_Usuario = " . $_SESSION['usuario_id']);
    if ($result && $row = $result->fetch_assoc()) {
        $id_entrenador_logueado = $row['ID_Entrenador'];
    }
}

// ==================== PROGRAMAR CLASE (solo admin) ====================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_clase_programada']) && $es_admin) {
    $id_clase = (int)$_POST['id_clase'];
    $id_entrenador = (int)$_POST['id_entrenador'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = $_POST['hora_fin'];
    $cupo_maximo = (int)$_POST['cupo_maximo'];
    $descripcion = mysqli_real_escape_string($conn, $_POST['descripcion']);
    
    $sql = "INSERT INTO clases_programadas (ID_Clase, ID_Entrenador, Fecha, Hora_Inicio, Hora_Fin, Cupo_Maximo, Descripcion, Estado) 
            VALUES ($id_clase, $id_entrenador, '$fecha', '$hora_inicio', '$hora_fin', $cupo_maximo, '$descripcion', 'programada')";
    
    if ($conn->query($sql)) {
        $mensaje = '<div class="alert alert-success">Clase programada correctamente</div>';
    } else {
        $mensaje = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
    }
}

// ==================== CANCELAR CLASE (solo admin) ====================
if (isset($_POST['cancelar_clase']) && $es_admin) {
    $id_clase_prog = (int)$_POST['id_clase_programada'];
    $conn->query("UPDATE clases_programadas SET Estado = 'cancelada' WHERE ID_Clase_Programada = $id_clase_prog");
    $mensaje = '<div class="alert alert-warning">Clase cancelada</div>';
}

// ==================== COMPLETAR CLASE (solo admin) ====================
if (isset($_POST['completar_clase']) && $es_admin) {
    $id_clase_prog = (int)$_POST['id_clase_programada'];
    $conn->query("UPDATE clases_programadas SET Estado = 'completada' WHERE ID_Clase_Programada = $id_clase_prog");
    $mensaje = '<div class="alert alert-info">Clase marcada como completada</div>';
}

// ==================== OBTENER CLASES SEGÚN ROL ====================
if ($es_admin) {
    // Admin ve todas las clases
    $clases_programadas = $conn->query("SELECT cp.*, c.Nombre as clase_nombre, u.Nombre as entrenador_nombre
                                        FROM clases_programadas cp
                                        INNER JOIN clases c ON cp.ID_Clase = c.ID_Clase
                                        LEFT JOIN entrenadores e ON cp.ID_Entrenador = e.ID_Entrenador
                                        LEFT JOIN usuarios u ON e.ID_Usuario = u.ID_Usuario
                                        WHERE cp.Fecha >= CURDATE()
                                        ORDER BY cp.Fecha ASC, cp.Hora_Inicio ASC");
} else {
    // Entrenador solo ve sus clases
    $clases_programadas = $conn->query("SELECT cp.*, c.Nombre as clase_nombre, u.Nombre as entrenador_nombre
                                        FROM clases_programadas cp
                                        INNER JOIN clases c ON cp.ID_Clase = c.ID_Clase
                                        LEFT JOIN entrenadores e ON cp.ID_Entrenador = e.ID_Entrenador
                                        LEFT JOIN usuarios u ON e.ID_Usuario = u.ID_Usuario
                                        WHERE cp.ID_Entrenador = $id_entrenador_logueado AND cp.Fecha >= CURDATE()
                                        ORDER BY cp.Fecha ASC, cp.Hora_Inicio ASC");
}

// Lista de tipos de clase para el select
$tipos_clases = $conn->query("SELECT * FROM clases ORDER BY Nombre");

// Lista de entrenadores para el select (solo admin)
$entrenadores_lista = $conn->query("SELECT e.ID_Entrenador, u.Nombre 
                                    FROM entrenadores e 
                                    INNER JOIN usuarios u ON e.ID_Usuario = u.ID_Usuario 
                                    ORDER BY u.Nombre");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clases Grupales - Sistema de Gimnasio</title>
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
                        <li class="nav-item"><a class="nav-link" href="index.php"><i class="ti ti-dashboard me-1"></i> Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="membresias.php"><i class="ti ti-cards me-1"></i> Membresías</a></li>
                        <li class="nav-item"><a class="nav-link" href="socios.php"><i class="ti ti-users me-1"></i> Socios</a></li>
                        <li class="nav-item"><a class="nav-link active" href="clases.php"><i class="ti ti-calendar me-1"></i> Clases</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php"><i class="ti ti-logout me-1"></i> Salir</a></li>
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
                            <h2 class="page-title"><i class="ti ti-calendar me-2" style="color: var(--gym-verde);"></i> Clases Grupales</h2>
                        </div>
                    </div>
                </div>

                <!-- Formulario para programar nueva clase (solo admin) -->
                <?php if ($es_admin): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">Programar Nueva Clase</h3>
                    </div>
                    <form method="POST">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Tipo de Clase</label>
                                    <select name="id_clase" class="form-select" required>
                                        <option value="">Seleccionar...</option>
                                        <?php while($tc = $tipos_clases->fetch_assoc()): ?>
                                            <option value="<?php echo $tc['ID_Clase']; ?>"><?php echo $tc['Nombre']; ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Entrenador</label>
                                    <select name="id_entrenador" class="form-select" required>
                                        <option value="">Seleccionar...</option>
                                        <?php while($e = $entrenadores_lista->fetch_assoc()): ?>
                                            <option value="<?php echo $e['ID_Entrenador']; ?>"><?php echo $e['Nombre']; ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Fecha</label>
                                    <input type="date" name="fecha" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">Hora Inicio</label>
                                    <input type="time" name="hora_inicio" class="form-control" required>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">Hora Fin</label>
                                    <input type="time" name="hora_fin" class="form-control" required>
                                </div>
                                <div class="col-md-1 mb-3">
                                    <label class="form-label">Cupo</label>
                                    <input type="number" name="cupo_maximo" class="form-control" value="15" required>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Descripción</label>
                                    <textarea name="descripcion" class="form-control" rows="2" placeholder="Opcional"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" name="crear_clase_programada" class="btn btn-guardar">Programar Clase</button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Listado de clases programadas -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Próximas Clases</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                    <th>Clase</th>
                                    <th>Entrenador</th>
                                    <th>Cupo</th>
                                    <th>Estado</th>
                                    <?php if ($es_admin): ?>
                                    <th>Acciones</th>
                                    <?php endif; ?>
                                 </thead>
                            <tbody>
                                <?php while($c = $clases_programadas->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($c['Fecha'])); ?></td>
                                    <td><?php echo substr($c['Hora_Inicio'], 0, 5); ?> - <?php echo substr($c['Hora_Fin'], 0, 5); ?></td>
                                    <td><strong><?php echo $c['clase_nombre']; ?></strong><br><small class="text-muted"><?php echo $c['Descripcion']; ?></small></td>
                                    <td><?php echo $c['entrenador_nombre']; ?></td>
                                    <td><span class="badge bg-success"><?php echo $c['Cupo_Maximo']; ?> cupos</span></td>
                                    <td>
                                        <?php 
                                        $estado_color = 'secondary';
                                        if ($c['Estado'] == 'programada') $estado_color = 'success';
                                        if ($c['Estado'] == 'completada') $estado_color = 'info';
                                        if ($c['Estado'] == 'cancelada') $estado_color = 'danger';
                                        ?>
                                        <span class="badge bg-<?php echo $estado_color; ?>"><?php echo $c['Estado']; ?></span>
                                    </td>
                                    <?php if ($es_admin): ?>
                                    <td>
                                        <?php if ($c['Estado'] == 'programada'): ?>
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="id_clase_programada" value="<?php echo $c['ID_Clase_Programada']; ?>">
                                            <button type="submit" name="cancelar_clase" class="btn btn-sm btn-danger" onclick="return confirm('¿Cancelar esta clase?')">Cancelar</button>
                                            <button type="submit" name="completar_clase" class="btn btn-sm btn-info" onclick="return confirm('¿Marcar como completada?')">Completar</button>
                                        </form>
                                        <?php else: ?>
                                        <span class="text-muted">Sin acciones</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
</body>
</html>