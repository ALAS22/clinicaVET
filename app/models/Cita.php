<?php

namespace app\models;

use DateTime;

class Cita{
    public const PENDIENTE = 'pendiente';
    public const APROBADA  = 'aprobada';
    public const CANCELADA = 'cancelada';
    public const ATENDIDA  = 'atendida';

    public const ESTADOS = [self::PENDIENTE, self::APROBADA, self::CANCELADA, self::ATENDIDA];

    // Horario de la Clinica
    public const HORA_INICIO   = 9;    // primera cita a las 9
    public const HORA_FIN      = 17;   // cierra a las 17:00 (última cita a las 16
    public const DIAS_ADELANTE = 60;   // dias de anticipacion para agendar

    // limpieza o tienen setter
    private string $fechaHora;   // formato 'AAAA-MM-DD HH:MM:SS'
    private string $motivo;

    public function __construct(
        private int $usuarioId,
        private int $mascotaId,
        string $fechaHora,
        string $motivo,
        private string $estado = self::PENDIENTE,
        private ?string $comentario = null,   // comentario del admin
        private ?int $id = null
    ) {
        $this->setFechaHora($fechaHora);
        $this->setMotivo($motivo);
    }

    //---- getters ----//
    public function getId(): ?int { return $this->id; }
    public function getUsuarioId(): int { return $this->usuarioId; }
    public function getMascotaId(): int { return $this->mascotaId; }
    public function getFechaHora(): string { return $this->fechaHora; }
    public function getMotivo(): string { return $this->motivo; }
    public function getEstado(): string { return $this->estado; }
    public function getComentario(): ?string { return $this->comentario; }

    //---- setters ----//
    public function setFechaHora(string $fechaHora): void { $this->fechaHora = trim($fechaHora); }
    public function setMotivo(string $motivo): void { $this->motivo = trim($motivo); }
    public function setEstado(string $estado): void { $this->estado = $estado; }

    //---- horarios ----//
    // Las horas de inicio de cada bloque
    public static function horasDelDia(): array
    {
        $horas = [];

        for ($h = self::HORA_INICIO; $h < self::HORA_FIN; $h++) {
            $horas[] = sprintf('%02d:00', $h);
        }

        return $horas;
    }

    // Valida solo el dia
    public static function validarDia(string $fecha): array
    {
        // el "!" hace que la hora quede en 00:00:00
        $dia = DateTime::createFromFormat('!Y-m-d', $fecha);

        // Si la fecha no existe, PHP la corrige, aquí lo detectamos
        if ($dia === false || $dia->format('Y-m-d') !== $fecha) {
            return ['La fecha no es válida.'];
        }

        $hoy    = new DateTime('today');
        $limite = new DateTime('today');
        $limite->modify('+' . self::DIAS_ADELANTE . ' days');

        if ($dia < $hoy) {
            return ['No se pueden agendar citas en fechas pasadas.'];
        }
        if ($dia > $limite) {
            return ['Solo se puede agendar con ' . self::DIAS_ADELANTE . ' días de anticipación.'];
        }
        if ((int) $dia->format('N') === 7) {   // N: 1 = lunes ... 7 = domingo
            return ['La clínica no atiende los domingos.'];
        }

        return [];
    }

    //---- validacion completa ----//
    public function validar(): array
    {
        $errores = [];

        $fecha = DateTime::createFromFormat('Y-m-d H:i:s', $this->fechaHora);

        if ($fecha === false || $fecha->format('Y-m-d H:i:s') !== $this->fechaHora) {
            $errores[] = 'La fecha y la hora no son válidas.';
        } else {
            $erroresDia = self::validarDia($fecha->format('Y-m-d'));

            if (!empty($erroresDia)) {
                $errores = array_merge($errores, $erroresDia);
            } else {
                $hora    = (int) $fecha->format('G');                 // hora 0-23, sin cero a la izquierda
                $enPunto = $fecha->format('i:s') === '00:00';         // minutos y segundos en cero

                if (!$enPunto || $hora < self::HORA_INICIO || $hora >= self::HORA_FIN) {
                    $errores[] = 'La hora debe estar entre las ' . self::HORA_INICIO . ':00 y las '
                               . (self::HORA_FIN - 1) . ':00, en punto.';
                } elseif ($fecha <= new DateTime('now')) {
                    $errores[] = 'Elige un horario que aún no haya pasado.';
                }
            }
        }

        $largo = mb_strlen($this->motivo);
        if ($largo < 10 || $largo > 500) {
            $errores[] = 'Describe los síntomas o el motivo de la cita (entre 10 y 500 caracteres).';
        }

        if (!in_array($this->estado, self::ESTADOS, true)) {
            $errores[] = 'El estado de la cita no es válido.';
        }

        return $errores;
    }

    // Una cita se puede modificar o cancelar si está activa y todavía no llega su hora
    public function esModificable(): bool
    {
        $activa = in_array($this->estado, [self::PENDIENTE, self::APROBADA], true);
        $fecha  = DateTime::createFromFormat('Y-m-d H:i:s', $this->fechaHora);

        return $activa && $fecha !== false && $fecha > new DateTime('now');
    }
}