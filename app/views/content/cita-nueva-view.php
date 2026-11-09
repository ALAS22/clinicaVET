<?php

use app\controllers\CitaControlador;
use app\controllers\MascotaControlador;
use app\models\Cita;

$citaControlador    = new CitaControlador();
$mascotaControlador = new MascotaControlador();

$mascotas = $mascotaControlador->listar();

// Mascota preseleccionada: .../cita-nueva/?mascota=3 (viene del botón "Agendar cita" de la lista)
$mascotaElegida = 0;
if (isset($_GET['mascota']) && is_numeric($_GET['mascota'])) {
    $mascotaElegida = (int) $_GET['mascota'];
}

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

<h1 class="title">Agendar una cita</h1>
<p class="mb-4"><a class="button is-small is-light" href="<?php echo APP_URL; ?>citas/">&larr; Volver a mis citas</a></p>

<?php if (empty($mascotas)) { ?>

    <div class="notification is-warning is-light">
        Primero debes registrar una mascota para poder agendarle una cita.
        <a href="<?php echo APP_URL; ?>mascotas/">Registrar una mascota</a>
    </div>

<?php } else { ?>

    <section class="box caja">
        <h2 class="title is-5">1. Elige el día</h2>

        <form action="<?php echo APP_URL; ?>cita-nueva/" method="GET">
            <input type="hidden" name="mascota" value="<?php echo $mascotaElegida; ?>">

            <div class="field has-addons">
                <div class="control is-expanded">
                    <input class="input" type="date" name="fecha"
                           min="<?php echo $hoy; ?>" max="<?php echo $maximo; ?>"
                           value="<?php echo htmlspecialchars($fecha); ?>" required>
                </div>
                <div class="control">
                    <button type="submit" class="button is-primary">Ver horarios</button>
                </div>
            </div>
        </form>

        <p class="help">Atendemos de lunes a sábado, de 9:00 a 17:00. Cada cita dura una hora.</p>
    </section>

    <?php if ($fecha !== '') { ?>

        <?php if ($errorDia !== '') { ?>

            <div class="notification is-warning is-light">
                <strong><?php echo htmlspecialchars($errorDia); ?></strong> Elige otro día.
            </div>

        <?php } elseif (!$hayLibres) { ?>

            <div class="notification is-warning is-light">
                <strong>No hay horarios disponibles ese día.</strong> Elige otro día.
            </div>

        <?php } else { ?>

            <section class="box caja">
                <h2 class="title is-5">2. Elige la hora y cuéntanos qué le pasa a tu mascota</h2>

                <form class="FormularioAjax" data-confirmar="si" data-confirmar-texto="¿Deseas agendar esta cita?"
                      action="<?php echo APP_URL; ?>app/ajax/citaAjax.php" method="POST" autocomplete="off">
                    <input type="hidden" name="accion" value="agendar">
                    <input type="hidden" name="fecha" value="<?php echo htmlspecialchars($fecha); ?>">

                    <div class="field">
                        <label class="label" for="mascota_id">Mascota</label>
                        <div class="control">
                            <div class="select is-fullwidth">
                                <select id="mascota_id" name="mascota_id" required>
                                    <option value="">Selecciona una opción</option>
                                    <?php foreach ($mascotas as $mascota) {
                                        $seleccionada = '';
                                        if ($mascota->getId() === $mascotaElegida) {
                                            $seleccionada = 'selected';
                                        }
                                    ?>
                                        <option value="<?php echo (int) $mascota->getId(); ?>" <?php echo $seleccionada; ?>>
                                            <?php echo htmlspecialchars($mascota->getNombre()); ?>
                                            (<?php echo htmlspecialchars($mascota->getEspecie()); ?>)
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="field">
                        <label class="label">Hora</label>
                        <div class="horarios">
                            <?php foreach ($horarios as $horario) {
                                $atributo = '';
                                $ayuda    = '';

                                if (!$horario['libre']) {
                                    $atributo = 'disabled';
                                    $ayuda    = ' title="No disponible"';
                                }
                            ?>
                                <label class="horario"<?php echo $ayuda; ?>>
                                    <input type="radio" name="hora" value="<?php echo $horario['hora']; ?>" <?php echo $atributo; ?> required>
                                    <span><?php echo $horario['hora']; ?></span>
                                </label>
                            <?php } ?>
                        </div>
                        <p class="help">Las horas tachadas ya están ocupadas.</p>
                    </div>

                    <div class="field">
                        <label class="label" for="motivo">Síntomas o motivo de la cita</label>
                        <div class="control">
                            <textarea class="textarea" id="motivo" name="motivo" rows="4" minlength="10" maxlength="500"
                                      placeholder="Ej. No quiere comer desde ayer" required></textarea>
                        </div>
                    </div>

                    <button type="submit" class="button is-primary is-fullwidth">Agendar cita</button>
                </form>
            </section>

        <?php } ?>

    <?php } ?>

<?php } ?>