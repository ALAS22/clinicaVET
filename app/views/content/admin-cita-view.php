<?php

use app\controllers\AdminCitaControlador;
use app\models\Cita;

$controlador = new AdminCitaControlador();

// El id viene en la direccion: .../admin-cita/?id=3
$id = 0;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int) $_GET['id'];
}

$cita = $controlador->obtener($id);


$opciones = [];
$etiquetas = [
    Cita::PENDIENTE => 'is-warning',
    Cita::APROBADA  => 'is-success',
    Cita::ATENDIDA  => 'is-info',
    Cita::CANCELADA => 'is-danger',
];

if ($cita !== null) {
    $opciones = $controlador->opcionesDeEstado($cita);
}
?>

<h1 class="title">Detalle de la cita</h1>

<?php if ($cita === null) { ?>

    <div class="notification is-warning is-light">No se encontró la cita.</div>
    <a class="button is-light" href="<?php echo APP_URL; ?>admin-citas/">&larr; Volver a la lista</a>

<?php } else {
    $estado         = $cita['cita_estado'];
    $puedeCancelar  = ($estado !== Cita::CANCELADA && in_array(Cita::CANCELADA, $opciones, true));
    $menuDeshabilitado = '';
    if (count($opciones) === 1) {
        $menuDeshabilitado = 'disabled';
    }
?>

    <div class="columns">

        <!-- Columna izquierda: los datos de la cita -->
        <div class="column">
            <section class="box caja">
                <p class="has-text-weight-bold">Cliente</p>
                <p class="mb-4">
                    <?php echo htmlspecialchars($cita['usuario_nombre']); ?>
                    (<?php echo (int) $cita['usuario_edad']; ?> años)
                    &middot; <?php echo htmlspecialchars($cita['usuario_email']); ?>
                </p>

                <p class="has-text-weight-bold">Mascota</p>
                <p class="mb-4">
                    <?php echo htmlspecialchars($cita['mascota_nombre']); ?> |
                    <?php echo htmlspecialchars(ucfirst($cita['mascota_especie'])); ?> |
                    <?php echo (int) $cita['mascota_edad']; ?> años
                </p>

                <p class="has-text-weight-bold">Fecha y hora</p>
                <p class="mb-4"><?php echo date('d/m/Y H:i', strtotime($cita['cita_fecha_hora'])); ?></p>

                <p class="has-text-weight-bold">Motivo</p>
                <p class="mb-4"><?php echo nl2br(htmlspecialchars($cita['cita_motivo'])); ?></p>

                <p class="has-text-weight-bold">Estado actual</p>
                <span class="tag is-rounded <?php echo $etiquetas[$estado]; ?>"><?php echo ucfirst($estado); ?></span>
            </section>

            <a class="button is-light" href="<?php echo APP_URL; ?>admin-citas/">&larr; Volver a la lista</a>
        </div>

        <!-- Columna derecha: comentario, estado y botones -->
        <div class="column">
            <section class="box caja">
                <form class="FormularioAjax" action="<?php echo APP_URL; ?>app/ajax/adminCitaAjax.php" method="POST" autocomplete="off">
                    <input type="hidden" name="id" value="<?php echo (int) $cita['cita_id']; ?>">

                    <div class="field">
                        <label class="label" for="comentario">Comentario para el cliente</label>
                        <div class="control">
                            <textarea class="textarea" id="comentario" name="comentario" rows="5" maxlength="500"><?php echo htmlspecialchars((string) $cita['cita_comentario']); ?></textarea>
                        </div>
                        <p class="help">Si lo dejas vacío y guardas, se borra el comentario.</p>
                    </div>

                    <div class="field">
                        <label class="label" for="estado">Estado</label>
                        <div class="control">
                            <div class="select is-fullwidth">
                                <select id="estado" name="estado" <?php echo $menuDeshabilitado; ?>>
                                    <?php foreach ($opciones as $opcion) {
                                        $seleccionada = '';
                                        if ($opcion === $estado) {
                                            $seleccionada = 'selected';
                                        }
                                    ?>
                                        <option value="<?php echo $opcion; ?>" <?php echo $seleccionada; ?>><?php echo ucfirst($opcion); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <?php if (count($opciones) === 1) { ?>
                            <p class="help">Esta cita ya no admite cambios de estado.</p>
                        <?php } ?>
                    </div>

                    <button type="submit" name="accion" value="guardar" class="button is-success is-light is-fullwidth mb-3">
                        Guardar cambios
                    </button>

                    <?php if ($puedeCancelar) { ?>
                        <button type="submit" name="accion" value="cancelar" class="button is-danger is-light is-fullwidth"
                                data-confirmar="si"
                                data-confirmar-texto="¿Cancelar esta cita? El horario quedará libre y el cliente verá tu comentario.">
                            Cancelar cita
                        </button>
                    <?php } ?>
                </form>
            </section>
        </div>

    </div>

<?php } ?>