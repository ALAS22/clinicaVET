<?php

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/autoload.php';

use app\controllers\SesionControlador;
use app\controllers\VistaControlador;
use app\models\Usuario;

SesionControlador::iniciar();

$vistaControlador = new VistaControlador();

// 1) ¿Qué página pidió el usuario? Si no pidió ninguna, es el login
$pedida = 'login';
if (isset($_GET['vista']) && is_string($_GET['vista']) && $_GET['vista'] !== '') {
    $pedida = $_GET['vista'];
}

// 2) ¿Existe esa página?
$vista = $vistaControlador->obtenerVista($pedida);
if ($vista === '404') {
    http_response_code(404);
}

// 3) ¿Puede verla este usuario? Si no, lo mandamos a donde corresponde
$destino = $vistaControlador->redireccion($vista);
if ($destino !== '') {
    header('Location: ' . $destino);
    exit;
}

// 4) Título de la pestaña del navegador
if ($vista === 'inicio') {
    $principal = 'mascotas/';
    if (SesionControlador::getRol() === Usuario::ROL_ADMIN) {
        $principal = 'admin-citas/';
    }

    header('Location: ' . APP_URL . $principal);
    exit;
}
$titulos = [
    'login'          => 'Iniciar sesión',
    'registro'       => 'Crear cuenta',
    'inicio'         => 'Inicio',
    'mascotas'       => 'Mascotas y citas',
    'mascota-editar' => 'Editar mascota',
    'citas'          => 'Mis citas',
    'cita-nueva'     => 'Agendar cita',
    'cita-editar'    => 'Modificar cita',
    'admin-citas'    => 'Citas',
    'admin-cita'     => 'Detalle de la cita',
    '404'            => 'Página no encontrada',
];

$tituloPagina = APP_NAME;
if (isset($titulos[$vista])) {
    $tituloPagina = $titulos[$vista];
}

// 5) Las pantallas de acceso llevan el panel verde con el logo;
//    las demás llevarán la barra de navegación (Etapa 2)
$esAcceso = in_array($vista, ['login', 'registro', '404'], true);

$claseBody = 'pagina-app';
if ($esAcceso) {
    $claseBody = 'pagina-acceso';
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <?php require_once __DIR__ . '/app/views/inc/head.php'; ?>
</head>
<body class="<?php echo $claseBody; ?>">

    <?php if ($esAcceso) { ?>

        <main class="acceso">
            <section class="acceso__formulario">
                <?php require_once __DIR__ . '/app/views/content/' . $vista . '-view.php'; ?>
            </section>

            <aside class="acceso__marca">
                <img src="<?php echo APP_URL; ?>app/views/fotos/logo.png" alt="Logo de la clínica VET">
            </aside>
        </main>

    <?php } else { ?>

          <?php require_once __DIR__ . '/app/views/inc/barra.php'; ?>

        <main class="contenido">
            <?php require_once __DIR__ . '/app/views/content/' . $vista . '-view.php'; ?>
        </main>

    <?php } ?>

    <?php require_once __DIR__ . '/app/views/inc/scripts.php'; ?>
</body>
</html>