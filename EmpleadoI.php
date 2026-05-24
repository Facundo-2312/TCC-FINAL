<?php

session_start();

if (!isset($_SESSION['Usuario'])) {
    header('Location: /proj/Login.php');
    exit();
}

require_once 'InterfazE.php';

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$empleados = ListarEmpleado();
$totalFuncionarios = count($empleados);
$rolesResumen = [];

foreach ($empleados as $fila) {
    $rol = trim((string) ($fila['Rol'] ?? 'Sin rol'));
    if (!isset($rolesResumen[$rol])) {
        $rolesResumen[$rol] = 0;
    }
    $rolesResumen[$rol]++;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Funcionarios</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<style>
:root{
    --bg:#171717;
    --panel:#242424;
    --panel-soft:#2c2c2c;
    --accent:#ff0055;
    --text:#f3f3f3;
    --muted:#bdbdbd;
    --line:rgba(255,255,255,.08);
}
*{box-sizing:border-box}
body{
    margin:0;
    background:
      radial-gradient(circle at top left, rgba(255,0,85,.15), transparent 28%),
      radial-gradient(circle at bottom right, rgba(255,95,135,.08), transparent 20%),
      var(--bg);
    color:var(--text);
    font-family:Arial, Helvetica, sans-serif;
    padding:20px;
}
.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:18px;
}
.back,.add-btn{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:10px 14px;
    border-radius:10px;
    text-decoration:none;
    color:#fff;
    font-weight:700;
}
.back{background:#3a3a3a}
.add-btn{background:var(--accent)}
.title{
    text-align:center;
    margin: 10px 0 20px;
    color:var(--accent);
    font-size:clamp(1.8rem,3vw,2.5rem);
}
.summary{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:14px;
    margin-bottom:20px;
}
.card{
    background:linear-gradient(180deg,var(--panel),var(--panel-soft));
    border:1px solid var(--line);
    border-radius:14px;
    padding:16px;
}
.card .label{color:var(--muted);font-size:.9rem}
.card .value{font-size:1.8rem;font-weight:800;margin-top:6px}
.table-wrap{
    background:linear-gradient(180deg,var(--panel),var(--panel-soft));
    border:1px solid var(--line);
    border-radius:14px;
    overflow:auto;
}
table{width:100%;border-collapse:collapse}
th,td{padding:12px;border-bottom:1px solid var(--line);text-align:left}
th{color:#ff6e99;font-size:.92rem}
.badge{display:inline-block;padding:5px 10px;border-radius:999px;background:#3a3a3a;color:#fff;font-size:.82rem}
.actions a{display:inline-block;padding:7px 10px;border-radius:8px;color:#fff;text-decoration:none;margin-right:6px}
.actions .edit{background:#2563eb}
.actions .delete{background:#dc2626}
.empty{padding:16px;color:var(--muted)}
@media (max-width: 900px){.summary{grid-template-columns:1fr 1fr}}
@media (max-width: 620px){.summary{grid-template-columns:1fr}}
</style>
</head>
<body>

<div class="topbar">
    <a href="/proj/Principal.php" class="back"><i class="fas fa-arrow-left"></i> Volver</a>
    <a href="/proj/Crear.php" class="add-btn"><i class="fas fa-user-plus"></i> Agregar Funcionario</a>
</div>

<h1 class="title">Gestión de Funcionarios</h1>

<div class="summary">
    <div class="card">
        <div class="label">Total funcionarios</div>
        <div class="value"><?php echo (int) $totalFuncionarios; ?></div>
    </div>
    <?php
    $tarjetas = array_slice($rolesResumen, 0, 3, true);
    foreach ($tarjetas as $rol => $cantidad) {
    ?>
        <div class="card">
            <div class="label"><?php echo h($rol); ?></div>
            <div class="value"><?php echo (int) $cantidad; ?></div>
        </div>
    <?php } ?>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>CI</th>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>Dirección</th>
                <th>Rol</th>
                <th>Usuario</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($empleados)) { ?>
                <?php foreach ($empleados as $fila) { ?>
                    <tr>
                        <td><?php echo (int) ($fila['CI'] ?? 0); ?></td>
                        <td><?php echo h($fila['Nombre'] ?? ''); ?></td>
                        <td><?php echo h($fila['Apellido'] ?? ''); ?></td>
                        <td><?php echo h($fila['Direccion'] ?? ''); ?></td>
                        <td><span class="badge"><?php echo h($fila['Rol'] ?? ''); ?></span></td>
                        <td><?php echo h($fila['Usuario'] ?? ''); ?></td>
                        <td class="actions">
                            <a class="edit" href="/proj/Actualizar.php?ID=<?php echo (int) ($fila['CI'] ?? 0); ?>"><i class="fas fa-pen"></i></a>
                            <a class="delete" href="/proj/Eliminar.php?ID=<?php echo (int) ($fila['CI'] ?? 0); ?>" onclick="return confirm('¿Eliminar funcionario?');"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr><td colspan="7" class="empty">No hay funcionarios registrados.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

</body>
</html>
