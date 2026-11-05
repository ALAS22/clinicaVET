<?php

use app\controllers\CitaControlador;
use app\controllers\MascotaControlador;
use app\models\Cita;

$citaControlador    = new CitaControlador();
$mascotaControlador = new MascotaControlador();

$mascotas = $mascotaControlador->listar();

// El día elegido viene en la dirección: .../cita-nueva/?fecha=2026-11-02
$fecha = '';
if (isset($_GET['fecha']) && is_string($_GET['fecha'])) {
    $fecha = $_GET['fecha'];
}

$errorDia  = '';
$horarios  = [];
$hayLibres = false;

if ($fecha !== '') {
    $errorDia = $citaControlador->errorDia($fecha);

    if ($errorDia === '') {
        $horarios = $citaControlador->horarios($fecha);

        foreach ($horarios as $horario) {
            if ($horario['libre']) {
                $hayLibres = true;
            }
        }
    }
}

// Límites del calendario (solo una ayuda visual: el servidor vuelve a validar)
$hoy    = date('Y-m-d');
$maximo = date('Y-m-d', strtotime('+' . Cita::DIAS_ADELANTE . ' days'));
?>

<h1>Agendar una cita</h1>
<p><a href="<?php echo APP_URL; ?>citas/">&larr; Volver a mis citas</a></p>

<?php if (empty($mascotas)) { ?>

    <p>Primero debes registrar una mascota para poder agendarle una cita.</p>
    <p><a href="<?php echo APP_URL; ?>mascotas/">Registrar una mascota</a></p>

<?php } else { ?>

    <h2>1. Elige el día</h2>

    <form action="<?php echo APP_URL; ?>cita-nueva/" method="GET">
        <input type="date" name="fecha"
               min="<?php echo $hoy; ?>" max="<?php echo $maximo; ?>"
               value="<?php echo htmlspecialchars($fecha); ?>" required>
        <button type="submit">Ver horarios</button>
    </form>

    <p>Atendemos de lunes a sábado, de 9:00 a 17:00. Cada cita dura una hora.</p>

    <?php if ($fecha !== '') { ?>

        <?php if ($errorDia !== '') { ?>

            <p><strong><?php echo htmlspecialchars($errorDia); ?></strong> Elige otro día.</p>

        <?php } elseif (!$hayLibres) { ?>

            <p><strong>No hay horarios disponibles ese día.</strong> Elige otro día.</p>

        <?php } else { ?>

            <h2>2. Elige la hora y cuéntanos qué le pasa a tu mascota</h2>
            <p>Las horas marcadas como "no disponibles" ya están ocupadas.</p>

            <form class="FormularioAjax" data-confirmar="si"
                  data-confirmar-texto="¿Deseas agendar esta cita?"
                  action="<?php echo APP_URL; ?>app/ajax/citaAjax.php" method="POST" autocomplete="off">
                <input type="hidden" name="accion" value="agendar">
                <input type="hidden" name="fecha" value="<?php echo htmlspecialchars($fecha); ?>">

                <div>
                    <label>Mascota</label>
                    <select name="mascota_id" required>
                        <option value="">Selecciona una opción</option>
                        <?php foreach ($mascotas as $mascota) { ?>
                            <option value="<?php echo $mascota->getId(); ?>">
                                <?php echo htmlspecialchars($mascota->getNombre()); ?>
                                (<?php echo htmlspecialchars($mascota->getEspecie()); ?>)
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div>
                    <label>Hora</label>
                    <select name="hora" required>
                        <option value="">Selecciona una hora</option>
                        <?php foreach ($horarios as $horario) {
                            $atributo = '';
                            $texto    = $horario['hora'];

                            if (!$horario['libre']) {
                                $atributo = 'disabled';
                                $texto    = $texto . ' (no disponible)';
                            }
                        ?>
                            <option value="<?php echo $horario['hora']; ?>" <?php echo $atributo; ?>>
                                <?php echo $texto; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div>
                    <label>Síntomas o motivo de la cita</label>
                    <textarea name="motivo" rows="4" minlength="10" maxlength="500" required></textarea>
                </div>

                <button type="submit">Agendar cita</button>
            </form>

        <?php } ?>

    <?php } ?>

<?php } ?>