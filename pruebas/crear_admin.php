<?php
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ .'/../autoload.php';

use app\models\Administrador;
use app\models\Cliente;
use app\models\Usuario;
use app\models\UsuarioModelo;

$nombre = "Ariana";
$edad = 28;
$email = "ariana222@gmail.com";
$clave = "AClave123";

$modelo = new UsuarioModelo();

if($modelo->existeEmail($email)){
    echo 'Este administrador ya existe';
}

$admin = new Administrador($nombre, $edad, $email,$clave);

$errores = array_merge(Usuario::validarCLave($clave,$clave));
$admin->validar();

if (!empty($errores)) {
    exit(implode('<br>', $errores));
}

$admin->setClave($clave);
$id = $modelo->guardar($admin);

echo $id
    ? "Administrador creado con id $id. <strong>Borra este archivo ahora.</strong>"
    : 'No se pudo crear el administrador.';