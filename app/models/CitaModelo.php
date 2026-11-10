<?php

namespace app\models;

use PDO;
use PDOException;

class CitaModelo extends ModeloBase
{
    //---- Horas ocupadas de un dia ----//
    public function horasOcupadas(string $fecha, int $excluirCitaId = 0): array{
        $sql = $this->conectar()->prepare(
            "SELECT DATE_FORMAT(cita_slot, '%H:%i') AS hora
             FROM cita
             WHERE cita_slot BETWEEN :inicio AND :fin
               AND cita_id <> :excluir"
        );
        $sql->execute([
            ':inicio'  => $fecha . ' 00:00:00',
            ':fin'     => $fecha . ' 23:59:59',
            ':excluir' => $excluirCitaId,
        ]);

        return $sql->fetchAll(PDO::FETCH_COLUMN);
    }

    public function estaOcupada(string $fechaHora, int $excluirCitaId = 0): bool{
        $sql = $this->conectar()->prepare(
            'SELECT 1 FROM cita WHERE cita_slot = :fecha AND cita_id <> :excluir LIMIT 1'
        );
        $sql->execute([':fecha' => $fechaHora, ':excluir' => $excluirCitaId]);

        return $sql->fetchColumn() !== false;
    }

    //---- Guardar (INSERT) ----//
    public function guardar(Cita $cita): ?int{
        if (!empty($cita->validar())) {
            return null;
        }

        try {
            $sql = $this->conectar()->prepare(
                'INSERT INTO cita (usuario_id, mascota_id, cita_fecha_hora, cita_motivo, cita_estado)
                 VALUES (:usuario, :mascota, :fecha, :motivo, :estado)'
            );

            $sql->execute([
                ':usuario' => $cita->getUsuarioId(),
                ':mascota' => $cita->getMascotaId(),
                ':fecha'   => $cita->getFechaHora(),
                ':motivo'  => $cita->getMotivo(),
                ':estado'  => $cita->getEstado(),
            ]);

            return (int) $this->conectar()->lastInsertId();

        } catch (PDOException $e) {
            //cuando dos personas piden la misma fecha
            return null;
        }
    }

    //---- Consultas ----//
    public function listarPorUsuario(int $usuarioId): array{
        $sql = $this->conectar()->prepare(
            'SELECT c.cita_id, c.cita_fecha_hora, c.cita_motivo, c.cita_estado, c.cita_comentario,
                    m.mascota_nombre
             FROM cita c
             INNER JOIN mascota m ON m.mascota_id = c.mascota_id
             WHERE c.usuario_id = :usuario
             ORDER BY c.cita_fecha_hora DESC'
        );
        $sql->execute([':usuario' => $usuarioId]);

        return $sql->fetchAll();
    }

    // Una cita como objeto
    public function buscarPorId(int $id, int $usuarioId): ?Cita{
        $sql = $this->conectar()->prepare(
            'SELECT * FROM cita WHERE cita_id = :id AND usuario_id = :usuario LIMIT 1'
        );
        $sql->execute([':id' => $id, ':usuario' => $usuarioId]);
        $fila = $sql->fetch();

        return $fila ? $this->crearCita($fila) : null;
    }

    //---- actualizar (UPDATE) ----//
    public function actualizar(Cita $cita): bool{
        if (!empty($cita->validar()) || $cita->getId() === null) {
            return false;
        }

        try {
            $sql = $this->conectar()->prepare(
                'UPDATE cita
                 SET cita_fecha_hora = :fecha, cita_motivo = :motivo, cita_estado = :estado
                 WHERE cita_id = :id AND usuario_id = :usuario'
            );

            $sql->execute([
                ':fecha'   => $cita->getFechaHora(),
                ':motivo'  => $cita->getMotivo(),
                ':estado'  => $cita->getEstado(),
                ':id'      => $cita->getId(),
                ':usuario' => $cita->getUsuarioId(),
            ]);

            return true;

        } catch (PDOException $e) {
            return false;
        }
    }

