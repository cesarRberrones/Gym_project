<?php
include "conexion.php";

// Iniciar sesión para mensajes
session_start();

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['accion'])) {
        $mensaje = "";
        $tipo = "";
        
        switch ($_POST['accion']) {
            case 'crear':
                $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
                $duracion = (int)$_POST['duracion_dias'];
                $precio = (float)$_POST['precio'];
                $descripcion = mysqli_real_escape_string($conn, $_POST['descripcion']);
                $estado = mysqli_real_escape_string($conn, $_POST['estado']);
                
                $sql = "INSERT INTO tipos_membresia (Nombre, Duracion_Dias, Precio, Descripcion, Estado) 
                        VALUES ('$nombre', $duracion, $precio, '$descripcion', '$estado')";
                
                if ($conn->query($sql)) {
                    $mensaje = "Membresía creada exitosamente";
                    $tipo = "success";
                } else {
                    $mensaje = "Error al crear: " . $conn->error;
                    $tipo = "error";
                }
                break;
                
            case 'editar':
                $id = (int)$_POST['id_tipo_membresia'];
                $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
                $duracion = (int)$_POST['duracion_dias'];
                $precio = (float)$_POST['precio'];
                $descripcion = mysqli_real_escape_string($conn, $_POST['descripcion']);
                $estado = mysqli_real_escape_string($conn, $_POST['estado']);
                
                $sql = "UPDATE tipos_membresia SET 
                        Nombre='$nombre', 
                        Duracion_Dias=$duracion, 
                        Precio=$precio, 
                        Descripcion='$descripcion', 
                        Estado='$estado' 
                        WHERE ID_TipoMembresía=$id";
                
                if ($conn->query($sql)) {
                    $mensaje = "Membresía actualizada exitosamente";
                    $tipo = "success";
                } else {
                    $mensaje = "Error al actualizar: " . $conn->error;
                    $tipo = "error";
                }
                break;
                
            case 'eliminar':
                $id = (int)$_POST['id_tipo_membresia'];
                
                //verificar si la membresía está siendo usada
                $check = $conn->query("SELECT COUNT(*) as total FROM socio_membresia WHERE ID_TipoMembresía = $id");
                $row = $check->fetch_assoc();
                
                if ($row['total'] > 0) {
                    $mensaje = "No se puede eliminar: Esta membresía tiene socios asociados";
                    $tipo = "warning";
                } else {
                    $sql = "DELETE FROM tipos_membresia WHERE ID_TipoMembresía = $id";
                    if ($conn->query($sql)) {
                        $mensaje = "Membresía eliminada exitosamente";
                        $tipo = "success";
                    } else {
                        $mensaje = "Error al eliminar: " . $conn->error;
                        $tipo = "error";
                    }
                }
                break;
        }
        
        $_SESSION['mensaje'] = $mensaje;
        $_SESSION['tipo'] = $tipo;
        header("Location: membresias.php");
        exit();
    }
}

//obtener estadísticas
$stats = [
    'total' => 0,
    'activas' => 0,
    'inactivas' => 0,
    'precio_promedio' => 0
];

