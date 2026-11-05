<h1>Iniciar sesión</h1>

<form class="FormularioAjax" action="<?php echo APP_URL; ?>app/ajax/usuarioAjax.php" method="POST" autocomplete="off">
    <input type="hidden" name="accion" value="iniciar">

    <div>
        <label>Correo electrónico</label>
        <input type="email" name="email" maxlength="100" required>
    </div>

    <div>
        <label>Contraseña</label>
        <input type="password" name="clave" maxlength="72" required>
    </div>

    <button type="submit">Entrar</button>
</form>

<p>¿No tienes cuenta? <a href="<?php echo APP_URL; ?>registro/">Crear cuenta</a></p>