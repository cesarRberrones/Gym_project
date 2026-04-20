<?php
include "conexion.php";
session_start();

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] != 1 && $_SESSION['rol'] != 2 && $_SESSION['rol'] != 4)) {
    header("Location: login.php");
    exit();
}

$mensaje = "";
$carrito = isset($_SESSION['carrito']) ? $_SESSION['carrito'] : [];
$total_venta = 0;
foreach($carrito as $item) {
    $total_venta += $item['subtotal'];
}

// ==================== ADMIN: CREAR PROMOCIÓN ====================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion_promo']) && $_SESSION['rol'] == 1) {
    $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
    $tipo = $_POST['tipo'];
    $valor = (float)$_POST['valor'];
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];
    $conn->query("INSERT INTO promociones (Nombre, Tipo, Valor, Fecha_Inicio, Fecha_Fin) VALUES ('$nombre', '$tipo', $valor, '$fecha_inicio', '$fecha_fin')");
    header("Location: pos.php");
    exit();
}

// ==================== ADMIN: ELIMINAR PROMOCIÓN ====================
if (isset($_GET['eliminar_promo']) && $_SESSION['rol'] == 1) {
    $id = (int)$_GET['eliminar_promo'];
    $conn->query("DELETE FROM promociones WHERE ID_Promocion = $id");
    header("Location: pos.php");
    exit();
}

