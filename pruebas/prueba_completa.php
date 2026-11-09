<?php

/*
 * PRUEBA COMPLETA DEL PROYECTO
 * Se ejecuta en el navegador: http://localhost/clinicaVET/pruebas/prueba_completa.php
 * Crea usuarios de prueba (prueba.*@test.local), los usa y los borra al terminar.
 * No toca datos reales.
 */

header('Content-Type: text/plain; charset=utf-8');
ob_start();   // Guarda todo el texto hasta el final, para que la sesión pueda regenerarse sin errores

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../autoload.php';

use app\controllers\AdminCitaControlador;
use app\controllers\CitaControlador;
use app\controllers\MascotaControlador;
use app\controllers\SesionControlador;
use app\controllers\UsuarioControlador;
use app\models\Administrador;
use app\models\Cita;
use app\models\Cliente;
use app\models\Mascota;
use app\models\ModeloBase;
use app\models\Usuario;
use app\models\UsuarioModelo;

// Solo se ejecuta desde la propia computadora
$ip = '';
if (isset($_SERVER['REMOTE_ADDR'])) {
    $ip = $_SERVER['REMOTE_ADDR'];
}
if ($ip !== '127.0.0.1' && $ip !== '::1') {
    exit('Esta prueba solo se puede ejecutar desde tu propia computadora.');
}

set_time_limit(120);
SesionControlador::iniciar();

// Una clase pequeña para poder usar la conexión a la base de datos desde esta prueba
class BD extends ModeloBase
{
    public function pdo(): PDO
    {
        return $this->conectar();
    }
}

/* ======================= FUNCIONES AUXILIARES ======================= */

$total  = 0;
$fallos = 0;

// Anota el resultado de una comprobación (✔ si se cumple, ✘ si no)
function comprobar(string $descripcion, bool $condicion, string $detalle = ''): void
{
    global $total, $fallos;
    $total++;

    if ($condicion) {
        echo '  ✔ ' . $descripcion . "\n";
        return;
    }

    $fallos++;
    echo '  ✘ ' . $descripcion . "\n";
    if ($detalle !== '') {
        echo '      → ' . $detalle . "\n";
    }
}

function titulo(string $texto): void
{
    echo "\n=== " . $texto . " ===\n";
}

// Muestra el resumen final y termina
function terminar(): void
{
    global $total, $fallos;

    echo "\n=====================================\n";
    echo 'RESULTADO: ' . $total . ' comprobaciones, ' . ($total - $fallos) . ' correctas, ' . $fallos . " con error.\n";

    if ($fallos === 0) {
        echo "TODO BIEN ✔\n";
    } else {
        echo "Revisa las líneas con ✘ (debajo de cada una está el detalle).\n";
    }
    exit;
}

// Resume la respuesta de un controlador en una línea (se muestra si algo falla)
function describir(array $r): string
{
    return $r['icono'] . ': ' . $r['titulo'] . ' - ' . strip_tags($r['texto']);
}

// ¿La respuesta del controlador tiene ese icono y (opcional) contiene ese texto?
function respondio(array $respuesta, string $icono, string $fragmento = ''): bool
{
    if ($respuesta['icono'] !== $icono) {
        return false;
    }
    if ($fragmento === '') {
        return true;
    }

    return mb_stripos($respuesta['titulo'] . ' ' . $respuesta['texto'], $fragmento) !== false;
}

// Simula que una persona con ese id y rol tiene la sesión iniciada
function entrarComo(int $id, string $rol): void
{
    $_SESSION['usuario'] = ['id' => $id, 'nombre' => 'Prueba', 'rol' => $rol];
}

// Ejecuta una consulta y devuelve el primer valor de la primera fila
function consultar(PDO $pdo, string $sql, array $parametros = []): mixed
{
    $consulta = $pdo->prepare($sql);
    $consulta->execute($parametros);

    return $consulta->fetchColumn();
}

// Borra los datos de prueba (y solo esos)
function limpiar(PDO $pdo): void
{
    $patron = "usuario_email LIKE 'prueba.%@test.local'";

    $pdo->exec("DELETE FROM cita WHERE usuario_id IN (SELECT usuario_id FROM usuario WHERE $patron)");
    $pdo->exec("DELETE FROM mascota WHERE usuario_id IN (SELECT usuario_id FROM usuario WHERE $patron)");
    $pdo->exec("DELETE FROM usuario WHERE $patron");
}

// Id de la cita más reciente de ese usuario en ese horario
function idCita(PDO $pdo, int $usuarioId, string $fechaHora): int
{
    return (int) consultar(
        $pdo,
        'SELECT cita_id FROM cita WHERE usuario_id = :u AND cita_fecha_hora = :f ORDER BY cita_id DESC LIMIT 1',
        [':u' => $usuarioId, ':f' => $fechaHora]
    );
}

// ¿Esa hora de ese día aparece como libre?
function horarioLibre(CitaControlador $citas, string $fecha, string $hora): bool
{
    foreach ($citas->horarios($fecha) as $horario) {
        if ($horario['hora'] === $hora) {
            return $horario['libre'];
        }
    }

    return false;
}

// Busca un día (entre 30 y 59 días adelante) con al menos 4 horarios libres
function buscarDiaLibre(CitaControlador $citas): array
{
    for ($d = 30; $d <= 59; $d++) {
        $fecha = date('Y-m-d', strtotime('+' . $d . ' days'));
        $libres = [];

        foreach ($citas->horarios($fecha) as $horario) {
            if ($horario['libre']) {
                $libres[] = $horario['hora'];
            }
        }

        if (count($libres) >= 4) {
            return ['fecha' => $fecha, 'horas' => $libres];
        }
    }

    return ['fecha' => '', 'horas' => []];
}

