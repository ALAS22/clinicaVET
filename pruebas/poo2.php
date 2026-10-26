<?php
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../autoload.php';

use app\models\Mascota;

// 1) Caso correcto
$m1 = new Mascota(1, 'Firulais', 'perro', 3);
echo $m1->presentarMascota() . '<br>';

// 2) Casos con error: cada uno debe fallar con su mensaje
$casos = [
    ['Michi', 'gato', -2],
    ['R2', 'perro', 4],
    ['Nemo', 'pez', 1],
];

foreach ($casos as [$nombre, $especie, $edad]) {
    try {
        $m = new Mascota(1, $nombre, $especie, $edad);
        echo $m->presentarMascota() . '<br>';
    } catch (InvalidArgumentException $e) {
        echo 'Error: ' . $e->getMessage() . '<br>';
    }
}

// 3) Como arreglo
echo '<pre>';
print_r($m1->toArray());
echo '</pre>';