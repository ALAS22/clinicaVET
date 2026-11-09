<?php

namespace app\controllers;

use app\models\Mascota;
use app\models\MascotaModelo;

class MascotaControlador extends ControladorBase
{
    private MascotaModelo $modelo;

    public function __construct(){
        $this->modelo = new MascotaModelo();
    }

    //---- Lo que usan las vistas ----//
    public function listar(): array{
        return $this->modelo->listarPorUsuario($this->usuarioId());
    }

    public function obtener(int $id): ?Mascota{
        return $this->modelo->buscarPorId($id, $this->usuarioId());
    }

    //---- Registrar ----//
    public function registrar(array $datos): array{
        $mascota = new Mascota(
            $this->usuarioId(),
            (string) ($datos['nombre'] ?? ''),
            (string) ($datos['especie'] ?? ''),
            $this->leerEdad($datos)
        );

        $errores = $mascota->validar();
        if (!empty($errores)) {
            return $this->respuesta('error', 'Revisa los datos', implode('<br>', $errores));
        }

        if ($this->modelo->guardar($mascota) === null) {
            return $this->respuesta('error', 'No se pudo registrar', 'Intenta de nuevo más tarde.');
        }

        return $this->respuesta(
            'success',
            'Mascota registrada',
            htmlspecialchars($mascota->getNombre()) . ' ya está en tu lista.',
            $this->urlLista()
        );
    }

    //---- Actualizar ----//
    public function actualizar(array $datos): array{
        $mascota = $this->obtener((int) ($datos['id'] ?? 0));

        // Si no existe o es de otro cliente, la respuesta es la misma
        if ($mascota === null) {
            return $this->respuesta('error', 'No encontrada', 'No se encontró la mascota.');
        }

        $mascota->setNombre((string) ($datos['nombre'] ?? ''));
        $mascota->setEspecie((string) ($datos['especie'] ?? ''));
        $mascota->setEdad($this->leerEdad($datos));

        $errores = $mascota->validar();
        if (!empty($errores)) {
            return $this->respuesta('error', 'Revisa los datos', implode('<br>', $errores));
        }

        if (!$this->modelo->actualizar($mascota)) {
            return $this->respuesta('error', 'No se pudo actualizar', 'Intenta de nuevo más tarde.');
        }

        return $this->respuesta('success', 'Mascota actualizada', 'Los cambios se guardaron.', $this->urlLista());
    }

    //---- Eliminar ----//
    public function eliminar(array $datos): array{
        if (!$this->modelo->eliminar((int) ($datos['id'] ?? 0), $this->usuarioId())) {
            return $this->respuesta('error', 'No se pudo eliminar', 'No se encontró la mascota.');
        }

        return $this->respuesta('success', 'Mascota eliminada', 'Ya no aparece en tu lista.', $this->urlLista());
    }

    //---- Auxiliares ----//
    private function usuarioId(): int{
        return SesionControlador::getId() ?? 0;
    }

    // Si la edad no es un numero regresa -1, para que validar() la rechace
    private function leerEdad(array $datos): int{
        if (isset($datos['edad']) && is_numeric($datos['edad'])) {
            return (int) $datos['edad'];
        }

        return -1;
    }

    private function urlLista(): string{
        return APP_URL . 'mascotas/';
    }
}