# SEO Content Locker - Contexto para agentes

Version actual del plugin: `1.1.18`.

Este repositorio contiene un plugin personalizado de WordPress llamado **SEO Content Locker**. Su objetivo es bloquear contenido parcial dentro de posts o paginas, capturar leads por email, otorgar acceso temporal gratuito, restaurar sesiones existentes y limitar abusos mediante control por IP, reCAPTCHA, logs y notificaciones.

## Resumen funcional

- Shortcode principal: `[lock]...[/lock]` protege secciones de contenido.
- Shortcode de suscripcion simple: `[my_subscription_form]` registra leads desde pagina con First Name y email, sin consentimiento visual ni Google reCAPTCHA. Acepta el atributo opcional `[my_subscription_form landing="1"]`; solo ese valor redirige a `/your-intermarketflow-access-is-confirmed/` y aplica la etiqueta `LANDING` en Mailchimp. Sin el atributo, o con cualquier otro valor, redirige a `/thank-you/` y aplica `HOME`.
- Shortcode de suscripcion completo: `[my_subscription_form_site]` registra leads desde pagina con First Name y email, consentimiento y Google reCAPTCHA.
- El plugin inyecta automaticamente un modal de captura en entradas individuales con First Name y email.
- El frontend valida First Name y email en todos los formularios publicos; consentimiento y Google reCAPTCHA solo cuando el formulario lo requiere.
- El email se persiste en `localStorage` con la clave `wpscl_e` para intentar restaurar acceso en visitas futuras.
- El acceso gratuito expira por fecha (`expires_at`), por defecto a los 15 dias desde el alta.
- Si una IP ya fue usada por otro lead, el submit real se bloquea y se registra en una tabla separada.
- Si el email ya existe, puede restaurar el acceso desde otra IP; ese caso se permite y genera una notificacion informativa.
- El email definido en `LOCKER_REPORT_EMAIL` salta la restriccion de IP duplicada para permitir pruebas/admin del flujo de suscripcion.
- La geolocalizacion basica se obtiene por IP.
- Mailchimp API v3 se usa para crear o actualizar contactos y aplicar tags.
- El admin de WordPress permite listar leads, buscar, ordenar, paginar, exportar CSV, expirar, editar fecha de expiracion y eliminar registros.
- El admin tambien permite eliminar registros individuales o seleccionados de la tabla de intentos por IP duplicada.
- Tambien hay pantallas de configuracion para Mailchimp y reCAPTCHA.
- Los eventos importantes generan logs persistentes y notificaciones por email.
- Un cron diario genera a las 10:00 de Argentina (`America/Argentina/Buenos_Aires`) un reporte con los leads ingresados exactamente 13 días antes. No modifica la zona horaria general de WordPress. Incluye todos los leads guardados aunque Mailchimp haya fallado y escribe resultados en `logs/day-13-report.log`.

## Arquitectura

El plugin usa una arquitectura modular cercana a capas:

- `seo-content-locker.php`: punto de entrada del plugin. Define constantes, carga archivos base y registra el hook de activacion que crea tablas.
- `autoload.php`: autoload manual basado en un mapa de clases. Si agregas clases con namespace, actualiza este mapa.
- `includes/`: integracion con WordPress para shortcodes, assets y endpoints AJAX.
- `app/Services/`: logica de negocio y servicios externos.
- `app/Repositories/`: acceso a base de datos via `$wpdb`.
- `app/Notifier/`: eventos y envio de notificaciones por email.
- `admin/`: pantallas, acciones administrativas y tablas `WP_List_Table`.
- `templates/`: fragmentos PHP para modal, formularios, avisos y pagina de suscripcion.
- `assets/`: JavaScript y CSS de frontend/admin.
- `support/`: helpers globales para validacion, permisos, IP, logs y notificaciones.
- `logs/`: archivos de log generados por el plugin.

## Flujo principal de registro

1. El usuario hace click en `Continue Reading` y se abre el modal.
2. `assets/front.js` envia `seocontentlocker_save_lead` a `admin-ajax.php` con First Name y email.
3. `includes/locker-ajax.php` valida nonce, First Name, email, slug e IP.
4. `LeadRegistrationService::register()` valida reCAPTCHA cuando el flujo lo requiere.
5. `LeadAccessService::checkLead()` verifica si el email ya existe y si el acceso sigue vigente.
6. `LeadAccessService::checkIp()` bloquea multiples registros desde la misma IP.
7. Se obtiene el pais por IP y se guarda el lead con `LeadRepository::insert()`.
8. `MailchimpService::subscribe()` hace `PUT` contra Mailchimp API v3 con `merge_fields.FNAME` y luego aplica tags.
9. Se disparan eventos para logs y notificaciones.
10. El frontend desbloquea contenido, muestra expiracion o informa el estado segun la respuesta.

## Flujo de restauracion de acceso

