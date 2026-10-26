<?php

namespace app\models;

use Override;

class Administrador extends Usuario{
    public function __construct(string $nombre, int $edad, string $email, string $rol, ?int $id = null)
    {
        parent::__construct($nombre, $edad, $email, self::ROL_ADMIN, $id);
    }
}