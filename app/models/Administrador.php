<?php

namespace app\models;

class Administrador extends Usuario
{
    public function __construct(string $nombre, int $edad, string $email, ?int $id = null)
    {
        parent::__construct($nombre, $edad, $email, self::ROL_ADMIN, $id);
    }
}