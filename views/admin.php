<?php
// ===== AJUSTAR según tu login.php (busca "$_SESSION[" en ese archivo) =====
$K_ID  = 'id_usuario';           // clave de sesión con el id del usuario
$K_ROL = 'id_rol';               // clave de sesión con el rol (1 = Administrador)
// Carpeta base del proyecto: funciona entres por index.php o directo desde views/
$BASE  = preg_replace('#/views$#', '', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/')) . '/';
$URL   = $BASE . 'index.php?page=admin';        // ruta de esta vista en tu pages.php
$URL_VOLVER = $BASE . 'index.php?page=gestion'; // a dónde lleva el botón Volver
// ==========================================================================

if (session_status() === PHP_SESSION_NONE) session_start();
if ((int)($_SESSION[$K_ROL] ?? 0) !== 1) {
    header('Location: ' . $BASE . 'index.php');
    exit;
}
$yo = (int)($_SESSION[$K_ID] ?? 0);
$_SESSION['csrf'] = $_SESSION['csrf'] ?? bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

// Si tu db/conexion.php ya entrega un PDO, úsalo aquí en lugar de esta conexión.
$pdo = new PDO('mysql:host=127.0.0.1;dbname=proyecto_sistema_gestion;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
if (!function_exists('h')) {
    function h($s)
    {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}
$METODOS = ['Pago Móvil', 'Efectivo', 'Transferencia'];

// ---------- Acciones (POST) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Solicitud no válida');
    }
    $a = $_POST['accion'] ?? '';
    try {
        if ($a === 'crear_usuario') {
            $u = trim($_POST['username'] ?? '');
            $p = $_POST['password'] ?? '';
            $r = (int)($_POST['id_rol'] ?? 2);
            if (strlen($u) < 3 || strlen($u) > 50 || strlen($p) < 6 || !in_array($r, [1, 2], true))
                throw new Exception('Usuario de 3 a 50 caracteres y contraseña de mínimo 6.');
            $pdo->prepare('INSERT INTO usuarios (id_rol, username, password) VALUES (?,?,?)')
                ->execute([$r, $u, password_hash($p, PASSWORD_DEFAULT)]);
            $_SESSION['flash'] = 'Usuario creado.';
        } elseif ($a === 'editar_usuario') {
            $id = (int)($_POST['id_usuario'] ?? 0);
            if ($id === $yo) throw new Exception('No puedes cambiar tu propio rol ni estado.');
            $r = (int)($_POST['id_rol'] ?? 2);
            $e = ($_POST['estado'] ?? '1') === '0' ? 0 : 1;
            if (!in_array($r, [1, 2], true)) throw new Exception('Rol no válido.');
            $pdo->prepare('UPDATE usuarios SET id_rol=?, estado=? WHERE id_usuario=?')->execute([$r, $e, $id]);
            $np = $_POST['password'] ?? '';
            if ($np !== '') {
                if (strlen($np) < 6) throw new Exception('La nueva contraseña necesita mínimo 6 caracteres.');
                $pdo->prepare('UPDATE usuarios SET password=? WHERE id_usuario=?')->execute([password_hash($np, PASSWORD_DEFAULT), $id]);
            }
            $_SESSION['flash'] = 'Usuario actualizado.';
        } elseif ($a === 'editar_cobro') {
            $n = trim($_POST['nombres'] ?? '');
            $ap = trim($_POST['apellidos'] ?? '');
            $ci = trim($_POST['cedula'] ?? '');
            $m = $_POST['metodo'] ?? '';
            $mo = (float)str_replace(',', '.', $_POST['monto'] ?? '0');
            if ($n === '' || !preg_match('/^\d{6,10}$/', $ci) || !in_array($m, $METODOS, true) || $mo <= 0)
                throw new Exception('Revisa nombre, cédula (6 a 10 dígitos), método y monto mayor a 0.');
            $pdo->prepare('UPDATE entidad_gestion SET nombres=?, apellidos=?, cedula_identidad=?, datos_contacto=? WHERE id_registro=?')
                ->execute([$n, $ap, $ci, $m . ' - Monto: $' . number_format($mo, 2, '.', ''), (int)$_POST['id_registro']]);
            $_SESSION['flash'] = 'Cobro actualizado.';
        } elseif ($a === 'eliminar_usuario') {
            $id = (int)($_POST['id_usuario'] ?? 0);
            if ($id === $yo) throw new Exception('No puedes eliminar tu propio usuario.');
            $pdo->prepare('DELETE FROM usuarios WHERE id_usuario=?')->execute([$id]);
            $_SESSION['flash'] = 'Usuario eliminado.';
        } elseif ($a === 'eliminar_cobro') {
            $pdo->prepare('DELETE FROM entidad_gestion WHERE id_registro=?')->execute([(int)($_POST['id_registro'] ?? 0)]);
            $_SESSION['flash'] = 'Cobro eliminado.';
        }
    } catch (PDOException $ex) {
        $_SESSION['flash'] = $ex->getCode() == '23000' ? 'Error: ese nombre de usuario ya existe.' : 'Error de base de datos.';
    } catch (Exception $ex) {
        $_SESSION['flash'] = 'Error: ' . $ex->getMessage();
    }
    header('Location: ' . $URL);
    exit;
}
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

// ---------- Datos ----------
$usuarios = $pdo->query('SELECT u.id_usuario, u.username, u.id_rol, u.estado, r.nombre_rol,
    (SELECT COUNT(*) FROM entidad_gestion e WHERE e.id_usuario_responsable = u.id_usuario) AS n_cobros
    FROM usuarios u LEFT JOIN roles r ON r.id_rol = u.id_rol ORDER BY u.id_usuario')->fetchAll();

$f = ['desde' => $_GET['desde'] ?? '', 'hasta' => $_GET['hasta'] ?? '', 'cajero' => (int)($_GET['cajero'] ?? 0), 'metodo' => $_GET['metodo'] ?? ''];
$w = [];
$par = [];
if ($f['desde'] !== '') {
    $w[] = 'DATE(e.fecha_registro) >= ?';
    $par[] = $f['desde'];
}
if ($f['hasta'] !== '') {
    $w[] = 'DATE(e.fecha_registro) <= ?';
    $par[] = $f['hasta'];
}
if ($f['cajero'] > 0) {
    $w[] = 'e.id_usuario_responsable = ?';
    $par[] = $f['cajero'];
}
if ($f['metodo'] !== '') {
    $w[] = "TRIM(SUBSTRING_INDEX(e.datos_contacto, ' - ', 1)) = ?";
    $par[] = $f['metodo'];
}
$st = $pdo->prepare("SELECT e.id_registro, e.nombres, e.apellidos, e.cedula_identidad, e.fecha_registro, u.username,
        TRIM(SUBSTRING_INDEX(e.datos_contacto, ' - ', 1)) AS metodo,
        CAST(SUBSTRING_INDEX(e.datos_contacto, '$', -1) AS DECIMAL(10,2)) AS monto
    FROM entidad_gestion e LEFT JOIN usuarios u ON u.id_usuario = e.id_usuario_responsable"
    . ($w ? ' WHERE ' . implode(' AND ', $w) : '') . ' ORDER BY e.fecha_registro DESC');
$st->execute($par);
$cobros = $st->fetchAll();
$total = array_sum(array_column($cobros, 'monto'));
$porMetodo = [];
foreach ($cobros as $c) $porMetodo[$c['metodo']] = ($porMetodo[$c['metodo']] ?? 0) + $c['monto'];
$activos = count(array_filter($usuarios, fn($u) => $u['estado'] == 1));
$qs = http_build_query(array_filter($f));

// ---------- Exportar a Excel (respeta los filtros) ----------
if (($_GET['export'] ?? '') === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="cobros_' . date('Ymd') . '.xls"');
    echo "\xEF\xBB\xBF<table border='1'><tr><th>ID</th><th>Cliente</th><th>Cédula</th><th>Cajero</th><th>Método</th><th>Monto (USD)</th><th>Fecha</th></tr>";
    foreach ($cobros as $c)
        echo '<tr><td>' . $c['id_registro'] . '</td><td>' . h($c['nombres'] . ' ' . $c['apellidos']) . '</td><td>' . h($c['cedula_identidad'])
            . '</td><td>' . h($c['username']) . '</td><td>' . h($c['metodo']) . '</td><td>' . $c['monto'] . '</td><td>' . h($c['fecha_registro']) . '</td></tr>';
    echo '<tr><td colspan="5"><b>Total</b></td><td><b>' . number_format($total, 2, '.', '') . '</b></td><td></td></tr></table>';
    exit;
}

$ed = null;
if (isset($_GET['editar'])) {
    foreach ($cobros as $c) if ($c['id_registro'] == (int)$_GET['editar']) $ed = $c;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel de administración</title>
    <link rel="stylesheet" href="<?= h($BASE) ?>assets/admin.css">
</head>

<body>
    <header class="barra">
        <a class="btn-borde" href="<?= h($URL_VOLVER) ?>">Volver</a>
    </header>

    <main>
        <h1 class="titulo">Panel de Administración - ASCARDIO</h1>
        <p class="sub">Control de usuarios, cobros y reportes del sistema.</p>
        <p class="atajos">Ir a: <a class="chip" href="#usuarios">Usuarios</a> <a class="chip" href="#cobros">Cobros</a></p>
        <?php if ($flash): ?>
            <p class="aviso <?= str_starts_with($flash, 'Error') ? 'error' : 'ok' ?>" role="status"><?= h($flash) ?></p>
        <?php endif; ?>

        <section class="resumen" aria-label="Resumen">
            <div><span>Total cobrado</span><strong>$<?= number_format($total, 2) ?></strong></div>
            <div><span>Cobros</span><strong><?= count($cobros) ?></strong></div>
            <div><span>Usuarios activos</span><strong><?= $activos ?></strong></div>
            <ul>
                <?php foreach ($porMetodo as $m => $t): ?><li><?= h($m) ?>: <b>$<?= number_format($t, 2) ?></b></li><?php endforeach; ?>
            </ul>
        </section>

        <section id="usuarios">
            <h2>Usuarios</h2>
            <form method="post" class="fila">
                <input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="accion" value="crear_usuario">
                <input name="username" placeholder="Usuario" required minlength="3" maxlength="50">
                <input name="password" type="password" placeholder="Contraseña (mín. 6)" required minlength="6">
                <select name="id_rol">
                    <option value="2">Cajero</option>
                    <option value="1">Administrador</option>
                </select>
                <button>Crear usuario</button>
            </form>
            <div class="tabla">
                <table>
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Nueva contraseña</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): $yoMismo = $u['id_usuario'] == $yo;
                            $fid = 'u' . $u['id_usuario']; ?>
                            <tr>
                                <td><?= h($u['username']) ?><?= $yoMismo ? ' (tú)' : '' ?></td>
                                <td><select form="<?= $fid ?>" name="id_rol" <?= $yoMismo ? 'disabled' : '' ?>>
                                        <option value="1" <?= $u['id_rol'] == 1 ? 'selected' : '' ?>>Administrador</option>
                                        <option value="2" <?= $u['id_rol'] == 2 ? 'selected' : '' ?>>Cajero</option>
                                    </select></td>
                                <td><select form="<?= $fid ?>" name="estado" <?= $yoMismo ? 'disabled' : '' ?>>
                                        <option value="1" <?= $u['estado'] == 1 ? 'selected' : '' ?>>Activo</option>
                                        <option value="0" <?= $u['estado'] == 0 ? 'selected' : '' ?>>Inactivo</option>
                                    </select></td>
                                <td><input form="<?= $fid ?>" type="password" name="password" placeholder="Dejar vacío para no cambiar" minlength="6" <?= $yoMismo ? 'disabled' : '' ?>></td>
                                <td class="acciones">
                                    <form id="<?= $fid ?>" method="post">
                                        <input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="accion" value="editar_usuario">
                                        <input type="hidden" name="id_usuario" value="<?= (int)$u['id_usuario'] ?>">
                                        <?php if (!$yoMismo): ?><button>Guardar</button><?php endif; ?>
                                    </form>
                                    <?php if (!$yoMismo): ?><form method="post" data-confirm="<?= h('¿Eliminar al usuario ' . $u['username'] . '?' . ($u['n_cobros'] ? ' También se borrarán sus ' . $u['n_cobros'] . ' cobros.' : '')) ?>">
                                            <input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="accion" value="eliminar_usuario">
                                            <input type="hidden" name="id_usuario" value="<?= (int)$u['id_usuario'] ?>"><button class="peligro">Eliminar</button>
                                        </form><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="cobros">
            <h2>Cobros de todos los cajeros</h2>
            <form method="get" class="fila">
                <input type="hidden" name="page" value="admin">
                <label>Desde <input type="date" name="desde" value="<?= h($f['desde']) ?>"></label>
                <label>Hasta <input type="date" name="hasta" value="<?= h($f['hasta']) ?>"></label>
                <select name="cajero">
                    <option value="0">Todos los cajeros</option>
                    <?php foreach ($usuarios as $u): ?><option value="<?= (int)$u['id_usuario'] ?>" <?= $f['cajero'] == $u['id_usuario'] ? 'selected' : '' ?>><?= h($u['username']) ?></option><?php endforeach; ?>
                </select>
                <select name="metodo">
                    <option value="">Todos los métodos</option>
                    <?php foreach ($METODOS as $m): ?><option <?= $f['metodo'] === $m ? 'selected' : '' ?>><?= h($m) ?></option><?php endforeach; ?>
                </select>
                <button>Filtrar</button>
                <a class="boton" href="<?= h($URL . '&export=excel' . ($qs ? '&' . $qs : '')) ?>">Exportar a Excel</a>
            </form>

            <?php if ($ed): ?>
                <form method="post" class="fila edicion">
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="accion" value="editar_cobro">
                    <input type="hidden" name="id_registro" value="<?= (int)$ed['id_registro'] ?>">
                    <input name="nombres" value="<?= h($ed['nombres']) ?>" placeholder="Nombres" required>
                    <input name="apellidos" value="<?= h($ed['apellidos']) ?>" placeholder="Apellidos">
                    <input name="cedula" value="<?= h($ed['cedula_identidad']) ?>" placeholder="Cédula" required pattern="\d{6,10}" title="6 a 10 dígitos">
                    <select name="metodo"><?php foreach ($METODOS as $m): ?><option <?= $ed['metodo'] === $m ? 'selected' : '' ?>><?= h($m) ?></option><?php endforeach; ?></select>
                    <input name="monto" type="number" step="0.01" min="0.01" value="<?= h($ed['monto']) ?>" required>
                    <button>Guardar cambios</button> <a href="<?= h($URL) ?>#cobros">Cancelar</a>
                </form>
            <?php endif; ?>

            <input id="buscar" type="search" placeholder="Buscar en la tabla por nombre, cédula o cajero">
            <div class="tabla">
                <table id="tabla-cobros">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Cédula</th>
                            <th>Cajero</th>
                            <th>Método</th>
                            <th>Monto</th>
                            <th>Fecha</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cobros as $c): ?>
                            <tr>
                                <td><?= h($c['nombres'] . ' ' . $c['apellidos']) ?></td>
                                <td><?= h($c['cedula_identidad']) ?></td>
                                <td><?= h($c['username']) ?></td>
                                <td><?= h($c['metodo']) ?></td>
                                <td>$<?= number_format($c['monto'], 2) ?></td>
                                <td><?= h($c['fecha_registro']) ?></td>
                                <td class="acciones">
                                    <a href="<?= h($URL . '&editar=' . $c['id_registro'] . ($qs ? '&' . $qs : '')) ?>#cobros">Editar</a>
                                    <form method="post" data-confirm="¿Eliminar este cobro?">
                                        <input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="accion" value="eliminar_cobro">
                                        <input type="hidden" name="id_registro" value="<?= (int)$c['id_registro'] ?>"><button class="peligro">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$cobros): ?><tr>
                                <td colspan="7">No hay cobros con esos filtros.</td>
                            </tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        document.querySelectorAll('form[data-confirm]').forEach(function(f) {
            f.addEventListener('submit', function(e) {
                if (!confirm(f.dataset.confirm)) e.preventDefault();
            });
        });
        document.getElementById('buscar').addEventListener('input', function() {
            var q = this.value.toLowerCase();
            document.querySelectorAll('#tabla-cobros tbody tr').forEach(function(tr) {
                tr.hidden = q !== '' && tr.textContent.toLowerCase().indexOf(q) === -1;
            });
        });
    </script>
</body>

</html>