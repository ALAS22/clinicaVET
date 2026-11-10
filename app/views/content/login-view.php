<h1>Clínica VET</h1>
<h2>¡Bienvenido!</h2>

<form class="formulario FormularioAjax" action="<?php echo APP_URL; ?>app/ajax/usuarioAjax.php" method="POST" autocomplete="off">
    <input type="hidden" name="accion" value="iniciar">

    <div class="campo">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" maxlength="100" placeholder="login@gmail.com" required>
    </div>

    <div class="campo">
        <label for="clave">Contraseña:</label>
        <input type="password" id="clave" name="clave" maxlength="72" placeholder="************" required>
    </div>

    <button type="submit" class="boton">Entrar</button>
</form>

<p class="acceso__enlace">
    ¿Aún no tienes cuenta? ¡<a href="<?php echo APP_URL; ?>registro/">Regístrate</a>!
</p>