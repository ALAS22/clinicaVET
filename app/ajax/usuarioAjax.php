<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../autoload.php';

use app\controllers\SesionControlador;
use app\controllers\UsuarioControlador;
    

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

$controlador = new UsuarioControlador();

// Solo se aceptan peticiones POST, y el campo "accion" decide que hacer
$accion = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && is_string($_POST['accion'])) {
    $accion = $_POST['accion'];
}

switch ($accion) {
    case 'registrar':
        $respuesta = $controlador->registrar($_POST);
        break;

    case 'iniciar':
        $respuesta = $controlador->iniciarSesion($_POST);
        break;

    case 'cerrar':
        $respuesta = $controlador->cerrarSesion();
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