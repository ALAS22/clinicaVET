<?php

namespace app\controllers;

use app\models\Cliente;
use app\models\Usuario;
use app\models\UsuarioModelo;
use app\controllers\ControladorBase;

class UsuarioControlador extends ControladorBase
{
    private UsuarioModelo $modelo;

    public function __construct()
    {
        $this->modelo = new UsuarioModelo();
    }

    /*---------- Registrar un cliente ----------*/
    public function registrar(array $datos): array
    {
        // 1) Leer lo que llegó del formulario
        $nombre   = (string) ($datos['nombre'] ?? '');
        $email    = (string) ($datos['email'] ?? '');
        $clave    = (string) ($datos['clave'] ?? '');
        $repetida = (string) ($datos['clave_repetida'] ?? '');

        // Si la edad no es un número, se pone -1 para que validar() la rechace
        $edad = -1;
        if (isset($datos['edad']) && is_numeric($datos['edad'])) {
            $edad = (int) $datos['edad'];
        }

        // 2) Crear el objeto y juntar todos los errores
        $cliente = new Cliente($nombre, $edad, $email);
        $errores = array_merge($cliente->validar(), Usuario::validarClave($clave, $repetida));

        // 3) El correo no debe estar repetido (solo se consulta si lo demás está bien)
        if (empty($errores) && $this->modelo->existeEmail($cliente->getEmail())) {
            $errores[] = 'Ese correo ya está registrado.';
        }

        if (!empty($errores)) {
            return $this->respuesta('error', 'Revisa los datos', implode('<br>', $errores));
        }

        // 4) Cifrar la clave y guardar
        $cliente->setClave($clave);

        if ($this->modelo->guardar($cliente) === null) {
            return $this->respuesta('error', 'No se pudo registrar', 'Intenta de nuevo más tarde.');
        }

        return $this->respuesta('success', 'Cuenta creada', 'Ya puedes iniciar sesión.', APP_URL . 'login/');
    }

    /*---------- Iniciar sesión ----------*/
    public function iniciarSesion(array $datos): array
    {
        $email = (string) ($datos['email'] ?? '');
        $clave = (string) ($datos['clave'] ?? '');

        $usuario = $this->modelo->buscarPorEmail($email);

        // Mismo mensaje si el correo no existe o si la clave es incorrecta
        if ($usuario === null || !$usuario->verificarClave($clave)) {
            return $this->respuesta('error', 'No se pudo iniciar sesión', 'Correo o contraseña incorrectos.');
        }

        SesionControlador::abrir($usuario);

        return $this->respuesta(
            'success',
            'Bienvenido',
            htmlspecialchars($usuario->getNombre()),
            APP_URL . 'inicio/'
        );
    }

    /*---------- Cerrar sesión ----------*/
    public function cerrarSesion(): array
    {
        SesionControlador::cerrar();

        return $this->respuesta('success', 'Sesión cerrada', 'Hasta pronto.', APP_URL . 'login/');
    }
}