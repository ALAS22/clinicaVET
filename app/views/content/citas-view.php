<?php

use app\controllers\CitaControlador;
use app\models\Cita;

$controlador = new CitaControlador();
$citas = $controlador->listar();
?>

<h1>Mis citas</h1>
<p><a href="<?php echo APP_URL; ?>inicio/">&larr; Volver al inicio</a></p>
<p><a href="<?php echo APP_URL; ?>cita-nueva/">Agendar una cita nueva</a></p>

<?php if (empty($citas)) { ?>

    <p>Todavía no tienes citas.</p>

<?php } else { ?>

    <p>Una cita <strong>pendiente</strong> está esperando la aprobación de la clínica.</p>

    <table border="1" cellpadding="6">
        <thead>
            <tr>
                <th>Mascota</th>
                <th>Fecha y hora</th>
                <th>Motivo</th>
                <th>Estado</th>
                <th>Comentario de la clínica</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($citas as $cita) {
                $momento = strtotime($cita['cita_fecha_hora']);
                $activa  = in_array($cita['cita_estado'], [Cita::PENDIENTE, Cita::APROBADA], true);

                // Solo se puede modificar o cancelar una cita activa que aún no llega
                $puedeCambiar = $activa && $momento > time();
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($cita['mascota_nombre']); ?></td>
                    <td><?php echo date('d/m/Y H:i', $momento); ?></td>
                    <td><?php echo htmlspecialchars($cita['cita_motivo']); ?></td>
                    <td><?php echo ucfirst($cita['cita_estado']); ?></td>
                    <td><?php echo htmlspecialchars((string) $cita['cita_comentario']); ?></td>
                    <td>
                        <?php if ($puedeCambiar) { ?>
                            <a href="<?php echo APP_URL; ?>cita-editar/?id=<?php echo (int) $cita['cita_id']; ?>">Modificar</a>

                            <form class="FormularioAjax" data-confirmar="si"
                                  data-confirmar-texto="¿Seguro que quieres cancelar esta cita? El horario quedará libre."
                                  action="<?php echo APP_URL; ?>app/ajax/citaAjax.php" method="POST">
                                <input type="hidden" name="accion" value="cancelar">
                                <input type="hidden" name="id" value="<?php echo (int) $cita['cita_id']; ?>">
                                <button type="submit">Cancelar cita</button>
                            </form>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

<?php } ?>

/**
 * Lo que ve el cliente del administrador: la columna comentario de la clinica muestra lo que el administrador escriba,
 *  y el Estado cambia cuando la apruebe. Con eso se cumple lo de “cualquier cosa que el administrador edite,
 *  el cliente podrá visualizarlo”.
 * a
 * s
 */