// Agregar producto al carrito con validación de stock
if (isset($_GET['add']) && isset($_GET['id'])) {
    $id_producto = (int)$_GET['id'];
    $producto = $conn->query("SELECT * FROM productos WHERE ID_Producto = $id_producto")->fetch_assoc();
    
    if ($producto) {
        // Verificar que haya stock disponible
        if ($producto['Stock_Actual'] <= 0) {
            $_SESSION['mensaje'] = "Producto agotado. No hay stock disponible.";
            header("Location: pos.php");
            exit();
        }
        
        $encontrado = false;
        foreach($carrito as $key => $item) {
            if ($item['id'] == $id_producto) {
                // Verificar que no exceda el stock disponible
                $nueva_cantidad = $item['cantidad'] + 1;
                if ($nueva_cantidad > $producto['Stock_Actual']) {
                    $_SESSION['mensaje'] = "Stock insuficiente. Solo hay " . $producto['Stock_Actual'] . " unidades disponibles. No se pudo agregar más.";
                    header("Location: pos.php");
                    exit();
                }
                $carrito[$key]['cantidad']++;
                $carrito[$key]['subtotal'] = $carrito[$key]['cantidad'] * $carrito[$key]['precio'];
                $encontrado = true;
                break;
            }
        }
        if (!$encontrado) {
            // Verificar que haya al menos 1 unidad en stock
            if ($producto['Stock_Actual'] < 1) {
                $_SESSION['mensaje'] = "Producto agotado. No hay stock disponible.";
                header("Location: pos.php");
                exit();
            }
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

// Actualizar cantidad con validación de stock (NO elimina el producto si excede)
if (isset($_POST['update_cantidad'])) {
    $id_producto = (int)$_POST['id_producto'];
    $nueva_cantidad = (int)$_POST['cantidad'];
    
    // Obtener stock disponible
    $producto = $conn->query("SELECT Stock_Actual FROM productos WHERE ID_Producto = $id_producto")->fetch_assoc();
    
    // Validar que no exceda el stock
    if ($nueva_cantidad > $producto['Stock_Actual']) {
        $_SESSION['mensaje'] = "Stock insuficiente. Solo hay " . $producto['Stock_Actual'] . " unidades disponibles. No se pudo actualizar la cantidad.";
        header("Location: pos.php");
        exit();
    }
    
    // Si la cantidad es 0 o negativa, eliminar el producto del carrito
    if ($nueva_cantidad <= 0) {
        foreach($carrito as $key => $item) {
            if ($item['id'] == $id_producto) {
                unset($carrito[$key]);
                break;
            }
        }
    } else {
        // Actualizar la cantidad
        foreach($carrito as $key => $item) {
            if ($item['id'] == $id_producto) {
                $carrito[$key]['cantidad'] = $nueva_cantidad;
                $carrito[$key]['subtotal'] = $nueva_cantidad * $item['precio'];
                break;
            }
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

// Solo obtener promociones activas (sin aplicarlas automáticamente)
$hoy = date('Y-m-d');

// Procesar venta
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['procesar_venta'])) {
    $id_socio = $_POST['id_socio'] ? (int)$_POST['id_socio'] : null;
    $metodo_pago = $_POST['metodo_pago'];
    $descuento_manual = isset($_POST['descuento']) ? (float)$_POST['descuento'] : 0;
    $promocion_id = isset($_POST['promocion_id']) ? (int)$_POST['promocion_id'] : 0;
    
    // Variables para la promoción seleccionada
    $descuento_porcentaje = 0;
    $promo_nombre = "";
    $descuento_2x1_aplicado = false;
    $descuento_3x2_aplicado = false;
    
    // Aplicar la promoción seleccionada (si hay)
    if ($promocion_id > 0) {
        $promo = $conn->query("SELECT * FROM promociones WHERE ID_Promocion = $promocion_id")->fetch_assoc();
        if ($promo) {
            $promo_nombre = $promo['Nombre'];
            if ($promo['Tipo'] == 'porcentaje') {
                $descuento_porcentaje = $promo['Valor'];
            } elseif ($promo['Tipo'] == '2x1') {
                foreach($carrito as $key => $item) {
                    $cantidad = $item['cantidad'];
                    $gratis = floor($cantidad / 2);
                    if ($gratis > 0) {
                        $carrito[$key]['subtotal'] = ($cantidad - $gratis) * $item['precio'];
                        $descuento_2x1_aplicado = true;
                    }
                }
                // Recalcular total después de aplicar 2x1
                $total_venta = 0;
                foreach($carrito as $item) {
                    $total_venta += $item['subtotal'];
                }
                $_SESSION['carrito'] = $carrito;
            } elseif ($promo['Tipo'] == '3x2') {
                foreach($carrito as $key => $item) {
                    $cantidad = $item['cantidad'];
                    $gratis = floor($cantidad / 3);
                    if ($gratis > 0) {
                        $carrito[$key]['subtotal'] = ($cantidad - $gratis) * $item['precio'];
                        $descuento_3x2_aplicado = true;
                    }
                }
                // Recalcular total después de aplicar 3x2
                $total_venta = 0;
                foreach($carrito as $item) {
                    $total_venta += $item['subtotal'];
                }
                $_SESSION['carrito'] = $carrito;
            }
        }
    }
    
    // Calcular el total original (sin descuentos) para saber cuánto se descontó
    $total_original = 0;
    foreach($carrito as $item) {
        $total_original += $item['cantidad'] * $item['precio'];
    }
    
    // Usar el mayor descuento entre la promoción y el manual
    $descuento_final = max($descuento_porcentaje, $descuento_manual);
    $total_con_descuento = $total_venta - ($total_venta * $descuento_final / 100);
    
    // Calcular descuento total en dinero (para reportes)
    $descuento_total_dinero = $total_original - $total_con_descuento;
    
    // Insertar venta con descuento
    $sql_venta = "INSERT INTO ventas_pagos (ID_Socio, Monto, Descuento, Metodo_Pago, Concepto) 
                  VALUES (" . ($id_socio ? $id_socio : 'NULL') . ", $total_con_descuento, $descuento_total_dinero, '$metodo_pago', 'Venta POS - " . date('Y-m-d H:i:s') . "')";
    
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
            'descuento' => $descuento_total_dinero,
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

// Lista de productos (solo con stock > 0 para mostrar en POS)
$productos = $conn->query("SELECT * FROM productos WHERE Stock_Actual > 0 ORDER BY Nombre");
$socios = $conn->query("SELECT s.ID_Socios, u.Nombre FROM socios s INNER JOIN usuarios u ON s.ID_Usuario = u.ID_Usuario ORDER BY u.Nombre");

// Lista de promociones para el selector (todas las activas)
$promos_activas = $conn->query("SELECT * FROM promociones WHERE Fecha_Inicio <= '$hoy' AND Fecha_Fin >= '$hoy'");
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
                <a href="pos.php" class="navbar-brand d-flex align-items-center">
                    <img src="logo1.png" alt="GYM ADMIN" class="logo-gym me-2">
                    <span style="color: white; font-weight: 600;">
                        <?php if ($_SESSION['rol'] == 4): ?>
                            CAJA
                        <?php else: ?>
                            GYM ADMIN
                        <?php endif; ?>
                    </span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbar-menu">
                    <ul class="navbar-nav ms-auto">
                        <?php if ($_SESSION['rol'] == 4): ?>
                            <!-- Menú para CAJA (solo POS y Salir) -->
                            <li class="nav-item"><a class="nav-link active" href="pos.php"><i class="ti ti-shopping-cart me-1"></i> Punto de Venta</a></li>
                            <li class="nav-item"><a class="nav-link" href="logout.php"><i class="ti ti-logout me-1"></i> Salir</a></li>
                        <?php else: ?>
                            <!-- Menú normal para ADMIN/ENTRENADOR -->
                            <li class="nav-item"><a class="nav-link" href="index.php"><i class="ti ti-dashboard me-1"></i> Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link" href="membresias.php"><i class="ti ti-cards me-1"></i> Membresías</a></li>
                            <li class="nav-item"><a class="nav-link" href="socios.php"><i class="ti ti-users me-1"></i> Socios</a></li>
                            <li class="nav-item"><a class="nav-link active" href="pos.php"><i class="ti ti-shopping-cart me-1"></i> Punto de Venta</a></li>
                            <li class="nav-item"><a class="nav-link" href="inventario.php"><i class="ti ti-package me-1"></i> Inventario</a></li>
                            <!-- Botón para administrar promociones (solo ADMIN) -->
                            <?php if ($_SESSION['rol'] == 1): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#modalPromociones">
                                    <i class="ti ti-tag me-1"></i> Promociones
                                </a>
                            </li>
                            <?php endif; ?>
                            <li class="nav-item"><a class="nav-link" href="logout.php"><i class="ti ti-logout me-1"></i> Salir</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </header>

        <div class="page-wrapper">
            <div class="container-xl">
                <!-- Mostrar mensaje de error de stock -->
                <?php if (isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-danger alert-dismissible">
                    <i class="ti ti-alert-circle me-2"></i> <?php echo $_SESSION['mensaje']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['mensaje']); ?>
                <?php endif; ?>
                
                <?php if ($descuento_porcentaje > 0): ?>
                <div class="alert alert-info">
                    <i class="ti ti-tag me-2"></i> Promoción aplicada: <strong><?php echo $promo_nombre; ?></strong> (<?php echo $descuento_porcentaje; ?>% de descuento)
                </div>
                <?php endif; ?>
                
                <?php if ($descuento_2x1_aplicado): ?>
                <div class="alert alert-success">
                    <i class="ti ti-tag me-2"></i> Promoción aplicada: <strong>2x1</strong> (¡Lleva 2 y paga 1!)
                </div>
                <?php endif; ?>
                
                <?php if ($descuento_3x2_aplicado): ?>
                <div class.alert alert-success">
                    <i class="ti ti-tag me-2"></i> Promoción aplicada: <strong>3x2</strong> (¡Lleva 3 y paga 2!)
                </div>
                <?php endif; ?>
                
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
                                            <td><td colspan="4" class="text-center">Carrito vacío<\/td><\/tr>
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
                                    <td>
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
                                        <label class="form-label">Promoción a aplicar</label>
                                        <select name="promocion_id" class="form-select">
                                            <option value="">Ninguna</option>
                                            <?php 
                                            $promos_activas->data_seek(0);
                                            while($p = $promos_activas->fetch_assoc()): ?>
                                            <option value="<?php echo $p['ID_Promocion']; ?>">
                                                <?php echo $p['Nombre']; ?> 
                                                (<?php echo $p['Tipo'] == 'porcentaje' ? $p['Valor'].'%' : strtoupper($p['Tipo']); ?>)
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Descuento adicional (%)</label>
                                        <input type="number" step="0.01" name="descuento" class="form-control" value="0">
                                        <small class="form-hint">Se suma al descuento de la promoción</small>
                                    </div>
                                    <div class="mb-3">
                                        <h4>Subtotal: $<?php echo number_format($total_venta, 2); ?></h4>
                                        <?php if ($descuento_porcentaje > 0): ?>
                                        <h5 class="text-success">Descuento promoción: <?php echo $descuento_porcentaje; ?>%</h5>
                                        <?php endif; ?>
                                        <h3>Total: $<?php echo number_format($total_venta - ($total_venta * max($descuento_porcentaje, isset($_POST['descuento']) ? (float)$_POST['descuento'] : 0) / 100), 2); ?></h3>
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

    <!-- Modal para ADMIN: Gestionar Promociones -->
    <?php if ($_SESSION['rol'] == 1): ?>
    <div class="modal fade" id="modalPromociones" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Administrar Promociones</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="pos.php">
                        <input type="hidden" name="accion_promo" value="crear">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <input type="text" name="nombre" class="form-control" placeholder="Nombre de la promoción" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <select name="tipo" class="form-select" required>
                                    <option value="porcentaje">% Descuento</option>
                                    <option value="2x1">2x1</option>
                                    <option value="3x2">3x2</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <input type="number" step="0.01" name="valor" class="form-control" placeholder="Valor" required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <input type="date" name="fecha_inicio" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <input type="date" name="fecha_fin" class="form-control" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <button type="submit" class="btn btn-guardar">Crear Promoción</button>
                            </div>
                        </div>
                    </form>
                    <hr>
                    <table class="table table-sm">
                        <thead>
                            <tr><th>Nombre</th><th>Tipo</th><th>Valor</th><th>Vigencia</th><th></th> </thead>
                        <tbody>
                            <?php
                            $todas_promos = $conn->query("SELECT * FROM promociones ORDER BY Fecha_Inicio DESC");
                            while($p = $todas_promos->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo $p['Nombre']; ?></td>
                                <td><?php echo $p['Tipo']; ?></td>
                                <td><?php echo $p['Tipo'] == 'porcentaje' ? $p['Valor'].'%' : $p['Valor']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($p['Fecha_Inicio'])); ?> - <?php echo date('d/m/Y', strtotime($p['Fecha_Fin'])); ?></td>
                                <td><a href="?eliminar_promo=<?php echo $p['ID_Promocion']; ?>" class="btn btn-eliminar btn-sm" onclick="return confirm('¿Eliminar esta promoción?')">Eliminar</a></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
</body>
</html>