$result = $conn->query("SELECT COUNT(*) as total FROM tipos_membresia");
if ($result) $stats['total'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM tipos_membresia WHERE Estado='activo'");
if ($result) $stats['activas'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM tipos_membresia WHERE Estado='inactivo'");
if ($result) $stats['inactivas'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT AVG(Precio) as promedio FROM tipos_membresia");
if ($result) $stats['precio_promedio'] = $result->fetch_assoc()['promedio'] ?? 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Membresías - Sistema de Gimnasio</title>
    
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
                    <a class="nav-link active" href="membresias.php">
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
                    <a class="nav-link" href="logout.php">
                        <i class="ti ti-logout me-1"></i> Salir
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
                                <i class="ti ti-cards me-2" style="color: var(--gym-verde);"></i>
                                Gestión de Membresías
                            </h2>
                            <div class="text-muted mt-1">Administra los diferentes tipos de membresías del gimnasio</div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">Total Membresías</div>
                                    <div class="ms-auto lh-1">
                                        <i class="ti ti-cards" style="color: var(--gym-verde);"></i>
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
                                    <div class="subheader">Membresías Activas</div>
                                    <div class="ms-auto lh-1">
                                        <i class="ti ti-check" style="color: var(--estado-activo);"></i>
                                    </div>
                                </div>
                                <div class="h1 mb-0"><?php echo $stats['activas']; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">Membresías Inactivas</div>
                                    <div class="ms-auto lh-1">
                                        <i class="ti ti-x" style="color: var(--estado-inactivo);"></i>
                                    </div>
                                </div>
                                <div class="h1 mb-0"><?php echo $stats['inactivas']; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">Precio Promedio</div>
                                    <div class="ms-auto lh-1">
                                        <i class="ti ti-currency-dollar" style="color: var(--gym-verde);"></i>
                                    </div>
                                </div>
                                <div class="h1 mb-0">$<?php echo number_format($stats['precio_promedio'] ?? 0, 2); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-list me-2" style="color: var(--gym-verde);"></i>
                            Listado de Membresías
                        </h3>
                        <div class="card-actions">
                            <button class="btn btn-guardar" onclick="abrirModalCrear()">
                                <i class="ti ti-plus me-1"></i>
                                Nueva Membresía
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Duración</th>
                                    <th>Precio</th>
                                    <th>Descripción</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT * FROM tipos_membresia ORDER BY ID_TipoMembresía DESC";
                                $result = $conn->query($sql);

                                if ($result && $result->num_rows > 0) {
                                    while($row = $result->fetch_assoc()) {
                                        $estadoClase = ($row['Estado'] == "activo") ? "estado-activo" : "estado-inactivo";
                                        $iconoEstado = ($row['Estado'] == "activo") ? "ti ti-check" : "ti ti-x";
                                ?>
                                <tr data-id="<?php echo $row['ID_TipoMembresía']; ?>" 
                                    data-nombre="<?php echo htmlspecialchars($row['Nombre'], ENT_QUOTES); ?>"
                                    data-duracion="<?php echo $row['Duracion_Dias']; ?>"
                                    data-precio="<?php echo $row['Precio']; ?>"
                                    data-descripcion="<?php echo htmlspecialchars($row['Descripcion'] ?? '', ENT_QUOTES); ?>"
                                    data-estado="<?php echo $row['Estado']; ?>">
                                    <td>
                                        <span class="badge" style="background-color: var(--gym-negro); color: white;">
                                            #<?php echo str_pad($row['ID_TipoMembresía'], 3, '0', STR_PAD_LEFT); ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($row['Nombre']); ?></td>
                                    <td>
                                        <span class="badge" style="background-color: var(--btn-guardar); color: white;">
                                            <i class="ti ti-calendar me-1"></i>
                                            <?php echo $row['Duracion_Dias']; ?> días
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge" style="background-color: var(--gym-verde); color: white;">
                                            <i class="ti ti-currency-dollar me-1"></i>
                                            $<?php echo number_format($row['Precio'], 2); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $desc = $row['Descripcion'] ?? 'Sin descripción';
                                        echo strlen($desc) > 30 ? substr($desc, 0, 30) . '...' : $desc;
                                        ?>
                                    </td>
                                    <td>
                                        <span class="<?php echo $estadoClase; ?>">
                                            <i class="<?php echo $iconoEstado; ?> me-1"></i>
                                            <?php echo ucfirst($row['Estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-modificar btn-sm" onclick="abrirModalEditar(this)">
                                                <i class="ti ti-edit me-1"></i>
                                                Editar
                                            </button>
                                            <button class="btn btn-eliminar btn-sm" onclick="confirmarEliminar(<?php echo $row['ID_TipoMembresía']; ?>, '<?php echo htmlspecialchars($row['Nombre'], ENT_QUOTES); ?>')">
                                                <i class="ti ti-trash me-1"></i>
                                                Eliminar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                    }
                                } else { 
                                ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="empty">
                                            <div class="empty-icon">
                                                <i class="ti ti-cards" style="font-size: 3rem;"></i>
                                            </div>
                                            <p class="empty-title h3">No hay membresías registradas</p>
                                            <p class="empty-subtitle text-muted">
                                                Agregue una nueva membresía para su gimnasio
                                            </p>
                                            <div class="empty-action">
                                                <button class="btn btn-guardar" onclick="abrirModalCrear()">
                                                    <i class="ti ti-plus"></i>
                                                    Crear Membresía
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

    <!-- editar/crear -->
    <div class="modal fade" id="modalMembresia" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="ti ti-plus me-2"></i>
                        Nueva Membresía
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="membresias.php" id="formMembresia">
                    <div class="modal-body">
                        <input type="hidden" name="id_tipo_membresia" id="id_tipo_membresia">
                        <input type="hidden" name="accion" id="accion" value="crear">
                        
                        <div class="mb-3">
                            <label class="form-label required">Nombre de la membresía</label>
                            <input type="text" class="form-control" name="nombre" id="nombre" 
                                   placeholder="Ej: Membresía Anual, Membresía Premium" required maxlength="100">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Duración (días)</label>
                                <input type="number" class="form-control" name="duracion_dias" id="duracion_dias" 
                                       min="1" max="3650" required placeholder="30">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Precio ($)</label>
                                <input type="number" step="0.01" class="form-control" name="precio" id="precio" 
                                       min="0" required placeholder="0.00">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="descripcion" id="descripcion" 
                                      rows="3" placeholder="Describe los beneficios y características de esta membresía..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label required">Estado</label>
                            <select class="form-select" name="estado" id="estado" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="ti ti-x me-1"></i>
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-guardar">
                            <i class="ti ti-device-floppy me-2"></i>
                            Guardar Membresía
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


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
            document.getElementById('modalTitle').innerHTML = '<i class="ti ti-plus me-2"></i>Nueva Membresía';
            document.getElementById('accion').value = 'crear';
            document.getElementById('id_tipo_membresia').value = '';
            document.getElementById('nombre').value = '';
            document.getElementById('duracion_dias').value = '';
            document.getElementById('precio').value = '';
            document.getElementById('descripcion').value = '';
            document.getElementById('estado').value = 'activo';
            
            var modal = new bootstrap.Modal(document.getElementById('modalMembresia'));
            modal.show();
        }

        function abrirModalEditar(boton) {
            var fila = boton.closest('tr');
            
            document.getElementById('modalTitle').innerHTML = '<i class="ti ti-edit me-2"></i>Editar Membresía';
            document.getElementById('accion').value = 'editar';
            document.getElementById('id_tipo_membresia').value = fila.dataset.id;
            document.getElementById('nombre').value = fila.dataset.nombre;
            document.getElementById('duracion_dias').value = fila.dataset.duracion;
            document.getElementById('precio').value = fila.dataset.precio;
            document.getElementById('descripcion').value = fila.dataset.descripcion || '';
            document.getElementById('estado').value = fila.dataset.estado;
            
            var modal = new bootstrap.Modal(document.getElementById('modalMembresia'));
            modal.show();
        }

        function confirmarEliminar(id, nombre) {
            Swal.fire({
                title: '¿Eliminar membresía?',
                html: `¿Estás seguro de eliminar la membresía <strong>${nombre}</strong>?`,
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
                    form.action = 'membresias.php';
                    
                    var inputAccion = document.createElement('input');
                    inputAccion.type = 'hidden';
                    inputAccion.name = 'accion';
                    inputAccion.value = 'eliminar';
                    
                    var inputId = document.createElement('input');
                    inputId.type = 'hidden';
                    inputId.name = 'id_tipo_membresia';
                    inputId.value = id;
                    
                    form.appendChild(inputAccion);
                    form.appendChild(inputId);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        document.getElementById('formMembresia').addEventListener('submit', function(e) {
            const nombre = document.getElementById('nombre').value.trim();
            const duracion = document.getElementById('duracion_dias').value;
            const precio = document.getElementById('precio').value;
            
            if (nombre === '') {
                e.preventDefault();
                Swal.fire('Error', 'El nombre de la membresía es obligatorio', 'error');
                return false;
            }
            
            if (duracion < 1 || duracion > 3650) {
                e.preventDefault();
                Swal.fire('Error', 'La duración debe estar entre 1 y 3650 días', 'error');
                return false;
            }
            
            if (precio < 0) {
                e.preventDefault();
                Swal.fire('Error', 'El precio no puede ser negativo', 'error');
                return false;
            }
        });
    </script>
</body>
</html>