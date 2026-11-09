<?php

namespace app\models;

use PDOException;

class MascotaModelo extends ModeloBase{
    //---- Listar las mascotas de un cliente ----//
    public function listarPorUsuario(int $usuarioId): array{
        $sql = $this->conectar()->prepare(
            'SELECT * FROM mascota WHERE usuario_id = :usuario ORDER BY mascota_nombre ASC'
        );
        $sql->execute([':usuario' => $usuarioId]);

        $mascotas = [];
        foreach ($sql->fetchAll() as $fila) {
            $mascotas[] = $this->crearMascota($fila);
        }

        return $mascotas;
    }

    //---- Buscar una mascota (solo si es de ese cliente) ----//
    public function buscarPorId(int $id, int $usuarioId): ?Mascota{
        $sql = $this->conectar()->prepare(
            'SELECT * FROM mascota WHERE mascota_id = :id AND usuario_id = :usuario LIMIT 1'
        );
        $sql->execute([':id' => $id, ':usuario' => $usuarioId]);
        $fila = $sql->fetch();

        return $fila ? $this->crearMascota($fila) : null;
    }

    //---- Guardar (INSERT) ----//
    // Devuelve el id nuevo, o null si no se pudo guardar
    public function guardar(Mascota $mascota): ?int{
        // Segunda barrera: nunca se guarda una mascota invalida
        if (!empty($mascota->validar())) {
            return null;
        }

        try {
            $sql = $this->conectar()->prepare(
                'INSERT INTO mascota (usuario_id, mascota_nombre, mascota_especie, mascota_edad)
                 VALUES (:usuario, :nombre, :especie, :edad)'
            );

            $sql->execute([
                ':usuario' => $mascota->getUsuarioId(),
                ':nombre'  => $mascota->getNombre(),
                ':especie' => $mascota->getEspecie(),
                ':edad'    => $mascota->getEdad(),
            ]);

            return (int) $this->conectar()->lastInsertId();

        } catch (PDOException $e) {
            return null;
        }
    }

    //---- Actualizar (UPDATE) ----//
    public function actualizar(Mascota $mascota): bool{
        if (!empty($mascota->validar()) || $mascota->getId() === null) {
            return false;
        }

        try {
            // El "AND usuario_id" es para que solo se edite una mascota del dueño
            $sql = $this->conectar()->prepare(
                'UPDATE mascota
                 SET mascota_nombre = :nombre, mascota_especie = :especie, mascota_edad = :edad
                 WHERE mascota_id = :id AND usuario_id = :usuario'
            );

            $sql->execute([
                ':nombre'  => $mascota->getNombre(),
                ':especie' => $mascota->getEspecie(),
                ':edad'    => $mascota->getEdad(),
                ':id'      => $mascota->getId(),
                ':usuario' => $mascota->getUsuarioId(),
            ]);

            return true;

        } catch (PDOException $e) {
            return false;
        }
    }

    //---- Eliminar (DELETE) ----//
    public function eliminar(int $id, int $usuarioId): bool{
        try {
            $sql = $this->conectar()->prepare(
                'DELETE FROM mascota WHERE mascota_id = :id AND usuario_id = :usuario'
            );
            $sql->execute([':id' => $id, ':usuario' => $usuarioId]);

            return $sql->rowCount() === 1;

        } catch (PDOException $e) {
            return false;
        }
    }

    //---- Convierte una fila de la BD en un objeto Mascota ----//
    private function crearMascota(array $f): Mascota{
        return new Mascota(
            (int) $f['usuario_id'],
            $f['mascota_nombre'],
            $f['mascota_especie'],
            (int) $f['mascota_edad'],
            (int) $f['mascota_id']
        );
    }
}