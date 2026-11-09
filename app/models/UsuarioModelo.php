<?php

namespace app\models;

use PDOException;

class UsuarioModelo extends ModeloBase{
    //----Guardar (INSERT)----
    public function guardar(Usuario $usuario): ?int{
        if (!empty($usuario->validar()) || $usuario->getClave() === '') {
            return null;
        }

        try {
            $sql = $this->conectar()->prepare(
                'INSERT INTO usuario (usuario_nombre, usuario_edad, usuario_email, usuario_clave, usuario_rol)
                 VALUES (:nombre, :edad, :email, :clave, :rol)'
            );

            $sql->execute([
                ':nombre' => $usuario->getNombre(),
                ':edad'   => $usuario->getEdad(),
                ':email'  => $usuario->getEmail(),
                ':clave'  => $usuario->getClave(),
                ':rol'    => $usuario->getRol(),
            ]);

            return (int) $this->conectar()->lastInsertId();

        } catch (PDOException $e) {
            return null;
        }
    }

    //---- Consultas ----//
    public function existeEmail(string $email): bool{
        $sql = $this->conectar()->prepare(
            'SELECT 1 FROM usuario WHERE usuario_email = :email LIMIT 1'
        );
        $sql->execute([':email' => mb_strtolower(trim($email))]);

        return $sql->fetchColumn() !== false;
    }

    public function buscarPorEmail(string $email): ?Usuario{
        $sql = $this->conectar()->prepare(
            'SELECT * FROM usuario WHERE usuario_email = :email LIMIT 1'
        );
        $sql->execute([':email' => mb_strtolower(trim($email))]);
        $fila = $sql->fetch();

        return $fila ? $this->crearUsuario($fila) : null;
    }

    public function buscarPorId(int $id): ?Usuario{
        $sql = $this->conectar()->prepare(
            'SELECT * FROM usuario WHERE usuario_id = :id LIMIT 1'
        );
        $sql->execute([':id' => $id]);
        $fila = $sql->fetch();

        return $fila ? $this->crearUsuario($fila) : null;
    }

    //---- TRansforma una fila de la BD en un objeto ----//
    private function crearUsuario(array $f): Usuario{
        $id   = (int) $f['usuario_id'];
        $edad = (int) $f['usuario_edad'];

        $usuario = $f['usuario_rol'] === Usuario::ROL_ADMIN
        ? new Administrador($f['usuario_nombre'], $edad, $f['usuario_email'], $id)
            : new Cliente($f['usuario_nombre'], $edad, $f['usuario_email'], $id);

        $usuario->setClaveCifrada($f['usuario_clave']);

        return $usuario;
    }
}