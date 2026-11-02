# Crear base y usuario en phpMyAdmin

Estado: guía para el encargado; todavía no ejecutada ni verificada. XAMPP local usa MariaDB 10.4 aunque su panel diga MySQL. Se conserva el objetivo MySQL del proyecto; validar el motor del despliegue más adelante.

1. Abrir XAMPP Control Panel. Verificar Apache y MySQL activos; iniciar solo los que estén detenidos.
2. Pulsar Admin junto a MySQL, o abrir `http://localhost/phpmyadmin`.
3. En Bases de datos, crear `babybu_toys` con cotejamiento `utf8mb4_unicode_ci`. Si ya existe, no borrarla: revisar contenido antes de continuar.
4. Volver al inicio de phpMyAdmin → Cuentas de usuario → Agregar cuenta de usuario.
5. Usuario: `babybu_app`. Servidor/host: usar campo de texto `127.0.0.1`, coincidiendo con `.env`. Contraseña: generar una propia y guardarla localmente, sin enviarla por chat.
6. No seleccionar privilegios globales ni crear otra base con el nombre del usuario. Guardar la cuenta sin privilegios globales.
7. Editar privilegios de `babybu_app` → privilegios específicos de base de datos → seleccionar `babybu_toys`. Conceder los privilegios de desarrollo/migración sobre esta base únicamente; no marcar GRANT OPTION. Verificar que no se otorgaron permisos sobre otras bases.
8. En VS Code abrir `.env` y completar la sección siguiente. No cambiar APP_KEY ni subir el archivo.

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=babybu_toys
DB_USERNAME=babybu_app
DB_PASSWORD="REEMPLAZAR_LOCALMENTE"
```

La cadena anterior es un marcador, no una contraseña utilizable. Pegar la contraseña real únicamente en `.env`, entre comillas dobles si contiene espacios o `#`; si el generador incluye comillas o barras, aplicar el escape dotenv correspondiente o generar una contraseña larga sin esos caracteres.

Cuando la configuración esté lista, avisar sin compartir la contraseña. Siguiente verificación guiada:

```powershell
php artisan config:clear
```

Elimina solo la caché de configuración, para que Laravel lea los valores actuales de `.env`.

```powershell
php artisan migrate:status
```

Comprueba conexión y estado; en base nueva puede indicar que aún no existe la tabla de migraciones.

```powershell
php artisan migrate
```

Crea tablas pendientes en la base indicada. Revisar el nombre de base antes. No borra datos como lo haría migrate:fresh.

```powershell
php artisan usuario:crear
```

Crea la cuenta de acceso a Babybu Toys con una contraseña oculta. Esta cuenta de la aplicación es distinta del usuario técnico `babybu_app` de la base de datos. Repetir después para las tres personas autorizadas.

Fuente de referencia: https://docs.phpmyadmin.net/en/latest/privileges.html . Las etiquetas pueden variar con el idioma y versión.