// Hace una petición HTTP a nuestra propia aplicación, como lo haría el navegador
function peticion(string $url, string $metodo = 'GET', array $datos = [], string $cookie = ''): array
{
    $cabeceras = '';
    if ($cookie !== '') {
        $cabeceras .= 'Cookie: ' . $cookie . "\r\n";
    }

    $http = [
        'method'          => $metodo,
        'ignore_errors'   => true,   // para recibir también las respuestas 302 y 404
        'follow_location' => 0,      // no seguir las redirecciones: queremos verlas
        'timeout'         => 10,
    ];

    if ($metodo === 'POST') {
        $cabeceras .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $http['content'] = http_build_query($datos);
    }
    $http['header'] = $cabeceras;

    $cuerpo = @file_get_contents($url, false, stream_context_create(['http' => $http]));
    if ($cuerpo === false) {
        $cuerpo = '';
    }

    $recibidas = [];
    if (isset($http_response_header)) {
        $recibidas = $http_response_header;
    }

    $codigo = 0;
    if (isset($recibidas[0]) && preg_match('#HTTP/\S+\s+(\d{3})#', $recibidas[0], $coincidencia)) {
        $codigo = (int) $coincidencia[1];
    }

    $location = '';
    $cookieNueva = '';
    foreach ($recibidas as $linea) {
        if (stripos($linea, 'Location:') === 0) {
            $location = trim(substr($linea, 9));
        }
        if (stripos($linea, 'Set-Cookie: ' . APP_SESSION_NAME . '=') === 0) {
            $partes = explode(';', substr($linea, 11));
            $cookieNueva = trim($partes[0]);
        }
    }

    return ['codigo' => $codigo, 'location' => $location, 'cookie' => $cookieNueva, 'cuerpo' => $cuerpo];
}

// Convierte la respuesta de un archivo AJAX (texto JSON) en un arreglo
function leerJson(string $cuerpo): array
{
    $datos = json_decode($cuerpo, true);

    if (!is_array($datos) || !isset($datos['icono'])) {
        return ['icono' => '', 'titulo' => '(respuesta no válida)', 'texto' => substr(strip_tags($cuerpo), 0, 150), 'redirigir' => ''];
    }

    return $datos;
}

// Envía un formulario a un archivo AJAX y devuelve su respuesta
function ajax(string $archivo, array $datos, string $cookie = ''): array
{
    $r = peticion(APP_URL . 'app/ajax/' . $archivo . '.php', 'POST', $datos, $cookie);

    return leerJson($r['cuerpo']);
}

// ¿La página mostró algún error de PHP?
function hayErrorPHP(string $cuerpo): bool
{
    foreach (['Fatal error', 'Parse error', 'Warning:', 'Notice:', 'Deprecated:'] as $marca) {
        if (stripos($cuerpo, $marca) !== false) {
            return true;
        }
    }

    return false;
}

/* ======================= 1. ENTORNO ======================= */
titulo('1. ENTORNO: PHP, base de datos y archivos');

comprobar('PHP es 8.0 o superior', version_compare(PHP_VERSION, '8.0.0', '>='), 'Tu versión: ' . PHP_VERSION);
comprobar('Extensión pdo_mysql activada', extension_loaded('pdo_mysql'));
comprobar('Extensión mbstring activada', extension_loaded('mbstring'));
comprobar('Constantes de config/app.php definidas', defined('APP_URL') && defined('APP_NAME') && defined('APP_SESSION_NAME'));

$clases = [
    'app\controllers\ControladorBase', 'app\controllers\SesionControlador', 'app\controllers\UsuarioControlador',
    'app\controllers\MascotaControlador', 'app\controllers\CitaControlador', 'app\controllers\AdminCitaControlador',
    'app\controllers\VistaControlador',
    'app\models\ModeloBase', 'app\models\Usuario', 'app\models\Cliente', 'app\models\Administrador',
    'app\models\Mascota', 'app\models\Cita', 'app\models\UsuarioModelo', 'app\models\MascotaModelo',
    'app\models\CitaModelo',
];
$faltan = [];
foreach ($clases as $clase) {
    if (!class_exists($clase)) {
        $faltan[] = $clase;
    }
}
comprobar('Todas las clases se cargan con el autoload', empty($faltan), 'No se encontraron: ' . implode(', ', $faltan));
if (!empty($faltan)) {
    terminar();
}

$bd = new BD();
try {
    $pdo = $bd->pdo();
    comprobar('Conexión a la base de datos', true);
} catch (PDOException $e) {
    comprobar('Conexión a la base de datos', false, $e->getMessage());
    terminar();
}

foreach (['usuario', 'mascota', 'cita'] as $tabla) {
    $existe = (int) consultar(
        $pdo,
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t',
        [':t' => $tabla]
    );
    comprobar('Existe la tabla ' . $tabla, $existe > 0);
}

$columna = (int) consultar($pdo, "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cita' AND COLUMN_NAME = 'cita_slot'");
comprobar('La tabla cita tiene la columna generada cita_slot', $columna > 0);

$indice = (int) consultar($pdo, "SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cita' AND INDEX_NAME = 'uq_cita_slot'");
comprobar('La tabla cita tiene el índice único uq_cita_slot', $indice > 0);

$tipo = (string) consultar($pdo, "SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mascota' AND COLUMN_NAME = 'mascota_especie'");
comprobar("La especie 'conejo' está permitida en la tabla mascota", str_contains($tipo, "'conejo'"), 'Ejecuta el ALTER TABLE de mascota del mensaje anterior');

$admins = (int) consultar($pdo, "SELECT COUNT(*) FROM usuario WHERE usuario_rol = 'admin' AND usuario_email NOT LIKE 'prueba.%@test.local'");
comprobar('Existe al menos un administrador real (creado con crear_admin.php)', $admins > 0);

limpiar($pdo);   // por si una prueba anterior se quedo a medias

/* ======================= 2. CLASES DE DATOS ======================= */
titulo('2. CLASES DE DATOS (reglas, sin base de datos)');

$c = new Cliente('  Ana López Ruiz ', 34, '  ANA@Correo.COM ');
comprobar('Cliente válido no tiene errores', empty($c->validar()), implode(' | ', $c->validar()));
comprobar('El correo se guarda en minúsculas y sin espacios', $c->getEmail() === 'ana@correo.com');
comprobar('El nombre se guarda sin espacios sobrantes', $c->getNombre() === 'Ana López Ruiz');
$c->setClave('Segura1234');
comprobar('La clave se guarda cifrada, no en texto', $c->getClave() !== 'Segura1234' && str_starts_with($c->getClave(), '$2y$'));
comprobar('verificarClave acepta la clave correcta', $c->verificarClave('Segura1234'));
comprobar('verificarClave rechaza una clave incorrecta', !$c->verificarClave('otra'));
comprobar('toArray() no incluye la clave', !array_key_exists('clave', $c->toArray()));

$malo = new Cliente('A1', 15, 'correo-malo');
comprobar('Cliente con nombre, edad y correo inválidos da 3 errores', count($malo->validar()) === 3, implode(' | ', $malo->validar()));
comprobar('Clave corta rechazada', !empty(Usuario::validarClave('123', '123')));
comprobar('Claves distintas rechazadas', !empty(Usuario::validarClave('Segura1234', 'Otra12345')));
comprobar('Clave válida aceptada', empty(Usuario::validarClave('Segura1234', 'Segura1234')));

