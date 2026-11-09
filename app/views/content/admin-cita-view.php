<?php

use app\controllers\AdminCitaControlador;
use app\models\Cita;

$controlador = new AdminCitaControlador();

// El id viene en la dirección: .../admin-cita/?id=3
$id = 0;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int) $_GET['id'];
}

$cita = $controlador->obtener($id);
?>

<h1>Gestionar cita</h1>
<p><a href="<?php echo APP_URL; ?>admin-citas/">&larr; Volver a la lista de citas</a></p>

<?php if ($cita === null) { ?>

    <p>No se encontró la cita.</p>

<?php } else {
    $estado  = $cita['cita_estado'];
    $momento = strtotime($cita['cita_fecha_hora']);

    // Qué acciones tienen sentido según el estado y la hora
    $puedeAprobar  = ($estado === Cita::PENDIENTE && $momento > time());
    $puedeAtender  = ($estado === Cita::APROBADA && $momento <= time());
    $puedeCancelar = in_array($estado, [Cita::PENDIENTE, Cita::APROBADA], true);
?>

    <h2>Datos de la cita</h2>
    <p>Estado: <strong><?php echo ucfirst($estado); ?></strong></p>
    <p>Fecha y hora: <?php echo date('d/m/Y H:i', $momento); ?></p>
    <p>
        Cliente: <?php echo htmlspecialchars($cita['usuario_nombre']); ?>
        (<?php echo (int) $cita['usuario_edad']; ?> años) &middot;
        <?php echo htmlspecialchars($cita['usuario_email']); ?>
    </p>
    <p>
        Mascota: <?php echo htmlspecialchars($cita['mascota_nombre']); ?>,
        <?php echo htmlspecialchars($cita['mascota_especie']); ?>,
        <?php echo (int) $cita['mascota_edad']; ?> años
    </p>
    <p>Síntomas o motivo:</p>
    <p><?php echo nl2br(htmlspecialchars($cita['cita_motivo'])); ?></p>

    <h2>Cambiar el estado</h2>

    <?php if ($puedeAprobar) { ?>
        <form class="FormularioAjax" data-confirmar="si" data-confirmar-texto="¿Aprobar esta cita?"
              action="<?php echo APP_URL; ?>app/ajax/adminCitaAjax.php" method="POST">
            <input type="hidden" name="accion" value="aprobar">
            <input type="hidden" name="id" value="<?php echo (int) $cita['cita_id']; ?>">
            <button type="submit">Aprobar cita</button>
        </form>
    <?php } ?>

    <?php if ($puedeAtender) { ?>
        <form class="FormularioAjax" data-confirmar="si" data-confirmar-texto="¿Marcar esta cita como atendida?"
              action="<?php echo APP_URL; ?>app/ajax/adminCitaAjax.php" method="POST">
            <input type="hidden" name="accion" value="atender">
            <input type="hidden" name="id" value="<?php echo (int) $cita['cita_id']; ?>">
            <button type="submit">Marcar como atendida</button>
        </form>
    <?php } ?>

    <?php if ($estado === Cita::APROBADA && !$puedeAtender) { ?>
        <p>Podrás marcarla como atendida cuando llegue su hora.</p>
    <?php } ?>

    <?php if ($puedeCancelar) { ?>
        <form class="FormularioAjax" data-confirmar="si"
              data-confirmar-texto="¿Cancelar esta cita? El horario quedará libre."
              action="<?php echo APP_URL; ?>app/ajax/adminCitaAjax.php" method="POST">
            <input type="hidden" name="accion" value="cancelar">
            <input type="hidden" name="id" value="<?php echo (int) $cita['cita_id']; ?>">

            <label>Motivo de la cancelación (opcional, el cliente lo verá)</label><br>
            <textarea name="comentario" rows="3" maxlength="500"></textarea><br>

            <button type="submit">Cancelar cita</button>
        </form>
    <?php } ?>

    <?php if (!$puedeAprobar && !$puedeAtender && !$puedeCancelar) { ?>
        <p>Esta cita ya no admite cambios de estado.</p>
    <?php } ?>

    <h2>Comentario para el cliente</h2>

    <form class="FormularioAjax" action="<?php echo APP_URL; ?>app/ajax/adminCitaAjax.php" method="POST">
        <input type="hidden" name="accion" value="comentar">
        <input type="hidden" name="id" value="<?php echo (int) $cita['cita_id']; ?>">

        <textarea name="comentario" rows="4" maxlength="500"><?php echo htmlspecialchars((string) $cita['cita_comentario']); ?></textarea><br>

        <button type="submit">Guardar comentario</button>
    </form>
    <p>Si dejas el cuadro vacío y guardas, se borra el comentario.</p>

<?php } ?>