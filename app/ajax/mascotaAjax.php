<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../autoload.php';

use app\controllers\MascotaControlador;
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
// Solo los clientes con sesion iniciada pueden usar este archivo
if (!SesionControlador::hayUsuario() || SesionControlador::getRol() !== Usuario::ROL_CLIENTE) {
    echo json_encode([
        'icono'     => 'error',
        'titulo'    => 'No autorizado',
        'texto'     => 'Inicia sesión como cliente para continuar.',
        'redirigir' => APP_URL . 'login/',
    ]);
    exit;
}

$controlador = new MascotaControlador();

$accion = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && is_string($_POST['accion'])) {
    $accion = $_POST['accion'];
}

switch ($accion) {
    case 'registrar':
        $respuesta = $controlador->registrar($_POST);
        break;

    case 'actualizar':
        $respuesta = $controlador->actualizar($_POST);
        break;

    case 'eliminar':
        $respuesta = $controlador->eliminar($_POST);
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