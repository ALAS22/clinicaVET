<?php

use app\controllers\CitaControlador;
use app\controllers\MascotaControlador;
use app\models\Cita;

$citaControlador    = new CitaControlador();
$mascotaControlador = new MascotaControlador();

// El id viene en la dirección: .../cita-editar/?id=3
$id = 0;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int) $_GET['id'];
}

$cita = $citaControlador->obtener($id);


$nombreMascota = '';
$fechaActual   = '';
$horaActual    = '';
$fecha         = '';
$errorDia      = '';
$horarios      = [];
$hayLibres     = false;
$hoy           = date('Y-m-d');
$maximo        = date('Y-m-d', strtotime('+' . Cita::DIAS_ADELANTE . ' days'));

if ($cita !== null && $cita->esModificable()) {
    $mascota = $mascotaControlador->obtener($cita->getMascotaId());
    if ($mascota !== null) {
        $nombreMascota = $mascota->getNombre();
    }

    $fechaActual = substr($cita->getFechaHora(), 0, 10);   // AAAA-MM-DD
    $horaActual  = substr($cita->getFechaHora(), 11, 5);   // HH:MM

    //dia elegido
    $fecha = $fechaActual;
    if (isset($_GET['fecha']) && is_string($_GET['fecha'])) {
        $fecha = $_GET['fecha'];
    }

    $errorDia = $citaControlador->errorDia($fecha);

    if ($errorDia === '') {
        //horario actual aparezca libre
        $horarios = $citaControlador->horarios($fecha, $id);

        foreach ($horarios as $horario) {
            if ($horario['libre']) {
                $hayLibres = true;
            }
        }
    }
}
?>

<h1 class="title">Modificar cita</h1>
<p class="mb-4"><a class="button is-small is-light" href="<?php echo APP_URL; ?>citas/">&larr; Volver a mis citas</a></p>

<?php if ($cita === null || !$cita->esModificable()) { ?>

    <div class="notification is-warning is-light">No se encontró la cita, o ya no se puede modificar.</div>

<?php } else { ?>

    <section class="box caja">
        <p>Mascota: <strong><?php echo htmlspecialchars($nombreMascota); ?></strong></p>
        <p>
            Cita actual: <strong><?php echo date('d/m/Y H:i', strtotime($cita->getFechaHora())); ?></strong>
            (<?php echo $cita->getEstado(); ?>)
        </p>

        <?php if ($cita->getComentario() !== null && $cita->getComentario() !== '') { ?>
            <p class="mt-3"><strong>Comentario de la clínica:</strong> <?php echo htmlspecialchars($cita->getComentario()); ?></p>
        <?php } ?>
    </section>

    <div class="notification is-warning is-light">
        Si modificas la cita, volverá a quedar <strong>pendiente de aprobación</strong>.
    </div>

    <section class="box caja">
        <h2 class="title is-5">1. Elige el día</h2>

        <form action="<?php echo APP_URL; ?>cita-editar/" method="GET">
            <input type="hidden" name="id" value="<?php echo $id; ?>">

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
    </section>

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
            <h2 class="title is-5">2. Elige la hora y corrige los síntomas si hace falta</h2>

            <form class="FormularioAjax" data-confirmar="si"
                  data-confirmar-texto="¿Deseas guardar los cambios? La cita volverá a quedar pendiente de aprobación."
                  action="<?php echo APP_URL; ?>app/ajax/citaAjax.php" method="POST" autocomplete="off">
                <input type="hidden" name="accion" value="modificar">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <input type="hidden" name="fecha" value="<?php echo htmlspecialchars($fecha); ?>">

                <div class="field">
                    <label class="label">Hora</label>
                    <div class="horarios">
                        <?php foreach ($horarios as $horario) {
                            $atributo = '';
                            $ayuda    = '';

                            if (!$horario['libre']) {
                                $atributo = 'disabled';
                                $ayuda    = ' title="No disponible"';
                            } elseif ($fecha === $fechaActual && $horario['hora'] === $horaActual) {
                                $atributo = 'checked';   // la hora que ya tenía la cita
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
                        <textarea class="textarea" id="motivo" name="motivo" rows="4" minlength="10" maxlength="500" required><?php echo htmlspecialchars($cita->getMotivo()); ?></textarea>
                    </div>
                </div>

                <button type="submit" class="button is-primary is-fullwidth">Guardar cambios</button>
            </form>
        </section>

    <?php } ?>

<?php } ?>