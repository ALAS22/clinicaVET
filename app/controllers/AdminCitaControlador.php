<?php

namespace app\controllers;

use DateTime;
use app\models\Cita;
use app\models\CitaModelo;

class AdminCitaControlador extends ControladorBase
{
    private const POR_PAGINA = 10;

    private CitaModelo $modelo;

    public function __construct(){
        $this->modelo = new CitaModelo();
    }

    //----- Lo que usan las vistas ----//

    // Lee y limpia los filtros que vienen en la dirección (?estado=...&fecha=...&q=...&pagina=...)
    // Cualquier valor raro se ignora y queda el valor por defecto
    public function leerFiltros(array $datos): array{
        $estado = '';
        if (isset($datos['estado']) && is_string($datos['estado'])
            && in_array($datos['estado'], Cita::ESTADOS, true)) {
            $estado = $datos['estado'];
        }

        $fecha = '';
        if (isset($datos['fecha']) && is_string($datos['fecha'])) {
            $dia = DateTime::createFromFormat('!Y-m-d', $datos['fecha']);
            if ($dia !== false && $dia->format('Y-m-d') === $datos['fecha']) {
                $fecha = $datos['fecha'];
            }
        }

        $busqueda = '';
        if (isset($datos['q']) && is_string($datos['q'])) {
            $busqueda = mb_substr(trim($datos['q']), 0, 50);
        }

        $pagina = 1;
        if (isset($datos['pagina']) && is_numeric($datos['pagina']) && (int) $datos['pagina'] > 0) {
            $pagina = (int) $datos['pagina'];
        }

        return ['estado' => $estado, 'fecha' => $fecha, 'q' => $busqueda, 'pagina' => $pagina];
    }

    // Devuelve la pagina de citas y los datos para armar el paginador
    public function listar(array $filtros): array{
        $total = $this->modelo->contarTodas($filtros['estado'], $filtros['fecha'], $filtros['q']);

        $paginas = (int) ceil($total / self::POR_PAGINA);
        if ($paginas < 1) {
            $paginas = 1;
        }

        // Si piden una página que no existe, se muestra la última
        $pagina = $filtros['pagina'];
        if ($pagina > $paginas) {
            $pagina = $paginas;
        }

        $desplazamiento = ($pagina - 1) * self::POR_PAGINA;

        $citas = $this->modelo->listarTodas(
            $filtros['estado'],
            $filtros['fecha'],
            $filtros['q'],
            self::POR_PAGINA,
            $desplazamiento
        );

        return ['citas' => $citas, 'total' => $total, 'pagina' => $pagina, 'paginas' => $paginas];
    }

    public function obtener(int $id): ?array{
        return $this->modelo->detalle($id);
    }

    public function resumen(): array{
        return $this->modelo->contarPorEstado();
    }
    
    public function opcionesDeEstado(array $cita): array{
        $actual  = $cita['cita_estado'];
        $yaLlego = strtotime($cita['cita_fecha_hora']) <= time();
        $opciones =[$actual];

        if ($actual === Cita::PENDIENTE) {
            if (!$yaLlego) {
                $opciones[] = Cita::APROBADA;
            }
             $opciones[] = Cita::CANCELADA;
        }
        if ($actual === Cita::APROBADA) {
            if ($yaLlego) {
                $opciones[] = Cita::ATENDIDA;
            }
            $opciones[] = Cita::CANCELADA;
        }
        return $opciones;
    }

