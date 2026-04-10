<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include "conexion.php";

if (isset($_SESSION['usuario_id'])) {
    if ($_SESSION['rol'] == 1) {
        header("Location: admin/index.php");
        exit();
    } elseif ($_SESSION['rol'] == 2) {
        header("Location: entrenador/index.php");
        exit();
    } elseif ($_SESSION['rol'] == 3) {
        header("Location: socio/index.php");
        exit();
    }
}

$error = "";
$error_tipo = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    //validar campos vacíos
    if (empty($email) || empty($password)) {
        $error = "Por favor completa todos los campos";
        $error_tipo = "warning";
    } else {
        //buscar usuario por email
        $sql = "SELECT u.ID_Usuario, u.Nombre, u.Email, u.password, u.ID_Rol, r.Nombre_Rol 
                FROM usuarios u
                INNER JOIN roles r ON u.ID_Rol = r.ID_Rol
                WHERE u.Email = '$email'";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $usuario = $result->fetch_assoc();
            
            //verificar contraseña
            if ($password == $usuario['password']) {
                $_SESSION['usuario_id'] = $usuario['ID_Usuario'];
                $_SESSION['usuario_nombre'] = $usuario['Nombre'];
                $_SESSION['usuario_email'] = $usuario['Email'];
                $_SESSION['rol'] = $usuario['ID_Rol'];
                $_SESSION['rol_nombre'] = $usuario['Nombre_Rol'];
                
                //actualizar último acceso
                $conn->query("UPDATE usuarios SET ultimo_acceso = NOW() WHERE ID_Usuario = " . $usuario['ID_Usuario']);
                
               //redirigir según rol
if ($usuario['ID_Rol'] == 1) {
    header("Location: admin/index.php");
} elseif ($usuario['ID_Rol'] == 2) {
    header("Location: entrenador/index.php");
} elseif ($usuario['ID_Rol'] == 3) {
    header("Location: socio/index.php");
} else {
    header("Location: index.php"); 
}
exit();
            } else {
                $error = "Contraseña incorrecta";
                $error_tipo = "error";
            }
        } else {
            $error = "El correo electrónico no está registrado";
            $error_tipo = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar Sesión - Sistema de Gimnasio</title>
    
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <link href="css/gym-style.css" rel="stylesheet"/>
    <style>
    .login-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--gym-negro), #333);
    }
    .login-card {
        max-width: 400px;
        width: 100%;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }
    .login-header {
        background: var(--gym-negro);
        color: white;
        text-align: center;
        padding: 30px 20px;
    }
    .login-header img {
        max-width: 150px;
        margin-bottom: 15px;
    }
    .login-body {
        background: white;
        padding: 30px;
    }
    .btn-login {
        background: var(--gym-verde);
        color: white;
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 5px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .btn-login:hover {
        background: #5a9e12;
        transform: translateY(-2px);
    }
    .alert {
        padding: 12px 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .alert-warning {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .demo-users {
        color: #6c757d;  
        font-size: 0.75rem;
        margin-top: 20px;
        text-align: center;
        border-top: 1px solid #e9ecef;
        padding-top: 15px;
    }
    .demo-users i {
        opacity: 0.5;
        margin-right: 4px;
    }
</style>
</head>
<body>
    <div class="login-page">
        <div class="login-card">
            <div class="login-header">
                <img src="logo1.png" alt="GYM ADMIN">
                <h3>Sistema de Gimnasio</h3>
            </div>
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-<?php echo $error_tipo == 'warning' ? 'warning' : 'error'; ?>">
                        <i class="ti ti-<?php echo $error_tipo == 'warning' ? 'alert-triangle' : 'x'; ?> me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label class="form-label">Correo Electrónico</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="ti ti-mail"></i>
                            </span>
                            <input type="email" class="form-control" name="email" required 
                                   placeholder="ejemplo@correo.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="ti ti-lock"></i>
                            </span>
                            <input type="password" class="form-control" name="password" required 
                                   placeholder="********">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <button type="submit" class="btn-login">
                            <i class="ti ti-login me-2"></i> Iniciar Sesión
                        </button>
                    </div>
                    
                    <div class="demo-users">
                        <i class="ti ti-info-circle"></i>
                            <span>Prueba: admin@gimnasio.com / 123456 .  milkiss@gmail.com / 123456 . entrenador1@gimnasio.com / 123456</span>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- limpiar alertas después de 5 segundos -->
    <script>
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 1s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 1000);
            });
        }, 5000);
    </script>
</body>
</html>