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

$cita        = $citaControlador->obtener($id);
$modificable = ($cita !== null && $cita->esModificable());

if ($modificable) {
    $nombreMascota = '';
    $mascota = $mascotaControlador->obtener($cita->getMascotaId());
    if ($mascota !== null) {
        $nombreMascota = $mascota->getNombre();
    }

    $fechaActual = substr($cita->getFechaHora(), 0, 10);   // AAAA-MM-DD
    $horaActual  = substr($cita->getFechaHora(), 11, 5);   // HH:MM

    // Día elegido: el de la dirección, o el de la cita si aún no se elige otro
    $fecha = $fechaActual;
    if (isset($_GET['fecha']) && is_string($_GET['fecha'])) {
        $fecha = $_GET['fecha'];
    }

    $errorDia  = $citaControlador->errorDia($fecha);
    $horarios  = [];
    $hayLibres = false;

    if ($errorDia === '') {
        // El segundo dato excluye esta misma cita, para que su horario actual aparezca libre
        $horarios = $citaControlador->horarios($fecha, $id);

        foreach ($horarios as $horario) {
            if ($horario['libre']) {
                $hayLibres = true;
            }
        }
    }

    $hoy    = date('Y-m-d');
    $maximo = date('Y-m-d', strtotime('+' . Cita::DIAS_ADELANTE . ' days'));
}
?>

<h1>Modificar cita</h1>
<p><a href="<?php echo APP_URL; ?>citas/">&larr; Volver a mis citas</a></p>

<?php if (!$modificable) { ?>

    <p>No se encontró la cita, o ya no se puede modificar.</p>

<?php } else { ?>

    <p>Mascota: <strong><?php echo htmlspecialchars($nombreMascota); ?></strong></p>
    <p>
        Cita actual: <?php echo date('d/m/Y H:i', strtotime($cita->getFechaHora())); ?>
        (<?php echo $cita->getEstado(); ?>)
    </p>

    <?php if ($cita->getComentario() !== null && $cita->getComentario() !== '') { ?>
        <p>Comentario de la clínica: <?php echo htmlspecialchars($cita->getComentario()); ?></p>
    <?php } ?>

    <p>Si modificas la cita, volverá a quedar <strong>pendiente de aprobación</strong>.</p>

    <h2>1. Elige el día</h2>

    <form action="<?php echo APP_URL; ?>cita-editar/" method="GET">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <input type="date" name="fecha"
               min="<?php echo $hoy; ?>" max="<?php echo $maximo; ?>"
               value="<?php echo htmlspecialchars($fecha); ?>" required>
        <button type="submit">Ver horarios</button>
    </form>

    <?php if ($errorDia !== '') { ?>

        <p><strong><?php echo htmlspecialchars($errorDia); ?></strong> Elige otro día.</p>

    <?php } elseif (!$hayLibres) { ?>

        <p><strong>No hay horarios disponibles ese día.</strong> Elige otro día.</p>

    <?php } else { ?>

        <h2>2. Elige la hora y corrige los síntomas si hace falta</h2>

        <form class="FormularioAjax" data-confirmar="si"
              data-confirmar-texto="¿Deseas guardar los cambios? La cita volverá a quedar pendiente de aprobación."
              action="<?php echo APP_URL; ?>app/ajax/citaAjax.php" method="POST" autocomplete="off">
            <input type="hidden" name="accion" value="modificar">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <input type="hidden" name="fecha" value="<?php echo htmlspecialchars($fecha); ?>">

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
                        } elseif ($fecha === $fechaActual && $horario['hora'] === $horaActual) {
                            $atributo = 'selected';   // la hora que ya tenía la cita
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
                <textarea name="motivo" rows="4" minlength="10" maxlength="500" required><?php echo htmlspecialchars($cita->getMotivo()); ?></textarea>
            </div>

            <button type="submit">Guardar cambios</button>
        </form>

    <?php } ?>

<?php } ?>