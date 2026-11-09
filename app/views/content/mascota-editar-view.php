<?php

use app\controllers\MascotaControlador;
use app\models\Mascota;

$controlador = new MascotaControlador();

// El id viene en la dirección: .../mascota-editar/?id=3
$id = 0;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int) $_GET['id'];
}

$mascota = $controlador->obtener($id);
?>

<h1 class="title">Editar mascota</h1>
<p class="mb-4"><a class="button is-small is-light" href="<?php echo APP_URL; ?>mascotas/">&larr; Volver a la lista</a></p>

<?php if ($mascota === null) { ?>

    <div class="notification is-warning is-light">No se encontró la mascota.</div>

<?php } else { ?>

    <section class="box caja">
        <form class="FormularioAjax" action="<?php echo APP_URL; ?>app/ajax/mascotaAjax.php" method="POST" autocomplete="off">
            <input type="hidden" name="accion" value="actualizar">
            <input type="hidden" name="id" value="<?php echo (int) $mascota->getId(); ?>">

            <div class="columns">
                <div class="column">
                    <div class="field">
                        <label class="label" for="nombre">Nombre</label>
                        <div class="control">
                            <input class="input" type="text" id="nombre" name="nombre" maxlength="40"
                                   value="<?php echo htmlspecialchars($mascota->getNombre()); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="column">
                    <div class="field">
                        <label class="label" for="especie">Tipo de mascota</label>
                        <div class="control">
                            <div class="select is-fullwidth">
                                <select id="especie" name="especie" required>
                                    <?php foreach (Mascota::ESPECIES as $especie) {
                                        $seleccionada = '';
                                        if ($especie === $mascota->getEspecie()) {
                                            $seleccionada = 'selected';
                                        }
                                    ?>
                                        <option value="<?php echo $especie; ?>" <?php echo $seleccionada; ?>><?php echo ucfirst($especie); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="column">
                    <div class="field">
                        <label class="label" for="edad">Edad (años)</label>
                        <div class="control">
                            <input class="input" type="number" id="edad" name="edad" min="0" max="40"
                                   value="<?php echo $mascota->getEdad(); ?>" required>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="button is-link is-fullwidth">Guardar cambios</button>
        </form>
    </section>

<?php } ?>