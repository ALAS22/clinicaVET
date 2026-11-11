const Aviso = Swal.mixin({
  confirmButtonColor: "#283618",
  cancelButtonColor: "#FF5C5C",
  background: "#FEF9DE",
});
const formularios = document.querySelectorAll(".FormularioAjax");

formularios.forEach(function (formulario) {
  formulario.addEventListener("submit", function (evento) {
    evento.preventDefault();

    const boton = evento.submitter;

    let origen = formulario;
    if (boton && boton.hasAttribute("data-confirmar")) {
      origen = boton;
    }

    if (origen.getAttribute("data-confirmar") === "si") {
      let textoConfirmacion = origen.getAttribute("data-confirmar-texto");

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
          enviarFormulario(formulario, boton);
        }
      });
    } else {
      enviarFormulario(formulario, boton);
    }
  });
});

// Manda los datos del formulario al servidor sin recargar la pagina
function enviarFormulario(formulario, boton) {
  const datos = new FormData(formulario);

  if (boton && boton.name) {
    datos.append(boton.name, boton.value);
  }

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
    icon: resultado.icono,
    title: resultado.titulo,
    html: resultado.texto,
    confirmButtonText: "Aceptar",
  }).then(function () {
    if (resultado.redirigir !== "") {
      window.location.href = resultado.redirigir;
    }
  });
}
