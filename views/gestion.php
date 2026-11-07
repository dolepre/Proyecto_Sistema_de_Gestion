<?php
// Asegurarnos de incluir la conexión y el controlador de gestión
session_start();
require_once "../db/conexion.php"; // Ajusta la ruta si es necesario

// Procesar el formulario si se envía por POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registrar_cobro'])) {
    $id_responsable = $_SESSION['id_usuario'] ?? 1; // ID del cajero logueado
    $nombres = trim($_POST['nombres']);
    $apellidos = trim($_POST['apellidos']);
    $cedula = trim($_POST['cedula_identidad']);
    $monto = trim($_POST['monto']);
    $metodo_pago = $_POST['metodo_pago']; // Efectivo, Transferencia o Pago Móvil

    if (!empty($nombres) && !empty($cedula) && !empty($monto)) {
        try {
            // Guardamos el método de pago en 'datos_contacto'
            $sql = "INSERT INTO entidad_gestion (id_usuario_responsable, nombres, apellidos, cedula_identidad, datos_contacto, fecha_registro) 
                    VALUES (:resp, :nombres, :apellidos, :cedula, :pago, NOW())";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([
                'resp' => $id_responsable,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'cedula' => $cedula,
                'pago' => "$metodo_pago - Monto: $$monto"
            ]);
            $mensaje = "¡Cobro registrado con éxito!";
        } catch (PDOException $e) {
            $error = "Error al registrar: " . $e->getMessage();
        }
    } else {
        $error = "Por favor, complete los campos obligatorios.";
    }
}

