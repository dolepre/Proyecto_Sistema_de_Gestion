<?php
// Asegurarnos de incluir la conexión y el controlador de gestión
session_start();
require_once "../db/conexion.php"; // Ajusta la ruta si es necesario

$estado_transaccion = ""; // Puede ser 'exito', 'error' o ''

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
            // 1. Verificar si la cédula ya existe en la base de datos
            $sql_check = "SELECT nombres, apellidos FROM entidad_gestion WHERE cedula_identidad = :cedula LIMIT 1";
            $stmt_check = $conexion->prepare($sql_check);
            $stmt_check->execute(['cedula' => $cedula]);
            $persona_existente = $stmt_check->fetch(PDO::FETCH_ASSOC);

            $permitir_registro = true;

            if ($persona_existente) {
                // 2. Si la cédula ya existe, validamos que los nombres y apellidos coincidan
                if (strcasecmp(trim($persona_existente['nombres']), $nombres) !== 0 || 
                    strcasecmp(trim($persona_existente['apellidos']), $apellidos) !== 0) {
                    $error = "Error: La cédula <b>$cedula</b> ya está registrada a nombre de otra persona (" . $persona_existente['nombres'] . " " . $persona_existente['apellidos'] . ").";
                    $permitir_registro = false;
                }
            }

            // 3. Si pasa la validación (es la misma persona o es una cédula nueva), insertamos el cobro
            if ($permitir_registro) {
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
                $estado_transaccion = "exito";
            } else {
                $estado_transaccion = "error";
            }
        } catch (PDOException $e) {
            $error = "Error al registrar el cobro: " . $e->getMessage();
            $estado_transaccion = "error";
        }
    } else {
        $error = "Por favor, complete los campos obligatorios.";
        $estado_transaccion = "error";
    }
}

