<?php

use app\controllers\MascotaControlador;
use app\models\Mascota;

$controlador = new MascotaControlador();
$mascotas    = $controlador->listar();
?>

<h1 class="title">Mascotas y citas</h1>

<section class="box caja">
    <h2 class="title is-5">Registrar mascota</h2>

    <form class="FormularioAjax" action="<?php echo APP_URL; ?>app/ajax/mascotaAjax.php" method="POST" autocomplete="off">
        <input type="hidden" name="accion" value="registrar">

        <div class="columns">
            <div class="column">
                <div class="field">
                    <label class="label" for="nombre">Nombre</label>
                    <div class="control">
                        <input class="input" type="text" id="nombre" name="nombre" maxlength="40" placeholder="Ej. Luna" required>
                    </div>
                </div>
            </div>

            <div class="column">
                <div class="field">
                    <label class="label" for="especie">Tipo de mascota</label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select id="especie" name="especie" required>
                                <option value="">Selecciona una opción</option>
                                <?php foreach (Mascota::ESPECIES as $especie) { ?>
                                    <option value="<?php echo $especie; ?>"><?php echo ucfirst($especie); ?></option>
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
                        <input class="input" type="number" id="edad" name="edad" min="0" max="40" placeholder="Ej. 2" required>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="button is-link is-fullwidth">Registrar</button>
    </form>
</section>

<section class="box caja">
    <h2 class="title is-5">Lista de mascotas</h2>

    <?php if (empty($mascotas)) { ?>

        <p>Todavía no has registrado ninguna mascota.</p>

    <?php } else { ?>

        <div class="table-container">
            <table class="table tabla tabla-apilada is-fullwidth is-bordered is-hoverable">
                <thead>
                    <tr>
                        <td data-label="Mascota">Mascota</td>
                        <td data-label="Tipo">Tipo</td>
                        <td data-label="Edad">Edad</td>
                        <td data-label="Opciones">Opciones</td>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mascotas as $mascota) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($mascota->getNombre()); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($mascota->getEspecie())); ?></td>
                            <td><?php echo $mascota->getEdad(); ?> años</td>
                            <td>
                                <div class="buttons is-justify-content-center">
                                    <a class="button is-small is-primary" href="<?php echo APP_URL; ?>cita-nueva/?mascota=<?php echo (int) $mascota->getId(); ?>">Agendar cita</a>
                                    <a class="button is-small is-link" href="<?php echo APP_URL; ?>mascota-editar/?id=<?php echo (int) $mascota->getId(); ?>">Editar</a>

                                    <form class="FormularioAjax" data-confirmar="si"
                                          data-confirmar-texto="¿Eliminar esta mascota? También se borrarán sus citas."
                                          action="<?php echo APP_URL; ?>app/ajax/mascotaAjax.php" method="POST">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id" value="<?php echo (int) $mascota->getId(); ?>">
                                        <button type="submit" class="button is-small is-danger">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

    <?php } ?>
</section>