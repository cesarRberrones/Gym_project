<?php
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso Denegado - Sistema de Gimnasio</title>
    
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <link href="css/gym-style.css" rel="stylesheet"/>
    <style>
        .error-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--gym-negro), #333);
        }
        .error-card {
            max-width: 500px;
            width: 100%;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            text-align: center;
        }
        .error-header {
            background: var(--btn-eliminar);
            color: white;
            padding: 40px 20px;
        }
        .error-header i {
            font-size: 80px;
            margin-bottom: 20px;
        }
        .error-body {
            background: white;
            padding: 40px 30px;
        }
        .btn-volver {
            background: var(--gym-verde);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        .btn-volver:hover {
            background: #5a9e12;
            color: white;
        }
    </style>
</head>
<body>
    <div class="error-page">
        <div class="error-card">
            <div class="error-header">
                <i class="ti ti-lock"></i>
                <h1>Acceso Denegado</h1>
            </div>
            <div class="error-body">
                <i class="ti ti-alert-triangle" style="font-size: 48px; color: var(--btn-eliminar); margin-bottom: 20px;"></i>
                <h3>No tienes permisos para acceder a esta página</h3>
                <p class="text-muted mt-3">
                    <?php if (isset($_SESSION['usuario_nombre'])): ?>
                        Hola <strong><?php echo $_SESSION['usuario_nombre']; ?></strong>, tu rol actual no tiene acceso a esta sección.
                    <?php else: ?>
                        Debes iniciar sesión para acceder a esta página.
                    <?php endif; ?>
                </p>
                <p class="text-muted">
                    Si crees que esto es un error, contacta al administrador del sistema.
                </p>
                <a href="<?php echo isset($_SESSION['usuario_id']) ? 'index.php' : 'login.php'; ?>" class="btn-volver">
                    <i class="ti ti-arrow-left me-2"></i> Volver al inicio
                </a>
            </div>
        </div>
    </div>
</body>
</html>