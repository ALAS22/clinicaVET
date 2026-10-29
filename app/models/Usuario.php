<?php

namespace app\models;

class Usuario{
    public const ROL_CLIENTE = 'cliente';
    public const ROL_ADMIN = 'admin';

    protected ?int $id;
    protected string $nombre;
    protected int $edad;
    protected string $email;
    protected $clave = '';
    protected string $rol;

    public function __construct(string $nombre, int $edad, string $email, string $rol,?int $id = null){
        $this->id = $id;
        $this->setNombre($nombre);
        $this->setEdad($edad);
        $this->setEmail($email);
        $this->rol = $rol;
    }
    //----GETTERS----

    public function getId(): ?int{
         return $this->id; 
         }
    public function getNombre(): string{ 
        return $this->nombre; 
        }
    public function getEdad(): int{ 
        return $this->edad; 
        }
    public function getEmail(): string{ 
        return $this->email; 
        }
    public function getRol(): string{ 
        return $this->rol; 
        }
    public function getClave(): string{ 
        return $this->clave; 
        }
    //----SETTERS----
    public function setNombre(string $nombre): void{
        $this->nombre = trim($nombre);
    }
    public function setEdad(int $edad): void{
        $this->edad = $edad;
    }
    public function setEmail(string $email): void{
        $this->email = mb_strtolower(trim($email));
    }

    //Recibe la clave real y guarda su hash
    public function setClave(string $claveReal): void{
        $this->clave = password_hash($claveReal, PASSWORD_DEFAULT);
    }

    //----HASH que viene de la BD
    public function setClaveCifrada(string $hash): void{
        $this->clave = $hash;
    }
    public function verificarClave(string $claveReal): bool{
        return password_verify($claveReal, $this->clave);
    }

    //----Validaciones----
    //se valida antes de ciflar
    public static function validarCLave(string $clave, string $repetida): array{
        $errores = [];
        if (strlen($clave) < 8 || strlen($clave) > 16) {
            $errores[] = 'Las claves deben tener entre 8 y 16 caracteres.';
        }
        if ($clave !== $repetida) {
            $errores[] = 'Las claves no coinciden,';
        }
        return $errores;
    }

    public function validar():array{
        $errores = [];

        if (!preg_match('/^[\p{L} ]{3,80}$/u', $this->nombre)){
            $errores[] ='El nombre completo debe tener untre 3 y 80 letras';
        }
        if($this->edad < 18 || $this->edad > 100){
            $errores[] = 'La edad debe estar entre 18 y 100 años';
        }
         if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El correo electrónico no es válido.';
        }
        if (!in_array($this->rol, [self::ROL_CLIENTE, self::ROL_ADMIN], true)) {
            $errores[] = 'El rol no es válido.';
        }
        return $errores;
    }
    //----Otros metodos----
    //Cliente y administrador
    public function puedeIniciarSesion(): bool{
        return true;
    }
    public function toArray():array{
        return [
            'id' => $this->id,
            'nombre'=>$this->nombre,
            'edad' =>$this->edad,
            'email' =>$this->email,
            'rol' => $this->rol,

        ];
    }
}