$a = new Administrador('Admin Prueba', 40, 'admin@test.local');
comprobar('Administrador tiene el rol admin', $a->getRol() === Usuario::ROL_ADMIN);
comprobar('Cliente tiene el rol cliente', $c->getRol() === Usuario::ROL_CLIENTE);

$m = new Mascota(1, ' Firulais ', ' PERRO ', 3);
comprobar('Mascota válida (se limpia nombre y especie)', empty($m->validar()) && $m->getNombre() === 'Firulais' && $m->getEspecie() === 'perro');
comprobar('Mascota con nombre "R2" rechazada', !empty((new Mascota(1, 'R2', 'perro', 3))->validar()));
comprobar('Mascota con especie "pez" rechazada', !empty((new Mascota(1, 'Nemo', 'pez', 3))->validar()));
comprobar('Mascota con edad -1 rechazada', !empty((new Mascota(1, 'Rex', 'perro', -1))->validar()));
comprobar('Mascota con edad 41 rechazada', !empty((new Mascota(1, 'Rex', 'perro', 41))->validar()));
comprobar('Un conejo es válido', empty((new Mascota(1, 'Nieve', 'conejo', 1))->validar()));

$lunes   = date('Y-m-d', strtotime('next monday'));
$domingo = date('Y-m-d', strtotime('next sunday'));
$texto   = 'Mi mascota no quiere comer';

comprobar('Hay 8 bloques de una hora, de 09:00 a 16:00', Cita::horasDelDia()[0] === '09:00' && count(Cita::horasDelDia()) === 8 && Cita::horasDelDia()[7] === '16:00');
comprobar('Cita válida (lunes 10:00)', empty((new Cita(1, 1, $lunes . ' 10:00:00', $texto))->validar()));
comprobar('No se agenda en domingo', !empty((new Cita(1, 1, $domingo . ' 10:00:00', $texto))->validar()));
comprobar('No se agenda en el pasado', !empty((new Cita(1, 1, '2020-01-01 10:00:00', $texto))->validar()));
comprobar('No se agenda a más de 60 días', !empty((new Cita(1, 1, date('Y-m-d', strtotime('+90 days')) . ' 10:00:00', $texto))->validar()));
comprobar('No se acepta la hora 08:00', !empty((new Cita(1, 1, $lunes . ' 08:00:00', $texto))->validar()));
comprobar('No se acepta la hora 17:00', !empty((new Cita(1, 1, $lunes . ' 17:00:00', $texto))->validar()));
comprobar('No se acepta la hora 10:30', !empty((new Cita(1, 1, $lunes . ' 10:30:00', $texto))->validar()));
comprobar('No se acepta una fecha que no existe (31 de febrero)', !empty((new Cita(1, 1, '2027-02-31 10:00:00', $texto))->validar()));
comprobar('No se acepta un motivo muy corto', !empty((new Cita(1, 1, $lunes . ' 10:00:00', 'dolor'))->validar()));
comprobar('Una cita pendiente futura se puede modificar', (new Cita(1, 1, $lunes . ' 10:00:00', $texto))->esModificable());
comprobar('Una cita cancelada no se puede modificar', !(new Cita(1, 1, $lunes . ' 10:00:00', $texto, Cita::CANCELADA))->esModificable());
comprobar('Una cita pasada no se puede modificar', !(new Cita(1, 1, '2020-01-01 10:00:00', $texto))->esModificable());

/* ======================= 3. USUARIOS ======================= */
titulo('3. REGISTRO E INICIO DE SESIÓN (controlador y base de datos)');

$usuarios = new UsuarioControlador();
$modeloU  = new UsuarioModelo();

$datosA = [
    'nombre' => 'Ana Prueba López', 'edad' => '30', 'email' => 'prueba.a@test.local',
    'clave' => 'Segura1234', 'clave_repetida' => 'Segura1234',
];

$r = $usuarios->registrar($datosA);
comprobar('Registrar un cliente válido', respondio($r, 'success'), describir($r));

$r = $usuarios->registrar($datosA);
comprobar('No deja registrar el mismo correo dos veces', respondio($r, 'error', 'registrado'), describir($r));

$r = $usuarios->registrar(array_merge($datosA, ['email' => 'PRUEBA.A@TEST.LOCAL']));
comprobar('Detecta el correo repetido aunque cambien las mayúsculas', respondio($r, 'error', 'registrado'), describir($r));

$r = $usuarios->registrar(array_merge($datosA, ['email' => 'prueba.x@test.local', 'edad' => '15']));
comprobar('Rechaza una edad menor de 18', respondio($r, 'error', 'edad'), describir($r));

$r = $usuarios->registrar(array_merge($datosA, ['email' => 'prueba.x@test.local', 'edad' => 'abc']));
comprobar('Rechaza una edad que no es número', respondio($r, 'error', 'edad'), describir($r));

$r = $usuarios->registrar(array_merge($datosA, ['email' => 'prueba.x@test.local', 'clave' => 'corta', 'clave_repetida' => 'corta']));
comprobar('Rechaza una clave corta', respondio($r, 'error', 'clave'), describir($r));

$r = $usuarios->registrar(array_merge($datosA, ['email' => 'prueba.x@test.local', 'clave_repetida' => 'OtraClave99']));
comprobar('Rechaza claves que no coinciden', respondio($r, 'error', 'no coinciden'), describir($r));

$r = $usuarios->registrar(array_merge($datosA, ['email' => 'correo-malo']));
comprobar('Rechaza un correo inválido', respondio($r, 'error', 'correo'), describir($r));

$r = $usuarios->registrar(array_merge($datosA, ['email' => 'prueba.x@test.local', 'nombre' => '<script>alert(1)</script>']));
comprobar('Rechaza un nombre con código', respondio($r, 'error', 'nombre'), describir($r));

$r = $usuarios->registrar([]);
comprobar('Un formulario vacío da error y no rompe nada', respondio($r, 'error'), describir($r));

comprobar('El correo rechazado no quedó guardado', !$modeloU->existeEmail('prueba.x@test.local'));

