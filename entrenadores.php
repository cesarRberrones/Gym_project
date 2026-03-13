<?php
include "conexion.php";
session_start();

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['accion'])) {
        $mensaje = "";
        $tipo = "";
        
        switch ($_POST['accion']) {
            case 'crear':
                // Datos del usuario
                $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
                $email = mysqli_real_escape_string($conn, $_POST['email']);
                $telefono = mysqli_real_escape_string($conn, $_POST['telefono']);
                
                // Datos del entrenador
                $especialidad = mysqli_real_escape_string($conn, $_POST['especialidad']);
                
                // Insertar usuario (Rol 2 = Entrenador)
                $sql_usuario = "INSERT INTO usuarios (Nombre, Email, Telefono, Fecha_Registro, ID_Rol) 
                                VALUES ('$nombre', '$email', '$telefono', CURDATE(), 2)";
                
                if ($conn->query($sql_usuario)) {
                    $id_usuario = $conn->insert_id;
                    
                    // Insertar entrenador
                    $sql_entrenador = "INSERT INTO entrenadores (ID_Usuario, Especialidad) 
                                  VALUES ($id_usuario, '$especialidad')";
                    
                    if ($conn->query($sql_entrenador)) {
                        $mensaje = "Entrenador creado exitosamente";
                        $tipo = "success";
                    } else {
                        $mensaje = "Error al crear entrenador: " . $conn->error;
                        $tipo = "error";
                    }
                } else {
                    $mensaje = "Error al crear usuario: " . $conn->error;
                    $tipo = "error";
                }
                break;
                
            case 'editar':
                $id_entrenador = (int)$_POST['id_entrenador'];
                $id_usuario = (int)$_POST['id_usuario'];
                
                // Datos del usuario
                $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
                $email = mysqli_real_escape_string($conn, $_POST['email']);
                $telefono = mysqli_real_escape_string($conn, $_POST['telefono']);
                
                // Datos del entrenador
                $especialidad = mysqli_real_escape_string($conn, $_POST['especialidad']);
                
                // Actualizar usuario
                $sql_usuario = "UPDATE usuarios SET 
                                Nombre='$nombre', 
                                Email='$email', 
                                Telefono='$telefono' 
                                WHERE ID_Usuario=$id_usuario";
                
                if ($conn->query($sql_usuario)) {
                    // Actualizar entrenador
                    $sql_entrenador = "UPDATE entrenadores SET 
                                  Especialidad='$especialidad'
                                  WHERE ID_Entrenador=$id_entrenador";
                    
                    if ($conn->query($sql_entrenador)) {
                        $mensaje = "Entrenador actualizado exitosamente";
                        $tipo = "success";
                    } else {
                        $mensaje = "Error al actualizar entrenador: " . $conn->error;
                        $tipo = "error";
                    }
                } else {
                    $mensaje = "Error al actualizar usuario: " . $conn->error;
                    $tipo = "error";
                }
                break;
                
            case 'eliminar':
                $id_entrenador = (int)$_POST['id_entrenador'];
                $id_usuario = (int)$_POST['id_usuario'];
                
                // Verificar dependencias (clases, evaluaciones, planes)
                $check_clases = $conn->query("SELECT COUNT(*) as total FROM clases WHERE ID_Entrenador = $id_entrenador")->fetch_assoc();
                $check_eval = $conn->query("SELECT COUNT(*) as total FROM evaluacion_fisica WHERE ID_Entrenador = $id_entrenador")->fetch_assoc();
                $check_planes = $conn->query("SELECT COUNT(*) as total FROM planes_rutinas WHERE ID_Entrenador = $id_entrenador")->fetch_assoc();
                
                if ($check_clases['total'] > 0 || $check_eval['total'] > 0 || $check_planes['total'] > 0) {
                    $mensaje = "No se puede eliminar: El entrenador tiene clases, evaluaciones o rutinas asignadas.";
                    $tipo = "warning";
                } else {
                    // Eliminar entrenador
                    if ($conn->query("DELETE FROM entrenadores WHERE ID_Entrenador = $id_entrenador")) {
                        // Eliminar usuario
                        if ($conn->query("DELETE FROM usuarios WHERE ID_Usuario = $id_usuario")) {
                            $mensaje = "Entrenador eliminado exitosamente";
                            $tipo = "success";
                        } else {
                            $mensaje = "Error al eliminar usuario: " . $conn->error;
                            $tipo = "error";
                        }
                    } else {
                        $mensaje = "Error al eliminar entrenador: " . $conn->error;
                        $tipo = "error";
                    }
                }
                break;
        }
        
        $_SESSION['mensaje'] = $mensaje;
        $_SESSION['tipo'] = $tipo;
        header("Location: entrenadores.php");
        exit();
    }
}

