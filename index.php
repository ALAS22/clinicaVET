<?php

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/autoload.php';

use app\controllers\SesionControlador;

SesionControlador::iniciar();

// Páginas que existen. Cualquier otra será un 404.
$paginasPublicas = ['login', 'registro'];   // se ven sin iniciar sesión
$paginasPrivadas = ['inicio'];              // exigen haber iniciado sesión

// 1) ¿Qué página pidió el usuario? Si no pidió ninguna, es el login
$vista = 'login';
if (isset($_GET['vista']) && is_string($_GET['vista']) && $_GET['vista'] !== '') {
    $vista = $_GET['vista'];
}

// 2) ¿La página existe?
if (!in_array($vista, $paginasPublicas, true) && !in_array($vista, $paginasPrivadas, true)) {
    $vista = '404';
    http_response_code(404);
}

// 3) Reglas de acceso
if (in_array($vista, $paginasPrivadas, true) && !SesionControlador::hayUsuario()) {
    header('Location: ' . APP_URL . 'login/');
    exit;
}

if (in_array($vista, $paginasPublicas, true) && SesionControlador::hayUsuario()) {
    header('Location: ' . APP_URL . 'inicio/');
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