$ua = $modeloU->buscarPorEmail('prueba.a@test.local');
comprobar('El cliente A quedó guardado en la base de datos', $ua !== null);
if ($ua === null) {
    limpiar($pdo);
    terminar();
}
$idA = (int) $ua->getId();
comprobar('Su clave está cifrada en la base de datos', str_starts_with($ua->getClave(), '$2y$'));
comprobar('Su rol es cliente', $ua->getRol() === 'cliente');

$r = $usuarios->iniciarSesion(['email' => 'PRUEBA.A@test.local', 'clave' => 'Segura1234']);
comprobar('Iniciar sesión con datos correctos (el correo no distingue mayúsculas)', respondio($r, 'success'), describir($r));
comprobar('La sesión quedó abierta con el id y el rol correctos', SesionControlador::hayUsuario() && SesionControlador::getId() === $idA && SesionControlador::getRol() === 'cliente');
comprobar('La sesión no guarda la clave', !isset($_SESSION['usuario']['clave']));

$r1 = $usuarios->iniciarSesion(['email' => 'prueba.a@test.local', 'clave' => 'incorrecta']);
$r2 = $usuarios->iniciarSesion(['email' => 'no.existe@test.local', 'clave' => 'Segura1234']);
comprobar('Clave incorrecta es rechazada', respondio($r1, 'error', 'incorrectos'), describir($r1));
comprobar('Correo inexistente da el MISMO mensaje (no revela qué correos existen)', $r1['texto'] === $r2['texto'], describir($r2));

// El cliente B y el administrador de prueba
$b = new Cliente('Beto Prueba Gómez', 28, 'prueba.b@test.local');
$b->setClave('Segura1234');
$idB = (int) $modeloU->guardar($b);
comprobar('Se creó el cliente B', $idB > 0);

$adminPrueba = new Administrador('Admin Prueba Díaz', 40, 'prueba.admin@test.local');
$adminPrueba->setClave('Segura1234');
$idAdmin = (int) $modeloU->guardar($adminPrueba);
comprobar('Se creó el administrador de prueba', $idAdmin > 0);

$otro = new Cliente('Otro Repetido', 40, 'prueba.b@test.local');
$otro->setClave('Segura1234');
comprobar('El modelo rechaza un correo repetido (índice UNIQUE de la tabla)', $modeloU->guardar($otro) === null);

if ($idB === 0 || $idAdmin === 0) {
    limpiar($pdo);
    terminar();
}

/* ======================= 4. MASCOTAS ======================= */
titulo('4. MASCOTAS (controlador, modelo y permisos)');

$mascotas = new MascotaControlador();

entrarComo($idA, 'cliente');

$r = $mascotas->registrar(['nombre' => 'Firulais', 'especie' => 'perro', 'edad' => '3']);
comprobar('A registra una mascota válida', respondio($r, 'success'), describir($r));

$r = $mascotas->registrar(['nombre' => 'Nieve', 'especie' => 'conejo', 'edad' => '2']);
comprobar('A registra un conejo (la tabla lo permite)', respondio($r, 'success'), describir($r));

$r = $mascotas->registrar(['nombre' => 'Nemo', 'especie' => 'pez', 'edad' => '1']);
comprobar('Rechaza la especie "pez"', respondio($r, 'error', 'especie'), describir($r));

$r = $mascotas->registrar(['nombre' => 'R2', 'especie' => 'perro', 'edad' => '3']);
comprobar('Rechaza el nombre "R2"', respondio($r, 'error', 'nombre'), describir($r));

$r = $mascotas->registrar(['nombre' => 'Rex', 'especie' => 'perro', 'edad' => '99']);
comprobar('Rechaza la edad 99', respondio($r, 'error', 'edad'), describir($r));

$r = $mascotas->registrar(['nombre' => 'Rex', 'especie' => 'perro', 'edad' => 'abc']);
comprobar('Rechaza una edad que no es número', respondio($r, 'error', 'edad'), describir($r));

$r = $mascotas->registrar([]);
comprobar('Un formulario vacío da error y no rompe nada', respondio($r, 'error'), describir($r));

comprobar('A ve exactamente sus 2 mascotas', count($mascotas->listar()) === 2);

$idFiru  = (int) consultar($pdo, 'SELECT mascota_id FROM mascota WHERE usuario_id = :u AND mascota_nombre = :n', [':u' => $idA, ':n' => 'Firulais']);
$idNieve = (int) consultar($pdo, 'SELECT mascota_id FROM mascota WHERE usuario_id = :u AND mascota_nombre = :n', [':u' => $idA, ':n' => 'Nieve']);
comprobar('Las mascotas quedaron guardadas con su dueño correcto', $idFiru > 0 && $idNieve > 0);

$r = $mascotas->actualizar(['id' => $idFiru, 'nombre' => 'Firulais Max', 'especie' => 'perro', 'edad' => '4']);
comprobar('A actualiza su mascota', respondio($r, 'success'), describir($r));
comprobar('El cambio quedó guardado en la base de datos', consultar($pdo, 'SELECT mascota_nombre FROM mascota WHERE mascota_id = :id', [':id' => $idFiru]) === 'Firulais Max');

entrarComo($idB, 'cliente');

$r = $mascotas->registrar(['nombre' => 'Luna', 'especie' => 'gato', 'edad' => '2']);
comprobar('B registra su propia mascota', respondio($r, 'success'), describir($r));
$idLuna = (int) consultar($pdo, 'SELECT mascota_id FROM mascota WHERE usuario_id = :u AND mascota_nombre = :n', [':u' => $idB, ':n' => 'Luna']);

comprobar('B ve solo su mascota, no las de A', count($mascotas->listar()) === 1);

$r = $mascotas->actualizar(['id' => $idFiru, 'nombre' => 'Hackeada', 'especie' => 'gato', 'edad' => '1']);
comprobar('B NO puede modificar la mascota de A', respondio($r, 'error', 'encontrada'), describir($r));
comprobar('La mascota de A sigue intacta', consultar($pdo, 'SELECT mascota_nombre FROM mascota WHERE mascota_id = :id', [':id' => $idFiru]) === 'Firulais Max');

$r = $mascotas->eliminar(['id' => $idFiru]);
comprobar('B NO puede eliminar la mascota de A', respondio($r, 'error'), describir($r));
comprobar('La mascota de A sigue existiendo', (int) consultar($pdo, 'SELECT COUNT(*) FROM mascota WHERE mascota_id = :id', [':id' => $idFiru]) === 1);

entrarComo($idA, 'cliente');
$r = $mascotas->eliminar(['id' => $idNieve]);
comprobar('A elimina su propia mascota', respondio($r, 'success'), describir($r));
comprobar('A queda con 1 mascota', count($mascotas->listar()) === 1);

