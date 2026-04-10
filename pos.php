<?php
include "conexion.php";
session_start();

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] != 1 && $_SESSION['rol'] != 2)) {
    header("Location: login.php");
    exit();
}

$mensaje = "";
$carrito = isset($_SESSION['carrito']) ? $_SESSION['carrito'] : [];
$total_venta = 0;
foreach($carrito as $item) {
    $total_venta += $item['subtotal'];
}

// Agregar producto al carrito
if (isset($_GET['add']) && isset($_GET['id'])) {
    $id_producto = (int)$_GET['id'];
    $producto = $conn->query("SELECT * FROM productos WHERE ID_Producto = $id_producto")->fetch_assoc();
    
    if ($producto) {
        $encontrado = false;
        foreach($carrito as $key => $item) {
            if ($item['id'] == $id_producto) {
                $carrito[$key]['cantidad']++;
                $carrito[$key]['subtotal'] = $carrito[$key]['cantidad'] * $carrito[$key]['precio'];
                $encontrado = true;
                break;
            }
        }
        if (!$encontrado) {
            $carrito[] = [
                'id' => $producto['ID_Producto'],
                'nombre' => $producto['Nombre'],
                'precio' => $producto['Precio_Venta'],
                'cantidad' => 1,
                'subtotal' => $producto['Precio_Venta']
            ];
        }
        $_SESSION['carrito'] = $carrito;
    }
    header("Location: pos.php");
    exit();
}

// Eliminar producto del carrito
if (isset($_GET['remove']) && isset($_GET['id'])) {
    $id_producto = (int)$_GET['id'];
    foreach($carrito as $key => $item) {
        if ($item['id'] == $id_producto) {
            unset($carrito[$key]);
            break;
        }
    }
    $_SESSION['carrito'] = $carrito;
    header("Location: pos.php");
    exit();
}

// Actualizar cantidad
if (isset($_POST['update_cantidad'])) {
    $id_producto = (int)$_POST['id_producto'];
    $nueva_cantidad = (int)$_POST['cantidad'];
    foreach($carrito as $key => $item) {
        if ($item['id'] == $id_producto) {
            if ($nueva_cantidad <= 0) {
                unset($carrito[$key]);
            } else {
                $carrito[$key]['cantidad'] = $nueva_cantidad;
                $carrito[$key]['subtotal'] = $nueva_cantidad * $item['precio'];
            }
            break;
        }
    }
    $_SESSION['carrito'] = $carrito;
    header("Location: pos.php");
    exit();
}

// Vaciar carrito
if (isset($_GET['vaciar'])) {
    $_SESSION['carrito'] = [];
    header("Location: pos.php");
    exit();
}

// Procesar venta
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['procesar_venta'])) {
    $id_socio = $_POST['id_socio'] ? (int)$_POST['id_socio'] : null;
    $metodo_pago = $_POST['metodo_pago'];
    $descuento = isset($_POST['descuento']) ? (float)$_POST['descuento'] : 0;
    $total_con_descuento = $total_venta - ($total_venta * $descuento / 100);
    
    // Insertar venta
    $sql_venta = "INSERT INTO ventas_pagos (ID_Socio, Monto, Metodo_Pago, Concepto) 
                  VALUES (" . ($id_socio ? $id_socio : 'NULL') . ", $total_con_descuento, '$metodo_pago', 'Venta POS - " . date('Y-m-d H:i:s') . "')";
    
    if ($conn->query($sql_venta)) {
        $id_venta = $conn->insert_id;
        
        // Insertar detalles y actualizar stock
        foreach($carrito as $item) {
            $sql_detalle = "INSERT INTO detalle_venta_pos (ID_Pago, ID_Producto, Cantidad, Precio_Unitario) 
                            VALUES ($id_venta, {$item['id']}, {$item['cantidad']}, {$item['precio']})";
            $conn->query($sql_detalle);
            
            // Actualizar stock
            $conn->query("UPDATE productos SET Stock_Actual = Stock_Actual - {$item['cantidad']} WHERE ID_Producto = {$item['id']}");
        }
        
        $_SESSION['ultima_venta'] = [
            'id' => $id_venta,
            'total' => $total_con_descuento,
            'descuento' => $descuento,
            'items' => $carrito,
            'fecha' => date('Y-m-d H:i:s')
        ];
        
        $_SESSION['carrito'] = [];
        header("Location: ticket.php");
        exit();
    } else {
        $mensaje = '<div class="alert alert-danger">Error al procesar la venta: ' . $conn->error . '</div>';
    }
}

