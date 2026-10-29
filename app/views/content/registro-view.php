<h1>Crear cuenta</h1>

<form class="FormularioAjax" action="<?php echo APP_URL; ?>app/ajax/usuarioAjax.php" method="POST" autocomplete="off">
    <input type="hidden" name="accion" value="registrar">

    <div>
        <label>Nombre completo</label>
        <input type="text" name="nombre" maxlength="80" required>
    </div>

    <div>
        <label>Edad</label>
        <input type="number" name="edad" min="18" max="100" required>
    </div>

    <div>
        <label>Correo electrónico</label>
        <input type="email" name="email" maxlength="100" required>
    </div>

    <div>
        <label>Contraseña</label>
        <input type="password" name="clave" minlength="8" maxlength="72" required>
    </div>

    <div>
        <label>Repetir contraseña</label>
        <input type="password" name="clave_repetida" minlength="8" maxlength="72" required>
    </div>

    <button type="submit">Crear cuenta</button>
</form>

<p>¿Ya tienes cuenta? <a href="<?php echo APP_URL; ?>login/">Iniciar sesión</a></p>