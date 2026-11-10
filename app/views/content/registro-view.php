<h1>Registro</h1>
<h2>Crear cuenta</h2>

<form class="formulario formulario--registro FormularioAjax" action="<?php echo APP_URL; ?>app/ajax/usuarioAjax.php" method="POST" autocomplete="off">
    <input type="hidden" name="accion" value="registrar">

    <div class="campo campo--3">
        <label for="nombre">Nombre completo:</label>
        <input type="text" id="nombre" name="nombre" maxlength="80" placeholder="Ej. Alicia Saldaña" required>
    </div>

    <div class="campo campo--3">
        <label for="edad">Edad:</label>
        <input type="number" id="edad" name="edad" min="18" max="100" placeholder="Ej. 32" required>
    </div>

    <div class="campo campo--2">
        <label for="email">Correo electrónico:</label>
        <input type="email" id="email" name="email" maxlength="100" placeholder="Ej. login@gmail.com" required>
    </div>

    <div class="campo campo--2">
        <label for="clave">Contraseña:</label>
        <input type="password" id="clave" name="clave" minlength="8" maxlength="72" placeholder="********" required>
    </div>

    <div class="campo campo--2">
        <label for="clave_repetida">Repetir contraseña:</label>
        <input type="password" id="clave_repetida" name="clave_repetida" minlength="8" maxlength="72" placeholder="********" required>
    </div>

    <div class="formulario__acciones">
        <button type="submit" class="boton">Crear cuenta</button>
    </div>
</form>

<p class="acceso__enlace">
    ¿Ya tienes cuenta? <a href="<?php echo APP_URL; ?>login/">Iniciar sesión</a>
</p>