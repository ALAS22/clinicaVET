<?php
namespace app\models;

use Exception;
use PDO;

require_once __DIR__ . '/../../config/server.php';

class ModeloBase{
    private ?PDO $pdo = null;

    //Abre la conexion

    protected function conectar():PDO{
        if($this->pdo == null){
            $this->pdo = new PDO('mysql:host='. DB_SERVER . ';dbname=' . DB_NAME . ';charset=utf8mb4',DB_USER,DB_PASS,
            [
               PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
               PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
               PDO::ATTR_EMULATE_PREPARES=>false,
            ]
            ); 
        }
        return $this->pdo;

    }
}