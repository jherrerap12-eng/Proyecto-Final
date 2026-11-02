# GitHub y VS Code

## Situación inicial

Proyecto Final estaba vacía dentro del repositorio superior Desarrollo web, remoto `https://github.com/jherrerap12-eng/Desarrollo-web.git`, cuyo ignore excluye este proyecto. No se modificó ese repositorio. Se eligió recomendar repositorio exclusivo Babybu Toys; publicación pendiente de autorización.

## Repositorio exclusivo

Abrir solo Proyecto Final en VS Code. Verificar ruta del repositorio en Control de código fuente: no publicar desde la raíz de clases.

El repositorio local exclusivo ya se inicializó dentro de Proyecto Final con:

```powershell
git init -b main
```

Crea `.git` exclusivo sin borrar el superior. `git rev-parse --show-toplevel` debe devolver Proyecto Final. Revisar cambios uno por uno; `.env`, vendor, node_modules, registros y datos no deben aparecer. Revisar `git status --short --untracked-files=all` y diff antes de preparar archivos.

Primer commit: excepción de inicialización de main, únicamente base revisada, requiere autorización. Después no desarrollar módulos sobre main.

## Publicar e invitar

Tras autorizar publicación: VS Code → Publicar rama/Publish to GitHub → cuenta correcta → repositorio privado nuevo `babybu-toys`. Revisar contenido. No reutilizar remoto de clases.

GitHub → repositorio → Settings → Collaborators → Add people → usuario exacto del compañero, quien acepta invitación. No compartir tokens ni contraseñas.

## Trabajo diario y PR

Cada integrante clona, abre carpeta, actualiza main con Pull y crea rama desde indicador VS Code: `feature/inventario` o `feature/clientes`. Revisa diff, prepara solo archivos propios, commit descriptivo y publica rama.

GitHub → Pull Request hacia main; indicar Issue, cambios, pruebas/capturas. Otro integrante revisa Files changed y prueba antes de aprobar/integrar. Solicitar cambios si falla. Actualizar main después. No force push ni sobrescribir trabajo ajeno.

Compañero: Cliente, migración, controlador, Form Requests, vistas clientes, búsquedas y pruebas. Acordar campos y coordinar rutas/layout primero. Módulo no implementado en la base.
