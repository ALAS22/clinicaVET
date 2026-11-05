<?php

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ .'/../autoload.php';

use app\models\Cliente;
use app\models\UsuarioModelo;

$modelo = new UsuarioModelo;

$cliente = new Cliente('Ana Toxtega',26,'anaToxtega@gmail.com');

$cliente->setClave('ana17Fdiseño');
echo 'Existe antes: ' .var_export($modelo->existeEmail($cliente->getEmail()), true) .'<br>';

$id = $modelo->guardar($cliente);
echo 'Guardado con id: ' . var_export($id, true) . '<br>';

echo 'Existe después: ' . var_export($modelo->existeEmail($cliente->getEmail()), true) . '<br>';

//Se busca correo, se prueba hacerlo con mayuscular

$encontrado = $modelo->buscarPorEmail('ANATOXTEGA@gmail.com');  

if($encontrado){
    echo $encontrado->getEmail(). '<br>';
    echo 'Clave correcta: ' . var_export($encontrado->verificarClave('Segura1234'), true) . '<br>';
    echo 'Clave incorrecta: ' . var_export($encontrado->verificarClave('otra'), true) . '<br>';

}

$repetido = new Cliente('Otra Persona', 40, 'ana.prueba@correo.com');
$repetido->setClave('Otra12345');
echo 'Guardar repetido: ' . var_export($modelo->guardar($repetido), true) . '<br>';