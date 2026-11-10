<?php
use app\controllers\SesionControlador;
use app\models\Usuario;
/** @var string $vista Página actual. La define index.php antes de incluir este archivo */
if (SesionControlador::getRol() === Usuario::ROL_ADMIN) {
    $claseBarra = 'barra--admin';
    $enlaces = [
        'admin-citas/',
        'Citas/',
        ['admin-citas/','admin-cita'],
    ];
}else{
    $claseBarra = 'barra--admin';
    $enlaces =[
        ['mascotas/','Mascotas y citas',['mascotas','mascota-editar']],
        ['citas/','Mis citas',['citas','citas-nueva','cita-editar']],
    ];
}
?>

<nav class="barra <?php echo $claseBarra ?> is-flex is-flex-wrap-wrap is-align-items-center is-justify-content-space-between px-5 py-3">

<div class="is-flex is-flex-wrap-wrap is-align-items-center">
    <span class="barra__titulo mr-4"><?php echo htmlspecialchars(APP_NAME); ?></span>

    <?php foreach($enlaces as $enlace){
        $clase = 'button is-small is-white is-outlined';
            if (in_array($vista, $enlace[2], true)) {
                $clase = 'button is-small is-white';
            }
    } ?>
     <a class="<?php echo $clase; ?> mr-2" href="<?php echo APP_URL . $enlace[0]; ?>"><?php echo $enlace[1]; ?></a>
</div>
<div class="is-flex is-align-items-center">
        <span class="barra__usuario mr-3"><?php echo htmlspecialchars(SesionControlador::getNombre()); ?></span>

        <form class="FormularioAjax" data-confirmar="si" data-confirmar-texto="La sesión actual se cerrará."
              action="<?php echo APP_URL; ?>app/ajax/usuarioAjax.php" method="POST">
            <input type="hidden" name="accion" value="cerrar">
            <button type="submit" class="button is-small is-white is-outlined">Salir</button>
        </form>
    </div>

</nav>