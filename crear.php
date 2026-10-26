<?php
require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1']);

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Agregar Empleado</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<script src="<?php echo htmlspecialchars(app_url('no-popups.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
<style>
:root{
    --bg:#171717;
    --panel:#242424;
    --panel-soft:#2c2c2c;
    --accent:#ff0055;
    --accent-soft:#ff6b8f;
    --text:#f3f3f3;
    --muted:#bdbdbd;
    --line:rgba(255,255,255,.08);
}
*{box-sizing:border-box}
body{
    margin:0;
    min-height:100vh;
    font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color:var(--text);
    background:
        radial-gradient(circle at top left, rgba(255,0,85,.16), transparent 28%),
        radial-gradient(circle at bottom right, rgba(255,107,143,.10), transparent 24%),
        var(--bg);
}
.page{min-height:100vh;display:flex;flex-direction:column}
.topbar{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    padding:16px 20px;
    background:var(--accent);
    box-shadow:0 10px 28px rgba(0,0,0,.24);
}
.back{
    display:inline-flex;
    align-items:center;
    gap:10px;
    color:#fff;
    text-decoration:none;
    font-weight:700;
    padding:10px 14px;
    border-radius:12px;
    background:rgba(255,255,255,.12);
    transition:transform .2s ease, background-color .2s ease;
}
.back:hover{transform:translateY(-1px);background:rgba(255,255,255,.2)}
.title-block{flex:1;text-align:center}
.eyebrow{
    margin:0 0 4px;
    font-size:.78rem;
    letter-spacing:.18em;
    text-transform:uppercase;
    color:rgba(255,255,255,.85);
}
.title{
    margin:0;
    font-size:clamp(1.5rem, 2.8vw, 2.2rem);
    font-weight:800;
    text-transform:uppercase;
}
.subtitle{margin:6px 0 0;color:rgba(255,255,255,.86);font-size:.95rem}
.shell{
    width:min(1100px, calc(100% - 32px));
    margin:28px auto 40px;
    display:grid;
    grid-template-columns:1.05fr .95fr;
    gap:20px;
    align-items:start;
}
.hero,.card{
    border:1px solid var(--line);
    border-radius:20px;
    background:linear-gradient(180deg,var(--panel),var(--panel-soft));
    box-shadow:0 22px 48px rgba(0,0,0,.32);
}
.hero{
    padding:28px;
    position:relative;
    overflow:hidden;
}
.hero::after{
    content:"";
    position:absolute;
    inset:auto -40px -50px auto;
    width:180px;
    height:180px;
    border-radius:50%;
    background:radial-gradient(circle, rgba(255,0,85,.28), transparent 70%);
    pointer-events:none;
}
.hero-icon{
    width:72px;
    height:72px;
    border-radius:18px;
    display:grid;
    place-items:center;
    font-size:1.9rem;
    color:#fff;
    background:linear-gradient(135deg, var(--accent), var(--accent-soft));
    box-shadow:0 16px 28px rgba(255,0,85,.22);
}
.hero h2{margin:20px 0 10px;font-size:clamp(1.7rem, 3vw, 2.4rem)}
.hero p{margin:0;color:var(--muted);line-height:1.6;max-width:42ch}
.hero-list{
    list-style:none;
    margin:22px 0 0;
    padding:0;
    display:grid;
    gap:12px;
}
.hero-list li{display:flex;align-items:flex-start;gap:10px;color:#ececec}
.hero-list i{color:var(--accent-soft);margin-top:4px}
.card{padding:28px}
.card h3{margin:0 0 6px;font-size:1.25rem}
.card .hint{margin:0 0 22px;color:var(--muted);font-size:.95rem}
form{display:grid;gap:14px}
.field-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
input[type="text"],
input[type="number"],
input[type="password"]{
    width:100%;
    padding:14px 16px;
    border-radius:14px;
    border:1px solid var(--line);
    background:#1b1b1b;
    color:var(--text);
    outline:none;
    transition:border-color .2s ease, box-shadow .2s ease, transform .2s ease;
}
input[type="text"]::placeholder,
input[type="number"]::placeholder,
input[type="password"]::placeholder{color:#bdbdbd}
input[type="text"]:focus,
input[type="number"]:focus,
input[type="password"]:focus{
    border-color:rgba(255,0,85,.6);
    box-shadow:0 0 0 3px rgba(255,0,85,.14);
}
.actions{display:flex;justify-content:flex-end;margin-top:4px}
.guardar{
    appearance:none;
    border:none;
    border-radius:14px;
    padding:14px 20px;
    font-size:1rem;
    font-weight:800;
    color:#fff;
    background:linear-gradient(135deg, var(--accent), #ff2f6d);
    cursor:pointer;
    box-shadow:0 16px 28px rgba(255,0,85,.22);
    transition:transform .2s ease, filter .2s ease;
}
.guardar:hover{transform:translateY(-1px);filter:brightness(1.05)}
.guardar:active{transform:translateY(0)}
.note{color:var(--muted);font-size:.88rem;margin:0}
@media (max-width: 920px){
    .shell{grid-template-columns:1fr}
}
@media (max-width: 720px){
    .topbar{flex-wrap:wrap;justify-content:center}
    .title-block{order:2;flex:1 0 100%}
    .back{order:1}
    .shell{width:min(100% - 20px, 1100px);margin:18px auto 28px}
    .hero,.card{padding:20px}
    .field-row{grid-template-columns:1fr}
    .actions{justify-content:stretch}
    .guardar{width:100%}
}
</style>
</head>
<body>
<div class="page">
    <header class="topbar">
        <a href="EmpleadoI.php" class="back"><i class="fas fa-arrow-left"></i> Volver</a>
        <div class="title-block">
            <p class="eyebrow">Modulo de personal</p>
            <h1 class="title">Ingresar funcionario</h1>
            <p class="subtitle">Registra un nuevo empleado con acceso al sistema.</p>
        </div>
        <div style="width:112px"></div>
    </header>

    <main class="shell">
        <section class="hero">
            <div class="hero-icon"><i class="fas fa-user-plus"></i></div>
            <h2>Alta de funcionario</h2>
            <p>Completa los datos basicos para dejar el registro listo y asociado al usuario de acceso.</p>
            <ul class="hero-list">
                <li><i class="fas fa-shield-alt"></i> La sesion sigue protegida por autenticacion.</li>
                <li><i class="fas fa-paint-brush"></i> La interfaz ahora usa el mismo lenguaje visual que las pantallas recientes.</li>
                <li><i class="fas fa-mobile-alt"></i> El formulario se adapta mejor a pantallas pequenas.</li>
            </ul>
        </section>

        <section class="card">
            <h3>Datos del funcionario</h3>
            <p class="hint">Los campos se envian a <strong>InterfazE.php</strong> sin cambiar el flujo actual.</p>

            <form method="post" action="InterfazE.php" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="field-row">
                    <input type="number" name="CI" id="Pal" placeholder="CI" maxlength="100" required>
                    <input type="text" name="Nombre" id="Pal" placeholder="Nombre" maxlength="100" required>
                </div>

                <div class="field-row">
                    <input type="text" name="Apellido" id="Pal" placeholder="Apellido" maxlength="100" required>
                    <input type="text" name="Direccion" id="Pal" placeholder="Direccion" maxlength="100" required>
                </div>

                <div class="field-row">
                    <input type="text" name="Rol" id="Pal" placeholder="Rol" maxlength="100" required>
                    <input type="text" name="Usuario" id="Pal" placeholder="Usuario" maxlength="100" required>
                </div>

                <input type="password" name="Pass" id="Pal" placeholder="Contrasena" maxlength="100" required>

                <p class="note">Verifica el usuario y la contrasena antes de guardar.</p>

                <div class="actions">
                    <input class="guardar" type="submit" name="submit" value="Guardar datos">
                </div>

                <input id="createID" name="crud" type="hidden" value="1">
            </form>
        </section>
    </main>
</div>
</body>
</html>
