<?php
include "../conexion.php";
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 2) {
    header("Location: ../login.php");
    exit();
}

$id_usuario = $_SESSION['usuario_id'];
$entrenador = $conn->query("SELECT ID_Entrenador FROM entrenadores WHERE ID_Usuario = $id_usuario")->fetch_assoc();
$id_entrenador = $entrenador['ID_Entrenador'];

$socios = $conn->query("SELECT s.ID_Socios, u.Nombre, u.Email, u.Telefono
                        FROM planes_rutinas pr
                        INNER JOIN socios s ON pr.ID_Socio = s.ID_Socios
                        INNER JOIN usuarios u ON s.ID_Usuario = u.ID_Usuario
                        WHERE pr.ID_Entrenador = $id_entrenador
                        GROUP BY s.ID_Socios
                        ORDER BY u.Nombre");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel Entrenador - Sistema de Gimnasio</title>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <link href="../css/gym-style.css" rel="stylesheet"/>
</head>
<body>
    <div class="page">
        <header class="navbar navbar-expand-md navbar-gym">
            <div class="container-xl">
                <a href="index.php" class="navbar-brand d-flex align-items-center">
                    <img src="../logo1.png" alt="GYM ADMIN" class="logo-gym me-2">
                    <span style="color: white; font-weight: 600;">GYM ENTRENADOR</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbar-menu">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link active" href="index.php"><i class="ti ti-dashboard me-1"></i> Mis Socios</a></li>
                        <li class="nav-item"><a class="nav-link" href="../evaluaciones.php"><i class="ti ti-heart-rate-monitor me-1"></i> Evaluaciones</a></li>
                        <li class="nav-item"><a class="nav-link" href="../logout.php"><i class="ti ti-logout me-1"></i> Salir</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="page-wrapper">
            <div class="container-xl">
                <h2>Bienvenido, <?php echo $_SESSION['usuario_nombre']; ?></h2>
                <p class="text-muted">Estos son los socios que tienes asignados:</p>

                <div class="row">
                    <?php if($socios->num_rows > 0): while($s = $socios->fetch_assoc()): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h4><?php echo htmlspecialchars($s['Nombre']); ?></h4>
                                <p><?php echo $s['Email']; ?><br><?php echo $s['Telefono']; ?></p>
                                <div class="btn-group w-100">
                                    <a href="../socio_detalle.php?id=<?php echo $s['ID_Socios']; ?>" class="btn btn-modificar btn-sm">Ver Perfil</a>
                                    <a href="../evaluaciones.php?socio=<?php echo $s['ID_Socios']; ?>&tab=comidas" class="btn btn-info btn-sm">Ver Comidas</a>
                                    <a href="../evaluaciones.php?socio=<?php echo $s['ID_Socios']; ?>&tab=fisica" class="btn btn-guardar btn-sm">Evaluación</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; else: ?>
                    <div class="col-12"><div class="alert alert-info">No tienes socios asignados aún.</div></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>