// Obtener los registros de la base de datos para mostrarlos en la tabla
$filtro = $_GET['filtro'] ?? '';
if ($filtro != '') {
    $sql_sel = "SELECT * FROM entidad_gestion WHERE datos_contacto LIKE :filtro ORDER BY fecha_registro DESC";
    $stmt_sel = $conexion->prepare($sql_sel);
    $stmt_sel->execute(['filtro' => "%$filtro%"]);
} else {
    $sql_sel = "SELECT * FROM entidad_gestion ORDER BY fecha_registro DESC";
    $stmt_sel = $conexion->query($sql_sel);
}
$registros = $stmt_sel->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Cobros - ASCARDIO</title>
    <style>
        /* --- Variables y Reset --- */
        :root {
            --color-bg: #fffaf2;
            --color-title: #B31B24;
            --color-text: #2b2b2b;
            --color-border: #d4ceb8;
            --color-topbar: #B31B24;
            --color-card-bg: #ffffff;
            --color-alert-bg: #f8d7da;
            --color-alert-border: #f5c6cb;
            --color-alert-text: #721c24;
            --color-success-bg: #d4edda;
            --color-success-border: #c3e6cb;
            --color-success-text: #155724;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--color-bg);
            color: var(--color-text);
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            line-height: 1.5;
        }

        /* --- Barra Superior Roja --- */
        .topbar {
            background-color: var(--color-topbar);
            color: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 25px;
            width: 100%;
            min-height: 60px;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Logo de la empresa */
        .topbar-right img {
            max-height: 45px;
            width: auto;
            display: block;
        }

        /* Botón cerrar sesión */
        .topbar-btn {
            color: #ffffff;
            text-decoration: none;
            font-size: 13px;
            background-color: rgba(0, 0, 0, 0.15);
            padding: 6px 12px;
            border-radius: 2px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            transition: background-color 0.2s;
        }

        .topbar-btn:hover {
            background-color: rgba(0, 0, 0, 0.3);
            border-color: #ffffff;
        }

        /* --- Estructura Contenedora --- */
        .container {
            max-width: 1100px;
            margin: 25px auto;
            padding: 0 15px;
        }

        .page-header {
            margin-bottom: 20px;
            border-bottom: 2px solid var(--color-border);
            padding-bottom: 10px;
        }

        h2, h4 {
            color: var(--color-title);
            font-family: Georgia, 'Times New Roman', serif;
            font-weight: bold;
        }

        h2 {
            font-size: 24px;
            margin-bottom: 4px;
        }

        h4 {
            font-size: 18px;
            margin-bottom: 15px;
            border-bottom: 1px solid var(--color-border);
            padding-bottom: 5px;
        }

        .text-muted {
            color: #555555;
            font-size: 13px;
        }

        /* --- Alertas --- */
        .alert {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 13px;
        }

        .alert-danger {
            background-color: var(--color-alert-bg);
            border: 1px solid var(--color-alert-border);
            color: var(--color-alert-text);
        }

        .alert-success {
            background-color: var(--color-success-bg);
            border: 1px solid var(--color-success-border);
            color: var(--color-success-text);
        }

        /* --- Tarjetas --- */
        .card {
            background-color: var(--color-card-bg);
            border: 1px solid var(--color-border);
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        /* --- Formularios --- */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 5px;
            color: var(--color-text);
        }

        .form-control {
            padding: 7px 10px;
            border: 1px solid #b5b0a1;
            background-color: #fcfbf9;
            font-size: 14px;
            border-radius: 2px;
            width: 100%;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--color-title);
            background-color: #ffffff;
        }

        .form-actions {
            grid-column: 1 / -1;
            margin-top: 10px;
        }

        .btn-primary {
            background-color: var(--color-title);
            color: #ffffff;
            border: 1px solid #801319;
            padding: 8px 18px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            border-radius: 2px;
        }

        .btn-primary:hover {
            background-color: #94151d;
        }

        /* --- Filtros --- */
        .filter-container {
            margin-bottom: 15px;
            font-size: 13px;
        }

        .filter-container strong {
            margin-right: 8px;
        }

        .filter-link {
            color: var(--color-title);
            text-decoration: none;
            margin-right: 8px;
            padding: 3px 8px;
            border: 1px solid var(--color-border);
            background-color: #fff;
            border-radius: 2px;
            display: inline-block;
        }

        .filter-link:hover {
            background-color: var(--color-title);
            color: #ffffff;
            border-color: var(--color-title);
        }

        /* --- Tablas --- */
        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background-color: #ffffff;
        }

        .table th,
        .table td {
            border: 1px solid var(--color-border);
            padding: 8px 12px;
            text-align: left;
        }

        .table th {
            background-color: #f2ebd9;
            color: var(--color-text);
            font-size: 13px;
        }

        .table tbody tr:nth-child(even) {
            background-color: #faf6ee;
        }

        .table tbody tr:hover {
            background-color: #f1e9d8;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>

<div class="topbar">
    <div class="topbar-left"></div>
    <a href="../index.php?page=login" class="btn btn-danger mt-3">Cerrar Sesión</a>
</div>
<div class="topbar-right">
    <img src="logo-ascardio.png" alt="ASCARDIO" class="system-logo" style="height: 40px; width: auto;">
</div>
</div>

<body>
<div class="container mt-4">
    <h2>Módulo de Gestión de Cobros - ASCARDIO</h2>
    <p class="text-muted">Control de cuotas mensuales de afiliación, servicios médicos y ambulancia[cite: 7].</p>

    <?php if (isset($mensaje)): ?>
        <div class="alert alert-success"><?php echo $mensaje; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Formulario de Registro -->
    <div class="card p-4 mb-4 shadow-sm">
        <h4>Registrar Nueva Transacción</h4>
        <form action="#" method="POST">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label>Nombres del Paciente/Afiliado:</label>
                    <input type="text" name="nombres" class="form-control" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label>Apellidos:</label>
                    <input type="text" name="apellidos" class="form-control">
                </div>
                <div class="col-md-2 mb-3">
                    <label>Cédula:</label>
                    <input type="text" name="cedula_identidad" class="form-control" maxlength="8" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label>Monto ($):</label>
                    <input type="number" step="0.01" name="monto" class="form-control" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label>Método de Pago:</label>
                    <select name="metodo_pago" class="form-control" required>
                        <option value="Efectivo">Efectivo</option>
                        <option value="Transferencia">Transferencia</option>
                        <option value="Pago Móvil">Pago Móvil</option>
                    </select>
                </div>
            </div>
            <button type="submit" name="registrar_cobro" class="btn btn-primary">Registrar Cobro</button>
        </form>
    </div>

    <!-- Filtros Dinámicos (Requerimiento del informe) -->
    <div class="mb-3">
        <strong>Filtrar por método: </strong>
        <a href="#" class="btn btn-sm btn-secondary">Todos</a>
        <a href="#" class="btn btn-sm btn-success">Efectivo</a>
        <a href="#" class="btn btn-sm btn-info">Transferencias</a>
        <a href="#" class="btn btn-sm btn-warning">Pago Móvil</a>
    </div>

    <!-- Tabla de Registros -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nombres y Apellidos</th>
                    <th>Cédula</th>
                    <th>Detalles / Método de Pago</th>
                    <th>Fecha de Registro</th>
                    <?php if (isset($_SESSION['id_rol']) && $_SESSION['id_rol'] == 1): ?>
                        <th>Responsable (ID)</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (count($registros) > 0): ?>
                    <?php foreach ($registros as $row): ?>
                        <tr>
                            <td><?php echo $row['id_registro']; ?></td>
                            <td><?php echo $row['nombres'] . " " . $row['apellidos']; ?></td>
                            <td><?php echo $row['cedula_identidad']; ?></td>
                            <td><?php echo $row['datos_contacto']; ?></td>
                            <td><?php echo $row['fecha_registro']; ?></td>

                            <td>
                                <?php
                                if (isset($_SESSION['id_rol']) && $_SESSION['id_rol'] == 1) {
                                    echo $row['id_usuario_responsable'];
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No hay registros de cobros encontrados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php

if (isset($_SESSION['id_rol']) && $_SESSION['id_rol'] == 1) {
    echo "<a href='../index.php?page=admin' class='btn btn-secondary mt-3'>Ir al Panel de Administración</a>";
}

?>