    //---- Cancelar ----//
    // Solo cancela citas activas del cliente.
    public function cancelar(int $id, int $usuarioId): bool{
        try {
            $sql = $this->conectar()->prepare(
                "UPDATE cita SET cita_estado = 'cancelada'
                 WHERE cita_id = :id AND usuario_id = :usuario
                   AND cita_estado IN ('pendiente', 'aprobada')"
            );
            $sql->execute([':id' => $id, ':usuario' => $usuarioId]);

            return $sql->rowCount() === 1;

        } catch (PDOException $e) {
            return false;
        }
    }

        
    
    /*---- PARA EL ADMINISTRADOR ----*/
    private function condiciones(string $estado, string $fecha, string $busqueda): array{
        $condiciones = [];
        $parametros  = [];

        if ($estado !== '') {
            $condiciones[] = 'c.cita_estado = :estado';
            $parametros[':estado'] = $estado;
        }

        if ($fecha !== '') {
            $condiciones[] = 'DATE(c.cita_fecha_hora) = :fecha';
            $parametros[':fecha'] = $fecha;
        }

        if ($busqueda !== '') {
            $condiciones[] = '(u.usuario_nombre LIKE :buscar1
                               OR u.usuario_email LIKE :buscar2
                               OR m.mascota_nombre LIKE :buscar3)';
            $parametros[':buscar1'] = '%' . $busqueda . '%';
            $parametros[':buscar2'] = '%' . $busqueda . '%';
            $parametros[':buscar3'] = '%' . $busqueda . '%';
        }

        $where = '';
        if (!empty($condiciones)) {
            $where = 'WHERE ' . implode(' AND ', $condiciones);
        }