// Obtener todos los registros para la tabla
$sql_sel = "SELECT * FROM entidad_gestion ORDER BY fecha_registro DESC";
$stmt_sel = $conexion->query($sql_sel);
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

        .topbar-right img {
            max-height: 45px;
            width: auto;
            display: block;
        }

        .container {
            max-width: 1100px;
            margin: 25px auto;
            padding: 0 15px;
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

        .card {
            background-color: var(--color-card-bg);
            border: 1px solid var(--color-border);
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

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

        /* --- Estilos y Animación del Botón --- */
        .btn-primary {
            background-color: var(--color-title);
            color: #ffffff;
            border: 1px solid #801319;
            padding: 8px 18px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            border-radius: 2px;
            transition: all 0.4s ease-in-out; /* Animación suave */
        }

        .btn-primary:hover {
            background-color: #94151d;
        }

        /* Clases dinámicas para el éxito o error con animación de colores invertidos */
        .btn-primary.anim-exito {
            background-color: #ffffff !important;
            color: #155724 !important;
            border-color: #155724 !important;
            transform: scale(1.05);
        }

        .btn-primary.anim-error {
            background-color: #ffffff !important;
            color: #721c24 !important;
            border-color: #721c24 !important;
            transform: scale(1.05);
        }

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

        .btnAdmin {
            background-color: #721c24;
            color: #ffffff;
            border: 1px solid #721c24;
            padding: 8px 18px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            border-radius: 2px;
            text-decoration: none;
            display: inline-block;
            margin: 15px;
        }

        .btnAdmin:hover {
            background-color: #ffffff;
            color: #721c24;
            border-color: #721c24;
        }

        .btnCerrar {
            background-color: #B31B24;
            color: #ffffff;
            border: 1px solid #801319;
            padding: 6px 12px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            border-radius: 2px;
            text-decoration: none;
        }

        .btnCerrar:hover {
            background-color: #ffeef0;
            color: #B31B24;
            border-color: #B31B24;
        }

        .filter-link {
            color: var(--color-title);
            font-weight: bold;
            padding: 5px 10px;
            background-color: #fff7f8;
            text-decoration: none;
            border: #721c24 1px solid;
            margin-right: 10px;
            font-size: 13px;
            border-radius: 2px;
            display: inline-block;
            cursor: pointer;
        }

        .filter-link.active, .filter-link:hover {
            background-color: #721c24;
            color: #ffffff;
        }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-left">
        <a href="../index.php?page=login" class="btnCerrar">Cerrar Sesión</a>
    </div>
    <div class="topbar-right">
        <img src="../assets/img/logo-ascardio.png" alt="ASCARDIO" class="system-logo" style="height: 40px; width: auto; background-color: #ffefef; border-radius: 4px; padding: 2px;">
    </div>
</div>

<div class="container mt-4">
    <h2>Módulo de Gestión de Cobros - ASCARDIO</h2>
    <p class="text-muted">Control de cuotas mensuales de afiliación, servicios médicos y ambulancia. <strong>Bienvenido, <?php echo $_SESSION['username']; ?>. ID: <?php echo $_SESSION['id_usuario']; ?></strong></p>

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
            <div class="form-grid">
                <div class="form-group">
                    <label>Nombres del Paciente/Afiliado:</label>
                    <input type="text" name="nombres" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Apellidos:</label>
                    <input type="text" name="apellidos" class="form-control">
                </div>
                <div class="form-group">
                    <label>Cédula:</label>
                    <input type="text" name="cedula_identidad" class="form-control" maxlength="8" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                </div>
                <div class="form-group">
                    <label>Monto ($):</label>
                    <input type="number" step="0.01" name="monto" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Método de Pago:</label>
                    <select name="metodo_pago" class="form-control" required>
                        <option value="Efectivo">Efectivo</option>
                        <option value="Transferencia">Transferencia</option>
                        <option value="Pago Móvil">Pago Móvil</option>
                    </select>
                </div>
            </div>
            <div style="margin-top: 15px;">
                <button type="submit" name="registrar_cobro" id="btnRegistrar" class="btn-primary">Registrar Cobro</button>
            </div>
        </form>
    </div>

    <!-- Filtros Dinámicos con JS -->
    <div class="filters mb-3">
        <strong>Filtrar por método: </strong>
        <button class="filter-link active" data-filtro="todos">Todos</button>
        <button class="filter-link" data-filtro="Efectivo">Efectivo</button>
        <button class="filter-link" data-filtro="Transferencia">Transferencias</button>
        <button class="filter-link" data-filtro="Pago Móvil">Pago Móvil</button>
    </div>

    <!-- Tabla de Registros -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="tablaRegistros">
            <thead>
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
                        <tr data-metodo="<?php echo htmlspecialchars($row['datos_contacto']); ?>">
                            <td><?php echo $row['id_registro']; ?></td>
                            <td><?php echo htmlspecialchars($row['nombres'] . " " . $row['apellidos']); ?></td>
                            <td><?php echo htmlspecialchars($row['cedula_identidad']); ?></td>
                            <td><?php echo htmlspecialchars($row['datos_contacto']); ?></td>
                            <td><?php echo $row['fecha_registro']; ?></td>
                            <?php if (isset($_SESSION['id_rol']) && $_SESSION['id_rol'] == 1): ?>
                                <td><?php echo $row['id_usuario_responsable']; ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr id="filaVacia">
                        <td colspan="6" class="text-center">No hay registros de cobros encontrados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
if (isset($_SESSION['id_rol']) && $_SESSION['id_rol'] == 1) {
    echo "<div style='max-width: 1100px; margin: 0 auto;'><a href='../index.php?page=admin' class='btnAdmin'>Ir al Panel de Administración</a></div>";
}
?>

<!-- Script de JavaScript para Filtrar la Tabla y Animar el Botón -->
<script>
    document.addEventListener("DOMContentLoaded", () => {
        // --- 1. Animación del botón según el estado de PHP ---
        const estadoPHP = "<?php echo $estado_transaccion; ?>";
        const btnRegistrar = document.getElementById("btnRegistrar");

        if (estadoPHP === "exito") {
            btnRegistrar.classList.add("anim-exito");
            btnRegistrar.innerHTML = "✔ ¡Registrado!";
            
            // Regresar a la normalidad después de 2.5 segundos
            setTimeout(() => {
                btnRegistrar.classList.remove("anim-exito");
                btnRegistrar.innerHTML = "Registrar Cobro";
            }, 2500);
        } else if (estadoPHP === "error") {
            btnRegistrar.classList.add("anim-error");
            btnRegistrar.innerHTML = "✕ Error en registro";
            
            // Regresar a la normalidad después de 2.5 segundos
            setTimeout(() => {
                btnRegistrar.classList.remove("anim-error");
                btnRegistrar.innerHTML = "Registrar Cobro";
            }, 2500);
        }

        // --- 2. Lógica para Filtrar la Tabla en Tiempo Real ---
        const botonesFiltro = document.querySelectorAll(".filter-link");
        const filas = document.querySelectorAll("#tablaRegistros tbody tr[data-metodo]");

        botonesFiltro.forEach(boton => {
            boton.addEventListener("click", (e) => {
                e.preventDefault();

                botonesFiltro.forEach(b => b.classList.remove("active"));
                boton.classList.add("active");

                const filtro = boton.getAttribute("data-filtro");

                filas.forEach(fila => {
                    const textoMetodo = fila.getAttribute("data-metodo");

                    if (filtro === "todos" || textoMetodo.includes(filtro)) {
                        fila.style.display = ""; 
                    } else {
                        fila.style.display = "none"; 
                    }
                });
            });
        });
    });
</script>

</body>
</html>