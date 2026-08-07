# SEO Content Locker

Plugin personalizado de WordPress para bloquear contenido, capturar leads y administrar accesos temporales.

## Funcionalidades

- Shortcode `[lock]...[/lock]` para proteger contenido.
- Formularios de suscripcion con First Name y email.
- Validacion de email, consentimiento y Google reCAPTCHA segun el formulario.
- Restauracion de accesos existentes mediante email.
- Acceso gratuito con expiracion configurable.
- Control de registros repetidos por IP.
- Sincronizacion de leads y etiquetas con Mailchimp.
- Panel administrativo con busqueda, exportacion CSV, expiracion y eliminacion.
- Logs de suscripciones, accesos, errores y notificaciones.
- Reporte diario al administrador con los leads ingresados exactamente 13 dias antes.

## Reporte diario de leads

El plugin registra la tarea `seocontentlocker_day_13_report` para ejecutarse diariamente a las 10:00, usando la zona horaria configurada en WordPress.

El reporte incluye todos los leads guardados en la tabla, independientemente de si Mailchimp proceso correctamente el contacto. El email contiene una tabla con:

- First Name
- Email
- Fecha y hora de ingreso
- Pais

El reporte se envia a la direccion definida por `LOCKER_REPORT_EMAIL` en `seo-content-locker.php`.

Los resultados se guardan en:

```text
logs/day-13-report.log
```

Los errores de envio tambien se registran en:

```text
logs/error.log
```

## Configuracion del cron en Hostinger

Hostinger programa sus cron jobs en UTC. Las 10:00 de Argentina corresponden a las 13:00 UTC.

En hPanel, crear un cron personalizado diario a las 13:00 UTC con:

```bash
curl -fsS "https://tudominio.com/wp-cron.php?doing_wp_cron=1"
```

Reemplazar `tudominio.com` por el dominio real del sitio.

Antes de crear el cron, verificar en WordPress:

```text
Ajustes -> Generales -> Zona horaria -> Buenos Aires
```

## Verificacion con WP-CLI

Para verificar que el evento existe:

```bash
wp cron event list | grep seocontentlocker_day_13_report
```

Para ejecutarlo manualmente:

```bash
wp cron event run seocontentlocker_day_13_report
```

## Estructura

- `seo-content-locker.php`: entrada del plugin e instalacion.
- `includes/`: integracion con WordPress, AJAX, assets y cron.
- `app/Services/`: logica de negocio.
- `app/Repositories/`: acceso a base de datos.
- `app/Notifier/`: notificaciones administrativas.
- `admin/`: pantallas y acciones del panel.
- `support/`: helpers y logs.
- `templates/`: fragmentos visuales.
