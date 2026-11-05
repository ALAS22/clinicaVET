<?php

namespace app\controllers;

use app\models\Usuario;

class VistaControlador
{
    // Cada página y quién puede verla:
    //   'publica' = solo sin sesión (login, registro)
    //   'todos'   = cualquiera con sesión iniciada
    //   'cliente' = solo clientes
    //   'admin'   = solo el administrador
    private array $paginas = [
        'login'    => 'publica',
        'registro' => 'publica',
        'inicio'   => 'todos',
        'mascotas'       => 'cliente',
        'mascota-editar' => 'cliente',
        'citas'          => 'cliente',
        'cita-nueva'     => 'cliente',
        'cita-editar'    => 'cliente',
    ];

    // Devuelve el nombre de la vista a mostrar, o '404' si la página no existe
    public function obtenerVista(string $pedida): string
    {
        if (isset($this->paginas[$pedida])) {
            return $pedida;
        }

        return '404';
    }

    // Devuelve la dirección a la que hay que mandar al usuario,
    // o '' si puede ver la página sin problema
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

        // Todas las demás exigen sesión
        if ($acceso !== 'publica' && !$hayUsuario) {
            return APP_URL . 'login/';
        }

        // Páginas exclusivas de un rol
        if ($acceso === 'admin' && $rol !== Usuario::ROL_ADMIN) {
            return APP_URL . 'inicio/';
        }
        if ($acceso === 'cliente' && $rol !== Usuario::ROL_CLIENTE) {
            return APP_URL . 'inicio/';
        }

        return '';
    }
}