<?php

use app\controllers\MascotaControlador;
use app\models\Mascota;

$controlador = new MascotaControlador();
$mascotas = $controlador->listar();
?>

<h1>Mis mascotas</h1>
<p><a href="<?php echo APP_URL; ?>inicio/">&larr; Volver al inicio</a></p>

<h2>Registrar una mascota</h2>

<form class="FormularioAjax" action="<?php echo APP_URL; ?>app/ajax/mascotaAjax.php" method="POST" autocomplete="off">
    <input type="hidden" name="accion" value="registrar">

    <div>
        <label>Nombre</label>
        <input type="text" name="nombre" maxlength="40" required>
    </div>

    <div>
        <label>Tipo de animal</label>
        <select name="especie" required>
            <option value="">Selecciona una opción</option>
            <?php foreach (Mascota::ESPECIES as $especie) { ?>
                <option value="<?php echo $especie; ?>"><?php echo ucfirst($especie); ?></option>
            <?php } ?>
        </select>
    </div>

    <div>
        <label>Edad (años)</label>
        <input type="number" name="edad" min="0" max="40" required>
    </div>

    <button type="submit">Registrar</button>
</form>

<h2>Mascotas registradas</h2>

<?php if (empty($mascotas)) { ?>

    <p>Todavía no has registrado ninguna mascota.</p>

<?php } else { ?>

    <table border="1" cellpadding="6">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Edad</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($mascotas as $mascota) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($mascota->getNombre()); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($mascota->getEspecie())); ?></td>
                    <td><?php echo $mascota->getEdad(); ?> años</td>
                    <td>
                        <a href="<?php echo APP_URL; ?>mascota-editar/?id=<?php echo $mascota->getId(); ?>">Editar</a>

                        <form class="FormularioAjax" data-confirmar="si" action="<?php echo APP_URL; ?>app/ajax/mascotaAjax.php" method="POST">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id" value="<?php echo $mascota->getId(); ?>">
                            <button type="submit">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

<?php } ?>