- En posts, `assets/front.js` lee `localStorage.wpscl_e`.
- Si hay email guardado y la pagina tiene modal, boton o contenido bloqueado, llama al AJAX `seocontentlocker_check_lead_status`.
- `LeadAccessService::checkStatus()` decide si restaurar, expirar o bloquear por IP.
- Las comprobaciones automaticas de estado son silenciosas: no disparan emails administrativos de restauracion, expiracion o IP duplicada.
- Los emails administrativos de restauracion (`lead_restored`) solo deben dispararse para entradas (`post`), nunca para pages como la pagina de gracias.
- El submit real de los formularios de suscripcion (`[my_subscription_form]`, `[my_subscription_form_site]` y el modal generado por `[lock]`) puede disparar `lead_restored`; las comprobaciones automaticas de estado siguen siendo silenciosas.
- Si el acceso esta activo, el contenido bloqueado se muestra sin pedir registro nuevamente.

## Base de datos

El hook de activacion crea dos tablas personalizadas:

- `{$wpdb->prefix}leads_subscriptions`
  - Campos principales: `first_name`, `email`, `ip`, `country`, `post_slug`, `status`, `plan`, `created_at`, `expires_at`, `token`.
  - Tiene indice unico por `email`.
- `{$wpdb->prefix}leads_subscriptions_same_ip`
  - Registra intentos bloqueados por IP duplicada.
  - Campos principales: `email`, `ip`, `country`, `post_slug`, `created_at`.

Si se cambia el esquema, actualizar las funciones de instalacion en `seo-content-locker.php` y los repositorios correspondientes.

## Servicios clave

- `LeadRegistrationService`: orquesta validacion de captcha, verificacion de acceso/IP, guardado de lead, Mailchimp y eventos.
- `LeadAccessService`: centraliza reglas de acceso, expiracion, restauracion y bloqueo por IP.
- `MailchimpService`: integra con Mailchimp API v3 usando `wp_remote_request`; envia `first_name` como `merge_fields.FNAME`, y aplica `SUSCRIPTION_SYSTEM` y tags dinamicos:
  - `ARTICLE` para posts.
  - `NEWSLETTER` para pages.
  - `HOME` cuando el submit no proviene de `[my_subscription_form landing="1"]`; al aplicar `HOME`, se desactiva `LANDING` en el contacto.
  - `LANDING` únicamente cuando el submit proviene de `[my_subscription_form landing="1"]`; al aplicar `LANDING`, se desactiva `HOME` en el contacto.
- `RecaptchaService`: valida el token de Google reCAPTCHA con la secret key guardada en opciones.
- `LeadRepository`: encapsula operaciones sobre leads principales.
- `SameIpRepository`: encapsula registros de IP duplicada.
- `Dispatcher`, `Events`, `Notifier`: modelan eventos y envio de emails administrativos.

## Seguridad y convenciones WordPress

- Mantener `if (!defined('ABSPATH')) exit;` o equivalente en archivos PHP.
- Validar nonces en endpoints AJAX y acciones admin.
- Usar `current_user_can('manage_options')` para acciones administrativas.
- Sanitizar inputs con helpers de WordPress: `sanitize_email`, `sanitize_text_field`, `intval`, `wp_unslash`.
- Escapar output en admin/templates con `esc_html`, `esc_attr`, `esc_url` segun corresponda.
- Usar `$wpdb->prepare()` para SQL con datos externos.
- Preferir `wp_remote_get`, `wp_remote_post`, `wp_remote_request`, `wp_mail` y APIs nativas de WordPress.

## Endpoints y acciones relevantes

- AJAX publico/admin:
  - `seocontentlocker_save_lead`
  - `seocontentlocker_check_lead_status`
  - `seocontentlocker_mailchimp_test`
- Admin post:
  - `seocontentlocker_expire_lead`
  - `seocontentlocker_delete_lead`
  - `seocontentlocker_update_expire_date`
  - `seocontentlocker_export_csv`
- Bulk action:
  - `bulk_delete`

## Opciones de WordPress

- Mailchimp:
  - `seocontentlocker_mc_api_key`
  - `seocontentlocker_mc_account`
  - `seocontentlocker_mc_list_id`
- reCAPTCHA:
  - `seocontentlocker_recaptcha_site_key`
  - `seocontentlocker_recaptcha_secret_key`

## Logs y notificaciones

Los logs se escriben en `logs/` mediante helpers de `support/loggers.php`.

Archivos esperados:

- `subscription.log`
- `expired.log`
- `restore.log`
- `access.log`
- `same-ip.log`
- `mailchimp-success.log`
- `day-13-report.log`
- `error.log`

Eventos internos relevantes:

- `lead_created_success`
- `mailchimp_failed`
- `lead_restored`
- `lead_expired`
- `same_ip_blocked`

El email de reporte se define con la constante `LOCKER_REPORT_EMAIL` en `seo-content-locker.php`.

## Frontend

