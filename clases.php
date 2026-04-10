<?php
include "conexion.php";
session_start();

// ===================== ACCIONES POST =====================
$mensaje = "";
$tipo_msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    // --- CREAR NUEVA CLASE ---
    if ($accion == 'nueva_clase') {
        $nombre       = $conn->real_escape_string(trim($_POST['nombre']));
        $id_entrenador = (int)$_POST['id_entrenador'];
        $fecha_hora   = $conn->real_escape_string($_POST['fecha'] . ' ' . $_POST['hora'] . ':00');
        $cupo         = (int)$_POST['cupo'];
        $descripcion  = $conn->real_escape_string(trim($_POST['descripcion'] ?? ''));

        $sql = "INSERT INTO clases (Nombre, ID_Entrenador, Fecha, Cupo_Maximo, Descripcion, Estado)
                VALUES ('$nombre', $id_entrenador, '$fecha_hora', $cupo, '$descripcion', 'programada')";
        if ($conn->query($sql)) {
            $mensaje  = "✅ Clase <strong>$nombre</strong> creada exitosamente.";
            $tipo_msg = "success";
        } else {
            $mensaje  = "Error al crear clase: " . $conn->error;
            $tipo_msg = "danger";
        }
    }

    // --- CANCELAR CLASE ---
    if ($accion == 'cancelar_clase') {
        $id_clase = (int)$_POST['id_clase'];
        $conn->query("UPDATE clases SET Estado = 'cancelada' WHERE ID_Clase = $id_clase");
        $mensaje  = "Clase cancelada correctamente.";
        $tipo_msg = "warning";
    }

    // --- COMPLETAR CLASE (marcar como realizada) ---
    if ($accion == 'completar_clase') {
        $id_clase = (int)$_POST['id_clase'];
        $conn->query("UPDATE clases SET Estado = 'completada' WHERE ID_Clase = $id_clase");
        $mensaje  = "Clase marcada como completada.";
        $tipo_msg = "success";
    }

    // --- REGISTRAR RESERVA (con lista de espera automática) ---
    if ($accion == 'registrar_reserva') {
        $id_clase = (int)$_POST['id_clase'];
        $id_socio = (int)$_POST['id_socio'];

        $existe = $conn->query("SELECT ID_Reserva FROM reservas_clases
                                WHERE ID_Clase = $id_clase AND ID_Socio = $id_socio AND Estado != 'cancelada'");
        if ($existe->num_rows > 0) {
            $mensaje  = "Este socio ya tiene una reserva activa para esta clase.";
            $tipo_msg = "warning";
        } else {
            $r_cupo       = $conn->query("SELECT Cupo_Maximo FROM clases WHERE ID_Clase = $id_clase")->fetch_assoc();
            $r_confirmados = $conn->query("SELECT COUNT(*) as c FROM reservas_clases
                                           WHERE ID_Clase = $id_clase AND Estado = 'confirmada'")->fetch_assoc();
            $ocupados     = (int)$r_confirmados['c'];
            $cupo_max     = (int)$r_cupo['Cupo_Maximo'];

            $estado_reserva = ($ocupados < $cupo_max) ? 'confirmada' : 'en_espera';

            $sql = "INSERT INTO reservas_clases (ID_Clase, ID_Socio, Estado)
                    VALUES ($id_clase, $id_socio, '$estado_reserva')";
            if ($conn->query($sql)) {
                if ($estado_reserva == 'confirmada') {
                    $mensaje  = "✅ Reserva confirmada exitosamente.";
                    $tipo_msg = "success";
                } else {
                    $mensaje  = "⏳ Cupo lleno. El socio fue agregado a la <strong>lista de espera</strong>.";
                    $tipo_msg = "info";
                }
            } else {
                $mensaje  = "Error: " . $conn->error;
                $tipo_msg = "danger";
            }
        }
    }

    // --- CANCELAR RESERVA (y promover al siguiente en espera) ---
    if ($accion == 'cancelar_reserva') {
        $id_reserva = (int)$_POST['id_reserva'];
        $id_clase   = (int)$_POST['id_clase'];

        $r_estado = $conn->query("SELECT Estado FROM reservas_clases WHERE ID_Reserva = $id_reserva")->fetch_assoc();
        $conn->query("UPDATE reservas_clases SET Estado = 'cancelada' WHERE ID_Reserva = $id_reserva");

        // Si era confirmada, promover el primero en lista de espera
        if ($r_estado && $r_estado['Estado'] == 'confirmada') {
            $sig = $conn->query("SELECT ID_Reserva FROM reservas_clases
                                 WHERE ID_Clase = $id_clase AND Estado = 'en_espera'
                                 ORDER BY Fecha_Reserva ASC LIMIT 1");
            if ($sig->num_rows > 0) {
                $next_id = $sig->fetch_assoc()['ID_Reserva'];
                $conn->query("UPDATE reservas_clases SET Estado = 'confirmada' WHERE ID_Reserva = $next_id");
                $mensaje  = "Reserva cancelada. El siguiente socio en espera fue <strong>confirmado</strong> automáticamente.";
                $tipo_msg = "info";
            } else {
                $mensaje  = "Reserva cancelada correctamente.";
                $tipo_msg = "warning";
            }
        } else {
            $mensaje  = "Reserva de lista de espera cancelada.";
            $tipo_msg = "warning";
        }
    }

    // --- REGISTRAR ASISTENCIA ---
    if ($accion == 'registrar_asistencia') {
        $id_clase = (int)$_POST['id_clase'];
        $id_socio = (int)$_POST['id_socio'];

        $existe = $conn->query("SELECT ID_Asistencia FROM asistencias
                                WHERE ID_Socio = $id_socio AND ID_Clase = $id_clase");
        if ($existe->num_rows > 0) {
            $mensaje  = "La asistencia de este socio ya fue registrada.";
            $tipo_msg = "warning";
        } else {
            $fh  = date('Y-m-d H:i:s');
            $conn->query("INSERT INTO asistencias (ID_Socio, Fecha_Hora, ID_Clase) VALUES ($id_socio, '$fh', $id_clase)");
            $mensaje  = "✅ Asistencia registrada.";
            $tipo_msg = "success";
        }
    }

    // --- EDITAR CLASE ---
    if ($accion == 'editar_clase') {
        $id_clase     = (int)$_POST['id_clase'];
        $nombre       = $conn->real_escape_string(trim($_POST['nombre']));
        $id_entrenador = (int)$_POST['id_entrenador'];
        $fecha_hora   = $conn->real_escape_string($_POST['fecha'] . ' ' . $_POST['hora'] . ':00');
        $cupo         = (int)$_POST['cupo'];
        $descripcion  = $conn->real_escape_string(trim($_POST['descripcion'] ?? ''));

        $sql = "UPDATE clases SET Nombre='$nombre', ID_Entrenador=$id_entrenador,
                Fecha='$fecha_hora', Cupo_Maximo=$cupo, Descripcion='$descripcion'
                WHERE ID_Clase=$id_clase";
        $conn->query($sql);
        $mensaje  = "Clase actualizada correctamente.";
        $tipo_msg = "success";
    }
}

// ===================== PARÁMETROS DE NAVEGACIÓN =====================
$mes   = isset($_GET['mes'])  ? max(1, min(12, (int)$_GET['mes']))  : (int)date('n');
$anio  = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');
$vista = isset($_GET['vista']) ? $_GET['vista'] : 'calendario';
$clase_id = isset($_GET['clase']) ? (int)$_GET['clase'] : 0;

// Mes anterior / siguiente
$mes_ant  = $mes - 1; $anio_ant = $anio;
if ($mes_ant < 1) { $mes_ant = 12; $anio_ant--; }
$mes_sig  = $mes + 1; $anio_sig = $anio;
if ($mes_sig > 12) { $mes_sig = 1; $anio_sig++; }

$meses_nombres   = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$dias_semana     = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
$primer_dia_sem  = (int)date('w', strtotime("$anio-$mes-01"));
$dias_en_mes     = (int)date('t', strtotime("$anio-$mes-01"));

// ===================== DATOS =====================
// Entrenadores
$entrenadores_arr = [];
$r_ent = $conn->query("SELECT e.ID_Entrenador, u.Nombre FROM entrenadores e
                        INNER JOIN usuarios u ON e.ID_Usuario = u.ID_Usuario ORDER BY u.Nombre");
while ($e = $r_ent->fetch_assoc()) $entrenadores_arr[] = $e;

// Socios
$socios_arr = [];
$r_soc = $conn->query("SELECT s.ID_Socios, u.Nombre FROM socios s
                        INNER JOIN usuarios u ON s.ID_Usuario = u.ID_Usuario ORDER BY u.Nombre");
while ($s = $r_soc->fetch_assoc()) $socios_arr[] = $s;

// Clases del mes (para calendario)
$primer_dia_mes = "$anio-$mes-01";
$ultimo_dia_mes = date('Y-m-t', strtotime($primer_dia_mes));
$clases_mes     = [];
$r_clm = $conn->query("SELECT c.*, u.Nombre as entrenador_nombre,
    (SELECT COUNT(*) FROM reservas_clases r WHERE r.ID_Clase=c.ID_Clase AND r.Estado='confirmada') as confirmados,
    (SELECT COUNT(*) FROM reservas_clases r WHERE r.ID_Clase=c.ID_Clase AND r.Estado='en_espera') as en_espera
    FROM clases c
    LEFT JOIN entrenadores e ON c.ID_Entrenador=e.ID_Entrenador
    LEFT JOIN usuarios u ON e.ID_Usuario=u.ID_Usuario
    WHERE DATE(c.Fecha) BETWEEN '$primer_dia_mes' AND '$ultimo_dia_mes'
    ORDER BY c.Fecha ASC");
while ($cl = $r_clm->fetch_assoc()) {
    $dia = (int)date('j', strtotime($cl['Fecha']));
    $clases_mes[$dia][] = $cl;
}

// Todas las clases (vista lista)
$todas_clases = [];
if ($vista == 'lista') {
    $r_all = $conn->query("SELECT c.*, u.Nombre as entrenador_nombre,
        (SELECT COUNT(*) FROM reservas_clases r WHERE r.ID_Clase=c.ID_Clase AND r.Estado='confirmada') as confirmados,
        (SELECT COUNT(*) FROM reservas_clases r WHERE r.ID_Clase=c.ID_Clase AND r.Estado='en_espera') as en_espera
        FROM clases c
        LEFT JOIN entrenadores e ON c.ID_Entrenador=e.ID_Entrenador
        LEFT JOIN usuarios u ON e.ID_Usuario=u.ID_Usuario
        ORDER BY c.Fecha DESC");
    while ($cl = $r_all->fetch_assoc()) $todas_clases[] = $cl;
}

// Detalle de clase específica
$detalle_clase     = null;
$reservas_clase    = [];
$asistencias_clase = [];

if ($clase_id > 0) {
    $r_det = $conn->query("SELECT c.*, u.Nombre as entrenador_nombre, e.ID_Entrenador,
        (SELECT COUNT(*) FROM reservas_clases r WHERE r.ID_Clase=c.ID_Clase AND r.Estado='confirmada') as confirmados,
        (SELECT COUNT(*) FROM reservas_clases r WHERE r.ID_Clase=c.ID_Clase AND r.Estado='en_espera') as en_espera
        FROM clases c
        LEFT JOIN entrenadores e ON c.ID_Entrenador=e.ID_Entrenador
        LEFT JOIN usuarios u ON e.ID_Usuario=u.ID_Usuario
        WHERE c.ID_Clase=$clase_id");
    $detalle_clase = $r_det->fetch_assoc();

    $r_res = $conn->query("SELECT r.*, u.Nombre as socio_nombre
        FROM reservas_clases r
        INNER JOIN socios s ON r.ID_Socio=s.ID_Socios
        INNER JOIN usuarios u ON s.ID_Usuario=u.ID_Usuario
        WHERE r.ID_Clase=$clase_id AND r.Estado != 'cancelada'
        ORDER BY FIELD(r.Estado,'confirmada','en_espera'), r.Fecha_Reserva ASC");
    while ($rv = $r_res->fetch_assoc()) $reservas_clase[] = $rv;

    $r_asis = $conn->query("SELECT a.*, u.Nombre as socio_nombre
        FROM asistencias a
        INNER JOIN socios s ON a.ID_Socio=s.ID_Socios
        INNER JOIN usuarios u ON s.ID_Usuario=u.ID_Usuario
        WHERE a.ID_Clase=$clase_id
        ORDER BY a.Fecha_Hora ASC");
    while ($as = $r_asis->fetch_assoc()) $asistencias_clase[] = $as;
    
    // IDs de socios con asistencia ya registrada
    $socios_con_asistencia = array_column($asistencias_clase, 'ID_Socio');
}

// Helper: estado a badge
function estadoBadgeClase($estado) {
    switch ($estado) {
        case 'programada':  return '<span class="badge bg-blue-lt text-blue">📅 Programada</span>';
        case 'cancelada':   return '<span class="badge bg-red-lt text-red">❌ Cancelada</span>';
        case 'completada':  return '<span class="badge bg-green-lt text-green">✅ Completada</span>';
        default:            return "<span class=\"badge\">$estado</span>";
    }
}
function estadoBadgeReserva($estado) {
    switch ($estado) {
        case 'confirmada':  return '<span class="badge bg-green-lt text-green">✅ Confirmada</span>';
        case 'en_espera':   return '<span class="badge bg-yellow-lt text-yellow">⏳ En espera</span>';
        case 'cancelada':   return '<span class="badge bg-red-lt text-red">❌ Cancelada</span>';
        default:            return "<span class=\"badge\">$estado</span>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clases Grupales - Sistema de Gimnasio</title>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <link href="css/gym-style.css" rel="stylesheet"/>
    <style>
        /* ---- CALENDARIO ---- */
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
            background: #dee2e6;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        .calendar-header-day {
            background: var(--gym-negro, #1a1a1a);
            color: #fff;
            text-align: center;
            padding: 10px 4px;
            font-weight: 600;
            font-size: 0.82rem;
        }
        .calendar-cell {
            background: #fff;
            min-height: 100px;
            padding: 6px;
            vertical-align: top;
        }
        .calendar-cell.empty { background: #f8f9fa; }
        .calendar-cell.today { background: #f0fff4; border: 2px solid var(--gym-verde, #6fba12); }
        .calendar-day-num {
            font-size: 0.78rem;
            font-weight: 700;
            color: #495057;
            margin-bottom: 4px;
        }
        .calendar-cell.today .calendar-day-num { color: var(--gym-verde, #6fba12); }
        .clase-pill {
            display: block;
            font-size: 0.70rem;
            border-radius: 4px;
            padding: 2px 5px;
            margin-bottom: 3px;
            cursor: pointer;
            text-decoration: none;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: 500;
            transition: opacity .2s;
        }
        .clase-pill:hover { opacity: .8; }
        .pill-programada { background: #dbeafe; color: #1d4ed8; }
        .pill-cancelada  { background: #fee2e2; color: #b91c1c; text-decoration: line-through; }
        .pill-completada { background: #d1fae5; color: #065f46; }

        /* ---- BARRA DE CUPO ---- */
        .cupo-bar-wrap { font-size: 0.78rem; }
        .progress { height: 8px; border-radius: 4px; }

        /* ---- TABLA RESERVAS ---- */
        .badge-espera  { background:#fef3c7; color:#92400e; }
        .badge-confirm { background:#d1fae5; color:#065f46; }
    </style>
</head>
<body>
<div class="page">

<!-- ============ NAVBAR ============ -->
<header class="navbar navbar-expand-md navbar-gym">
    <div class="container-xl">
        <a href="index.php" class="navbar-brand d-flex align-items-center">
            <img src="logo1.png" alt="GYM ADMIN" class="logo-gym me-2">
            <span style="color:white;font-weight:600;">GYM ADMIN</span>
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
                <li class="nav-item"><a class="nav-link" href="evaluaciones.php"><i class="ti ti-heart-rate-monitor me-1"></i> Evaluaciones</a></li>
                <li class="nav-item"><a class="nav-link active" href="clases.php"><i class="ti ti-calendar me-1"></i> Clases</a></li>
                <li class="nav-item"><a class="nav-link" href="Pagos.php"><i class="ti ti-credit-card me-1"></i> Pagos/Caja</a></li>
                <li class="nav-item"><a class="nav-link" href="reportes.php"><i class="ti ti-chart-bar me-1"></i> Reportes</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php"><i class="ti ti-logout me-1"></i> Salir</a></li>
            </ul>
        </div>
    </div>
</header>

<div class="page-wrapper">
<div class="container-xl mt-3">

    <!-- ---- TÍTULO Y ACCIONES ---- -->
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <i class="ti ti-calendar-event me-2" style="color:var(--gym-verde,#6fba12)"></i>
                    Clases Grupales
                </h2>
            </div>
            <div class="col-auto d-flex gap-2">
                <a href="clases.php?vista=calendario&mes=<?= $mes ?>&anio=<?= $anio ?>"
                   class="btn <?= $vista=='calendario' ? 'btn-guardar' : 'btn-modificar' ?>">
                    <i class="ti ti-calendar me-1"></i> Calendario
                </a>
                <a href="clases.php?vista=lista"
                   class="btn <?= $vista=='lista' ? 'btn-guardar' : 'btn-modificar' ?>">
                    <i class="ti ti-list me-1"></i> Lista
                </a>
                <button class="btn btn-guardar" data-bs-toggle="modal" data-bs-target="#modalNuevaClase">
                    <i class="ti ti-plus me-1"></i> Nueva Clase
                </button>
            </div>
        </div>
    </div>

    <!-- ---- ALERTA ---- -->
    <?php if ($mensaje): ?>
    <div class="alert alert-<?= $tipo_msg ?> alert-dismissible" role="alert">
        <?= $mensaje ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- ========================================================
         VISTA: DETALLE DE CLASE
    ======================================================== -->
    <?php if ($clase_id > 0 && $detalle_clase): ?>
    <div class="mb-3">
        <a href="clases.php?vista=<?= $vista ?>&mes=<?= $mes ?>&anio=<?= $anio ?>" class="btn btn-modificar btn-sm">
            <i class="ti ti-arrow-left me-1"></i> Volver
        </a>
    </div>

    <!-- Info de la clase -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">
                <?= htmlspecialchars($detalle_clase['Nombre']) ?>
                &nbsp;<?= estadoBadgeClase($detalle_clase['Estado'] ?? 'programada') ?>
            </h3>
            <div class="card-actions d-flex gap-2">
                <?php if (($detalle_clase['Estado'] ?? 'programada') == 'programada'): ?>
                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalEditarClase">
                    <i class="ti ti-edit me-1"></i> Editar
                </button>
                <form method="POST" class="d-inline" onsubmit="return confirm('¿Marcar como completada?')">
                    <input type="hidden" name="accion" value="completar_clase">
                    <input type="hidden" name="id_clase" value="<?= $clase_id ?>">
                    <button class="btn btn-sm btn-primary" type="submit">
                        <i class="ti ti-check me-1"></i> Completar
                    </button>
                </form>
                <form method="POST" class="d-inline" onsubmit="return confirm('¿Cancelar esta clase?')">
                    <input type="hidden" name="accion" value="cancelar_clase">
                    <input type="hidden" name="id_clase" value="<?= $clase_id ?>">
                    <button class="btn btn-sm btn-danger" type="submit">
                        <i class="ti ti-ban me-1"></i> Cancelar Clase
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="mb-2"><span class="text-muted">Fecha y Hora:</span><br>
                        <strong><?= date('d/m/Y H:i', strtotime($detalle_clase['Fecha'])) ?></strong>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-2"><span class="text-muted">Entrenador:</span><br>
                        <strong><?= htmlspecialchars($detalle_clase['entrenador_nombre'] ?? '—') ?></strong>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-2 cupo-bar-wrap">
                        <span class="text-muted">Cupo:</span><br>
                        <strong><?= $detalle_clase['confirmados'] ?>/<?= $detalle_clase['Cupo_Maximo'] ?></strong>
                        confirmados &nbsp;
                        <?php if ($detalle_clase['en_espera'] > 0): ?>
                            <span class="badge badge-espera"><?= $detalle_clase['en_espera'] ?> en espera</span>
                        <?php endif; ?>
                        <div class="progress mt-1">
                            <?php $pct = $detalle_clase['Cupo_Maximo'] > 0
                                ? min(100, round(($detalle_clase['confirmados'] / $detalle_clase['Cupo_Maximo']) * 100))
                                : 0;
                                $color = $pct >= 100 ? 'bg-danger' : ($pct >= 75 ? 'bg-warning' : 'bg-success'); ?>
                            <div class="progress-bar <?= $color ?>" style="width:<?= $pct ?>%"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-2"><span class="text-muted">Descripción:</span><br>
                        <?= htmlspecialchars($detalle_clase['Descripcion'] ?? '—') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pestañas detalle -->
    <?php
    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'reservas';
    $base_url = "clases.php?clase=$clase_id&vista=$vista&mes=$mes&anio=$anio";
    ?>
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link <?= $tab=='reservas'?'active':'' ?>" href="<?= $base_url ?>&tab=reservas">
                <i class="ti ti-users me-1"></i> Reservas y Lista de Espera
                <span class="badge bg-blue ms-1"><?= count($reservas_clase) ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab=='asistencia'?'active':'' ?>" href="<?= $base_url ?>&tab=asistencia">
                <i class="ti ti-checklist me-1"></i> Lista de Asistencia
                <span class="badge bg-green ms-1"><?= count($asistencias_clase) ?></span>
            </a>
        </li>
    </ul>

    <!-- TAB: RESERVAS -->
    <?php if ($tab == 'reservas'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Reservas de la Clase</h3>
            <?php if (($detalle_clase['Estado'] ?? 'programada') == 'programada'): ?>
            <div class="card-actions">
                <button class="btn btn-guardar btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgregarReserva">
                    <i class="ti ti-user-plus me-1"></i> Agregar Socio
                </button>
            </div>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Socio</th>
                        <th>Estado</th>
                        <th>Fecha de Reserva</th>
                        <?php if (($detalle_clase['Estado'] ?? 'programada') == 'programada'): ?>
                        <th>Acción</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reservas_clase)): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">No hay reservas para esta clase.</td></tr>
                    <?php else: foreach ($reservas_clase as $i => $rv): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= htmlspecialchars($rv['socio_nombre']) ?></strong></td>
                        <td><?= estadoBadgeReserva($rv['Estado']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($rv['Fecha_Reserva'])) ?></td>
                        <?php if (($detalle_clase['Estado'] ?? 'programada') == 'programada'): ?>
                        <td>
                            <form method="POST" class="d-inline" onsubmit="return confirm('¿Cancelar esta reserva?')">
                                <input type="hidden" name="accion" value="cancelar_reserva">
                                <input type="hidden" name="id_reserva" value="<?= $rv['ID_Reserva'] ?>">
                                <input type="hidden" name="id_clase" value="<?= $clase_id ?>">
                                <button class="btn btn-danger btn-sm" type="submit">
                                    <i class="ti ti-x"></i>
                                </button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- TAB: ASISTENCIA -->
    <?php if ($tab == 'asistencia'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Lista de Asistencia</h3>
            <?php
            // Socios confirmados que no tienen asistencia aún
            $confirmados_sin_asistencia = array_filter($reservas_clase, function($rv) use ($socios_con_asistencia) {
                return $rv['Estado'] == 'confirmada' && !in_array($rv['ID_Socio'], $socios_con_asistencia);
            });
            if (!empty($confirmados_sin_asistencia)): ?>
            <div class="card-actions">
                <button class="btn btn-guardar btn-sm" data-bs-toggle="modal" data-bs-target="#modalRegistrarAsistencia">
                    <i class="ti ti-clipboard-check me-1"></i> Registrar Asistencia
                </button>
            </div>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr><th>#</th><th>Socio</th><th>Hora de Registro</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($asistencias_clase)): ?>
                    <tr><td colspan="3" class="text-center py-4 text-muted">No hay asistencias registradas.</td></tr>
                    <?php else: foreach ($asistencias_clase as $i => $as): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= htmlspecialchars($as['socio_nombre']) ?></strong></td>
                        <td><?= date('d/m/Y H:i', strtotime($as['Fecha_Hora'])) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <!-- Socios confirmados pendientes de asistencia -->
        <?php if (!empty($confirmados_sin_asistencia)): ?>
        <div class="card-footer text-muted" style="font-size:.82rem;">
            <i class="ti ti-info-circle me-1"></i>
            Socios con reserva confirmada pendientes de registrar asistencia:
            <?= implode(', ', array_map(fn($r) => htmlspecialchars($r['socio_nombre']), $confirmados_sin_asistencia)) ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ======= MODAL: AGREGAR RESERVA ======= -->
    <div class="modal fade" id="modalAgregarReserva" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="clases.php?clase=<?= $clase_id ?>&vista=<?= $vista ?>&mes=<?= $mes ?>&anio=<?= $anio ?>&tab=reservas">
            <input type="hidden" name="accion" value="registrar_reserva">
            <input type="hidden" name="id_clase" value="<?= $clase_id ?>">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-user-plus me-2"></i> Agregar Socio a la Clase</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php
                    // Socios que ya tienen reserva activa
                    $socios_reservados = array_column($reservas_clase, 'ID_Socio');
                    $socios_disponibles = array_filter($socios_arr, fn($s) => !in_array($s['ID_Socios'], $socios_reservados));
                    if (empty($socios_disponibles)): ?>
                        <p class="text-muted">Todos los socios ya tienen una reserva en esta clase.</p>
                    <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label required">Seleccionar Socio</label>
                        <select class="form-select" name="id_socio" required>
                            <option value="">Elegir socio...</option>
                            <?php foreach ($socios_disponibles as $s): ?>
                            <option value="<?= $s['ID_Socios'] ?>"><?= htmlspecialchars($s['Nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($detalle_clase['confirmados'] >= $detalle_clase['Cupo_Maximo']): ?>
                    <div class="alert alert-warning">
                        <i class="ti ti-alert-triangle me-1"></i>
                        El cupo está lleno. El socio será agregado a la <strong>lista de espera</strong>.
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <?php if (!empty($socios_disponibles)): ?>
                    <button type="submit" class="btn btn-guardar">Agregar</button>
                    <?php endif; ?>
                </div>
            </div>
            </form>
        </div>
    </div>

    <!-- ======= MODAL: REGISTRAR ASISTENCIA ======= -->
    <div class="modal fade" id="modalRegistrarAsistencia" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="clases.php?clase=<?= $clase_id ?>&vista=<?= $vista ?>&mes=<?= $mes ?>&anio=<?= $anio ?>&tab=asistencia">
            <input type="hidden" name="accion" value="registrar_asistencia">
            <input type="hidden" name="id_clase" value="<?= $clase_id ?>">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-clipboard-check me-2"></i> Registrar Asistencia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Socio (con reserva confirmada)</label>
                        <select class="form-select" name="id_socio" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($confirmados_sin_asistencia as $rv): ?>
                            <option value="<?= $rv['ID_Socio'] ?>"><?= htmlspecialchars($rv['socio_nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-guardar">Registrar</button>
                </div>
            </div>
            </form>
        </div>
    </div>

    <!-- ======= MODAL: EDITAR CLASE ======= -->
    <div class="modal fade" id="modalEditarClase" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="clases.php?clase=<?= $clase_id ?>&vista=<?= $vista ?>&mes=<?= $mes ?>&anio=<?= $anio ?>">
            <input type="hidden" name="accion" value="editar_clase">
            <input type="hidden" name="id_clase" value="<?= $clase_id ?>">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-edit me-2"></i> Editar Clase</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Nombre de la Clase</label>
                        <input type="text" class="form-control" name="nombre" required
                               value="<?= htmlspecialchars($detalle_clase['Nombre']) ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">Fecha</label>
                            <input type="date" class="form-control" name="fecha" required
                                   value="<?= date('Y-m-d', strtotime($detalle_clase['Fecha'])) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">Hora</label>
                            <input type="time" class="form-control" name="hora" required
                                   value="<?= date('H:i', strtotime($detalle_clase['Fecha'])) ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Entrenador</label>
                        <select class="form-select" name="id_entrenador" required>
                            <?php foreach ($entrenadores_arr as $ent): ?>
                            <option value="<?= $ent['ID_Entrenador'] ?>"
                                <?= $ent['ID_Entrenador'] == $detalle_clase['ID_Entrenador'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ent['Nombre']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Cupo Máximo</label>
                        <input type="number" class="form-control" name="cupo" min="1" required
                               value="<?= $detalle_clase['Cupo_Maximo'] ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion" rows="2"><?= htmlspecialchars($detalle_clase['Descripcion'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-guardar">Guardar Cambios</button>
                </div>
            </div>
            </form>
        </div>
    </div>

    <?php // ====================================================
    // FIN DETALLE - Resto de vistas siguen debajo
    // ==================================================== 
    ?>

    <?php else: // ====== VISTAS CALENDARIO / LISTA ====== ?>

    <!-- ========================================================
         VISTA: CALENDARIO MENSUAL
    ======================================================== -->
    <?php if ($vista == 'calendario'): ?>
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between">
            <a href="clases.php?vista=calendario&mes=<?= $mes_ant ?>&anio=<?= $anio_ant ?>"
               class="btn btn-modificar btn-sm"><i class="ti ti-chevron-left"></i></a>
            <h3 class="card-title mb-0">
                <i class="ti ti-calendar me-2" style="color:var(--gym-verde,#6fba12)"></i>
                <?= $meses_nombres[$mes] ?> <?= $anio ?>
            </h3>
            <a href="clases.php?vista=calendario&mes=<?= $mes_sig ?>&anio=<?= $anio_sig ?>"
               class="btn btn-modificar btn-sm"><i class="ti ti-chevron-right"></i></a>
        </div>
        <div class="card-body p-2">
            <div class="calendar-grid">
                <!-- Cabecera días -->
                <?php foreach ($dias_semana as $ds): ?>
                <div class="calendar-header-day"><?= $ds ?></div>
                <?php endforeach; ?>

                <!-- Celdas vacías al inicio -->
                <?php for ($i = 0; $i < $primer_dia_sem; $i++): ?>
                <div class="calendar-cell empty"></div>
                <?php endfor; ?>

                <!-- Días del mes -->
                <?php
                $hoy = (int)date('j');
                $mes_hoy = (int)date('n');
                $anio_hoy = (int)date('Y');
                for ($dia = 1; $dia <= $dias_en_mes; $dia++):
                    $es_hoy = ($dia == $hoy && $mes == $mes_hoy && $anio == $anio_hoy);
                ?>
                <div class="calendar-cell <?= $es_hoy ? 'today' : '' ?>">
                    <div class="calendar-day-num"><?= $dia ?></div>
                    <?php if (isset($clases_mes[$dia])): foreach ($clases_mes[$dia] as $cl): ?>
                    <a href="clases.php?clase=<?= $cl['ID_Clase'] ?>&vista=calendario&mes=<?= $mes ?>&anio=<?= $anio ?>"
                       class="clase-pill pill-<?= $cl['Estado'] ?? 'programada' ?>"
                       title="<?= htmlspecialchars($cl['Nombre']) ?> | <?= date('H:i', strtotime($cl['Fecha'])) ?> | <?= $cl['confirmados'] ?>/<?= $cl['Cupo_Maximo'] ?> confirmados">
                        <?= date('H:i', strtotime($cl['Fecha'])) ?> <?= htmlspecialchars(mb_strimwidth($cl['Nombre'], 0, 14, '…')) ?>
                    </a>
                    <?php endforeach; endif; ?>
                </div>
                <?php endfor; ?>

                <!-- Celdas vacías al final -->
                <?php
                $total_celdas = $primer_dia_sem + $dias_en_mes;
                $celdas_finales = (7 - ($total_celdas % 7)) % 7;
                for ($i = 0; $i < $celdas_finales; $i++): ?>
                <div class="calendar-cell empty"></div>
                <?php endfor; ?>
            </div>
        </div>
        <div class="card-footer text-muted d-flex gap-3" style="font-size:.8rem;">
            <span><span class="badge pill-programada me-1">●</span> Programada</span>
            <span><span class="badge pill-completada me-1">●</span> Completada</span>
            <span><span class="badge pill-cancelada me-1">●</span> Cancelada</span>
        </div>
    </div>

    <!-- Listado del mes debajo del calendario -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Clases de <?= $meses_nombres[$mes] ?> <?= $anio ?></h3>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr><th>Clase</th><th>Fecha</th><th>Entrenador</th><th>Cupo</th><th>Estado</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                    <?php
                    $clases_planas = array_merge(...array_values($clases_mes) ?: [[]]);
                    usort($clases_planas, fn($a, $b) => strtotime($a['Fecha']) - strtotime($b['Fecha']));
                    if (empty($clases_planas)):
                    ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No hay clases en este mes.</td></tr>
                    <?php else: foreach ($clases_planas as $cl): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($cl['Nombre']) ?></strong>
                            <?php if ($cl['Descripcion'] ?? ''): ?>
                            <br><small class="text-muted"><?= htmlspecialchars(mb_strimwidth($cl['Descripcion'], 0, 50, '…')) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($cl['Fecha'])) ?></td>
                        <td><?= htmlspecialchars($cl['entrenador_nombre'] ?? '—') ?></td>
                        <td>
                            <span class="<?= $cl['confirmados'] >= $cl['Cupo_Maximo'] ? 'text-danger fw-bold' : '' ?>">
                                <?= $cl['confirmados'] ?>/<?= $cl['Cupo_Maximo'] ?>
                            </span>
                            <?php if ($cl['en_espera'] > 0): ?>
                            <span class="badge badge-espera ms-1"><?= $cl['en_espera'] ?> espera</span>
                            <?php endif; ?>
                        </td>
                        <td><?= estadoBadgeClase($cl['Estado'] ?? 'programada') ?></td>
                        <td>
                            <a href="clases.php?clase=<?= $cl['ID_Clase'] ?>&vista=calendario&mes=<?= $mes ?>&anio=<?= $anio ?>"
                               class="btn btn-sm btn-modificar">
                                <i class="ti ti-eye me-1"></i> Ver
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; // fin vista calendario ?>

    <!-- ========================================================
         VISTA: LISTA DE TODAS LAS CLASES
    ======================================================== -->
    <?php if ($vista == 'lista'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Todas las Clases</h3>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr><th>Clase</th><th>Fecha</th><th>Entrenador</th><th>Cupo</th><th>Estado</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($todas_clases)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No hay clases registradas.</td></tr>
                    <?php else: foreach ($todas_clases as $cl): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($cl['Nombre']) ?></strong>
                            <?php if ($cl['Descripcion'] ?? ''): ?>
                            <br><small class="text-muted"><?= htmlspecialchars(mb_strimwidth($cl['Descripcion'], 0, 50, '…')) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($cl['Fecha'])) ?></td>
                        <td><?= htmlspecialchars($cl['entrenador_nombre'] ?? '—') ?></td>
                        <td>
                            <?= $cl['confirmados'] ?>/<?= $cl['Cupo_Maximo'] ?>
                            <?php if ($cl['en_espera'] > 0): ?>
                            <span class="badge badge-espera ms-1"><?= $cl['en_espera'] ?> espera</span>
                            <?php endif; ?>
                        </td>
                        <td><?= estadoBadgeClase($cl['Estado'] ?? 'programada') ?></td>
                        <td>
                            <a href="clases.php?clase=<?= $cl['ID_Clase'] ?>&vista=lista"
                               class="btn btn-sm btn-modificar">
                                <i class="ti ti-eye me-1"></i> Ver
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; // fin vista lista ?>

    <?php endif; // fin else (no detalle) ?>

</div><!-- /container -->
</div><!-- /page-wrapper -->
</div><!-- /page -->

<!-- ========================================================
     MODAL: NUEVA CLASE (siempre disponible)
======================================================== -->
<div class="modal fade" id="modalNuevaClase" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="clases.php?vista=<?= $vista ?>&mes=<?= $mes ?>&anio=<?= $anio ?>">
        <input type="hidden" name="accion" value="nueva_clase">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-calendar-plus me-2"></i> Nueva Clase Grupal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label required">Nombre de la Clase</label>
                    <input type="text" class="form-control" name="nombre" placeholder="Ej: Spinning, Zumba, Yoga..." required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Fecha</label>
                        <input type="date" class="form-control" name="fecha" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Hora de inicio</label>
                        <input type="time" class="form-control" name="hora" value="08:00" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label required">Entrenador</label>
                    <select class="form-select" name="id_entrenador" required>
                        <option value="">Seleccionar entrenador...</option>
                        <?php foreach ($entrenadores_arr as $ent): ?>
                        <option value="<?= $ent['ID_Entrenador'] ?>"><?= htmlspecialchars($ent['Nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label required">Cupo Máximo</label>
                    <input type="number" class="form-control" name="cupo" min="1" max="200" value="20" required>
                    <small class="text-muted">Cuando el cupo esté lleno, los siguientes socios irán a lista de espera.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Descripción (opcional)</label>
                    <textarea class="form-control" name="descripcion" rows="2" placeholder="Detalles, requisitos, nivel..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-guardar">
                    <i class="ti ti-device-floppy me-1"></i> Crear Clase
                </button>
            </div>
        </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
<script>
    // Auto-cerrar alertas después de 6 segundos
    setTimeout(function () {
        document.querySelectorAll('.alert-dismissible').forEach(function (el) {
            el.style.transition = 'opacity 1s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 1000);
        });
    }, 6000);
</script>
</body>
</html>
