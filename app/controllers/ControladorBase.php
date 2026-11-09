<?php

namespace app\controllers;

class ControladorBase
{
    // Arma la respuesta que el JavaScript mostrará con SweetAlert
    protected function respuesta(string $icono, string $titulo, string $texto, string $redirigir = ''): array{
        return [
            'icono'     => $icono,       // 'success', 'error' o 'warning'
            'titulo'    => $titulo,
            'texto'     => $texto,
            'redirigir' => $redirigir,   // URL a la que ir al aceptar ('' = quedarse)
        ];
    }
}