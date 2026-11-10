<?php

use app\controllers\CitaControlador;
use app\models\Cita;

$controlador = new CitaControlador();
$todas       = $controlador->listar();

// Pendientes = citas activas (pendiente o aprobada) que aún no llegan; Historial = el resto
$pendientes = [];
$historial  = [];

foreach ($todas as $cita) {
    $activa = in_array($cita['cita_estado'], [Cita::PENDIENTE, Cita::APROBADA], true);

    if ($activa && strtotime($cita['cita_fecha_hora']) > time()) {
        $pendientes[] = $cita;
    } else {
        $historial[] = $cita;
    }
}

// listar() trae las más lejanas primero; en Pendientes queremos las más próximas primero
$pendientes = array_reverse($pendientes);

// Pestaña elegida: .../citas/?ver=historial
$ver = 'pendientes';
if (isset($_GET['ver']) && $_GET['ver'] === 'historial') {
    $ver = 'historial';
}

$citas        = $pendientes;
$clasePend    = 'is-active';
$claseHist    = '';
if ($ver === 'historial') {
    $citas     = $historial;
    $clasePend = '';
    $claseHist = 'is-active';
}

// Color de la etiqueta de cada estado (los define el tema de Bulma en estilos.css)
$etiquetas = [
    Cita::PENDIENTE => 'is-warning',
    Cita::APROBADA  => 'is-success',
    Cita::ATENDIDA  => 'is-info',
    Cita::CANCELADA => 'is-danger',
];
?>

<div class="is-flex is-flex-wrap-wrap is-justify-content-space-between is-align-items-center mb-4">
    <h1 class="title mb-0">Mis citas</h1>
    <a class="button is-primary" href="<?php echo APP_URL; ?>cita-nueva/">Agendar una cita</a>
</div>

<div class="tabs">
    <ul>
        <li class="<?php echo $clasePend; ?>">
            <a href="<?php echo APP_URL; ?>citas/">Pendientes (<?php echo count($pendientes); ?>)</a>
        </li>
        <li class="<?php echo $claseHist; ?>">
            <a href="<?php echo APP_URL; ?>citas/?ver=historial">Historial (<?php echo count($historial); ?>)</a>
        </li>
    </ul>
</div>

<?php if (empty($citas)) { ?>

    <div class="notification is-light">
        <?php if ($ver === 'historial') { ?>
            Todavía no tienes citas en tu historial.
        <?php } else { ?>
            No tienes citas pendientes.
        <?php } ?>
    </div>

<?php } else { ?>

    <div class="table-container">
        <table class="table tabla tabla-apilada is-fullwidth is-bordered    ">
            <thead>
                <tr>
                    <td data-label="Mascota">Mascota</td>
                    <td data-label="Fecha">Fecha</td>
                    <td class="texto-izquierda" data-label="Motivo">Motivo</td>
                    <td data-label="Estado">Estado</td>
                    <td class="texto-izquierda" data-label="Comentario">Comentario</td>
                    <?php if ($ver === 'pendientes') { ?>
                        <td data-label="Opciones">Opciones</td>
                    <?php } ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($citas as $cita) {
                    $comentario = (string) $cita['cita_comentario'];
                    if ($comentario === '') {
                        $comentario = '—';
                    }
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($cita['mascota_nombre']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($cita['cita_fecha_hora'])); ?></td>
                        <td class="texto-izquierda"><?php echo htmlspecialchars($cita['cita_motivo']); ?></td>
                        <td>
                            <span class="tag is-rounded <?php echo $etiquetas[$cita['cita_estado']]; ?>">
                                <?php echo ucfirst($cita['cita_estado']); ?>
                            </span>
                        </td>
                        <td class="texto-izquierda"><?php echo htmlspecialchars($comentario); ?></td>

                        <?php if ($ver === 'pendientes') { ?>
                            <td>
                                <div class="buttons is-justify-content-center">
                                    <a class="button is-small is-link" href="<?php echo APP_URL; ?>cita-editar/?id=<?php echo (int) $cita['cita_id']; ?>">Modificar</a>

                                    <form class="FormularioAjax" data-confirmar="si"
                                          data-confirmar-texto="¿Seguro que quieres cancelar esta cita? El horario quedará libre."
                                          action="<?php echo APP_URL; ?>app/ajax/citaAjax.php" method="POST">
                                        <input type="hidden" name="accion" value="cancelar">
                                        <input type="hidden" name="id" value="<?php echo (int) $cita['cita_id']; ?>">
                                        <button type="submit" class="button is-small is-danger">Cancelar</button>
                                    </form>
                                </div>
                            </td>
                        <?php } ?>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

<?php } ?>