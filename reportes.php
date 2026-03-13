<?php
include "conexion.php";
session_start();

$ingresos_hoy = 0;
$ingresos_mes = 0;
$nuevos_socios = 0;
$membresias_activas = 0;
$membresias_vencidas = 0;
$productos_top = [];

//Ingresos diarios y mensuales
$res_hoy = $conn->query("SELECT SUM(Monto) as total FROM ventas_pagos WHERE DATE(Fecha_Pago) = CURDATE()");
if ($res_hoy) $ingresos_hoy = $res_hoy->fetch_assoc()['total'] ?? 0;
$res_mes = $conn->query("SELECT SUM(Monto) as total FROM ventas_pagos WHERE MONTH(Fecha_Pago) = MONTH(CURDATE()) AND YEAR(Fecha_Pago) = YEAR(CURDATE())");
if ($res_mes) $ingresos_mes = $res_mes->fetch_assoc()['total'] ?? 0;

//Proyección de ingresos
$dias_mes_actual = date('t');
$dia_actual = date('j');
$proyeccion_mes = ($dia_actual > 0) ? ($ingresos_mes / $dia_actual) * $dias_mes_actual : 0;

//Nuevos socios
$res_socios = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE ID_Rol = 3 AND MONTH(Fecha_Registro) = MONTH(CURDATE())");
if ($res_socios) $nuevos_socios = $res_socios->fetch_assoc()['total'] ?? 0;

//Membresías Activas y Vencidas
$res_activas = $conn->query("SELECT COUNT(DISTINCT ID_Socio) as total FROM socio_membresia WHERE Fecha_Fin >= CURDATE()");
if ($res_activas) $membresias_activas = $res_activas->fetch_assoc()['total'] ?? 0;
$res_vencidas = $conn->query("SELECT COUNT(DISTINCT ID_Socio) as total FROM socio_membresia WHERE Fecha_Fin < CURDATE() AND ID_Socio NOT IN (SELECT ID_Socio FROM socio_membresia WHERE Fecha_Fin >= CURDATE())");
if ($res_vencidas) $membresias_vencidas = $res_vencidas->fetch_assoc()['total'] ?? 0;

//Productos más vendidos
$sql_productos = "SELECT p.Nombre, SUM(d.Cantidad) as total_vendido, SUM(d.Cantidad * d.Precio_Unitario) as ingresos_generados 
                  FROM detalle_venta_pos d 
                  JOIN productos p ON d.ID_Producto = p.ID_Producto 
                  GROUP BY p.ID_Producto 
                  ORDER BY total_vendido DESC LIMIT 5";
$res_productos = $conn->query($sql_productos);
if ($res_productos) {
    while($row = $res_productos->fetch_assoc()) {
        $productos_top[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reportes - Sistema de Gimnasio</title>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
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
                        <li class="nav-item"><a class="nav-link" href="entrenadores.php"><i class="ti ti-run me-1"></i> Entrenadores</a></li>
                        <li class="nav-item"><a class="nav-link" href="clases.php"><i class="ti ti-calendar me-1"></i> Clases</a></li>
                        <li class="nav-item"><a class="nav-link active" href="reportes.php"><i class="ti ti-chart-bar me-1"></i> Reportes</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="page-wrapper">
            <div class="container-xl mt-4">
                <div class="page-header d-print-none mb-4">
                    <div class="row align-items-center">
                        <div class="col">
                            <h2 class="page-title"><i class="ti ti-chart-bar me-2" style="color: var(--gym-verde);"></i> Reportes y Estadísticas</h2>
                        </div>
                    </div>
                </div>

                <div class="row row-cards mb-4">
                    <div class="col-sm-6 col-lg-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">Ingresos Hoy</div>
                                    <div class="ms-auto lh-1"><i class="ti ti-coin text-success" style="font-size: 1.5rem;"></i></div>
                                </div>
                                <div class="h1 mb-0 mt-2">$<?php echo number_format($ingresos_hoy, 2); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">Ingresos del Mes</div>
                                    <div class="ms-auto lh-1"><i class="ti ti-businessplan text-primary" style="font-size: 1.5rem;"></i></div>
                                </div>
                                <div class="h1 mb-0 mt-2">$<?php echo number_format($ingresos_mes, 2); ?></div>
                                <div class="text-muted mt-1" style="font-size: 0.8rem;">Proyección: $<?php echo number_format($proyeccion_mes, 2); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">Membresías Activas</div>
                                    <div class="ms-auto lh-1"><i class="ti ti-credit-card text-success" style="font-size: 1.5rem;"></i></div>
                                </div>
                                <div class="h1 mb-0 mt-2"><?php echo $membresias_activas; ?></div>
                                <div class="text-danger mt-1" style="font-size: 0.8rem;"><i class="ti ti-alert-triangle"></i> <?php echo $membresias_vencidas; ?> vencidas</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">Nuevos Socios (Mes)</div>
                                    <div class="ms-auto lh-1"><i class="ti ti-user-plus text-info" style="font-size: 1.5rem;"></i></div>
                                </div>
                                <div class="h1 mb-0 mt-2"><?php echo $nuevos_socios; ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row row-cards justify-content-center">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="ti ti-shopping-cart me-2" style="color: var(--gym-verde);"></i> Top 5 Productos Más Vendidos</h3>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-vcenter card-table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Producto</th>
                                                <th class="text-center">Unidades Vendidas</th>
                                                <th class="text-end">Ingresos Generados</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(empty($productos_top)): ?>
                                                <tr><td colspan="3" class="text-center py-4 text-muted">Aún no hay ventas de productos registradas en el sistema.</td></tr>
                                            <?php else: ?>
                                                <?php foreach($productos_top as $producto): ?>
                                                <tr>
                                                    <td class="fw-bold"><?php echo htmlspecialchars($producto['Nombre']); ?></td>
                                                    <td class="text-center">
                                                        <span class="badge bg-blue-lt"><?php echo $producto['total_vendido']; ?></span>
                                                    </td>
                                                    <td class="text-end text-success fw-bold">
                                                        $<?php echo number_format($producto['ingresos_generados'], 2); ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
</body>
</html>