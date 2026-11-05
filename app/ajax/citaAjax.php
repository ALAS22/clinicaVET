<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../autoload.php';

use app\controllers\CitaControlador;
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
if(!SesionControlador::hayUsuario() || SesionControlador::getRol() !== Usuario::ROL_CLIENTE){
    echo json_encode([
        'icono'     => 'error',
        'titulo'    => 'No autorizado',
        'texto'     => 'Inicia sesión como cliente para continuar.',
        'redirigir' => APP_URL . 'login/',
    ]);
    exit;
}

$controlador = new CitaControlador();

$accion = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && is_string($_POST['accion'])) {
    $accion = $_POST['accion'];
}

switch ($accion) {
    case 'agendar':
        $respuesta = $controlador->agendar($_POST);
        break;

    case 'modificar':
        $respuesta = $controlador->modificar($_POST);
        break;

    case 'cancelar':
        $respuesta = $controlador->cancelar($_POST);
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