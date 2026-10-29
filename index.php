<?php

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/autoload.php';

use app\controllers\SesionControlador;
use app\controllers\VistaControlador;

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

// 4) Mostrar la página
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require_once __DIR__ . '/app/views/inc/head.php'; ?>
</head>
<body>
    <?php require_once __DIR__ . '/app/views/content/' . $vista . '-view.php'; ?>
    <?php require_once __DIR__ . '/app/views/inc/scripts.php'; ?>
</body>
</html>