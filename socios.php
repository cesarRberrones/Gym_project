<?php
include "conexion.php";
session_start();

//Procesar formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['accion'])) {
        $mensaje = "";
        $tipo = "";
        
        switch ($_POST['accion']) {
            case 'crear':
                //datos del usuario
                $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
                $email = mysqli_real_escape_string($conn, $_POST['email']);
                $telefono = mysqli_real_escape_string($conn, $_POST['telefono']);
                
                //datos del socio
                $fecha_nacimiento = $_POST['fecha_nacimiento'];
                $genero = mysqli_real_escape_string($conn, $_POST['genero']);
                $direccion = mysqli_real_escape_string($conn, $_POST['direccion']);
                
                //insertar usuario primero (rol 3 = Socio)
                $sql_usuario = "INSERT INTO usuarios (Nombre, Email, Telefono, Fecha_Registro, ID_Rol) 
                                VALUES ('$nombre', '$email', '$telefono', CURDATE(), 3)";
                
                if ($conn->query($sql_usuario)) {
                    $id_usuario = $conn->insert_id;
                    
                    //insertar socio
                    $sql_socio = "INSERT INTO socios (ID_Usuario, Fecha_Nacimiento, Genero, Direccion) 
                                  VALUES ($id_usuario, '$fecha_nacimiento', '$genero', '$direccion')";
                    
                    if ($conn->query($sql_socio)) {
                        $mensaje = "Socio creado exitosamente";
                        $tipo = "success";
                    } else {
                        $mensaje = "Error al crear socio: " . $conn->error;
                        $tipo = "error";
                    }
                } else {
                    $mensaje = "Error al crear usuario: " . $conn->error;
                    $tipo = "error";
                }
                break;
                
            case 'editar':
                $id_socio = (int)$_POST['id_socio'];
                $id_usuario = (int)$_POST['id_usuario'];
                
                // Datos del usuario
                $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
                $email = mysqli_real_escape_string($conn, $_POST['email']);
                $telefono = mysqli_real_escape_string($conn, $_POST['telefono']);
                
                //datos del socio
                $fecha_nacimiento = $_POST['fecha_nacimiento'];
                $genero = mysqli_real_escape_string($conn, $_POST['genero']);
                $direccion = mysqli_real_escape_string($conn, $_POST['direccion']);
                
                //actualizar usuuario
                $sql_usuario = "UPDATE usuarios SET 
                                Nombre='$nombre', 
                                Email='$email', 
                                Telefono='$telefono' 
                                WHERE ID_Usuario=$id_usuario";
                
                if ($conn->query($sql_usuario)) {
                    //actualizar socio
                    $sql_socio = "UPDATE socios SET 
                                  Fecha_Nacimiento='$fecha_nacimiento', 
                                  Genero='$genero', 
                                  Direccion='$direccion' 
                                  WHERE ID_Socios=$id_socio";
                    
                    if ($conn->query($sql_socio)) {
                        $mensaje = "Socio actualizado exitosamente";
                        $tipo = "success";
                    } else {
                        $mensaje = "Error al actualizar socio: " . $conn->error;
                        $tipo = "error";
                    }
                } else {
                    $mensaje = "Error al actualizar usuario: " . $conn->error;
                    $tipo = "error";
                }
                break;
                
            case 'eliminar':
                $id_socio = (int)$_POST['id_socio'];
                $id_usuario = (int)$_POST['id_usuario'];
                
                //verificar si tiene dependencias
                $check_membresia = $conn->query("SELECT COUNT(*) as total FROM socio_membresia WHERE ID_Socio = $id_socio");
                $row_m = $check_membresia->fetch_assoc();
                
                $check_asistencias = $conn->query("SELECT COUNT(*) as total FROM asistencias WHERE ID_Socio = $id_socio");
                $row_a = $check_asistencias->fetch_assoc();
                
                $check_pagos = $conn->query("SELECT COUNT(*) as total FROM ventas_pagos WHERE ID_Socio = $id_socio");
                $row_p = $check_pagos->fetch_assoc();
                
                if ($row_m['total'] > 0 || $row_a['total'] > 0 || $row_p['total'] > 0) {
                    $mensaje = "No se puede eliminar: El socio tiene registros asociados (membresías, asistencias o pagos)";
                    $tipo = "warning";
                } else {
                    // eliminar socio primero
                    $sql_socio = "DELETE FROM socios WHERE ID_Socios = $id_socio";
                    if ($conn->query($sql_socio)) {
                        // eliminar usuario
                        $sql_usuario = "DELETE FROM usuarios WHERE ID_Usuario = $id_usuario";
                        if ($conn->query($sql_usuario)) {
                            $mensaje = "Socio eliminado exitosamente";
                            $tipo = "success";
                        } else {
                            $mensaje = "Error al eliminar usuario: " . $conn->error;
                            $tipo = "error";
                        }
                    } else {
                        $mensaje = "Error al eliminar socio: " . $conn->error;
                        $tipo = "error";
                    }
                }
                break;
        }
        
        $_SESSION['mensaje'] = $mensaje;
        $_SESSION['tipo'] = $tipo;
        header("Location: socios.php");
        exit();
    }
}

