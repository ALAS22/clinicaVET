<?php

namespace app\models;

class Mascota{
    public const ESPECIES = ['perro','gato','ave','otro'];

    private string $nombre;
    private string $especie;
    private int $edad;

    public function __construct(
        private int $usuarioId,
        string $nombre,
        string $especie,
        int $edad,
        private ?int $id = null

    ){
        $this->setNombre($nombre);
        $this->setEspecie($especie);
        $this->setEdad($edad);
    }
    //----GETTERS-----
    public function getID(): ?int{
        return $this->id;
        }
    public function getUsuarioId(): int{
        return $this->usuarioId;
        }
    public function getNombre(): string{
        return $this->nombre;
        }
    public function getEspecie(): string{
        return $this->especie;
        }
    public function getEdad(): int{
        return $this->edad;
        }
    //----SETTERS(van a limpiar y guardar)----
    public function setNombre(string $nombre): void{
        $this->nombre = trim($nombre);
    }
    public function setEspecie(string $especie): void{
        $this->especie = mb_strtolower(trim($especie));
    }
    public function setEdad(int $edad): void{
        $this->edad = $edad;
    }
    //----VALIDACION DE DATOS----
    public function validar(): array{
        $errores = [];

        if(!preg_match('/^[>}\p{L} ]{2,40}$/u', $this->nombre)){
            $errores[]="El nombre debe tener entre 2 y 40 letras";
        }

        if(!in_array($this->especie, self::ESPECIES, true)){
            $errores[] = 'La especue no es valida. Opciones' .implode(', ',self::ESPECIES);
        }
        if ($this->edad < 0 || $this->edad > 40) {
            $errores[] = 'La edad debe de estar entre 0 y 40 años.';
            
        }
        return $errores;
    }

    //----Otros metodos----

    public function presentarMascote(): string{
        return "{$this->nombre} es  un {$this->especie} de {$this->edad} años";
    }

    public function toArray(): array{
        return[
            'id'    => $this->id,
            'usuario_id'    => $this->usuarioId,
            'nombre'    => $this->nombre,
            'especie'   => $this->especie,
            'edad'      => $this->edad,
        ];
    }
  
}