- Archivo principal: `assets/front.js`.
- Los assets se versionan con `SEO_CONTENT_LOCKER_VERSION`; subir esa constante cuando se necesite romper cache de JS/CSS.
- El objeto localizado es `seocontentlocker_ajax`; incluye `isPost` para limitar la restauracion automatica a entradas.
- `assets/front.js` soporta multiples formularios publicos y detecta si un formulario requiere reCAPTCHA mediante `data-recaptcha-required="1"`.
- Cuando hay reCAPTCHA, `assets/front.js` debe tomar el token desde el formulario enviado para evitar mezclar widgets entre modal y formularios de pagina.
- Las respuestas esperadas usan `data.status` con valores como:
  - `success`
  - `restored`
  - `expired`
  - `mailchimp_failed`
- La pagina de suscripcion simple usa el shortcode `[my_subscription_form]`, solicita First Name y email, y omite consentimiento visual y reCAPTCHA. Solo el atributo exacto `landing="1"` cambia la redireccion a `/your-intermarketflow-access-is-confirmed/` y la etiqueta de Mailchimp a `LANDING`; sin atributo o con otro valor redirige a `/thank-you/` y aplica `HOME`.
- El formulario `[my_subscription_form]` muestra `*` dentro de los placeholders de First name y Email como indicacion visual de campos obligatorios. Su boton mantiene la misma apariencia cuando esta deshabilitado mientras faltan datos.
- La pagina/formulario de sitio completo usa el shortcode `[my_subscription_form_site]`, solicita First Name y email, y mantiene consentimiento, reCAPTCHA y el comportamiento anterior.
- Los formularios de pagina generados por ambos shortcodes usan `Roboto Condensed` como tipografia de marca por defecto y permiten una fuente distinta mediante `--locker-page-font-family` en el contenedor de la pagina. El acento se toma desde `--e-global-color-accent`, con fallback del manual de marca. Los campos mantienen contraste sobre fondos oscuros mediante variables locales para texto, placeholder, borde, fondo y radio (`--locker-form-field-*`), que Elementor puede sobrescribir desde el contenedor. El modal generado por `[lock]` mantiene sus estilos visuales propios.
- Las rutas de agradecimiento se definen en `seo-content-locker.php` mediante `SEO_CONTENT_LOCKER_THANK_YOU_PATH` (`/thank-you/`) y `SEO_CONTENT_LOCKER_LANDING_THANK_YOU_PATH` (`/your-intermarketflow-access-is-confirmed/`). `includes/locker-assets.php` las convierte en URLs del sitio mediante `home_url()` y las expone al frontend; los formularios simples con `landing="1"` usan la segunda ruta.

## Admin

- Menu principal: `SEO Content Locker`.
- Slug constante: `seo-locker`.
- Tabla principal: `SeoContentLocker\Admin\LeadTable`.
- Tabla de IP duplicada: `SeoContentLocker\Admin\SameIpTable`.
- Las acciones administrativas viven en `admin/actions.php`.
- La exportacion CSV incluye BOM UTF-8 para compatibilidad con Excel.

## Como trabajar en este repo

- Antes de editar, revisar `git status --short`.
- Mantener cambios acotados al modulo afectado.
- Cuando un cambio modifique comportamiento, shortcodes, endpoints, servicios, opciones, tablas, flujos de frontend/admin o pasos de verificacion, actualizar este `AGENTS.md` en el mismo trabajo.
- No reemplazar la arquitectura modular por includes grandes o logica mezclada.
- Si se agrega una clase nueva, registrar la clase en `autoload.php`.
- Si se toca un flujo AJAX, verificar nonce, sanitizacion, respuesta JSON y manejo en `assets/front.js`.
- Si se toca admin, verificar permisos, nonces, redirects y escaping.
- Si se toca persistencia, actualizar repositorios y considerar migracion/activacion de tablas.
- Evitar romper compatibilidad con WordPress: usar APIs core siempre que sea razonable.

## Verificacion recomendada

No hay suite automatica detectada en este repositorio. Para cambios PHP, ejecutar al menos:

```powershell
php -l seo-content-locker.php
php -l autoload.php
php -l includes\locker-ajax.php
php -l app\Services\LeadRegistrationService.php
php -l app\Services\LeadAccessService.php
php -l app\Repositories\LeadRepository.php
php -l app\Repositories\SameIpRepository.php
```

Para cambios funcionales, probar manualmente en WordPress:

- Alta de lead nuevo con reCAPTCHA valido.
- Restauracion con email guardado.
- Intento con acceso expirado.
- Intento desde IP duplicada.
- Sincronizacion y tags de Mailchimp.
- Ejecucion manual del evento `seocontentlocker_day_13_report` y verificacion de `logs/day-13-report.log`.
- Exportacion CSV.
- Edicion/expiracion/eliminacion de leads desde admin.

## Notas del proyecto

- El plugin esta orientado a monetizacion, captacion de leads y control de acceso SEO-friendly.
- La primera version funcional ya cubre bloqueo por shortcode, modal, lead capture, expiracion temporal, control por IP, Mailchimp, reCAPTCHA, admin, CSV, logs y notificaciones.
- Hay textos existentes con mojibake en algunos archivos PHP; si se corrigen, hacerlo de forma deliberada y revisar encoding para no mezclar cambios funcionales con cambios masivos de texto.