// Obtener lista de entrenadores
$sql = "SELECT e.*, u.Nombre, u.Email, u.Telefono, u.foto,
               (SELECT COUNT(*) FROM clases WHERE ID_Entrenador = e.ID_Entrenador) as total_clases
        FROM entrenadores e
        INNER JOIN usuarios u ON e.ID_Usuario = u.ID_Usuario
        ORDER BY e.ID_Entrenador DESC";
$result = $conn->query($sql);

// Estadísticas
$stats = [
    'total' => $conn->query("SELECT COUNT(*) as total FROM entrenadores")->fetch_assoc()['total'],
    'clases_totales' => $conn->query("SELECT COUNT(*) as total FROM clases")->fetch_assoc()['total']
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrenadores - Sistema de Gimnasio</title>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="css/gym-style.css" rel="stylesheet"/>
</head>
<body>
    <div class="page">
        <header class="navbar navbar-expand-md navbar-gym">
            <div class="container-xl">
                <a href="index.php" class="navbar-brand d-flex align-items-center">
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
                        <li class="nav-item"><a class="nav-link active" href="entrenadores.php"><i class="ti ti-run me-1"></i> Entrenadores</a></li>
                        <li class="nav-item"><a class="nav-link" href="clases.php"><i class="ti ti-calendar me-1"></i> Clases</a></li>
                        <li class="nav-item"><a class="nav-link" href="reportes.php"><i class="ti ti-chart-bar me-1"></i> Reportes</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="page-wrapper">
            <div class="container-xl mt-4">
                <div class="page-header d-print-none mb-4">
                    <div class="row align-items-center">
                        <div class="col">
                            <h2 class="page-title"><i class="ti ti-run me-2" style="color: var(--gym-verde);"></i> Gestión de Entrenadores</h2>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">Total Entrenadores</div>
                                    <div class="ms-auto lh-1"><i class="ti ti-run" style="color: var(--gym-verde);"></i></div>
                                </div>
                                <div class="h1 mb-0"><?php echo $stats['total']; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">Clases Totales Impartidas</div>
                                    <div class="ms-auto lh-1"><i class="ti ti-calendar" style="color: var(--estado-activo);"></i></div>
                                </div>
                                <div class="h1 mb-0"><?php echo $stats['clases_totales']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Listado de Staff</h3>
                        <div class="card-actions">
                            <button class="btn btn-guardar" onclick="abrirModalCrear()"><i class="ti ti-plus me-1"></i> Nuevo Entrenador</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Especialidad</th>
                                    <th>Contacto</th>
                                    <th>Clases</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0) { 
                                    while($row = $result->fetch_assoc()) { ?>
                                <tr data-id="<?php echo $row['ID_Entrenador']; ?>"
                                    data-id-usuario="<?php echo $row['ID_Usuario']; ?>"
                                    data-nombre="<?php echo htmlspecialchars($row['Nombre'], ENT_QUOTES); ?>"
                                    data-email="<?php echo htmlspecialchars($row['Email'], ENT_QUOTES); ?>"
                                    data-telefono="<?php echo htmlspecialchars($row['Telefono'], ENT_QUOTES); ?>"
                                    data-especialidad="<?php echo htmlspecialchars($row['Especialidad'], ENT_QUOTES); ?>">
                                    <td><span class="badge" style="background-color: var(--gym-negro); color: white;">#<?php echo str_pad($row['ID_Entrenador'], 3, '0', STR_PAD_LEFT); ?></span></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($row['Nombre']); ?></td>
                                    <td><span class="badge bg-blue-lt"><?php echo htmlspecialchars($row['Especialidad']); ?></span></td>
                                    <td>
                                        <div class="text-muted"><i class="ti ti-mail"></i> <?php echo htmlspecialchars($row['Email']); ?></div>
                                        <div class="text-muted"><i class="ti ti-phone"></i> <?php echo htmlspecialchars($row['Telefono']); ?></div>
                                    </td>
                                    <td><?php echo $row['total_clases']; ?> asignadas</td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-modificar btn-sm" onclick="abrirModalEditar(this)"><i class="ti ti-edit me-1"></i> Editar</button>
                                            <button class="btn btn-eliminar btn-sm" onclick="confirmarEliminar(<?php echo $row['ID_Entrenador']; ?>, <?php echo $row['ID_Usuario']; ?>, '<?php echo htmlspecialchars($row['Nombre'], ENT_QUOTES); ?>')"><i class="ti ti-trash me-1"></i> Eliminar</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php } } else { ?>
                                <tr><td colspan="6" class="text-center py-4">No hay entrenadores registrados.</td></tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEntrenador" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nuevo Entrenador</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="entrenadores.php" id="formEntrenador">
                    <div class="modal-body">
                        <input type="hidden" name="id_entrenador" id="id_entrenador">
                        <input type="hidden" name="id_usuario" id="id_usuario">
                        <input type="hidden" name="accion" id="accion" value="crear">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Nombre completo</label>
                                <input type="text" class="form-control" name="nombre" id="nombre" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Email</label>
                                <input type="email" class="form-control" name="email" id="email" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Teléfono</label>
                                <input type="text" class="form-control" name="telefono" id="telefono" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Especialidad</label>
                                <input type="text" class="form-control" name="especialidad" id="especialidad" placeholder="Ej. Crossfit, Yoga, Pesas..." required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-guardar">Guardar Entrenador</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        <?php if (isset($_SESSION['mensaje'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '<?php echo $_SESSION['tipo']; ?>',
                title: 'Mensaje',
                text: '<?php echo $_SESSION['mensaje']; ?>',
                timer: 3000
            });
        });
        <?php unset($_SESSION['mensaje']); unset($_SESSION['tipo']); endif; ?>

        function abrirModalCrear() {
            document.getElementById('modalTitle').innerText = 'Nuevo Entrenador';
            document.getElementById('accion').value = 'crear';
            document.getElementById('formEntrenador').reset();
            document.getElementById('id_entrenador').value = '';
            document.getElementById('id_usuario').value = '';
            new bootstrap.Modal(document.getElementById('modalEntrenador')).show();
        }

        function abrirModalEditar(btn) {
            let tr = btn.closest('tr');
            document.getElementById('modalTitle').innerText = 'Editar Entrenador';
            document.getElementById('accion').value = 'editar';
            document.getElementById('id_entrenador').value = tr.dataset.id;
            document.getElementById('id_usuario').value = tr.dataset.idUsuario;
            document.getElementById('nombre').value = tr.dataset.nombre;
            document.getElementById('email').value = tr.dataset.email;
            document.getElementById('telefono').value = tr.dataset.telefono;
            document.getElementById('especialidad').value = tr.dataset.especialidad;
            new bootstrap.Modal(document.getElementById('modalEntrenador')).show();
        }

        function confirmarEliminar(id_entrenador, id_usuario, nombre) {
            Swal.fire({
                title: '¿Eliminar entrenador?',
                text: `Se eliminará a ${nombre}`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Sí, eliminar'
            }).then((result) => {
                if (result.isConfirmed) {
                    let form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `<input type="hidden" name="accion" value="eliminar">
                                      <input type="hidden" name="id_entrenador" value="${id_entrenador}">
                                      <input type="hidden" name="id_usuario" value="${id_usuario}">`;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
</body>
</html>