/* ======================= 5. CITAS DEL CLIENTE ======================= */
titulo('5. CITAS DEL CLIENTE (horarios, reglas y permisos)');

$citas = new CitaControlador();
$dia   = buscarDiaLibre($citas);

comprobar('Hay un día con al menos 4 horarios libres para probar', $dia['fecha'] !== '', 'Libera horarios o borra citas de prueba y repite');
if ($dia['fecha'] === '') {
    limpiar($pdo);
    terminar();
}

$f  = $dia['fecha'];
$h1 = $dia['horas'][0];
$h2 = $dia['horas'][1];
$h3 = $dia['horas'][2];
$h4 = $dia['horas'][3];
$motivo = 'Mi mascota no quiere comer desde ayer';

entrarComo($idA, 'cliente');

$r = $citas->agendar(['mascota_id' => $idFiru, 'fecha' => $f, 'hora' => $h1, 'motivo' => $motivo]);
comprobar('A agenda una cita (' . $f . ' ' . $h1 . ')', respondio($r, 'success'), describir($r));
$idC1 = idCita($pdo, $idA, $f . ' ' . $h1 . ':00');
comprobar('La cita quedó pendiente', consultar($pdo, 'SELECT cita_estado FROM cita WHERE cita_id = :id', [':id' => $idC1]) === 'pendiente');
comprobar('Ese horario ya aparece como no disponible', !horarioLibre($citas, $f, $h1));

$r = $citas->agendar(['mascota_id' => $idFiru, 'fecha' => $f, 'hora' => $h1, 'motivo' => $motivo]);
comprobar('A no puede agendar el mismo horario otra vez', respondio($r, 'error', 'ocupado'), describir($r));

entrarComo($idB, 'cliente');

$r = $citas->agendar(['mascota_id' => $idLuna, 'fecha' => $f, 'hora' => $h1, 'motivo' => $motivo]);
comprobar('B no puede agendar el horario que ya tomó A', respondio($r, 'error', 'ocupado'), describir($r));

$r = $citas->agendar(['mascota_id' => $idFiru, 'fecha' => $f, 'hora' => $h2, 'motivo' => $motivo]);
comprobar('B no puede agendar usando la mascota de A', respondio($r, 'error', 'no encontrada'), describir($r));

entrarComo($idA, 'cliente');

$r = $citas->agendar(['mascota_id' => $idFiru, 'fecha' => $domingo, 'hora' => $h1, 'motivo' => $motivo]);
comprobar('Rechaza agendar en domingo', respondio($r, 'error', 'domingos'), describir($r));

$r = $citas->agendar(['mascota_id' => $idFiru, 'fecha' => '2020-01-01', 'hora' => $h1, 'motivo' => $motivo]);
comprobar('Rechaza agendar en una fecha pasada', respondio($r, 'error', 'pasadas'), describir($r));

$r = $citas->agendar(['mascota_id' => $idFiru, 'fecha' => date('Y-m-d', strtotime('+90 days')), 'hora' => $h1, 'motivo' => $motivo]);
comprobar('Rechaza agendar con más de 60 días de anticipación', respondio($r, 'error', 'anticipación'), describir($r));

foreach (['08:00', '17:00', '10:30'] as $horaMala) {
    $r = $citas->agendar(['mascota_id' => $idFiru, 'fecha' => $f, 'hora' => $horaMala, 'motivo' => $motivo]);
    comprobar('Rechaza la hora ' . $horaMala, respondio($r, 'error', 'en punto'), describir($r));
}

$r = $citas->agendar(['mascota_id' => $idFiru, 'fecha' => $f, 'hora' => $h2, 'motivo' => 'dolor']);
comprobar('Rechaza un motivo muy corto', respondio($r, 'error', 'motivo'), describir($r));

$r = $citas->agendar(['mascota_id' => $idFiru, 'fecha' => 'abc', 'hora' => $h2, 'motivo' => $motivo]);
comprobar('Rechaza una fecha que no es fecha', respondio($r, 'error', 'válidas'), describir($r));

$r = $citas->agendar(['fecha' => $f, 'hora' => $h2, 'motivo' => $motivo]);
comprobar('Rechaza una cita sin mascota', respondio($r, 'error', 'no encontrada'), describir($r));

// Modificar
$r = $citas->modificar(['id' => $idC1, 'fecha' => $f, 'hora' => $h2, 'motivo' => $motivo . ' y tiene fiebre']);
comprobar('A modifica su cita a otra hora', respondio($r, 'success'), describir($r));
comprobar('La hora anterior quedó libre', horarioLibre($citas, $f, $h1));
comprobar('La hora nueva quedó ocupada', !horarioLibre($citas, $f, $h2));
comprobar(
    'Modificar una cita aprobada la regresa a pendiente',
    respondio($r, 'success') &&
    consultar(
        $pdo,
        'SELECT cita_estado FROM cita WHERE cita_id = :id',
        [':id' => $idC1]
    ) === 'pendiente',
    describir($r)
);

$pdo->prepare("UPDATE cita SET cita_estado = 'aprobada' WHERE cita_id = :id")->execute([':id' => $idC1]);
$r = $citas->modificar(['id' => $idC1, 'fecha' => $f, 'hora' => $h3, 'motivo' => $motivo]);
comprobar('Modificar una cita aprobada la regresa a pendiente', respondio($r, 'success') && consultar($pdo, 'SELECT cita_estado FROM cita WHERE cita_id = :id', [':id' => $idC1]) === 'pendiente', describir($r));

// Permisos
entrarComo($idB, 'cliente');

$r = $citas->modificar(['id' => $idC1, 'fecha' => $f, 'hora' => $h4, 'motivo' => $motivo]);
comprobar('B NO puede modificar la cita de A', respondio($r, 'error', 'encontrada'), describir($r));

$r = $citas->cancelar(['id' => $idC1]);
comprobar('B NO puede cancelar la cita de A', respondio($r, 'error', 'cancelar'), describir($r));
comprobar('La cita de A sigue pendiente', consultar($pdo, 'SELECT cita_estado FROM cita WHERE cita_id = :id', [':id' => $idC1]) === 'pendiente');

// Cancelar y liberar el horario
entrarComo($idA, 'cliente');

