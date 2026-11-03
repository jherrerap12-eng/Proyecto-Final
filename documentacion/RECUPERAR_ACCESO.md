# Recuperar acceso desde la terminal

Para la instalación que administras, abrir la raíz del proyecto en VS Code → Terminal nueva. MySQL debe estar iniciado y .env apuntar a la base correcta.

```powershell
php artisan usuario:restablecer-contrasena
```

Solicita correo registrado y dos veces la contraseña nueva, de forma oculta. Mínimo 12 caracteres con letras y números. Al escribir no aparecen letras ni asteriscos: es normal; pulsar Enter al terminar.

Solo cuando la validación completa pasa, actualiza esa cuenta con hash, rota su token de recordar y elimina enlaces de recuperación pendientes de esa cuenta. Limpia el límite de intentos de ingreso para localhost IPv4/IPv6. No cambia pedidos, pagos, otras cuentas ni credenciales de MySQL; no crea usuarios si el correo no existe.

Después abrir http://127.0.0.1:8000/ingresar y escribir manualmente el correo y la contraseña nueva, evitando autocompletado antiguo. No usar la contraseña de GitHub o DB_PASSWORD salvo que deliberadamente se haya elegido igual.

Este es un comando administrativo accesible desde la terminal del proyecto, no una ruta pública ni recuperación por correo electrónico. No enviar contraseñas por chat, guardarlas en argumentos, capturas o código. Ejecutar únicamente en una instalación que se administra legítimamente.

Pruebas aisladas: contraseña nueva permite ingreso, anterior falla, confirmación distinta/débil no modifica la cuenta, otras cuentas se conservan y correo inexistente no crea usuarios. El desarrollo de este comando no restablece contraseñas reales hasta que el operador lo ejecute.
