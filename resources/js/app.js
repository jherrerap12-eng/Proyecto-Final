import * as bootstrap from 'bootstrap';

// Evita envíos accidentales repetidos; el servidor sigue siendo la autoridad.
document.querySelectorAll('form[data-envio-unico]').forEach((formulario) => {
    formulario.addEventListener('submit', () => {
        const boton = formulario.querySelector('button[type="submit"]');
        if (boton) {
            boton.disabled = true;
            boton.textContent = 'Procesando…';
        }
    });
});