$r = $citas->cancelar(['id' => $idC1]);
comprobar('A cancela su cita', respondio($r, 'success'), describir($r));
comprobar('La cita quedó cancelada', consultar($pdo, 'SELECT cita_estado FROM cita WHERE cita_id = :id', [':id' => $idC1]) === 'cancelada');
comprobar('Su cita_slot quedó en NULL (el horario se liberó)', consultar($pdo, 'SELECT cita_slot FROM cita WHERE cita_id = :id', [':id' => $idC1]) === null);
comprobar('El horario aparece libre otra vez', horarioLibre($citas, $f, $h3));

$r = $citas->cancelar(['id' => $idC1]);
comprobar('No se puede cancelar dos veces', respondio($r, 'error'), describir($r));

$r = $citas->modificar(['id' => $idC1, 'fecha' => $f, 'hora' => $h4, 'motivo' => $motivo]);
comprobar('No se puede modificar una cita cancelada', respondio($r, 'error', 'modificar'), describir($r));

entrarComo($idB, 'cliente');

$r = $citas->agendar(['mascota_id' => $idLuna, 'fecha' => $f, 'hora' => $h3, 'motivo' => $motivo]);
comprobar('B ahora SÍ puede tomar el horario que A liberó', respondio($r, 'success'), describir($r));
$idC2 = idCita($pdo, $idB, $f . ' ' . $h3 . ':00');

// Una cita pasada no se puede modificar ni cancelar
$pdo->prepare("UPDATE cita SET cita_estado = 'aprobada', cita_fecha_hora = DATE_SUB(NOW(), INTERVAL 1 DAY) WHERE cita_id = :id")->execute([':id' => $idC2]);

$r = $citas->modificar(['id' => $idC2, 'fecha' => $f, 'hora' => $h4, 'motivo' => $motivo]);
comprobar('Una cita que ya pasó no se puede modificar', respondio($r, 'error', 'modificar'), describir($r));

$r = $citas->cancelar(['id' => $idC2]);
comprobar('Una cita que ya pasó no se puede cancelar', respondio($r, 'error', 'cancelar'), describir($r));

/* ======================= 6. PANEL DEL ADMINISTRADOR ======================= */
titulo('6. PANEL DEL ADMINISTRADOR');

// Tres citas más del cliente A para trabajar
entrarComo($idA, 'cliente');
$r = $citas->agendar(['mascota_id' => $idFiru, 'fecha' => $f, 'hora' => $h1, 'motivo' => $motivo]);
comprobar('Cita de apoyo 1 creada', respondio($r, 'success'), describir($r));
$r = $citas->agendar(['mascota_id' => $idFiru, 'fecha' => $f, 'hora' => $h2, 'motivo' => $motivo]);
comprobar('Cita de apoyo 2 creada', respondio($r, 'success'), describir($r));
$r = $citas->agendar(['mascota_id' => $idFiru, 'fecha' => $f, 'hora' => $h4, 'motivo' => $motivo]);
comprobar('Cita de apoyo 3 creada', respondio($r, 'success'), describir($r));

$idC3 = idCita($pdo, $idA, $f . ' ' . $h1 . ':00');
$idC4 = idCita($pdo, $idA, $f . ' ' . $h2 . ':00');
$idC5 = idCita($pdo, $idA, $f . ' ' . $h4 . ':00');

$adm = new AdminCitaControlador();
entrarComo($idAdmin, 'admin');

$estadoDe = function (int $id) use ($pdo) {
    return consultar($pdo, 'SELECT cita_estado FROM cita WHERE cita_id = :id', [':id' => $id]);
};

$r = $adm->aprobar(['id' => $idC3]);
comprobar('El administrador aprueba una cita pendiente', respondio($r, 'success') && $estadoDe($idC3) === 'aprobada', describir($r));

$r = $adm->aprobar(['id' => $idC3]);
comprobar('No se puede aprobar una cita ya aprobada', respondio($r, 'error', 'No se pudo aprobar'), describir($r));

$r = $adm->aprobar(['id' => 999999]);
comprobar('Aprobar una cita que no existe da error', respondio($r, 'error', 'encontrada'), describir($r));

$r = $adm->aprobar(['id' => $idC4]);
comprobar('Aprueba otra cita', respondio($r, 'success'), describir($r));

$r = $adm->atender(['id' => $idC4]);
comprobar('No se puede marcar como atendida una cita futura', respondio($r, 'error', 'No se pudo marcar'), describir($r));

$r = $adm->atender(['id' => $idC2]);
comprobar('Marca como atendida una cita aprobada cuya hora ya pasó', respondio($r, 'success') && $estadoDe($idC2) === 'atendida', describir($r));

$r = $adm->atender(['id' => $idC2]);
comprobar('No se puede atender dos veces', respondio($r, 'error'), describir($r));

$r = $adm->cancelar(['id' => $idC2]);
comprobar('No se puede cancelar una cita ya atendida', respondio($r, 'error', 'No se pudo cancelar'), describir($r));

$r = $adm->aprobar(['id' => $idC2]);
comprobar('No se puede aprobar una cita ya atendida', respondio($r, 'error'), describir($r));

$r = $adm->cancelar(['id' => $idC5, 'comentario' => 'Motivo de prueba: la clínica no atenderá ese día']);
comprobar('El administrador cancela una cita con un motivo', respondio($r, 'success') && $estadoDe($idC5) === 'cancelada', describir($r));
comprobar('El horario cancelado quedó libre (cita_slot NULL)', consultar($pdo, 'SELECT cita_slot FROM cita WHERE cita_id = :id', [':id' => $idC5]) === null);
comprobar('El motivo quedó guardado como comentario', consultar($pdo, 'SELECT cita_comentario FROM cita WHERE cita_id = :id', [':id' => $idC5]) === 'Motivo de prueba: la clínica no atenderá ese día');

$r = $adm->comentar(['id' => $idC3, 'comentario' => str_repeat('a', 501)]);
comprobar('Rechaza un comentario de más de 500 caracteres', respondio($r, 'error', 'largo'), describir($r));

$r = $adm->comentar(['id' => $idC3, 'comentario' => 'Trae la cartilla de vacunación']);
comprobar('El administrador deja un comentario', respondio($r, 'success'), describir($r));

$r = $adm->comentar(['id' => $idC4, 'comentario' => 'Comentario temporal']);
$r = $adm->comentar(['id' => $idC4, 'comentario' => '']);
comprobar('Un comentario vacío borra el comentario', respondio($r, 'success') && consultar($pdo, 'SELECT cita_comentario FROM cita WHERE cita_id = :id', [':id' => $idC4]) === null, describir($r));

