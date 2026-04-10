<?php
session_start();

if (!isset($_SESSION['ultima_venta'])) {
    header("Location: pos.php");
    exit();
}

$venta = $_SESSION['ultima_venta'];
unset($_SESSION['ultima_venta']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ticket de Venta - Sistema de Gimnasio</title>
    <style>
        @media print {
            .no-print { display: none; }
        }
        body {
            font-family: monospace;
            font-size: 14px;
            margin: 0;
            padding: 20px;
        }
        .ticket {
            max-width: 300px;
            margin: 0 auto;
            border: 1px solid #ccc;
            padding: 15px;
            border-radius: 5px;
        }
        .text-center { text-align: center; }
        .total { font-size: 18px; font-weight: bold; }
        hr { margin: 10px 0; }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="text-center">
            <h3>GYM ADMIN</h3>
            <p>Ticket de Venta<br><?php echo $venta['fecha']; ?></p>
        </div>
        <hr>
        <table style="width: 100%;">
            <thead>
                <tr><th>Producto</th><th>Cant</th><th>Precio</th><th>Subtotal</th> </thead>
            <tbody>
                <?php foreach($venta['items'] as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                    <td><?php echo $item['cantidad']; ?></td>
                    <td>$<?php echo number_format($item['precio'], 2); ?></td>
                    <td>$<?php echo number_format($item['subtotal'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <hr>
        <div class="text-center">
            <?php if($venta['descuento'] > 0): ?>
            <p>Descuento: <?php echo $venta['descuento']; ?>%</p>
            <?php endif; ?>
            <p class="total">Total: $<?php echo number_format($venta['total'], 2); ?></p>
            <p>¡Gracias por su compra!</p>
        </div>
        <hr>
        <div class="text-center no-print">
            <button onclick="window.print();" class="btn btn-guardar">Imprimir</button>
            <a href="pos.php" class="btn btn-modificar">Nueva Venta</a>
        </div>
    </div>
</body>
</html>