// obtener lista de socios con JOIN
$sql = "SELECT s.*, u.Nombre, u.Email, u.Telefono, u.ID_Usuario,
               (SELECT COUNT(*) FROM socio_membresia WHERE ID_Socio = s.ID_Socios) as total_membresias,
               (SELECT MAX(Fecha_Fin) FROM socio_membresia WHERE ID_Socio = s.ID_Socios) as ultima_membresia
        FROM socios s
        INNER JOIN usuarios u ON s.ID_Usuario = u.ID_Usuario
        ORDER BY s.ID_Socios DESC";
$result = $conn->query($sql);

//estadísticas
$stats = [
    'total' => $conn->query("SELECT COUNT(*) as total FROM socios")->fetch_assoc()['total'],
    'con_membresia' => $conn->query("SELECT COUNT(DISTINCT ID_Socio) as total FROM socio_membresia WHERE Fecha_Fin >= CURDATE()")->fetch_assoc()['total'],
    'hombres' => $conn->query("SELECT COUNT(*) as total FROM socios WHERE Genero='M'")->fetch_assoc()['total'],
    'mujeres' => $conn->query("SELECT COUNT(*) as total FROM socios WHERE Genero='F'")->fetch_assoc()['total']
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Socios - Sistema de Gimnasio</title>
    
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
                            <a class="nav-link active" href="socios.php">
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
                            <a class="nav-link" href="ventas.php">
                                <i class="ti ti-credit-card me-1"></i> Ventas
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="page-wrapper">
            <div class="container-xl">
                <div class="page-header d-print-none mb-4">
                    <div class="row align-items-center">
                        <div class="col">
                            <h2 class="page-title">
                                <i class="ti ti-users me-2" style="color: var(--gym-verde);"></i>
                                Gestión de Socios
                            </h2>
                            <div class="text-muted mt-1">Administra los socios del gimnasio</div>
                        </div>
                    </div>
                </div>

                <!--tarjetas de estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">Total Socios</div>
                                    <div class="ms-auto lh-1">
                                        <i class="ti ti-users" style="color: var(--gym-verde);"></i>
                                    </div>
                                </div>
                                <div class="h1 mb-0"><?php echo $stats['total']; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">Con Membresía Activa</div>
                                    <div class="ms-auto lh-1">
                                        <i class="ti ti-check" style="color: var(--estado-activo);"></i>
                                    </div>
                                </div>
                                <div class="h1 mb-0"><?php echo $stats['con_membresia']; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">Hombres</div>
                                    <div class="ms-auto lh-1">
                                        <i class="ti ti-man" style="color: var(--btn-guardar);"></i>
                                    </div>
                                </div>
                                <div class="h1 mb-0"><?php echo $stats['hombres']; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">Mujeres</div>
                                    <div class="ms-auto lh-1">
                                        <i class="ti ti-woman" style="color: var(--btn-modificar);"></i>
                                    </div>
                                </div>
                                <div class="h1 mb-0"><?php echo $stats['mujeres']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card principal -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-list me-2" style="color: var(--gym-verde);"></i>
                            Listado de Socios
                        </h3>
                        <div class="card-actions">
                            <button class="btn btn-guardar" onclick="abrirModalCrear()">
                                <i class="ti ti-plus me-1"></i>
                                Nuevo Socio
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Género</th>
                                    <th>Membresías</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0) { 
                                    while($row = $result->fetch_assoc()) {
                                        $generoIcono = $row['Genero'] == 'M' ? 'ti ti-man' : ($row['Genero'] == 'F' ? 'ti ti-woman' : 'ti ti-gender-other');
                                        $fecha_venc = $row['ultima_membresia'] ? date('d/m/Y', strtotime($row['ultima_membresia'])) : 'Sin membresía';
                                ?>
                                <tr data-id="<?php echo $row['ID_Socios']; ?>"
                                    data-id-usuario="<?php echo $row['ID_Usuario']; ?>"
                                    data-nombre="<?php echo htmlspecialchars($row['Nombre'], ENT_QUOTES); ?>"
                                    data-email="<?php echo htmlspecialchars($row['Email'], ENT_QUOTES); ?>"
                                    data-telefono="<?php echo htmlspecialchars($row['Telefono'], ENT_QUOTES); ?>"
                                    data-fecha-nacimiento="<?php echo $row['Fecha_Nacimiento']; ?>"
                                    data-genero="<?php echo $row['Genero']; ?>"
                                    data-direccion="<?php echo htmlspecialchars($row['Direccion'] ?? '', ENT_QUOTES); ?>">
                                    <td>
                                        <span class="badge" style="background-color: var(--gym-negro); color: white;">
                                            #<?php echo str_pad($row['ID_Socios'], 3, '0', STR_PAD_LEFT); ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($row['Nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Telefono']); ?></td>
                                    <td>
                                        <span class="badge" style="background-color: var(--btn-guardar);">
                                            <i class="<?php echo $generoIcono; ?> me-1"></i>
                                            <?php echo $row['Genero'] == 'M' ? 'Hombre' : ($row['Genero'] == 'F' ? 'Mujer' : 'Otro'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge" style="background-color: var(--gym-verde);">
                                            <i class="ti ti-cards me-1"></i>
                                            <?php echo $row['total_membresias']; ?> membresías
                                        </span>
                                        <small class="d-block text-muted">Última: <?php echo $fecha_venc; ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-modificar btn-sm" onclick="abrirModalEditar(this)">
                                                <i class="ti ti-edit me-1"></i>
                                                Editar
                                            </button>
                                            <button class="btn btn-info btn-sm" onclick="verDetalles(<?php echo $row['ID_Socios']; ?>)">
                                                <i class="ti ti-eye me-1"></i>
                                                Ver
                                            </button>
                                            <button class="btn btn-eliminar btn-sm" onclick="confirmarEliminar(<?php echo $row['ID_Socios']; ?>, <?php echo $row['ID_Usuario']; ?>, '<?php echo htmlspecialchars($row['Nombre'], ENT_QUOTES); ?>')">
                                                <i class="ti ti-trash me-1"></i>
                                                Eliminar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php } 
                                } else { ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="empty">
                                            <div class="empty-icon">
                                                <i class="ti ti-users" style="font-size: 3rem;"></i>
                                            </div>
                                            <p class="empty-title h3">No hay socios registrados</p>
                                            <p class="empty-subtitle text-muted">
                                                Comienza agregando el primer socio
                                            </p>
                                            <div class="empty-action">
                                                <button class="btn btn-guardar" onclick="abrirModalCrear()">
                                                    <i class="ti ti-plus"></i>
                                                    Crear Socio
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL CREAR/EDITAR SOCIO -->
    <div class="modal fade" id="modalSocio" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="ti ti-plus me-2"></i>
                        Nuevo Socio
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="socios.php" id="formSocio">
                    <div class="modal-body">
                        <input type="hidden" name="id_socio" id="id_socio">
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
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Teléfono</label>
                                <input type="text" class="form-control" name="telefono" id="telefono" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Fecha de nacimiento</label>
                                <input type="date" class="form-control" name="fecha_nacimiento" id="fecha_nacimiento" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Género</label>
                                <select class="form-select" name="genero" id="genero" required>
                                    <option value="M">Hombre</option>
                                    <option value="F">Mujer</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Dirección</label>
                                <input type="text" class="form-control" name="direccion" id="direccion">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="ti ti-x me-1"></i>
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-guardar">
                            <i class="ti ti-device-floppy me-2"></i>
                            Guardar Socio
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        <?php if (isset($_SESSION['mensaje'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '<?php echo $_SESSION['tipo']; ?>',
                title: '<?php echo $_SESSION['tipo'] == "success" ? "¡Éxito!" : ($_SESSION["tipo"] == "error" ? "Error" : "Advertencia"); ?>',
                text: '<?php echo $_SESSION['mensaje']; ?>',
                timer: 3000,
                showConfirmButton: true
            });
        });
        <?php 
            unset($_SESSION['mensaje']);
            unset($_SESSION['tipo']);
        endif; 
        ?>

        function abrirModalCrear() {
            document.getElementById('modalTitle').innerHTML = '<i class="ti ti-plus me-2"></i>Nuevo Socio';
            document.getElementById('accion').value = 'crear';
            document.getElementById('id_socio').value = '';
            document.getElementById('id_usuario').value = '';
            document.getElementById('nombre').value = '';
            document.getElementById('email').value = '';
            document.getElementById('telefono').value = '';
            document.getElementById('fecha_nacimiento').value = '';
            document.getElementById('genero').value = 'M';
            document.getElementById('direccion').value = '';
            
            var modal = new bootstrap.Modal(document.getElementById('modalSocio'));
            modal.show();
        }

        function abrirModalEditar(boton) {
            var fila = boton.closest('tr');
            
            document.getElementById('modalTitle').innerHTML = '<i class="ti ti-edit me-2"></i>Editar Socio';
            document.getElementById('accion').value = 'editar';
            document.getElementById('id_socio').value = fila.dataset.id;
            document.getElementById('id_usuario').value = fila.dataset.idUsuario;
            document.getElementById('nombre').value = fila.dataset.nombre;
            document.getElementById('email').value = fila.dataset.email;
            document.getElementById('telefono').value = fila.dataset.telefono;
            document.getElementById('fecha_nacimiento').value = fila.dataset.fechaNacimiento;
            document.getElementById('genero').value = fila.dataset.genero;
            document.getElementById('direccion').value = fila.dataset.direccion || '';
            
            var modal = new bootstrap.Modal(document.getElementById('modalSocio'));
            modal.show();
        }

        function verDetalles(id) {
            window.location.href = 'socio_detalle.php?id=' + id;
        }

        function confirmarEliminar(id_socio, id_usuario, nombre) {
            Swal.fire({
                title: '¿Eliminar socio?',
                html: `¿Estás seguro de eliminar al socio <strong>${nombre}</strong>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: 'var(--btn-eliminar)',
                cancelButtonColor: 'var(--btn-guardar)',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'socios.php';
                    
                    var inputAccion = document.createElement('input');
                    inputAccion.type = 'hidden';
                    inputAccion.name = 'accion';
                    inputAccion.value = 'eliminar';
                    
                    var inputIdSocio = document.createElement('input');
                    inputIdSocio.type = 'hidden';
                    inputIdSocio.name = 'id_socio';
                    inputIdSocio.value = id_socio;
                    
                    var inputIdUsuario = document.createElement('input');
                    inputIdUsuario.type = 'hidden';
                    inputIdUsuario.name = 'id_usuario';
                    inputIdUsuario.value = id_usuario;
                    
                    form.appendChild(inputAccion);
                    form.appendChild(inputIdSocio);
                    form.appendChild(inputIdUsuario);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        document.getElementById('formSocio').addEventListener('submit', function(e) {
            const nombre = document.getElementById('nombre').value.trim();
            const email = document.getElementById('email').value.trim();
            const telefono = document.getElementById('telefono').value.trim();
            
            if (nombre === '') {
                e.preventDefault();
                Swal.fire('Error', 'El nombre es obligatorio', 'error');
                return false;
            }
            
            if (email === '' || !email.includes('@')) {
                e.preventDefault();
                Swal.fire('Error', 'Email válido es obligatorio', 'error');
                return false;
            }
            
            if (telefono === '') {
                e.preventDefault();
                Swal.fire('Error', 'El teléfono es obligatorio', 'error');
                return false;
            }
        });
    </script>
</body>
</html>