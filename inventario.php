<?php
include "conexion.php";
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 1) {
    header("Location: login.php");
    exit();
}

$mensaje = "";

// ==================== CRUD PROVEEDORES ====================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion_proveedor'])) {
    $accion = $_POST['accion_proveedor'];
    $id_proveedor = (int)$_POST['id_proveedor'];
    $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
    $contacto = mysqli_real_escape_string($conn, $_POST['contacto']);
    $telefono = mysqli_real_escape_string($conn, $_POST['telefono']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $direccion = mysqli_real_escape_string($conn, $_POST['direccion']);
    
    if ($accion == 'crear') {
        $sql = "INSERT INTO proveedores (Nombre, Contacto, Telefono, Email, Direccion) 
                VALUES ('$nombre', '$contacto', '$telefono', '$email', '$direccion')";
        if ($conn->query($sql)) {
            $mensaje = '<div class="alert alert-success">Proveedor agregado</div>';
        } else {
            $mensaje = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
    } elseif ($accion == 'editar') {
        $sql = "UPDATE proveedores SET Nombre='$nombre', Contacto='$contacto', Telefono='$telefono', Email='$email', Direccion='$direccion' 
                WHERE ID_Proveedor=$id_proveedor";
        if ($conn->query($sql)) {
            $mensaje = '<div class="alert alert-success">Proveedor actualizado</div>';
        } else {
            $mensaje = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
    } elseif ($accion == 'eliminar') {
        $sql = "DELETE FROM proveedores WHERE ID_Proveedor=$id_proveedor";
        if ($conn->query($sql)) {
            $mensaje = '<div class="alert alert-success">Proveedor eliminado</div>';
        } else {
            $mensaje = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
    }
}

// ==================== CRUD PRODUCTOS ====================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'crear') {
    $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
    $descripcion = mysqli_real_escape_string($conn, $_POST['descripcion']);
    $precio_venta = (float)$_POST['precio_venta'];
    $stock_actual = (int)$_POST['stock_actual'];
    $stock_minimo = (int)$_POST['stock_minimo'];
    $id_proveedor = (int)$_POST['id_proveedor'] ?: 'NULL';
    
    $sql = "INSERT INTO productos (Nombre, Descripcion, Precio_Venta, Stock_Actual, Stock_Minimo, ID_Proveedor) 
            VALUES ('$nombre', '$descripcion', $precio_venta, $stock_actual, $stock_minimo, $id_proveedor)";
    
    if ($conn->query($sql)) {
        $mensaje = '<div class="alert alert-success">Producto agregado correctamente</div>';
    } else {
        $mensaje = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'editar') {
    $id = (int)$_POST['id_producto'];
    $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
    $descripcion = mysqli_real_escape_string($conn, $_POST['descripcion']);
    $precio_venta = (float)$_POST['precio_venta'];
    $stock_actual = (int)$_POST['stock_actual'];
    $stock_minimo = (int)$_POST['stock_minimo'];
    $id_proveedor = (int)$_POST['id_proveedor'] ?: 'NULL';
    
    $sql = "UPDATE productos SET 
            Nombre='$nombre', 
            Descripcion='$descripcion', 
            Precio_Venta=$precio_venta, 
            Stock_Actual=$stock_actual, 
            Stock_Minimo=$stock_minimo, 
            ID_Proveedor=$id_proveedor
            WHERE ID_Producto=$id";
    
    if ($conn->query($sql)) {
        $mensaje = '<div class="alert alert-success">Producto actualizado</div>';
    } else {
        $mensaje = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
    }
}

if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    $conn->query("DELETE FROM productos WHERE ID_Producto = $id");
    header("Location: inventario.php");
    exit();
}

// Obtener datos
$productos = $conn->query("SELECT p.*, pr.Nombre as proveedor_nombre 
                           FROM productos p 
                           LEFT JOIN proveedores pr ON p.ID_Proveedor = pr.ID_Proveedor 
                           ORDER BY p.Nombre");
$proveedores = $conn->query("SELECT * FROM proveedores ORDER BY Nombre");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventario - Sistema de Gimnasio</title>
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
                        <li class="nav-item"><a class="nav-link active" href="inventario.php"><i class="ti ti-package me-1"></i> Inventario</a></li>
                        <li class="nav-item"><a class="nav-link" href="pos.php"><i class="ti ti-shopping-cart me-1"></i> POS</a></li>
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
                            <h2 class="page-title"><i class="ti ti-package me-2" style="color: var(--gym-verde);"></i> Inventario</h2>
                        </div>
                    </div>
                </div>

                <!-- Pestañas -->
                <ul class="nav nav-tabs mb-4">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#productos">Productos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#proveedores">Proveedores</a>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- ==================== TABLA PRODUCTOS ==================== -->
                    <div class="tab-pane fade show active" id="productos">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Productos Registrados</h3>
                                <div class="card-actions">
                                    <button class="btn btn-guardar" onclick="abrirModalProducto()">Nuevo Producto</button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-vcenter">
                                    <thead>
                                        <tr><th>ID</th><th>Nombre</th><th>Proveedor</th><th>Precio</th><th>Stock</th><th>Stock Mínimo</th><th>Estado</th><th>Acciones</th> </thead>
                                    <tbody>
                                        <?php while($p = $productos->fetch_assoc()): 
                                            $stock_bajo = ($p['Stock_Actual'] <= $p['Stock_Minimo']);
                                        ?>
                                        <tr>
                                            <td><?php echo $p['ID_Producto']; ?></td>
                                            <td><?php echo htmlspecialchars($p['Nombre']); ?></td>
                                            <td><?php echo $p['proveedor_nombre'] ?? 'Sin proveedor'; ?></td>
                                            <td>$<?php echo number_format($p['Precio_Venta'], 2); ?></td>
                                            <td><?php if($stock_bajo): ?><span class="badge bg-danger"><?php else: ?><span class="badge bg-success"><?php endif; ?><?php echo $p['Stock_Actual']; ?></span></td>
                                            <td><?php echo $p['Stock_Minimo']; ?></td>
                                            <td><span class="badge <?php echo $stock_bajo ? 'bg-danger' : 'bg-success'; ?>"><?php echo $stock_bajo ? '¡Stock bajo!' : 'OK'; ?></span></td>
                                            <td>
                                                <button class="btn btn-modificar btn-sm" onclick="editarProducto(<?php echo htmlspecialchars(json_encode($p)); ?>)">Editar</button>
                                                <a href="?eliminar=<?php echo $p['ID_Producto']; ?>" class="btn btn-eliminar btn-sm" onclick="return confirm('¿Eliminar?')">Eliminar</a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== TABLA PROVEEDORES ==================== -->
                    <div class="tab-pane fade" id="proveedores">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Proveedores</h3>
                                <div class="card-actions">
                                    <button class="btn btn-guardar" onclick="abrirModalProveedor()">Nuevo Proveedor</button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-vcenter">
                                    <thead>
                                        <tr><th>ID</th><th>Nombre</th><th>Contacto</th><th>Teléfono</th><th>Email</th><th>Acciones</th> </thead>
                                    <tbody>
                                        <?php 
                                        $proveedores->data_seek(0);
                                        while($prov = $proveedores->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $prov['ID_Proveedor']; ?></td>
                                            <td><?php echo htmlspecialchars($prov['Nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($prov['Contacto']); ?></td>
                                            <td><?php echo $prov['Telefono']; ?></td>
                                            <td><?php echo $prov['Email']; ?></td>
                                            <td>
                                                <button class="btn btn-modificar btn-sm" onclick="editarProveedor(<?php echo htmlspecialchars(json_encode($prov)); ?>)">Editar</button>
                                                <a href="?eliminar_proveedor=<?php echo $prov['ID_Proveedor']; ?>" class="btn btn-eliminar btn-sm" onclick="return confirm('¿Eliminar proveedor?')">Eliminar</a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL PRODUCTO -->
    <div class="modal fade" id="modalProducto" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="accion" id="accion_producto" value="crear">
                    <input type="hidden" name="id_producto" id="id_producto">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalProductoTitle">Nuevo Producto</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="nombre" id="nombre" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Proveedor</label>
                            <select name="id_proveedor" id="id_proveedor" class="form-select">
                                <option value="">Sin proveedor</option>
                                <?php 
                                $proveedores->data_seek(0);
                                while($prov = $proveedores->fetch_assoc()): ?>
                                    <option value="<?php echo $prov['ID_Proveedor']; ?>"><?php echo $prov['Nombre']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Precio Venta</label>
                            <input type="number" step="0.01" name="precio_venta" id="precio_venta" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stock Actual</label>
                                <input type="number" name="stock_actual" id="stock_actual" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stock Mínimo</label>
                                <input type="number" name="stock_minimo" id="stock_minimo" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" id="descripcion" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-guardar">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL PROVEEDOR -->
    <div class="modal fade" id="modalProveedor" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="accion_proveedor" id="accion_proveedor" value="crear">
                    <input type="hidden" name="id_proveedor" id="id_proveedor_edit">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalProveedorTitle">Nuevo Proveedor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="nombre" id="prov_nombre" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contacto</label>
                            <input type="text" name="contacto" id="prov_contacto" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" id="prov_telefono" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="prov_email" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dirección</label>
                            <textarea name="direccion" id="prov_direccion" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-guardar">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
    <script>
        function abrirModalProducto() {
            document.getElementById('accion_producto').value = 'crear';
            document.getElementById('id_producto').value = '';
            document.getElementById('nombre').value = '';
            document.getElementById('id_proveedor').value = '';
            document.getElementById('precio_venta').value = '';
            document.getElementById('stock_actual').value = '';
            document.getElementById('stock_minimo').value = '';
            document.getElementById('descripcion').value = '';
            new bootstrap.Modal(document.getElementById('modalProducto')).show();
        }
        
        function editarProducto(p) {
            document.getElementById('accion_producto').value = 'editar';
            document.getElementById('id_producto').value = p.ID_Producto;
            document.getElementById('nombre').value = p.Nombre;
            document.getElementById('id_proveedor').value = p.ID_Proveedor || '';
            document.getElementById('precio_venta').value = p.Precio_Venta;
            document.getElementById('stock_actual').value = p.Stock_Actual;
            document.getElementById('stock_minimo').value = p.Stock_Minimo;
            document.getElementById('descripcion').value = p.Descripcion || '';
            new bootstrap.Modal(document.getElementById('modalProducto')).show();
        }
        
        function abrirModalProveedor() {
            document.getElementById('accion_proveedor').value = 'crear';
            document.getElementById('id_proveedor_edit').value = '';
            document.getElementById('prov_nombre').value = '';
            document.getElementById('prov_contacto').value = '';
            document.getElementById('prov_telefono').value = '';
            document.getElementById('prov_email').value = '';
            document.getElementById('prov_direccion').value = '';
            new bootstrap.Modal(document.getElementById('modalProveedor')).show();
        }
        
        function editarProveedor(p) {
            document.getElementById('accion_proveedor').value = 'editar';
            document.getElementById('id_proveedor_edit').value = p.ID_Proveedor;
            document.getElementById('prov_nombre').value = p.Nombre;
            document.getElementById('prov_contacto').value = p.Contacto || '';
            document.getElementById('prov_telefono').value = p.Telefono || '';
            document.getElementById('prov_email').value = p.Email || '';
            document.getElementById('prov_direccion').value = p.Direccion || '';
            new bootstrap.Modal(document.getElementById('modalProveedor')).show();
        }
    </script>
</body>
</html>