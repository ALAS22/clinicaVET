<?php

use app\controllers\AdminCitaControlador;
use app\models\Cita;

$controlador = new AdminCitaControlador();
$filtros = $controlador->leerFiltros($_GET);
$resultado = $controlador->listar($filtros);
$resumen = $controlador->resumen();

$citas = $resultado['citas'];
$total = $resultado['total'];
$pagina = $resultado['pagina'];
$paginas = $resultado['paginas'];

$etiquetas = [
    Cita::PENDIENTE => 'is-warning',
    Cita::APROBADA  => 'is-success',
    Cita::ATENDIDA  => 'is-info',
    Cita::CANCELADA => 'is-danger',
];

$base = [
    'estado' => $filtros['estado'],
    'fecha'  => $filtros['fecha'],
    'q'      => $filtros['q'],
];
?>

<h1 class="title">Citas</h1>
<!--Cada rtiqueta wa un enlace por estado -->
<div class="tags mb-4">
    <?php foreach ($etiquetas as $estado => $clase){ ?>
    <a class="tag is-medium is-rounded <?php echo $clase; ?>" href="<?php echo APP_URL; ?>admin-citas/?estado=<?php echo $estado; ?>">
        <?php echo ucfirst($estado); ?>: <?php echo (int) $resumen[$estado]; ?>
    </a>
    <?php } ?>
</div>

<section class="box-caja">
    <form action="<?php echo APP_URL; ?>admin-citas/" method="GET">
        <div class="colums is-vcentered">
            <div class="column">
                <div class="field">
                    <label class="label" for="estado">Estado</label>

                    <div class="control">
                        <div class="select is-fullwidth">
                            <select id="estado" name="estado">
                                <option value="">Todos</option>
                                <?php foreach (Cita::ESTADOS as $estado) {
                                    $seleccionada = '';
                                    if ($estado === $filtros['estado']) {
                                        $seleccionada = 'selected';
                                    }?>
                                <option value="<?php echo $estado; ?>" <?php echo $seleccionada; ?>><?php echo ucfirst($estado); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="column">
                <div class="field">
                    <label class="label" for="fecha">Día</label>
                    <div class="control">
                        <input class="input" type="date" id="fecha" name="fecha" value="<?php echo htmlspecialchars($filtros['fecha']); ?>">
                    </div>
                </div>
            </div>
            <div class="column is-5">
                <div class="field">
                    <label class="label" for="q">Buscar(cliente, correo o mascota)</label>
                    <div class="control">
                        <input class="input" type="text" id="q" name="q" maxlength="50" value="<?php echo htmlspecialchars($filtros['q']); ?>">
                    </div>
            </div>
        </div>

        <div class="column is-narrow">
            <div class="buttons">
                <button type="submit" class="button is-primary">Filtrar</button>
                <a class="button is-light" href="<?php echo APP_URL; ?>admin-citas/"Quitar filtros></a>
            </div>
        </div>
    </div>
    </form>
</section>

<p class="mb-3">Resultados: <strong><?php echo $total; ?></strong> cita(s).</p>
<?php if (empty($citas)) { ?>
    <div class="notification is-light">No hay citas con esos filtros.</div>
<?php } else { ?>

<div class="table-container">
    <table class="table tabla tabla-apilada is-fullwidth is-bordered is-hoverable">
        <thead>
            <tr>
                <td data-label="Fecha y hora">Fecha y hora</td>
                <td class="texto-izquierda" data-label="Cliente">Cliente</td>
                <td data-label="Mascota">Mascota</td>
                <td data-label="Estado">Estado</td>
                <td data-label="Acción">Acción</td>
            </tr>
        </thead>
        <tbody>
             <?php foreach ($citas as $cita) { ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($cita['cita_fecha_hora'])); ?></td>
                        <td class="texto-izquierda">
                            <strong><?php echo htmlspecialchars($cita['usuario_nombre']); ?></strong><br>
                            <span class="is-size-7"><?php echo htmlspecialchars($cita['usuario_email']); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($cita['mascota_nombre']); ?></td>
                        <td>
                            <span class="tag is-rounded <?php echo $etiquetas[$cita['cita_estado']]; ?>">
                                <?php echo ucfirst($cita['cita_estado']); ?>
                            </span>
                        </td>
                        <td>
                            <a class="button is-small is-link" href="<?php echo APP_URL; ?>admin-cita/?id=<?php echo (int) $cita['cita_id']; ?>">Gestionar</a>
                        </td>
                    </tr>
                     <?php } ?>
        </tbody>
    </table>
</div>

<?php if ($paginas > 1) { ?>

        <nav class="pagination is-centered is-rounded" role="navigation" aria-label="Paginación">

            <?php if ($pagina > 1) {
                $base['pagina'] = $pagina - 1;
            ?>
             <a class="pagination-previous" href="<?php echo APP_URL; ?>admin-citas/?<?php echo htmlspecialchars(http_build_query($base)); ?>">Anterior</a>
              <?php } else { ?>
                <a class="pagination-previous is-disabled" disabled>Anterior</a>
            <?php } ?>

             <?php if ($pagina < $paginas) {
                $base['pagina'] = $pagina + 1;
            ?>

            <a class="pagination-next" href="<?php echo APP_URL; ?>admin-citas/?<?php echo htmlspecialchars(http_build_query($base)); ?>">Siguiente</a>
            <?php } else { ?>
                <a class="pagination-next is-disabled" disabled>Siguiente</a>
            <?php } ?>

            <ul class="pagination-list">
                <?php for ($n = 1; $n <= $paginas; $n++) {
                    $base['pagina'] = $n;
                ?>
                    <li>
                        <?php if ($n === $pagina) { ?>
                            <a class="pagination-link is-current" aria-current="page"><?php echo $n; ?></a>
                        <?php } else { ?>
                            <a class="pagination-link" href="<?php echo APP_URL; ?>admin-citas/?<?php echo htmlspecialchars(http_build_query($base)); ?>"><?php echo $n; ?></a>
                        <?php } ?>
                    </li>
                <?php } ?>
            </ul>
        </nav>

    <?php } ?>

<?php } ?>