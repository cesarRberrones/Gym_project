<?php
include "conexion.php";
session_start();

// Verificar acceso
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] != 1 && $_SESSION['rol'] != 2 && $_SESSION['rol'] != 3)) {
    header("Location: login.php");
    exit();
}

$es_admin = ($_SESSION['rol'] == 1);
$es_entrenador = ($_SESSION['rol'] == 2);
$es_socio = ($_SESSION['rol'] == 3);
$id_socio_logueado = 0;
$mensaje = "";
$pestana_activa = isset($_GET['tab']) ? $_GET['tab'] : 'fisica';

// Si es socio, obtener su ID
if ($es_socio) {
    $result = $conn->query("SELECT ID_Socios FROM socios WHERE ID_Usuario = " . $_SESSION['usuario_id']);
    if ($result && $row = $result->fetch_assoc()) {
        $id_socio_logueado = $row['ID_Socios'];
    }
}

// Determinar socio seleccionado
$socio_seleccionado = 0;
if ($es_socio) {
    $socio_seleccionado = $id_socio_logueado;
    // Si es socio y no hay parámetro, redirigir con su ID
    if (!isset($_GET['socio'])) {
        header("Location: evaluaciones.php?socio=$socio_seleccionado&tab=$pestana_activa");
        exit();
    }
} else {
    $socio_seleccionado = isset($_GET['socio']) ? (int)$_GET['socio'] : 0;
}

// ==================== EVALUACIÓN FÍSICA ====================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion_evaluacion'])) {
    $id_socio = (int)$_POST['id_socio'];
    $fecha = $_POST['fecha_evaluacion'];
    $peso = (float)$_POST['peso'];
    $altura = (float)$_POST['altura'];
    $imc = $altura > 0 ? round($peso / ($altura * $altura), 2) : 0;
    $grasa = isset($_POST['porcentaje_grasa']) ? (float)$_POST['porcentaje_grasa'] : null;
    $cintura = isset($_POST['cintura']) ? (float)$_POST['cintura'] : null;
    $observaciones = mysqli_real_escape_string($conn, $_POST['observaciones']);
    
    $sql = "INSERT INTO evaluacion_fisica (ID_Socio, Fecha_Evaluacion, Peso, Altura, IMC, porcentaje_grasa, cintura, Observaciones) 
            VALUES ($id_socio, '$fecha', $peso, $altura, $imc, " . ($grasa !== null ? $grasa : 'NULL') . ", " . ($cintura !== null ? $cintura : 'NULL') . ", '$observaciones')";
    if ($conn->query($sql)) {
        $mensaje = '<div class="alert alert-success">Evaluación guardada correctamente</div>';
    } else {
        $mensaje = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
    }
}

// ==================== REGISTRO DE COMIDAS ====================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion_comida']) && ($es_socio || $es_admin)) {
    $id_socio = (int)$_POST['id_socio'];
    $id_alimento = (int)$_POST['id_alimento'];
    $fecha = $_POST['fecha_comida'];
    $gramos = (float)$_POST['gramos'];
    
    $alimento = $conn->query("SELECT Calorias_por_100g FROM alimentos WHERE ID_Alimento = $id_alimento")->fetch_assoc();
    $calorias = ($gramos * $alimento['Calorias_por_100g']) / 100;
    
    $sql = "INSERT INTO registro_comidas (ID_Socio, ID_Alimento, Fecha, Porcion_gramos, Calorias_totales) 
            VALUES ($id_socio, $id_alimento, '$fecha', $gramos, $calorias)";
    
    if ($conn->query($sql)) {
        $mensaje = '<div class="alert alert-success">Comida registrada correctamente</div>';
    } else {
        $mensaje = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
    }
}

// ==================== AGREGAR COMENTARIO (solo admin/entrenador) ====================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion_comentario']) && !$es_socio) {
    $id_registro = (int)$_POST['id_registro'];
    $comentario = mysqli_real_escape_string($conn, $_POST['comentario']);
    $conn->query("UPDATE registro_comidas SET comentario_entrenador = '$comentario' WHERE ID_Registro = $id_registro");
    header("Location: evaluaciones.php?socio=$socio_seleccionado&tab=comidas");
    exit();
}

// ==================== ELIMINAR COMIDA (solo admin) ====================
if (isset($_GET['eliminar_comida']) && $es_admin) {
    $id_registro = (int)$_GET['eliminar_comida'];
    $conn->query("DELETE FROM registro_comidas WHERE ID_Registro = $id_registro");
    header("Location: evaluaciones.php?socio=$socio_seleccionado&tab=comidas");
    exit();
}

