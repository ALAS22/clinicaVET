<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../autoload.php';

use app\controllers\AdminCitaControlador;
use app\controllers\SesionControlador;
use app\models\Usuario;

SesionControlador::iniciar();

header('Content-Type: application/json; charset=utf-8');

// Todos los datos deben ser texto. Si llega un arreglo (por ejemplo nombre[]=x), se rechaza
foreach ($_POST as $valor) {
    if (!is_string($valor)) {
        echo json_encode([
            'icono'     => 'error',
            'titulo'    => 'Petición no válida',
            'texto'     => 'Los datos enviados no tienen el formato correcto.',
            'redirigir' => '',
        ]);
        exit;
    }
}

// Solo el administrador con sesión iniciada puede usar este archivo
if (!SesionControlador::hayUsuario() || SesionControlador::getRol() !== Usuario::ROL_ADMIN) {
    echo json_encode([
        'icono'     => 'error',
        'titulo'    => 'No autorizado',
        'texto'     => 'Esta acción es solo para el administrador.',
        'redirigir' => APP_URL . 'login/',
    ]);
    exit;
}

$controlador = new AdminCitaControlador();

$accion = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && is_string($_POST['accion'])) {
    $accion = $_POST['accion'];
}

switch ($accion) {
    case 'aprobar':
        $respuesta = $controlador->aprobar($_POST);
        break;

    case 'atender':
        $respuesta = $controlador->atender($_POST);
        break;

    case 'cancelar':
        $respuesta = $controlador->cancelar($_POST);
        break;

    case 'comentar':
        $respuesta = $controlador->comentar($_POST);
        break;

    default:
        $respuesta = [
            'icono'     => 'error',
            'titulo'    => 'Petición no válida',
            'texto'     => 'No se pudo procesar la solicitud.',
            'redirigir' => '',
        ];
}

echo json_encode($respuesta);