    Public function guardar(array $datos): array{
        $id          = (int) ($datos['id'] ?? 0);
        $comentario  = $this->leerTexto($datos, 'comentario');
        $estadoNuevo = $this->leerTexto($datos, 'estado');

         if (mb_strlen($comentario) > 500) {
            return $this->respuesta('error', 'Comentario muy largo', 'El comentario puede tener hasta 500 caracteres.');
        }

        $cita = $this->modelo->detalle($id);
        if ($cita === null) {
            return $this->respuesta('error', 'No encontrada', 'No se encontró la cita.');
        }
        $estadoActual = $cita['cita_estado'];
         if ($estadoNuevo === '' || $estadoNuevo === $estadoActual) {
            if (!$this->modelo->guardarComentario($id, $comentario)) {
                return $this->respuesta('error', 'No se pudo guardar', 'Intenta de nuevo más tarde.');
            }

            return $this->respuesta(
                'success',
                'Cambios guardados',
                'El cliente verá el comentario en "Mis citas".',
                $this->urlDetalle($id)
            );
         }
         if (!in_array($estadoNuevo, $this->opcionesDeEstado($cita), true)) {
            return $this->respuesta(
                'error',
                'Cambio no permitido',
                'No se puede pasar la cita de "' . $estadoActual . '" a "' . htmlspecialchars($estadoNuevo) . '".'
            );
        }

        if (!$this->modelo->cambiarEstado($id, $estadoActual, $estadoNuevo, $comentario)) {
            return $this->respuesta(
                'error',
                'No se pudo guardar',
                'La cita cambió mientras la editabas. Recarga la página e inténtalo de nuevo.'
            );
        }

        return $this->respuesta(
            'success',
            'Cambios guardados',
            'La cita quedó como "' . $estadoNuevo . '".',
            $this->urlDetalle($id)
        );

    }
    //---- Aprobar ----//
    public function aprobar(array $datos): array{
        $id = (int) ($datos['id'] ?? 0);

        if ($this->modelo->detalle($id) === null) {
            return $this->respuesta('error', 'No encontrada', 'No se encontró la cita.');
        }

        if (!$this->modelo->aprobar($id, date('Y-m-d H:i:s'))) {
            return $this->respuesta(
                'error',
                'No se pudo aprobar',
                'Solo se pueden aprobar citas pendientes que aún no han pasado.'
            );
        }

        return $this->respuesta(
            'success',
            'Cita aprobada',
            'El cliente verá el cambio en "Mis citas".',
            $this->urlDetalle($id)
        );
    }

    //---- Marcar como atendida ----//
    public function atender(array $datos): array{
        $id = (int) ($datos['id'] ?? 0);

        if ($this->modelo->detalle($id) === null) {
            return $this->respuesta('error', 'No encontrada', 'No se encontró la cita.');
        }

        if (!$this->modelo->atender($id, date('Y-m-d H:i:s'))) {
            return $this->respuesta(
                'error',
                'No se pudo marcar',
                'Solo se pueden marcar como atendidas las citas aprobadas cuya hora ya llegó.'
            );
        }

        return $this->respuesta('success', 'Cita atendida', 'La cita quedó marcada como atendida.', $this->urlDetalle($id));
    }

    //---- Cancelar (con motivo opcional) ----//
    public function cancelar(array $datos): array{
        $id         = (int) ($datos['id'] ?? 0);
        $comentario = $this->leerTexto($datos, 'comentario');

        if (mb_strlen($comentario) > 500) {
            return $this->respuesta('error', 'Comentario muy largo', 'El comentario puede tener hasta 500 caracteres.');
        }

        if ($this->modelo->detalle($id) === null) {
            return $this->respuesta('error', 'No encontrada', 'No se encontró la cita.');
        }

        if (!$this->modelo->cancelarComoAdmin($id)) {
            return $this->respuesta(
                'error',
                'No se pudo cancelar',
                'Solo se pueden cancelar citas pendientes o aprobadas.'
            );
        }

        // Si escribiO un motivo, se guarda para que el cliente lo vea.
        // Si lo dejo vacio, se conserva el comentario que estaba.
        if ($comentario !== '') {
            $this->modelo->guardarComentario($id, $comentario);
        }

        return $this->respuesta('success', 'Cita cancelada', 'El horario quedó libre.', $this->urlDetalle($id));
    }

    //---- Comentar ----//
    public function comentar(array $datos): array
    {
        $id         = (int) ($datos['id'] ?? 0);
        $comentario = $this->leerTexto($datos, 'comentario');

        if (mb_strlen($comentario) > 500) {
            return $this->respuesta('error', 'Comentario muy largo', 'El comentario puede tener hasta 500 caracteres.');
        }

        if ($this->modelo->detalle($id) === null) {
            return $this->respuesta('error', 'No encontrada', 'No se encontró la cita.');
        }

        if (!$this->modelo->guardarComentario($id, $comentario)) {
            return $this->respuesta('error', 'No se pudo guardar', 'Intenta de nuevo más tarde.');
        }

        return $this->respuesta(
            'success',
            'Comentario guardado',
            'El cliente podrá verlo en "Mis citas".',
            $this->urlDetalle($id)
        );
    }

    //----- Auxiliares ----//
    private function urlDetalle(int $id): string{
        return APP_URL . 'admin-cita/?id=' . $id;
    }

    // Lee un campo de texto del formulario (si no llego o no es texto, regresa '')
    private function leerTexto(array $datos, string $campo): string{
        if (isset($datos[$campo]) && is_string($datos[$campo])) {
            return trim($datos[$campo]);
        }

        return '';
    }
}