// Lo que ve el cliente
entrarComo($idA, 'cliente');
$verComentario = false;
$verMotivo     = false;
foreach ($citas->listar() as $fila) {
    if ($fila['cita_comentario'] === 'Trae la cartilla de vacunación') {
        $verComentario = true;
    }
    if ($fila['cita_estado'] === 'cancelada' && str_contains((string) $fila['cita_comentario'], 'Motivo de prueba')) {
        $verMotivo = true;
    }
}
comprobar('El cliente ve el comentario del administrador', $verComentario);
comprobar('El cliente ve el motivo de la cancelación', $verMotivo);

// Lista, filtros y búsqueda
entrarComo($idAdmin, 'admin');

$filtros = $adm->leerFiltros(['estado' => 'hackeado', 'fecha' => 'abc', 'q' => "' OR 1=1 --", 'pagina' => '-5']);
comprobar('Los filtros inválidos se ignoran (estado, fecha, página)', $filtros['estado'] === '' && $filtros['fecha'] === '' && $filtros['pagina'] === 1);
$res = $adm->listar($filtros);
comprobar("Buscar \"' OR 1=1 --\" se trata como texto (no hay inyección SQL)", $res['total'] === 0);

$res = $adm->listar($adm->leerFiltros(['estado' => 'pendiente']));
$todasPendientes = true;
foreach ($res['citas'] as $fila) {
    if ($fila['cita_estado'] !== 'pendiente') {
        $todasPendientes = false;
    }
}
comprobar('El filtro por estado "pendiente" solo muestra pendientes', $todasPendientes);

$res = $adm->listar($adm->leerFiltros(['q' => 'prueba.a@test.local']));
comprobar('La búsqueda por correo encuentra las citas del cliente A', $res['total'] >= 3, 'Encontró: ' . $res['total']);

$res = $adm->listar($adm->leerFiltros(['fecha' => $f]));
comprobar('El filtro por día encuentra las citas de ese día', $res['total'] >= 3, 'Encontró: ' . $res['total']);

$res = $adm->listar($adm->leerFiltros(['pagina' => '9999']));
comprobar('Una página que no existe muestra la última', $res['pagina'] === $res['paginas']);

$resumen = $adm->resumen();
comprobar('El resumen cuenta citas en los 4 estados', isset($resumen['pendiente'], $resumen['aprobada'], $resumen['cancelada'], $resumen['atendida']));
comprobar('El resumen refleja las citas de la prueba', $resumen['aprobada'] >= 2 && $resumen['atendida'] >= 1 && $resumen['cancelada'] >= 2);

$detalle = $adm->obtener($idC3);
comprobar('El detalle trae los datos del cliente y la mascota', $detalle !== null && $detalle['usuario_email'] === 'prueba.a@test.local' && $detalle['mascota_nombre'] === 'Firulais Max');
comprobar('Pedir el detalle de una cita que no existe da null', $adm->obtener(999999) === null);

/* ======================= 7. NAVEGADOR (HTTP REAL) ======================= */
titulo('7. NAVEGADOR: rutas, páginas y permisos (peticiones HTTP reales)');

