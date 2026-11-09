<?php

namespace app\controllers;

use app\models\Usuario;

class VistaControlador
{
    // Cada pagina y quien puede verla:
    private array $paginas = [
        'login'          => 'publica',
        'registro'       => 'publica',
        'inicio'         => 'todos',
        'mascotas'       => 'cliente',
        'mascota-editar' => 'cliente',
        'citas'          => 'cliente',
        'cita-nueva'     => 'cliente',
        'cita-editar'    => 'cliente',
        'admin-citas'    => 'admin',
        'admin-cita'     => 'admin',    
    ];

    // regresa el nombre de la vista a mostrar o 404
    public function obtenerVista(string $pedida): string
    {
        if (isset($this->paginas[$pedida])) {
            return $pedida;
        }

        return '404';
    }

    // regresa la direccion a la que hay que mandar al usuario o '' 
    public function redireccion(string $vista): string
    {
        if ($vista === '404') {
            return '';
        }

        $acceso     = $this->paginas[$vista];
        $hayUsuario = SesionControlador::hayUsuario();
        $rol        = SesionControlador::getRol();

        // Las páginas públicas no tienen sentido si ya iniciaste sesión
        if ($acceso === 'publica' && $hayUsuario) {
            return APP_URL . 'inicio/';
        }

        // Todas las demas piden sesion
        if ($acceso !== 'publica' && !$hayUsuario) {
            return APP_URL . 'login/';
        }

        // Paginas exclusivas de un rol
        if ($acceso === 'admin' && $rol !== Usuario::ROL_ADMIN) {
            return APP_URL . 'inicio/';
        }
        if ($acceso === 'cliente' && $rol !== Usuario::ROL_CLIENTE) {
            return APP_URL . 'inicio/';
        }

        return '';
    }
}