// Lista de productos
$productos = $conn->query("SELECT * FROM productos WHERE Stock_Actual > 0 ORDER BY Nombre");
$socios = $conn->query("SELECT s.ID_Socios, u.Nombre FROM socios s INNER JOIN usuarios u ON s.ID_Usuario = u.ID_Usuario ORDER BY u.Nombre");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Punto de Venta - Sistema de Gimnasio</title>
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
                        <li class="nav-item"><a class="nav-link active" href="pos.php"><i class="ti ti-shopping-cart me-1"></i> Punto de Venta</a></li>
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
                            <h2 class="page-title"><i class="ti ti-shopping-cart me-2" style="color: var(--gym-verde);"></i> Punto de Venta</h2>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Productos -->
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Productos Disponibles</h3>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-vcenter">
                                        <thead>
                                            <tr><th>Producto</th><th>Precio</th><th>Stock</th><th></th> </thead>
                                        <tbody>
                                            <?php while($p = $productos->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($p['Nombre']); ?></td>
                                                <td>$<?php echo number_format($p['Precio_Venta'], 2); ?></td>
                                                <td><span class="badge bg-blue"><?php echo $p['Stock_Actual']; ?></span></td>
                                                <td><a href="?add=1&id=<?php echo $p['ID_Producto']; ?>" class="btn btn-guardar btn-sm"><i class="ti ti-plus"></i> Agregar</a></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Carrito -->
                    <div class="col-md-5">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Carrito de Compras</h3>
                                <div class="card-actions">
                                    <a href="?vaciar=1" class="btn btn-eliminar btn-sm">Vaciar</a>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-vcenter">
                                        <thead>
                                            <tr><th>Producto</th><th>Cant</th><th>Subtotal</th><th></th> </thead>
                                        <tbody>
                                            <?php if(empty($carrito)): ?>
                                            <td><td colspan="4" class="text-center">Carrito vacío</td></tr>
                                            <?php else: foreach($carrito as $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                                                <td>
                                                    <form method="POST" action="pos.php" class="d-flex">
                                                        <input type="hidden" name="id_producto" value="<?php echo $item['id']; ?>">
                                                        <input type="number" name="cantidad" value="<?php echo $item['cantidad']; ?>" style="width: 60px;" class="form-control form-control-sm">
                                                        <button type="submit" name="update_cantidad" class="btn btn-sm btn-modificar ms-1">Actualizar</button>
                                                    </form>
                                                </td>
                                                <td>$<?php echo number_format($item['subtotal'], 2); ?></td>
                                                <td><a href="?remove=1&id=<?php echo $item['id']; ?>" class="btn btn-eliminar btn-sm"><i class="ti ti-trash"></i></a></td>
                                            </tr>
                                            <?php endforeach; endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer">
                                <form method="POST" action="pos.php">
                                    <div class="mb-3">
                                        <label class="form-label">Socio (opcional)</label>
                                        <select name="id_socio" class="form-select">
                                            <option value="">Cliente general</option>
                                            <?php while($s = $socios->fetch_assoc()): ?>
                                                <option value="<?php echo $s['ID_Socios']; ?>"><?php echo htmlspecialchars($s['Nombre']); ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Método de Pago</label>
                                        <select name="metodo_pago" class="form-select" required>
                                            <option value="efectivo">Efectivo</option>
                                            <option value="tarjeta">Tarjeta</option>
                                            <option value="transferencia">Transferencia</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Descuento (%)</label>
                                        <input type="number" step="0.01" name="descuento" class="form-control" value="0">
                                    </div>
                                    <div class="mb-3">
                                        <h4>Total: $<?php echo number_format($total_venta, 2); ?></h4>
                                    </div>
                                    <button type="submit" name="procesar_venta" class="btn btn-guardar w-100">Procesar Venta</button>
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