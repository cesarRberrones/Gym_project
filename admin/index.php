<?php
//verificación de sesión
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit();
}

//verificar que sea admin (rol 1)
if ($_SESSION['rol'] != 1) {
    header("Location: ../sin_acceso.php");
    exit();
}

include "../conexion.php";

//función para contar registros
function contar($conn, $tabla) {
    $result = $conn->query("SELECT COUNT(*) as total FROM $tabla");
    if ($result) {
        $row = $result->fetch_assoc();
        return $row['total'];
    }
    return 0;
}

//obtener conteos básicos
$total_membresias = contar($conn, 'tipos_membresia');
$total_socios = contar($conn, 'socios');
$total_entrenadores = contar($conn, 'entrenadores');
$total_clases = contar($conn, 'clases');

//obtener membresías para mostrar
$membresias = [];
$result = $conn->query("SELECT * FROM tipos_membresia LIMIT 5");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $membresias[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel Admin - Sistema de Gimnasio</title>
    
    <!-- Tabler CSS -->
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css" rel="stylesheet"/>
    <!-- Tabler Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <!--CSS-->
    <link href="../css/gym-style.css" rel="stylesheet"/>
</head>
<body>
    <div class="page">
        <!-- Header -->
        <header class="navbar navbar-expand-md navbar-gym">
    <div class="container-xl">
        <a href="../index.php" class="navbar-brand d-flex align-items-center">
            <img src="../logo1.png" alt="GYM ADMIN" class="logo-gym me-2">
            <span style="color: white; font-weight: 600;">GYM ADMIN</span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbar-menu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="../index.php">
                        <i class="ti ti-dashboard me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../membresias.php">
                        <i class="ti ti-cards me-1"></i> Membresías
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../socios.php">
                        <i class="ti ti-users me-1"></i> Socios
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../entrenadores.php">
                        <i class="ti ti-run me-1"></i> Entrenadores
                    </a>
                </li>
                <li class="nav-item">
                     <a class="nav-link" href="../evaluaciones.php">
                    <i class="ti ti-heart-rate-monitor me-1"></i> Evaluaciones
                  </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../clases.php">
                        <i class="ti ti-calendar me-1"></i> Clases
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../Pagos.php">
                        <i class="ti ti-credit-card me-1"></i> Pagos/Caja
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../reportes.php">
                        <i class="ti ti-chart-bar me-1"></i> Reportes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../logout.php">
                        <i class="ti ti-logout me-1"></i> Salir
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>

        <div class="page-wrapper">
            <div class="container-xl">
                <!-- carta bienvenida -->
                <div class="welcome-card">
                    <h2><i class="ti ti-dumpling me-2"></i>Bienvenido Administrador</h2>
                    <p class="mb-0"><?php echo date('l, d F Y'); ?> | Panel de Control</p>
                </div>

                <!-- estatus -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <a href="../socios.php" class="text-decoration-none">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $total_socios; ?></div>
                                <div class="stat-label">Total Socios</div>
                                <i class="ti ti-users" style="font-size: 24px; color: var(--gym-verde); margin-top: 10px;"></i>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $total_entrenadores; ?></div>
                            <div class="stat-label">Entrenadores</div>
                            <i class="ti ti-run" style="font-size: 24px; color: var(--gym-verde); margin-top: 10px;"></i>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <a href="../membresias.php" class="text-decoration-none">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $total_membresias; ?></div>
                                <div class="stat-label">Membresías</div>
                                <i class="ti ti-cards" style="font-size: 24px; color: var(--gym-verde); margin-top: 10px;"></i>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $total_clases; ?></div>
                            <div class="stat-label">Clases</div>
                            <i class="ti ti-calendar" style="font-size: 24px; color: var(--gym-verde); margin-top: 10px;"></i>
                        </div>
                    </div>
                </div>

                <!--módulos del sistema -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Módulos del Sistema</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <div class="card module-card" onclick="window.location.href='../membresias.php'">
                                            <div class="card-body">
                                                <i class="ti ti-cards module-icon"></i>
                                                <div class="module-title">Membresías</div>
                                                <span class="module-badge available">Disponible</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <div class="card module-card" onclick="window.location.href='../socios.php'">
                                            <div class="card-body">
                                                <i class="ti ti-users module-icon"></i>
                                                <div class="module-title">Socios</div>
                                                <span class="module-badge available">Disponible</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <div class="card module-card" onclick="window.location.href='../entrenadores.php'">
                                            <div class="card-body">
                                                <i class="ti ti-run module-icon"></i>
                                                <div class="module-title">Entrenadores</div>
                                                <span class="module-badge available">Disponible</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <div class="card module-card" onclick="window.location.href='../clases.php'">
                                            <div class="card-body">
                                                <i class="ti ti-calendar module-icon"></i>
                                                <div class="module-title">Clases</div>
                                                <span class="module-badge available">Disponible</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- lista de membresías -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Membresías Registradas</h3>
                                <div class="card-actions">
                                    <a href="../membresias.php" class="btn btn-modificar btn-sm">Ver Todas</a>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-vcenter card-table">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Duración</th>
                                            <th>Precio</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($membresias as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['Nombre']); ?></td>
                                                <td><?php echo $row['Duracion_Dias']; ?> días</td>
                                                <td>$<?php echo number_format($row['Precio'], 2); ?></td>
                                                <td>
                                                    <span class="<?php echo ($row['Estado'] == 'activo') ? 'estado-activo' : 'estado-inactivo'; ?>">
                                                        <?php echo ucfirst($row['Estado']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
    <script>
        console.log("Panel Admin cargado correctamente");
    </script>
</body>
</html>