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

<h1>Editar mascota</h1>
<p><a href="<?php echo APP_URL; ?>mascotas/">&larr; Volver a mis mascotas</a></p>

<?php if ($mascota === null) { ?>

    <p>No se encontró la mascota.</p>

<?php } else { ?>

    <form class="FormularioAjax" action="<?php echo APP_URL; ?>app/ajax/mascotaAjax.php" method="POST" autocomplete="off">
        <input type="hidden" name="accion" value="actualizar">
        <input type="hidden" name="id" value="<?php echo $mascota->getId(); ?>">

        <div>
            <label>Nombre</label>
            <input type="text" name="nombre" maxlength="40" value="<?php echo htmlspecialchars($mascota->getNombre()); ?>" required>
        </div>

        <div>
            <label>Tipo de animal</label>
            <select name="especie" required>
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

        <div>
            <label>Edad (años)</label>
            <input type="number" name="edad" min="0" max="40" value="<?php echo $mascota->getEdad(); ?>" required>
        </div>

        <button type="submit">Guardar cambios</button>
    </form>

<?php } ?>