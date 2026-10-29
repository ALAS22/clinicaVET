<?php

namespace app\controllers;

class ControladorBase{
    protected function respuesta(string $icono, string $titulo, string $texto, string $redirigir = ''): array{
        return [
            'icono' => $icono,  // 'success', 'error' o 'warning'
            'tiulo' => $titulo,
            'texto' => $texto,
            'redirigir' => $redirigir,   // URL a la que ir al aceptar ('' = quedarse)
        ];
    
    }
}