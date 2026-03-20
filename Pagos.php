<?php
include "conexion.php";
session_start();

//obtiene socios para el formulario
//"s" es el apodo para la tabla 'socios'.
//"u" es el apodo para la tabla 'usuarios'.
$socios_query = $conn->query("SELECT s.ID_Socios, u.Nombre FROM socios s INNER JOIN usuarios u ON s.ID_Usuario = u.ID_Usuario");

//obtinene membresías y el precio, ademas checa si esta activo el tipo de membresia
$membresias_query = $conn->query("SELECT Nombre, Precio FROM tipos_membresia WHERE Estado = 'activo'");

//obtiene productos con su precio de venta
$productos_query = $conn->query("SELECT Nombre, Precio_Venta FROM productos");

//obtiene las clases y sus tarifas de la tabla clases
//"c" es el apodo para la tabla 'clases'.
//"t" es el apodo para la tabla 'tarifas_clases'.
$clases_query = $conn->query("SELECT c.Nombre, t.Precio FROM clases c INNER JOIN tarifas_clases t ON c.ID_Clase = t.ID_Clases");

//Variable para mostrar mensajes de éxito o error
$mensaje_alerta = "";

//procesa el formulario cuando se envia
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_socio = $_POST['id_socio'];
    $monto = $_POST['monto'];
    $metodo = $_POST['metodo_pago'];
    $concepto_final = $_POST['tipo_pago'] . " - " . $_POST['detalle_pago'];

    $sql = "INSERT INTO ventas_pagos (ID_Socio, Monto, Metodo_Pago, Concepto) 
            VALUES ('$id_socio', '$monto', '$metodo', '$concepto_final')";

    if ($conn->query($sql) === TRUE) {
        $mensaje_alerta = '<div class="alert alert-success alert-dismissible" role="alert">
                            <div class="d-flex">
                                <div>
                                    <i class="ti ti-check icon alert-icon"></i>
                                </div>
                                <div>¡Pago registrado exitosamente en la caja!</div>
                            </div>
                            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                           </div>';
    } else {
        $mensaje_alerta = '<div class="alert alert-danger alert-dismissible" role="alert">
                            <div class="d-flex">
                                <div>
                                    <i class="ti ti-alert-circle icon alert-icon"></i>
                                </div>
                                <div>Error al guardar: ' . $conn->error . '</div>
                            </div>
                            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                           </div>';
    }
}

//obtiene los pagos registrados para mostarlos en la tabla
//"vp" es el apodo para la tabla 'ventas_pagos'.
//"s" es el apodo para la tabla 'socios'.
//"u" es el apodo para la tabla 'usuarios'.
$query_pagos = "SELECT vp.ID_Pago, u.Nombre AS NombreSocio, vp.Fecha_Pago, vp.Monto, vp.Metodo_Pago, vp.Concepto 
                FROM ventas_pagos vp
                INNER JOIN socios s ON vp.ID_Socio = s.ID_Socios
                INNER JOIN usuarios u ON s.ID_Usuario = u.ID_Usuario
                ORDER BY vp.Fecha_Pago DESC";
$ventas_pagos = $conn->query($query_pagos);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Caja y Pagos - Sistema de Gimnasio</title>
    
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <link href="css/gym-style.css" rel="stylesheet"/>
</head>
<body>
    <div class="page">
        <!-- HEADER CORREGIDO - IDÉNTICO AL DE MEMBRESIAS.PHP -->
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
                            <a class="nav-link active" href="Pagos.php">
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
            <div class="page-header d-print-none text-white">
                <div class="container-xl">
                    <div class="row g-2 align-items-center">
                        <div class="col">
                            <h2 class="page-title text-dark">
                                Módulo de Caja y Pagos
                            </h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="page-body">
                <div class="container-xl">
                    
                    <?php echo $mensaje_alerta; ?>

                    <div class="row row-cards">
                        <div class="col-12">
                            <form method="POST" action="" class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Registrar un Nuevo Pago</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        
                                        <div class="col-md-6">
                                            <label class="form-label required">Socio que paga</label>
                                            <select name="id_socio" class="form-select" required>
                                                <option value="">Seleccione un socio...</option>
                                                <?php 
                                                //reinicia el puntero por si se usa de nuevo
                                                $socios_query->data_seek(0);
                                                while($row_socio = $socios_query->fetch_assoc()): 
                                                ?>
                                                    <option value="<?php echo $row_socio['ID_Socios']; ?>">
                                                        <?php echo $row_socio['Nombre']; ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label required">¿Qué está pagando?</label>
                                            <select name="tipo_pago" class="form-select" required>
                                                <option value="">Seleccione una opción...</option>
                                                <option value="Membresía">Membresía</option>
                                                <option value="Producto">Producto</option>
                                                <option value="Clase">Clase</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label required">Detalle (Ej. Mensualidad, Proteína, etc.)</label>
                                            <input type="text" name="detalle_pago" class="form-control" placeholder="Especifique el pago..." required>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label required">Monto cobrado</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" step="0.01" name="monto" class="form-control" placeholder="0.00" required>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label required">Método de pago</label>
                                            <select name="metodo_pago" class="form-select" required>
                                                <option value="">Seleccione...</option>
                                                <option value="efectivo">Efectivo</option>
                                                <option value="tarjeta">Tarjeta</option>
                                                <option value="transferencia">Transferencia</option>
                                            </select>
                                        </div>

                                    </div>
                                </div>
                                <div class="card-footer text-end">
                                    <button type="submit" class="btn btn-guardar">
                                        <i class="ti ti-device-floppy me-2"></i> Guardar Pago
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Historial de Pagos</h3>
                                </div>
                                <div class="table-responsive">
                                    <table class="table card-table table-vcenter text-nowrap datatable">
                                        <thead>
                                            <tr>
                                                <th>N° Recibo</th>
                                                <th>Socio</th>
                                                <th>Concepto</th>
                                                <th>Monto</th>
                                                <th>Método</th>
                                                <th>Fecha y Hora</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($ventas_pagos && $ventas_pagos->num_rows > 0): ?>
                                                <?php while($pago = $ventas_pagos->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><span class="text-muted">#<?php echo str_pad($pago['ID_Pago'], 5, "0", STR_PAD_LEFT); ?></span></td>
                                                        <td><?php echo $pago['NombreSocio']; ?></td>
                                                        <td><?php echo $pago['Concepto']; ?></td>
                                                        <td class="text-green fw-bold">$<?php echo number_format($pago['Monto'], 2); ?></td>
                                                        <td>
                                                            <?php 
                                                                //color dependiendo del método de pago
                                                                $badge_color = 'bg-azure';
                                                                if($pago['Metodo_Pago'] == 'efectivo') $badge_color = 'bg-green';
                                                                if($pago['Metodo_Pago'] == 'transferencia') $badge_color = 'bg-blue';
                                                            ?>
                                                            <span class="badge <?php echo $badge_color; ?>">
                                                                <?php echo ucfirst($pago['Metodo_Pago']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo date('d/m/Y h:i A', strtotime($pago['Fecha_Pago'])); ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="6" class="text-center empty">
                                                        <div class="empty-icon"><i class="ti ti-cash-off"></i></div>
                                                        <p class="empty-title">Aún no hay pagos registrados</p>
                                                    </td>
                                                </tr>
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

    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
</body>
</html>