// ==================== OBTENER DATOS PARA MOSTRAR ====================
// Evaluaciones del socio
$evaluaciones = [];
if ($socio_seleccionado) {
    $eval = $conn->query("SELECT * FROM evaluacion_fisica WHERE ID_Socio = $socio_seleccionado ORDER BY Fecha_Evaluacion DESC");
    while($row = $eval->fetch_assoc()) {
        $evaluaciones[] = $row;
    }
}

// Comidas de hoy del socio
$comidas_hoy = [];
$total_calorias_hoy = 0;
if ($socio_seleccionado) {
    $fecha_hoy = date('Y-m-d');
    $com = $conn->query("SELECT r.*, a.Nombre as alimento_nombre, a.Calorias_por_100g 
                         FROM registro_comidas r 
                         INNER JOIN alimentos a ON r.ID_Alimento = a.ID_Alimento 
                         WHERE r.ID_Socio = $socio_seleccionado AND r.Fecha = '$fecha_hoy'
                         ORDER BY r.ID_Registro DESC");
    while($row = $com->fetch_assoc()) {
        $comidas_hoy[] = $row;
        $total_calorias_hoy += $row['Calorias_totales'];
    }
}

// Datos para gráfica (últimas 6 evaluaciones de peso)
$pesos = [];
$fechas = [];
if ($socio_seleccionado) {
    $graf = $conn->query("SELECT Fecha_Evaluacion, Peso FROM evaluacion_fisica 
                          WHERE ID_Socio = $socio_seleccionado 
                          ORDER BY Fecha_Evaluacion ASC LIMIT 6");
    while($row = $graf->fetch_assoc()) {
        $fechas[] = date('d/m', strtotime($row['Fecha_Evaluacion']));
        $pesos[] = $row['Peso'];
    }
}

// Lista de alimentos para el select (solo si es socio o admin)
$alimentos = [];
if ($es_socio || $es_admin) {
    $alimentos = $conn->query("SELECT ID_Alimento, Nombre, Calorias_por_100g FROM alimentos ORDER BY Nombre");
}

// Lista de socios para select (solo admin/entrenador)
$socios_lista = null;
if (!$es_socio) {
    $socios_lista = $conn->query("SELECT s.ID_Socios, u.Nombre FROM socios s INNER JOIN usuarios u ON s.ID_Usuario = u.ID_Usuario ORDER BY u.Nombre");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Evaluaciones - Sistema de Gimnasio</title>
    
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <link href="css/gym-style.css" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <div class="page">
        <header class="navbar navbar-expand-md navbar-gym">
    <div class="container-xl">
        <a href="index.php" class="navbar-brand d-flex align-items-center">
            <img src="logo1.png" alt="GYM ADMIN" class="logo-gym me-2">
            <span style="color: white; font-weight: 600;">
                <?php if ($es_admin): ?>GYM ADMIN
                <?php elseif ($es_entrenador): ?>GYM ENTRENADOR
                <?php else: ?>GYM SOCIO
                <?php endif; ?>
            </span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbar-menu">
            <ul class="navbar-nav ms-auto">
                <?php if ($es_admin): ?>
                    <!-- Menú para ADMIN -->
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="ti ti-dashboard me-1"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="membresias.php"><i class="ti ti-cards me-1"></i> Membresías</a></li>
                    <li class="nav-item"><a class="nav-link" href="socios.php"><i class="ti ti-users me-1"></i> Socios</a></li>
                    <li class="nav-item"><a class="nav-link active" href="evaluaciones.php"><i class="ti ti-heart-rate-monitor me-1"></i> Evaluaciones</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php"><i class="ti ti-logout me-1"></i> Salir</a></li>
                <?php elseif ($es_entrenador): ?>
                    <!-- Menú para ENTRENADOR -->
                    <li class="nav-item"><a class="nav-link" href="entrenador/index.php"><i class="ti ti-dashboard me-1"></i> Mis Socios</a></li>
                    <li class="nav-item"><a class="nav-link active" href="evaluaciones.php"><i class="ti ti-heart-rate-monitor me-1"></i> Evaluaciones</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php"><i class="ti ti-logout me-1"></i> Salir</a></li>
                <?php else: ?>
                    <!-- Menú para SOCIO -->
                    <li class="nav-item"><a class="nav-link" href="socio/index.php"><i class="ti ti-home me-1"></i> Mi Perfil</a></li>
                    <li class="nav-item"><a class="nav-link active" href="evaluaciones.php"><i class="ti ti-heart-rate-monitor me-1"></i> Mis Evaluaciones</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php"><i class="ti ti-logout me-1"></i> Salir</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</header>

        <div class="page-wrapper">
            <div class="container-xl">
                <?php echo $mensaje; ?>
                
                <!-- Selector de socio (solo para admin/entrenador) -->
                <?php if (!$es_socio): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="evaluaciones.php" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Seleccionar Socio</label>
                                <select name="socio" class="form-select" onchange="this.form.submit()">
                                    <option value="">-- Seleccione un socio --</option>
                                    <?php if($socios_lista) while($s = $socios_lista->fetch_assoc()): ?>
                                        <option value="<?php echo $s['ID_Socios']; ?>" <?php echo ($socio_seleccionado == $s['ID_Socios']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($s['Nombre']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <input type="hidden" name="tab" value="<?php echo $pestana_activa; ?>">
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($socio_seleccionado): ?>
                
                <!-- Pestañas -->
                <ul class="nav nav-tabs mb-4">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $pestana_activa == 'fisica' ? 'active' : ''; ?>" 
                           href="?socio=<?php echo $socio_seleccionado; ?>&tab=fisica">
                            <i class="ti ti-activity me-1"></i> Evaluación Física
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $pestana_activa == 'comidas' ? 'active' : ''; ?>" 
                           href="?socio=<?php echo $socio_seleccionado; ?>&tab=comidas">
                            <i class="ti ti-apple me-1"></i> Registro de Comidas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $pestana_activa == 'grafica' ? 'active' : ''; ?>" 
                           href="?socio=<?php echo $socio_seleccionado; ?>&tab=grafica">
                            <i class="ti ti-chart-line me-1"></i> Evolución
                        </a>
                    </li>
                </ul>

                <!-- ==================== PESTAÑA: EVALUACIÓN FÍSICA ==================== -->
                <?php if ($pestana_activa == 'fisica'): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Nueva Evaluación Física</h3>
                    </div>
                    <form method="POST" action="evaluaciones.php?socio=<?php echo $socio_seleccionado; ?>&tab=fisica">
                        <input type="hidden" name="id_socio" value="<?php echo $socio_seleccionado; ?>">
                        <input type="hidden" name="accion_evaluacion" value="1">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Fecha</label>
                                    <input type="date" name="fecha_evaluacion" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Peso (kg)</label>
                                    <input type="number" step="0.1" name="peso" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Altura (m)</label>
                                    <input type="number" step="0.01" name="altura" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">% Grasa corporal</label>
                                    <input type="number" step="0.1" name="porcentaje_grasa" class="form-control">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Cintura (cm)</label>
                                    <input type="number" step="0.1" name="cintura" class="form-control">
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Observaciones</label>
                                    <textarea name="observaciones" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-guardar">Guardar Evaluación</button>
                        </div>
                    </form>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title">Historial de Evaluaciones</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter">
                            <thead>
                                <tr><th>Fecha</th><th>Peso</th><th>Altura</th><th>IMC</th><th>% Grasa</th><th>Cintura</th> </thead>
                            <tbody>
                                <?php foreach($evaluaciones as $e): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($e['Fecha_Evaluacion'])); ?></td>
                                    <td><?php echo $e['Peso']; ?> kg<\/td>
                                    <td><?php echo $e['Altura']; ?> m<\/td>
                                    <td><?php echo $e['IMC']; ?><\/td>
                                    <td><?php echo $e['Porcentaje_Grasa'] ?? '—'; ?>%<\/td>
                                    <td><?php echo $e['Cintura'] ?? '—'; ?> cm<\/td>
                                  </tr>
                                <?php endforeach; ?>
                                <?php if(empty($evaluaciones)): ?>
                                  <tr><td colspan="6" class="text-center">Sin evaluaciones registradas<\/td><\/tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ==================== PESTAÑA: REGISTRO DE COMIDAS ==================== -->
                <?php if ($pestana_activa == 'comidas'): ?>
                
                <!-- Formulario de registro (solo socio o admin) -->
                <?php if ($es_socio || $es_admin): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Registrar Comida</h3>
                    </div>
                    <form method="POST" action="evaluaciones.php?socio=<?php echo $socio_seleccionado; ?>&tab=comidas">
                        <input type="hidden" name="id_socio" value="<?php echo $socio_seleccionado; ?>">
                        <input type="hidden" name="accion_comida" value="1">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Fecha</label>
                                    <input type="date" name="fecha_comida" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Alimento</label>
                                    <select name="id_alimento" class="form-select" required>
                                        <option value="">Seleccionar...</option>
                                        <?php if($alimentos) while($a = $alimentos->fetch_assoc()): ?>
                                            <option value="<?php echo $a['ID_Alimento']; ?>">
                                                <?php echo htmlspecialchars($a['Nombre']); ?> (<?php echo $a['Calorias_por_100g']; ?> cal/100g)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Cantidad (gramos)</label>
                                    <input type="number" step="10" name="gramos" class="form-control" placeholder="Ej: 150" required>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-guardar">Registrar Comida</button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Listado de comidas del día -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title">Comidas de Hoy</h3>
                        <div class="card-actions">
                            <span class="badge bg-green">Total: <?php echo round($total_calorias_hoy); ?> kcal</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter">
                            <thead>
                                 <tr><th>Alimento</th><th>Gramos</th><th>Calorías</th><th>Comentario</th><?php if (!$es_socio): ?><th>Acción</th><?php endif; ?></tr>
                            </thead>
                            <tbody>
                                <?php foreach($comidas_hoy as $c): ?>
                                  <tr>
                                      <td><?php echo htmlspecialchars($c['alimento_nombre']); ?></td>
                                      <td><?php echo $c['Porcion_gramos']; ?> g</td>
                                      <td><?php echo round($c['Calorias_totales']); ?> kcal</td>
                                      <td>
                                        <?php if ($c['comentario_entrenador']): ?>
                                            <span class="text-muted"><?php echo htmlspecialchars($c['comentario_entrenador']); ?></span>
                                        <?php else: ?>
                                            <em class="text-muted">Sin comentario</em>
                                        <?php endif; ?>
                                        <?php if (!$es_socio): ?>
                                        <button class="btn btn-sm btn-outline-primary mt-1" onclick="agregarComentario(<?php echo $c['ID_Registro']; ?>, '<?php echo htmlspecialchars($c['alimento_nombre']); ?>')">
                                            <i class="ti ti-message"></i>
                                        </button>
                                        <?php endif; ?>
                                      </td>
                                    <?php if (!$es_socio): ?>
                                      <td>
                                        <button class="btn btn-sm btn-eliminar" onclick="eliminarComida(<?php echo $c['ID_Registro']; ?>)">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                      </td>
                                    <?php endif; ?>
                                  </tr>
                                <?php endforeach; ?>
                                <?php if(empty($comidas_hoy)): ?>
                                  <tr><td colspan="<?php echo $es_socio ? '4' : '5'; ?>" class="text-center">No hay comidas registradas hoy</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ==================== PESTAÑA: GRÁFICA SIMPLIFICADA ==================== -->
                <?php if ($pestana_activa == 'grafica'): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Evolución de Peso</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="pesoChart" style="height: 300px;"></canvas>
                        <?php if(empty($pesos)): ?>
                        <p class="text-center text-muted mt-3">No hay datos suficientes para mostrar gráfica</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="ti ti-info-circle me-2"></i> Selecciona un socio para comenzar
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal para comentario (solo admin/entrenador) -->
    <?php if (!$es_socio): ?>
    <div class="modal fade" id="modalComentario" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="evaluaciones.php?socio=<?php echo $socio_seleccionado; ?>&tab=comidas">
                    <input type="hidden" name="accion_comentario" value="1">
                    <input type="hidden" name="id_registro" id="comentario_id_registro">
                    <div class="modal-header">
                        <h5 class="modal-title">Agregar Comentario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Alimento:</strong> <span id="comentario_alimento"></span></p>
                        <textarea name="comentario" class="form-control" rows="3" placeholder="Ej: Esta porción es alta en calorías, intenta reducirla..."></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-guardar">Guardar Comentario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
    <script>
        <?php if (!empty($pesos) && $pestana_activa == 'grafica'): ?>
        const ctx = document.getElementById('pesoChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($fechas); ?>,
                datasets: [{
                    label: 'Peso (kg)',
                    data: <?php echo json_encode($pesos); ?>,
                    borderColor: '#74b816',
                    backgroundColor: 'rgba(116, 184, 22, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'top' }
                }
            }
        });
        <?php endif; ?>
        
        function agregarComentario(id, alimento) {
            document.getElementById('comentario_id_registro').value = id;
            document.getElementById('comentario_alimento').innerText = alimento;
            new bootstrap.Modal(document.getElementById('modalComentario')).show();
        }
        
        function eliminarComida(id) {
            if (confirm('¿Eliminar este registro? Solo administradores pueden hacer esto.')) {
                window.location.href = 'evaluaciones.php?socio=<?php echo $socio_seleccionado; ?>&tab=comidas&eliminar_comida=' + id;
            }
        }
    </script>
</body>
</html>