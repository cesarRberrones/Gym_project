<?php
include "conexion.php";
session_start();

//obtener listas para los selects
$socios = $conn->query("SELECT s.ID_Socios, u.Nombre FROM socios s INNER JOIN usuarios u ON s.ID_Usuario = u.ID_Usuario ORDER BY u.Nombre");
$membresias = $conn->query("SELECT * FROM tipos_membresia WHERE Estado = 'activo' ORDER BY Nombre");


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_socio = (int)$_POST['id_socio'];
    $id_membresia = (int)$_POST['id_membresia'];
    $fecha_inicio = $_POST['fecha_inicio'];
    
    //calcular fecha de fin
    $result = $conn->query("SELECT Duracion_Dias FROM tipos_membresia WHERE ID_TipoMembresía = $id_membresia");
    $row = $result->fetch_assoc();
    $duracion = $row['Duracion_Dias'];
    
    $fecha_fin = date('Y-m-d', strtotime($fecha_inicio . " + $duracion days"));
    
    //insertar asignación
    $sql = "INSERT INTO socio_membresia (ID_Socio, ID_TipoMembresía, Fecha_Inicio, Fecha_Fin) 
            VALUES ($id_socio, $id_membresia, '$fecha_inicio', '$fecha_fin')";
    
    if ($conn->query($sql)) {
        $_SESSION['mensaje'] = "Membresía asignada exitosamente";
        $_SESSION['tipo'] = "success";
        header("Location: socio_detalle.php?id=" . $id_socio);
        exit();
    } else {
        $error = "Error al asignar: " . $conn->error;
    }
}

// socio específico por GET
$socio_seleccionado = isset($_GET['socio']) ? (int)$_GET['socio'] : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Asignar Membresía - Sistema de Gimnasio</title>
    
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
                                <i class="ti ti-cards me-2" style="color: var(--gym-verde);"></i>
                                Asignar Membresía a Socio
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

                <div class="row">
                    <div class="col-md-6 mx-auto">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Nueva Asignación</h3>
                            </div>
                            <div class="card-body">
                                <?php if (isset($error)): ?>
                                    <div class="alert alert-danger"><?php echo $error; ?></div>
                                <?php endif; ?>

                                <form method="POST" action="asignar_membresia.php">
                                    <div class="mb-3">
                                        <label class="form-label required">Socio</label>
                                        <select class="form-select" name="id_socio" required>
                                            <option value="">Seleccionar socio...</option>
                                            <?php 
                                            if ($socios && $socios->num_rows > 0) {
                                                while($s = $socios->fetch_assoc()): 
                                            ?>
                                                <option value="<?php echo $s['ID_Socios']; ?>" 
                                                    <?php echo ($socio_seleccionado == $s['ID_Socios']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($s['Nombre']); ?>
                                                </option>
                                            <?php 
                                                endwhile;
                                            } else {
                                                echo '<option value="">No hay socios disponibles</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label required">Membresía</label>
                                        <select class="form-select" name="id_membresia" required>
                                            <option value="">Seleccionar membresía...</option>
                                            <?php 
                                            if ($membresias && $membresias->num_rows > 0) {
                                                while($m = $membresias->fetch_assoc()): 
                                            ?>
                                                <option value="<?php echo $m['ID_TipoMembresía']; ?>">
                                                    <?php echo htmlspecialchars($m['Nombre']); ?> - 
                                                    $<?php echo number_format($m['Precio'], 2); ?> 
                                                    (<?php echo $m['Duracion_Dias']; ?> días)
                                                </option>
                                            <?php 
                                                endwhile;
                                            } else {
                                                echo '<option value="">No hay membresías activas</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label required">Fecha de inicio</label>
                                        <input type="date" class="form-control" name="fecha_inicio" 
                                               value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <button type="submit" class="btn btn-guardar w-100">
                                            <i class="ti ti-device-floppy me-2"></i>
                                            Asignar Membresía
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
</body>
</html>