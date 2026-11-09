<?php

namespace app\controllers;

use app\models\Cita;
use app\models\CitaModelo;
use app\models\MascotaModelo;

class CitaControlador extends ControladorBase
{
    private CitaModelo $modelo;
    private MascotaModelo $mascotas;

    public function __construct()
    {
        $this->modelo   = new CitaModelo();
        $this->mascotas = new MascotaModelo();
    }

    /*---------- Lo que usan las vistas ----------*/
    public function listar(): array
    {
        return $this->modelo->listarPorUsuario($this->usuarioId());
    }

    public function obtener(int $id): ?Cita
    {
        return $this->modelo->buscarPorId($id, $this->usuarioId());
    }

    // Si el día no sirve devuelve el motivo; si sirve, devuelve ''
    public function errorDia(string $fecha): string
    {
        $errores = Cita::validarDia($fecha);

        if (empty($errores)) {
            return '';
        }

        return $errores[0];
    }

    // Horarios de un día: [['hora' => '09:00', 'libre' => true], ...]
    // Devuelve [] si el día no es válido. Al modificar, se excluye la propia cita
    public function horarios(string $fecha, int $excluirCitaId = 0): array
    {
        if ($this->errorDia($fecha) !== '') {
            return [];
        }

        $ocupadas = $this->modelo->horasOcupadas($fecha, $excluirCitaId);
        $ahora    = date('Y-m-d H:i:s');
        $horarios = [];

        foreach (Cita::horasDelDia() as $hora) {
            // Las horas que ya pasaron hoy tampoco están disponibles
            $yaPaso = ($fecha . ' ' . $hora . ':00') <= $ahora;
            $libre  = !in_array($hora, $ocupadas, true) && !$yaPaso;

            $horarios[] = ['hora' => $hora, 'libre' => $libre];
        }

        return $horarios;
    }

    /*---------- Agendar ----------*/
    public function agendar(array $datos): array
    {
        $mascotaId = (int) ($datos['mascota_id'] ?? 0);
        $fecha     = (string) ($datos['fecha'] ?? '');
        $hora      = (string) ($datos['hora'] ?? '');
        $motivo    = (string) ($datos['motivo'] ?? '');

        // 1) La mascota debe ser de este cliente
        $mascota = $this->mascotas->buscarPorId($mascotaId, $this->usuarioId());
        if ($mascota === null) {
            return $this->respuesta('error', 'Mascota no encontrada', 'Elige una de tus mascotas.');
        }

        // 2) Armar y validar la cita
        $cita    = new Cita($this->usuarioId(), $mascotaId, $fecha . ' ' . $hora . ':00', $motivo);
        $errores = $cita->validar();
        if (!empty($errores)) {
            return $this->respuesta('error', 'Revisa los datos', implode('<br>', $errores));
        }

        // 3) ¿Ya está ocupado ese horario?
        if ($this->modelo->estaOcupada($cita->getFechaHora())) {
            return $this->respuesta('error', 'Horario ocupado', 'Ese horario ya fue tomado. Elige otro día u otra hora.');
        }

        // 4) Guardar
        if ($this->modelo->guardar($cita) === null) {
            return $this->respuesta(
                'error',
                'No se pudo agendar',
                'Es posible que alguien haya tomado ese horario justo ahora. Intenta con otro.'
            );
        }

        return $this->respuesta(
            'success',
            'Cita agendada',
            'La cita de ' . htmlspecialchars($mascota->getNombre()) . ' quedó pendiente de aprobación.',
            APP_URL . 'citas/'
        );
    }

    /*---------- Modificar ----------*/
    public function modificar(array $datos): array
    {
        $cita = $this->obtener((int) ($datos['id'] ?? 0));

        // Si no existe o es de otro cliente, la respuesta es la misma
        if ($cita === null) {
            return $this->respuesta('error', 'No encontrada', 'No se encontró la cita.');
        }

        if (!$cita->esModificable()) {
            return $this->respuesta(
                'error',
                'No se puede modificar',
                'Solo se pueden modificar citas pendientes o aprobadas que aún no han pasado.'
            );
        }

        $fecha  = (string) ($datos['fecha'] ?? '');
        $hora   = (string) ($datos['hora'] ?? '');
        $motivo = (string) ($datos['motivo'] ?? '');

        $cita->setFechaHora($fecha . ' ' . $hora . ':00');
        $cita->setMotivo($motivo);

        // Una cita aprobada que se cambia vuelve a pendiente: el administrador debe aprobarla otra vez
        $cita->setEstado(Cita::PENDIENTE);

        $errores = $cita->validar();
        if (!empty($errores)) {
            return $this->respuesta('error', 'Revisa los datos', implode('<br>', $errores));
        }

        // El tercer dato excluye la propia cita, para poder conservar su mismo horario
        if ($this->modelo->estaOcupada($cita->getFechaHora(), (int) $cita->getId())) {
            return $this->respuesta('error', 'Horario ocupado', 'Ese horario ya fue tomado. Elige otro día u otra hora.');
        }

        if (!$this->modelo->actualizar($cita)) {
            return $this->respuesta('error', 'No se pudo actualizar', 'Intenta con otro horario o más tarde.');
        }

        return $this->respuesta(
            'success',
            'Cita actualizada',
            'Los cambios se guardaron. La cita queda pendiente de aprobación.',
            APP_URL . 'citas/'
        );
    }

    /*---------- Cancelar ----------*/
    public function cancelar(array $datos): array
    {
        $id   = (int) ($datos['id'] ?? 0);
        $cita = $this->obtener($id);

        if ($cita === null || !$cita->esModificable()) {
            return $this->respuesta(
                'error',
                'No se puede cancelar',
                'Solo se pueden cancelar citas pendientes o aprobadas que aún no han pasado.'
            );
        }

        if (!$this->modelo->cancelar($id, $this->usuarioId())) {
            return $this->respuesta('error', 'No se pudo cancelar', 'Intenta de nuevo más tarde.');
        }

        return $this->respuesta('success', 'Cita cancelada', 'El horario quedó libre.', APP_URL . 'citas/');
    }

    /*---------- Auxiliares ----------*/
    private function usuarioId(): int
    {
        return SesionControlador::getId() ?? 0;
    }
}