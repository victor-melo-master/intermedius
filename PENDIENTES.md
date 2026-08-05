# Pendientes

## Panel de Configuración (Control General de la App)

### Contexto
Existe un mecanismo de ajustes globales persistidos en la tabla `ajustes` (clave/valor/descripcion).
Actualmente hay dos ajustes funcionales:

| Clave             | Valor | Descripción                                                    |
| ----------------- | ----- | -------------------------------------------------------------- |
| `password_segura` | `1`   | Rechaza contraseñas comprometidas (regla HIBP `uncompromised`) |
| `envio_emails`    | `1`   | Activa/desactiva el envío de correos de la app                 |

### Estado del backend (YA implementado)
- Modelo `api/app/Models/Ajuste.php` con helpers `obtener()` y `activo()`.
- Controlador `api/app/Http/Controllers/Api/V1/Configuracion/AjusteController.php`:
  - `GET /api/v1/configuracion/ajustes` (autenticado).
  - `PATCH /api/v1/configuracion/ajustes` (`role:admin|super_admin`).
- Rutas registradas en `api/routes/api.php` dentro del grupo `configuracion`.
- Guardas de `envio_emails` activas en: `UserController::store`, `User::sendEmailVerificationNotification`, `AlertarTasasFaltantesJob`, `GenerarReporteMensualComisionesJob`.

### Trabajo pendiente (frontend)
1. **Vista "Configuración general"**: pantalla accesible por `admin`/`super_admin` (usar `auth.canConfig`).
2. **Ruta** `/configuracion` en `frontend/src/router/index.js` con `meta.roles: ['admin', 'super_admin']`.
3. **Enlace en la navegación** (`AppShell.vue`): item "Configuración" junto a "Usuarios".
4. **Toggle por ajuste** que haga `PATCH configuracion/ajustes` con `{ "clave": "1"|"0" }` y refresque el listado vía `GET configuracion/ajustes`.
5. **Desplegar ambos ajustes** actuales (`password_segura` y `envio_emails`) con su `descripcion` como tooltip/ayuda.
6. *(Opcional)* Mostrar aviso cuando `envio_emails` esté desactivado (p. ej. "Los usuarios no recibirán correos de verificación").

### Notas
- `super_admin` no se puede crear ni promover vía API (solo el seed lo asigna).
- Los ajustes se siembran en `docker/mysql/00-init.sh` y `docker/mysql/seed.sql` (BDs frescas) y en las migraciones `2026_08_05_000002_*` y `2026_08_05_000003_*`.
