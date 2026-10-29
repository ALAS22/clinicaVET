<?php
namespace app\controllers;

use app\models\Usuario;

class SesionControlador{
    //----Control de la sesion----
    public static function iniciar(): void{
        if (session_status() === PHP_SESSION_NONE) {
            session_name(APP_SESSION_NAME);
            session_set_cookie_params([
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }
    //Guardar inf del usuario
    public static function abrir(Usuario $usuario): void{
        session_regenerate_id(true);
        $_SESSION['usuario'] = [
            'id'    =>$usuario->getId(),
            'nombre' =>$usuario->getNombre(),
            'rol'   =>$usuario->getRol(),
        ];
    }
    public static function cerrar(): void{
        $_SESSION = [];
        session_destroy();
    }
    //----COnsultas----
    public static function hayUsuario(): bool{
        return isset($_SESSION['usuario']['id']);
    }
    public static function getId(): ?int{
        return $_SESSION['usuario']['id'] ?? null;
    }
    public static function getNombre(): string{
        return $_SESSION['usuario']['nombre'] ?? '';
    }
 
    public static function getRol(): string{
        return $_SESSION['usuario']['rol'] ?? '';
    }
}