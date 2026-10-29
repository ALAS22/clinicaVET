<?php use app\controllers\SesionControlador; ?>

<h1>Bienvenido, <?php echo htmlspecialchars(SesionControlador::getNombre()); ?></h1>
<p>Tu rol: <?php echo htmlspecialchars(SesionControlador::getRol()); ?></p>

<form class="FormularioAjax" data-confirmar="si" action="<?php echo APP_URL; ?>app/ajax/usuarioAjax.php" method="POST">
    <input type="hidden" name="accion" value="cerrar">
    <button type="submit">Cerrar sesión</button>
</form>