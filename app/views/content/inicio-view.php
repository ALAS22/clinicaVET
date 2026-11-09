<?php use app\controllers\SesionControlador;
use app\models\Usuario;
use app\controllers\AdminCitaControlador;
 ?>

<h1>Bienvenido, <?php echo htmlspecialchars(SesionControlador::getNombre()); ?></h1>
<p>Tu rol: <?php echo htmlspecialchars(SesionControlador::getRol()); ?></p>

<?php if (SesionControlador::getRol() === Usuario::ROL_CLIENTE) { ?>
    <p><a href="<?php echo APP_URL; ?>mascotas/">Mis mascotas</a></p>
    <p><a href="<?php echo APP_URL; ?>citas/">Mis citas</a></p>
<?php } ?>

<?php if (SesionControlador::getRol() === Usuario::ROL_ADMIN) {
    $adminControlador = new AdminCitaControlador();
    $resumen = $adminControlador->resumen();
?>
    <h2>Resumen de citas</h2>
    <ul>
        <li>Pendientes de aprobar: <strong><?php echo $resumen['pendiente']; ?></strong></li>
        <li>Aprobadas: <?php echo $resumen['aprobada']; ?></li>
        <li>Atendidas: <?php echo $resumen['atendida']; ?></li>
        <li>Canceladas: <?php echo $resumen['cancelada']; ?></li>
    </ul>

    <p><a href="<?php echo APP_URL; ?>admin-citas/?estado=pendiente">Ver citas pendientes</a></p>
    <p><a href="<?php echo APP_URL; ?>admin-citas/">Ver todas las citas</a></p>
<?php } ?>

<form class="FormularioAjax" data-confirmar="si" action="<?php echo APP_URL; ?>app/ajax/usuarioAjax.php" method="POST">
    <input type="hidden" name="accion" value="cerrar">
    <button type="submit">Cerrar sesión</button>
</form>