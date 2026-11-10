const Aviso = Swal.mixin({
  confirmButtonColor: "#283618",
  cancelButtonColor: "#FF5C5C",
  background: "#FEF9DE",
});

const formulario = document.querySelectorAll(".FormularioAjax");

formulario.forEach(function (formulario) {
  formulario.addEventListener("submit", function (evento) {
    evento.preventDefault();

    if (formulario.getAttribute("data-confirmar") === "si") {
      // Obtener el texto personalizado del formulario
      let textoConfirmacion = formulario.getAttribute("data-confirmar-texto");

      // Si no tiene un texto personalizado, usamos el mensaje predeterminado
      if (textoConfirmacion === null) {
        textoConfirmacion = "Quieres realizar la acción solicitada";
      }

      Aviso.fire({
        title: "¿Estás seguro?",
        text: textoConfirmacion,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Sí, continuar",
        cancelButtonText: "Cancelar",
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

//---- Mandar formulario al servidor sin recargar página

function enviarFormulario(formulario) {
  const datos = new FormData(formulario);

  fetch(formulario.getAttribute("action"), {
    method: "POST",
    body: datos,
  })
    .then(function (respuesta) {
      return respuesta.json();
    })
    .then(function (resultado) {
      return mostrarAlerta(resultado);
    })
    .catch(function () {
      Aviso.fire({
        icon: "error",
        title: "Error de conexión",
        text: "No se pudo comunicar con el servidor",
      });
    });
}

function mostrarAlerta(resultado) {
  Aviso.fire({
    icon: resultado.icon,
    title: resultado.titulo,
    html: resultado.texto,
    confirmButtonText: "Aceptar",
  }).then(function () {
    if (resultado.redirigir !== "") {
      window.location.href = resultado.redirigir;
    }
  });
}
