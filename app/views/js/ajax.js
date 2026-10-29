// Busca todos los formularios que tengan la clase "FormularioAjax"
const formularios = document.querySelectorAll('.FormularioAjax');

formularios.forEach(function (formulario) {

    // Cuando se intenta enviar el formulario...
    formulario.addEventListener('submit', function (evento) {

        // ...evitamos el envío normal del navegador (que recargaría la página)
        evento.preventDefault();

        // Si el formulario pide confirmación, preguntamos primero
        if (formulario.getAttribute('data-confirmar') === 'si') {
            Swal.fire({
                title: '¿Estás seguro?',
                text: 'Quieres realizar la acción solicitada',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, continuar',
                cancelButtonText: 'Cancelar'
            }).then(function (resultado) {
                if (resultado.isConfirmed) {
                    enviarFormulario(formulario);
                }
            });
        } else {
            enviarFormulario(formulario);
        }
    });
});

// Manda los datos del formulario al servidor sin recargar la página
function enviarFormulario(formulario) {
    const datos = new FormData(formulario);

    fetch(formulario.getAttribute('action'), {
        method: 'POST',
        body: datos
    })
        .then(function (respuesta) {
            return respuesta.json();
        })
        .then(function (resultado) {
            mostrarAlerta(resultado);
        })
        .catch(function () {
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo comunicar con el servidor.'
            });
        });
}

// Muestra la respuesta del servidor y, si trae una dirección, nos lleva ahí
function mostrarAlerta(resultado) {
    Swal.fire({
        icon: resultado.icono,
        title: resultado.titulo,
        html: resultado.texto,
        confirmButtonText: 'Aceptar'
    }).then(function () {
        if (resultado.redirigir !== '') {
            window.location.href = resultado.redirigir;
        }
    });
}