if (!(bool) ini_get('allow_url_fopen')) {
    comprobar('PHP permite hacer peticiones HTTP (allow_url_fopen)', false, 'Actívalo en php.ini para ejecutar esta sección');
} else {
    $base = APP_URL;

    echo "\n  -- Sin sesión iniciada --\n";

    $r = peticion($base . 'login/');
    comprobar('La página /login/ carga', $r['codigo'] === 200 && str_contains($r['cuerpo'], 'Iniciar sesión') && !hayErrorPHP($r['cuerpo']), 'Código: ' . $r['codigo']);

    $r = peticion($base . 'registro/');
    comprobar('La página /registro/ carga', $r['codigo'] === 200 && str_contains($r['cuerpo'], 'Crear cuenta') && !hayErrorPHP($r['cuerpo']), 'Código: ' . $r['codigo']);

    foreach (['inicio/', 'mascotas/', 'citas/', 'cita-nueva/', 'admin-citas/'] as $ruta) {
        $r = peticion($base . $ruta);
        comprobar('Sin sesión, /' . $ruta . ' manda al login', $r['codigo'] === 302 && str_contains($r['location'], 'login'), 'Código: ' . $r['codigo'] . ' ' . $r['location']);
    }

    $r = peticion($base . 'pagina-que-no-existe/');
    comprobar('Una página inexistente da 404', $r['codigo'] === 404 && str_contains($r['cuerpo'], 'Error 404'), 'Código: ' . $r['codigo'] . ' (si es el 404 de Apache, falla el .htaccess)');

    $r = leerJson(peticion($base . 'app/ajax/usuarioAjax.php')['cuerpo']);
    comprobar('Abrir un archivo AJAX sin enviar datos da "Petición no válida"', respondio($r, 'error', 'no válida'), describir($r));

    foreach (['mascotaAjax', 'citaAjax', 'adminCitaAjax'] as $archivo) {
        $r = ajax($archivo, ['accion' => 'agendar']);
        comprobar($archivo . '.php sin sesión responde "No autorizado"', respondio($r, 'error', 'No autorizado'), describir($r));
    }

    $r = ajax('usuarioAjax', ['accion' => 'registrar', 'nombre' => ['x']]);
    comprobar('Enviar un dato como arreglo (nombre[]=x) se rechaza', respondio($r, 'error', 'no válida'), describir($r) . ' (falta el bloque del Paso 1)');

    $r = ajax('usuarioAjax', ['accion' => 'iniciar', 'email' => 'prueba.a@test.local', 'clave' => 'incorrecta']);
    comprobar('Iniciar sesión con clave incorrecta se rechaza', respondio($r, 'error', 'incorrectos'), describir($r));

    echo "\n  -- Con la sesión del cliente A --\n";

    $login = peticion($base . 'app/ajax/usuarioAjax.php', 'POST', ['accion' => 'iniciar', 'email' => 'prueba.a@test.local', 'clave' => 'Segura1234']);
    $cookieA = $login['cookie'];
    comprobar('A inicia sesión por HTTP y recibe su cookie', respondio(leerJson($login['cuerpo']), 'success') && $cookieA !== '', describir(leerJson($login['cuerpo'])));

    $r = peticion($base . 'inicio/', 'GET', [], $cookieA);
    comprobar('A ve su página de inicio', $r['codigo'] === 200 && str_contains($r['cuerpo'], 'Bienvenido') && str_contains($r['cuerpo'], 'Mis mascotas') && !hayErrorPHP($r['cuerpo']), 'Código: ' . $r['codigo']);

    $r = peticion($base . 'login/', 'GET', [], $cookieA);
    comprobar('Con sesión, /login/ manda al inicio', $r['codigo'] === 302 && str_contains($r['location'], 'inicio'), 'Código: ' . $r['codigo']);

    $paginasCliente = [
        'mascotas/'                         => 'Firulais Max',
        'mascota-editar/?id=' . $idFiru     => 'Guardar cambios',
        'citas/'                            => 'Trae la cartilla',
        'cita-nueva/'                       => 'Elige el día',
        'cita-nueva/?fecha=' . $f           => 'Agendar cita',
        'cita-editar/?id=' . $idC3          => 'Guardar cambios',
        'cita-editar/?id=' . $idC1          => 'ya no se puede modificar',
    ];
    foreach ($paginasCliente as $ruta => $texto) {
        $r = peticion($base . $ruta, 'GET', [], $cookieA);
        comprobar('A abre /' . $ruta, $r['codigo'] === 200 && str_contains($r['cuerpo'], $texto) && !hayErrorPHP($r['cuerpo']), 'Código: ' . $r['codigo'] . ', busca el texto: ' . $texto);
    }

    $r = peticion($base . 'citas/', 'GET', [], $cookieA);
    comprobar('A ve el motivo de cancelación que escribió el administrador', str_contains($r['cuerpo'], 'Motivo de prueba'));

    $r = peticion($base . 'cita-nueva/?fecha=' . $domingo, 'GET', [], $cookieA);
    comprobar('Elegir un domingo muestra el aviso en pantalla', str_contains($r['cuerpo'], 'domingos') && !hayErrorPHP($r['cuerpo']));

    $r = ajax('adminCitaAjax', ['accion' => 'aprobar', 'id' => (string) $idC3], $cookieA);
    comprobar('Un cliente NO puede usar el panel del administrador (AJAX)', respondio($r, 'error', 'No autorizado'), describir($r));

    $r = peticion($base . 'admin-citas/', 'GET', [], $cookieA);
    comprobar('Un cliente NO puede abrir la página del administrador', $r['codigo'] === 302 && str_contains($r['location'], 'inicio'), 'Código: ' . $r['codigo']);

    $r = ajax('usuarioAjax', ['accion' => 'cerrar'], $cookieA);
    comprobar('A cierra sesión', respondio($r, 'success'), describir($r));

    $r = peticion($base . 'inicio/', 'GET', [], $cookieA);
    comprobar('Después de cerrar sesión, /inicio/ manda al login', $r['codigo'] === 302 && str_contains($r['location'], 'login'), 'Código: ' . $r['codigo']);

    echo "\n  -- Con la sesión del administrador --\n";

    $login = peticion($base . 'app/ajax/usuarioAjax.php', 'POST', ['accion' => 'iniciar', 'email' => 'prueba.admin@test.local', 'clave' => 'Segura1234']);
    $cookieAdmin = $login['cookie'];
    comprobar('El administrador inicia sesión por HTTP', respondio(leerJson($login['cuerpo']), 'success') && $cookieAdmin !== '', describir(leerJson($login['cuerpo'])));

    $r = peticion($base . 'inicio/', 'GET', [], $cookieAdmin);
    comprobar('El administrador ve el resumen de citas', $r['codigo'] === 200 && str_contains($r['cuerpo'], 'Resumen de citas') && !hayErrorPHP($r['cuerpo']), 'Código: ' . $r['codigo']);

    $paginasAdmin = [
        'admin-citas/'                       => 'Resultados',
        'admin-citas/?estado=pendiente'      => 'Resultados',
        'admin-citas/?q=prueba.a'            => 'Resultados',
        'admin-citas/?estado=hackeado&pagina=-3' => 'Resultados',
        'admin-cita/?id=' . $idC3            => 'Datos de la cita',
        'admin-cita/?id=' . $idC2            => 'ya no admite cambios',
        'admin-cita/?id=999999'              => 'No se encontró la cita',
    ];
    foreach ($paginasAdmin as $ruta => $texto) {
        $r = peticion($base . $ruta, 'GET', [], $cookieAdmin);
        comprobar('El administrador abre /' . $ruta, $r['codigo'] === 200 && str_contains($r['cuerpo'], $texto) && !hayErrorPHP($r['cuerpo']), 'Código: ' . $r['codigo'] . ', busca el texto: ' . $texto);
    }

    $r = ajax('mascotaAjax', ['accion' => 'registrar', 'nombre' => 'Rex', 'especie' => 'perro', 'edad' => '3'], $cookieAdmin);
    comprobar('El administrador NO puede usar el AJAX de mascotas', respondio($r, 'error', 'No autorizado'), describir($r));

    $r = peticion($base . 'mascotas/', 'GET', [], $cookieAdmin);
    comprobar('El administrador NO puede abrir /mascotas/', $r['codigo'] === 302 && str_contains($r['location'], 'inicio'), 'Código: ' . $r['codigo']);

    $r = ajax('adminCitaAjax', ['accion' => 'comentar', 'id' => (string) $idC3, 'comentario' => 'Comentario por HTTP'], $cookieAdmin);
    comprobar('El administrador comenta una cita por HTTP', respondio($r, 'success'), describir($r));

    $r = ajax('usuarioAjax', ['accion' => 'cerrar'], $cookieAdmin);
    comprobar('El administrador cierra sesión', respondio($r, 'success'), describir($r));
}

/* ======================= 8. CIERRE DE SESIÓN Y LIMPIEZA ======================= */
titulo('8. CIERRE DE SESIÓN Y LIMPIEZA');

entrarComo($idA, 'cliente');
$r = $usuarios->cerrarSesion();
comprobar('Cerrar sesión (controlador) vacía la sesión', respondio($r, 'success') && !SesionControlador::hayUsuario(), describir($r));

limpiar($pdo);
$sobran = (int) consultar($pdo, "SELECT COUNT(*) FROM usuario WHERE usuario_email LIKE 'prueba.%@test.local'");
$sobranMascotas = (int) consultar($pdo, "SELECT COUNT(*) FROM mascota WHERE mascota_id IN ($idFiru, $idLuna)");
comprobar('Los datos de prueba se borraron', $sobran === 0 && $sobranMascotas === 0);

terminar();