        return ['where' => $where, 'parametros' => $parametros];
    }

    public function listarTodas(string $estado, string $fecha, string $busqueda, int $limite, int $desplazamiento): array{
        $filtro = $this->condiciones($estado, $fecha, $busqueda);

        $sql = $this->conectar()->prepare(
            'SELECT c.cita_id, c.cita_fecha_hora, c.cita_estado,
                    m.mascota_nombre, u.usuario_nombre, u.usuario_email
             FROM cita c
             INNER JOIN mascota m ON m.mascota_id = c.mascota_id
             INNER JOIN usuario u ON u.usuario_id = c.usuario_id '
            . $filtro['where'] .
            ' ORDER BY c.cita_fecha_hora ASC
              LIMIT :limite OFFSET :desplazamiento'
        );

        foreach ($filtro['parametros'] as $nombre => $valor) {
            $sql->bindValue($nombre, $valor);
        }
        $sql->bindValue(':limite', $limite, PDO::PARAM_INT);
        $sql->bindValue(':desplazamiento', $desplazamiento, PDO::PARAM_INT);
        $sql->execute();

        return $sql->fetchAll();
    }

    public function contarTodas(string $estado, string $fecha, string $busqueda): int{
        $filtro = $this->condiciones($estado, $fecha, $busqueda);

        $sql = $this->conectar()->prepare(
            'SELECT COUNT(*)
             FROM cita c
             INNER JOIN mascota m ON m.mascota_id = c.mascota_id
             INNER JOIN usuario u ON u.usuario_id = c.usuario_id ' . $filtro['where']
        );

        foreach ($filtro['parametros'] as $nombre => $valor) {
            $sql->bindValue($nombre, $valor);
        }
        $sql->execute();

        return (int) $sql->fetchColumn();
    }

    // Todos los datos de una cita, con los del cliente y la mascota. null si no existe
    public function detalle(int $id): ?array{
        $sql = $this->conectar()->prepare(
            'SELECT c.cita_id, c.cita_fecha_hora, c.cita_motivo, c.cita_estado, c.cita_comentario,
                    m.mascota_nombre, m.mascota_especie, m.mascota_edad,
                    u.usuario_nombre, u.usuario_email, u.usuario_edad
             FROM cita c
             INNER JOIN mascota m ON m.mascota_id = c.mascota_id
             INNER JOIN usuario u ON u.usuario_id = c.usuario_id
             WHERE c.cita_id = :id
             LIMIT 1'
        );
        $sql->execute([':id' => $id]);
        $fila = $sql->fetch();

        if ($fila === false) {
            return null;
        }

        return $fila;
    }

    public function contarPorEstado(): array{
        $conteo = [
            Cita::PENDIENTE => 0,
            Cita::APROBADA  => 0,
            Cita::CANCELADA => 0,
            Cita::ATENDIDA  => 0,
        ];

        $consulta = $this->conectar()->query(
            'SELECT cita_estado, COUNT(*) AS total FROM cita GROUP BY cita_estado'
        );

        foreach ($consulta->fetchAll() as $fila) {
            $conteo[$fila['cita_estado']] = (int) $fila['total'];
        }

        return $conteo;
    }
    //ADMINISTRADOR
    //---- Cambios de estado ----//
    // En cada UPDATE, el WHERE pide el estado. si la cita ya cambio, no se toca

    public function aprobar(int $id, string $ahora): bool{
        try {
            $sql = $this->conectar()->prepare(
                "UPDATE cita SET cita_estado = 'aprobada'
                 WHERE cita_id = :id AND cita_estado = 'pendiente' AND cita_fecha_hora > :ahora"
            );
            $sql->execute([':id' => $id, ':ahora' => $ahora]);

            return $sql->rowCount() === 1;

        } catch (PDOException $e) {
            return false;
        }
    }

    public function atender(int $id, string $ahora): bool{
        try {
            $sql = $this->conectar()->prepare(
                "UPDATE cita SET cita_estado = 'atendida'
                 WHERE cita_id = :id AND cita_estado = 'aprobada' AND cita_fecha_hora <= :ahora"
            );
            $sql->execute([':id' => $id, ':ahora' => $ahora]);

            return $sql->rowCount() === 1;

        } catch (PDOException $e) {
            return false;
        }
    }

    // Igual que cancelar(), pero sin pedir cita de un cliente en particular
    public function cancelarComoAdmin(int $id): bool{
        try {
            $sql = $this->conectar()->prepare(
                "UPDATE cita SET cita_estado = 'cancelada'
                 WHERE cita_id = :id AND cita_estado IN ('pendiente', 'aprobada')"
            );
            $sql->execute([':id' => $id]);

            return $sql->rowCount() === 1;

        } catch (PDOException $e) {
            return false;
        }
    }

    public function guardarComentario(int $id, string $comentario): bool{
        $valor = null;
        if ($comentario !== '') {
            $valor = $comentario;
        }

        try {
            $sql = $this->conectar()->prepare(
                'UPDATE cita SET cita_comentario = :comentario WHERE cita_id = :id'
            );
            $sql->execute([':comentario' => $valor, ':id' => $id]);

            return true;

        } catch (PDOException $e) {
            return false;
        }
    }
     public function cambiarEstado(int $id, string $estadoActual, string $estadoNuevo, string $comentario) :bool{
        $valor = null;
        if($comentario !== ''){
            $valor = $comentario;
        }

        try {
            $sql = $this->conectar()->prepare(
                'UPDATE cita SET cita_estado = :nuevo, cita_comentario = :comentario
                 WHERE cita_id = :id AND cita_estado = :actual'
            );
            $sql->execute([
                ':nuevo'      => $estadoNuevo,
                ':comentario' => $valor,
                ':id'         => $id,
                ':actual'     => $estadoActual,
            ]);
            return $sql->rowCount() === 1;
        } catch (PDOException $e) {
            return false;
        }

     }
    //---- Convierte una fila de la BD en un objeto Cita ----//
    private function crearCita(array $f): Cita{
        return new Cita(
            (int) $f['usuario_id'],
            (int) $f['mascota_id'],
            $f['cita_fecha_hora'],
            $f['cita_motivo'],
            $f['cita_estado'],
            $f['cita_comentario'],
            (int) $f['cita_id']
        );
    }
}