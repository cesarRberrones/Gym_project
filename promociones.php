<?php
include "conexion.php";
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 1) {
    header("Location: login.php");
    exit();
}

$mensaje = "";

// Crear promoción
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'crear') {
    $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
    $tipo = $_POST['tipo'];
    $valor = (float)$_POST['valor'];
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];
    
    $sql = "INSERT INTO promociones (Nombre, Tipo, Valor, Fecha_Inicio, Fecha_Fin) 
            VALUES ('$nombre', '$tipo', $valor, '$fecha_inicio', '$fecha_fin')";
    
    if ($conn->query($sql)) {
        $mensaje = '<div class="alert alert-success">Promoción creada</div>';
    } else {
        $mensaje = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
    }
}

// Eliminar promoción
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    $conn->query("DELETE FROM promociones WHERE ID_Promocion = $id");
    header("Location: promociones.php");
    exit();
}

$promociones = $conn->query("SELECT * FROM promociones ORDER BY Fecha_Inicio DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Promociones - Sistema de Gimnasio</title>
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
                        <li class="nav-item"><a class="nav-link" href="inventario.php"><i class="ti ti-package me-1"></i> Inventario</a></li>
                        <li class="nav-item"><a class="nav-link active" href="promociones.php"><i class="ti ti-tag me-1"></i> Promociones</a></li>
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
                            <h2 class="page-title"><i class="ti ti-tag me-2" style="color: var(--gym-verde);"></i> Promociones</h2>
                        </div>
                    </div>
                </div>

                <!-- Formulario nueva promoción -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">Nueva Promoción</h3>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="accion" value="crear">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Nombre</label>
                                    <input type="text" name="nombre" class="form-control" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Tipo</label>
                                    <select name="tipo" class="form-select" required>
                                        <option value="porcentaje">% Descuento</option>
                                        <option value="2x1">2x1</option>
                                        <option value="monto_fijo">Monto fijo</option>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">Valor</label>
                                    <input type="number" step="0.01" name="valor" class="form-control" required>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">Fecha Inicio</label>
                                    <input type="date" name="fecha_inicio" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">Fecha Fin</label>
                                    <input type="date" name="fecha_fin" class="form-control" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-guardar">Crear Promoción</button>
                        </div>
                    </form>
                </div>

                <!-- Lista de promociones -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Promociones Activas</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter">
                            <thead>
                                <tr><th>Nombre</th><th>Tipo</th><th>Valor</th><th>Vigencia</th><th>Acciones</th> </thead>
                            <tbody>
                                <?php while($p = $promociones->fetch_assoc()): 
                                    $hoy = date('Y-m-d');
                                    $activa = ($p['Fecha_Inicio'] <= $hoy && $p['Fecha_Fin'] >= $hoy);
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($p['Nombre']); ?><?php if($activa): ?> <span class="badge bg-success">Activa</span><?php endif; ?></td>
                                    <td><?php echo $p['Tipo']; ?></td>
                                    <td><?php echo $p['Valor']; ?><?php echo $p['Tipo'] == 'porcentaje' ? '%' : ''; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($p['Fecha_Inicio'])); ?> - <?php echo date('d/m/Y', strtotime($p['Fecha_Fin'])); ?></td>
                                    <td><a href="?eliminar=<?php echo $p['ID_Promocion']; ?>" class="btn btn-eliminar btn-sm" onclick="return confirm('¿Eliminar esta promoción?')">Eliminar</a></td>
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