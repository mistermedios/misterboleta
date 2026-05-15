<<<<<<< HEAD
# misterboleta
Plataforma de reserva y venta de boletería para eventos
=======
# MisterBoleta

Aplicacion PHP + MySQL para venta de boletas.

## Requisitos

- PHP 8.0 o superior
- MySQL 5.7 o superior
- Extensiones PDO y `pdo_mysql`

## Configuracion

El archivo `config/database.php` ya soporta variables de entorno:

- `DB_HOST`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`
- `APP_BASE_URL`

Si tu hosting no permite variables de entorno, puedes editar temporalmente los valores por defecto en `config/database.php`.

## Despliegue En OrangeHost

1. Crea una base de datos MySQL desde el panel de OrangeHost.
2. Importa `sql/database.sql`.
3. Sube el proyecto completo por FTP o Administrador de Archivos.
4. Si tu dominio apunta a `public_html`, deja accesible al menos:
   - `public/`
   - `css/`
   - `js/`
   - `user/`
   - `admin/`
   - `includes/`
   - `config/`
5. Configura `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.
6. Configura `APP_BASE_URL` con la ruta real del proyecto.

### Estructura Recomendada En `public_html`

Sube todo el contenido de este proyecto dentro de `public_html/` para que quede asi:

- `public_html/index.php`
- `public_html/.htaccess`
- `public_html/public/`
- `public_html/admin/`
- `public_html/user/`
- `public_html/css/`
- `public_html/js/`
- `public_html/includes/`
- `public_html/config/`
- `public_html/sql/`

El archivo `index.php` de la raiz redirige automaticamente hacia `public/index.php`.

### Valor De `APP_BASE_URL`

- Si el proyecto queda directo en `public_html`: usa vacio
- Si lo subes dentro de `public_html/misterboleta`: usa `/misterboleta`

### Endurecimiento Incluido Para Hosting

- `.htaccess` en raiz para desactivar listado de directorios
- bloqueo directo a `README.md`, `SPEC.md` y archivos `.sql`
- bloqueo web completo a `config/`, `includes/` y `sql/`

### Ejemplos de `APP_BASE_URL`

- Sitio en la raiz del dominio: vacio o `/`
- Sitio en subcarpeta: `/misterboleta`

## Flujo De Ordenes

- La orden se crea en estado `pending`
- Las boletas se generan en estado `reserved`
- El administrador puede confirmar o cancelar la orden desde `admin/index.php`
- Al confirmar:
  - `orders.status` pasa a `confirmed`
  - `orders.payment_status` pasa a `paid`
  - las boletas pasan a `sold`
- Al cancelar:
  - `orders.status` pasa a `cancelled`
  - las boletas pasan a `cancelled`
  - se restauran los cupos en `event_zones`

## Seguridad Aplicada

- CSRF en formularios y acciones sensibles
- Cierre de sesion por `POST`
- Regeneracion de sesion al iniciar sesion
- Restriccion de acceso a ordenes por propietario o admin
- Rutas centralizadas para despliegue en subcarpeta
- Cookies de sesion con `httponly` y `samesite`

## Nota

El proyecto queda listo para un flujo de pago manual o conciliado por administracion. Si luego quieres integrar MercadoPago o una pasarela real, conviene hacerlo con webhooks antes de marcar una orden como pagada automaticamente.

### Configuración de MercadoPago

- Define `APP_BASE_URL` para que las URL de retorno y webhook sean correctas.
- Define `MP_ACCESS_TOKEN` con tu `ACCESS_TOKEN` de MercadoPago.
- Define `MP_PUBLIC_KEY` solo si necesitas la clave pública en el frontend.
- El webhook se expone en `public/api/mercadopago-webhook.php`.
>>>>>>> 60bd69d (Initial commit)
