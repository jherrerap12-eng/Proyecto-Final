const formulario = document.querySelector('[data-formulario-pedido]');
if (formulario) {
    const contenedor = formulario.querySelector('[data-piezas]');
    const seleccion = new Map();
    const estado = formulario.querySelector('[data-estado-busqueda]');
    const moneda = new Intl.NumberFormat('es-GT', { style: 'currency', currency: 'GTQ' });
    const actualizarTotal = () => {
        let centavos = 0;
        contenedor.querySelectorAll('input:checked').forEach((entrada) => { centavos += Number(entrada.dataset.centavos); });
        formulario.querySelector('[data-total-pedido]').textContent = `Total estimado: ${moneda.format(centavos / 100)}`;
    };
    const recordar = () => {
        seleccion.clear();
        contenedor.querySelectorAll('input:checked').forEach((entrada) => seleccion.set(entrada.value, { id: entrada.value, texto: entrada.dataset.texto, centavos: Number(entrada.dataset.centavos) }));
        actualizarTotal();
    };
    contenedor.addEventListener('change', recordar);
    recordar();
    for (const tipo of ['clientes', 'piezas']) {
        const boton = formulario.querySelector(`[data-boton-${tipo}]`);
        const campo = formulario.querySelector(tipo === 'clientes' ? '[data-buscar-cliente]' : '[data-buscar-pieza]');
        const buscar = async () => {
            if (boton.disabled) return;
            boton.disabled = true;
            estado.textContent = 'Buscando…';
            try {
                const url = new URL(boton.dataset.url, window.location.origin);
                url.searchParams.set('buscar', campo.value);
                const respuesta = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (!respuesta.ok) throw new Error(respuesta.status === 401 ? 'Tu sesión terminó. Vuelve a ingresar.' : 'No se pudo completar la búsqueda.');
                const { resultados } = await respuesta.json();
                if (tipo === 'clientes') {
                    const lista = formulario.querySelector('[data-clientes]');
                    const actual = lista.selectedOptions[0];
                    const opciones = new Map(resultados.map((dato) => [String(dato.id), dato]));
                    if (actual?.value) opciones.set(actual.value, { id: actual.value, texto: actual.textContent });
                    lista.replaceChildren(new Option('Selecciona un cliente', ''));
                    opciones.forEach((dato) => lista.add(new Option(dato.texto, dato.id, false, String(dato.id) === actual?.value)));
                } else {
                    const opciones = new Map([...seleccion]);
                    resultados.forEach((dato) => { if (!opciones.has(String(dato.id))) opciones.set(String(dato.id), dato); });
                    const fragmento = document.createDocumentFragment();
                    opciones.forEach((dato) => {
                        const columna = document.createElement('div'); columna.className = 'col-md-6';
                        const etiqueta = document.createElement('label'); etiqueta.className = 'd-flex gap-2 border rounded p-3 h-100';
                        const entrada = document.createElement('input');
                        Object.assign(entrada, { type: 'checkbox', name: 'productos[]', value: dato.id, checked: seleccion.has(String(dato.id)), className: 'form-check-input flex-shrink-0' });
                        entrada.dataset.centavos = dato.centavos; entrada.dataset.texto = dato.texto;
                        const texto = document.createElement('span'); texto.className = 'text-break'; texto.textContent = dato.texto;
                        etiqueta.append(entrada, texto); columna.append(etiqueta); fragmento.append(columna);
                    });
                    contenedor.replaceChildren(fragmento);
                    actualizarTotal();
                }
                estado.textContent = `${resultados.length} resultados. Se conserva la selección anterior.`;
            } catch (error) { estado.textContent = error.message || 'Error de conexión. Intenta de nuevo.'; }
            finally { boton.disabled = false; }
        };
        boton.addEventListener('click', buscar);
        campo.addEventListener('keydown', (evento) => { if (evento.key === 'Enter') { evento.preventDefault(); buscar(); } });
    }
}
