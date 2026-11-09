<?php
//app/views/content/admin-citas-view.php
use app\controllers\AdminCitaControlador;
use app\models\Cita;

$controlador = new AdminCitaControlador();
$filtros     = $controlador->leerFiltros($_GET);
$resultado   = $controlador->listar($filtros);

$citas   = $resultado['citas'];
$total   = $resultado['total'];
$pagina  = $resultado['pagina'];
$paginas = $resultado['paginas'];
?>

<h1>Citas</h1>
<p><a href="<?php echo APP_URL; ?>inicio/">&larr; Volver al inicio</a></p>

<form action="<?php echo APP_URL; ?>admin-citas/" method="GET">
    <label>Estado</label>
    <select name="estado">
        <option value="">Todos</option>
        <?php foreach (Cita::ESTADOS as $estado) {
            $seleccionada = '';
            if ($estado === $filtros['estado']) {
                $seleccionada = 'selected';
            }
        ?>
            <option value="<?php echo $estado; ?>" <?php echo $seleccionada; ?>><?php echo ucfirst($estado); ?></option>
        <?php } ?>
    </select>

    <label>Día</label>
    <input type="date" name="fecha" value="<?php echo htmlspecialchars($filtros['fecha']); ?>">

    <label>Buscar (cliente, correo o mascota)</label>
    <input type="text" name="q" maxlength="50" value="<?php echo htmlspecialchars($filtros['q']); ?>">

    <button type="submit">Filtrar</button>
    <a href="<?php echo APP_URL; ?>admin-citas/">Quitar filtros</a>
</form>

<p>Resultados: <strong><?php echo $total; ?></strong> cita(s).</p>

<?php if (empty($citas)) { ?>

    <p>No hay citas con esos filtros.</p>

<?php } else { ?>

    <table border="1" cellpadding="6">
        <thead>
            <tr>
                <th>Fecha y hora</th>
                <th>Cliente</th>
                <th>Mascota</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($citas as $cita) { ?>
                <tr>
                    <td><?php echo date('d/m/Y H:i', strtotime($cita['cita_fecha_hora'])); ?></td>
                    <td>
                        <?php echo htmlspecialchars($cita['usuario_nombre']); ?><br>
                        <?php echo htmlspecialchars($cita['usuario_email']); ?>
                    </td>
                    <td><?php echo htmlspecialchars($cita['mascota_nombre']); ?></td>
                    <td><?php echo ucfirst($cita['cita_estado']); ?></td>
                    <td>
                        <a href="<?php echo APP_URL; ?>admin-cita/?id=<?php echo (int) $cita['cita_id']; ?>">Gestionar</a>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <?php if ($paginas > 1) {
        // Los filtros se conservan en los enlaces de cada página
        $base = [
            'estado' => $filtros['estado'],
            'fecha'  => $filtros['fecha'],
            'q'      => $filtros['q'],
        ];
    ?>
        <p>
            Página <?php echo $pagina; ?> de <?php echo $paginas; ?>:

            <?php if ($pagina > 1) {
                $base['pagina'] = $pagina - 1;
            ?>
                <a href="<?php echo APP_URL; ?>admin-citas/?<?php echo htmlspecialchars(http_build_query($base)); ?>">&larr; Anterior</a>
            <?php } ?>

            <?php for ($n = 1; $n <= $paginas; $n++) {
                $base['pagina'] = $n;
            ?>
                <?php if ($n === $pagina) { ?>
                    <strong><?php echo $n; ?></strong>
                <?php } else { ?>
                    <a href="<?php echo APP_URL; ?>admin-citas/?<?php echo htmlspecialchars(http_build_query($base)); ?>"><?php echo $n; ?></a>
                <?php } ?>
            <?php } ?>

            <?php if ($pagina < $paginas) {
                $base['pagina'] = $pagina + 1;
            ?>
                <a href="<?php echo APP_URL; ?>admin-citas/?<?php echo htmlspecialchars(http_build_query($base)); ?>">Siguiente &rarr;</a>
            <?php } ?>
        </p>
    <?php } ?>

<?php } ?>