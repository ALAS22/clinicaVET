<?php
/** @var string $tituloPagina Título de la pestaña. Lo define index.php antes de incluir este archivo */
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($tituloPagina . ' | ' . APP_NAME); ?></title>

<!-- Fuentes -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">

<!-- Bulma -->
<link rel="stylesheet" href="<?php echo APP_URL; ?>app/views/css/estilos.css">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>