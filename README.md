# DataForge

**DataForge is a multi-user web console for MySQL databases written in plain PHP.**
**Each account only sees the databases it created, and every schema change is recorded in an audit log.**
**It ships with Docker Compose, PHPUnit, PHPStan and a CI pipeline that runs on every push.**

Proyecto academico en equipo de la UTSJR. Gestor web multiusuario de bases de datos MySQL
construido con PHP nativo, sin frameworks.

---

## Contexto y rol

Este repositorio es el resultado de un proyecto academico de la carrera de **Ingenieria en TI
e Innovacion Digital** de la **UTSJR**. La universidad pidio inventar una empresa como parte de
la dinamica del curso: **"Raju Technology"** es ficticia, no existe y no tiene relacion con
ninguna empresa real.

En el equipo mi funcion fue la de **arquitecto de software y lider del equipo**:

- Disene la arquitectura de la aplicacion y el reparto de responsabilidades entre capas.
- Disene los flujos de seguridad: autenticacion, gestion de sesiones, tokens CSRF, limites de
  peticiones y el modelo de aislamiento entre cuentas.
- Planifique las iteraciones y el trabajo de cada integrante.

Tambien implemente, pero el aporte principal de este proyecto es el diseno y la division del
trabajo. **Numero de integrantes del equipo: TODO** (pendiente de confirmar).

La version 4.0 es una reescritura completa del nucleo que la v3.2 dejo como conjunto de
scripts PHP sueltos. La documentacion de la version anterior esta en el historial de Git.

---

## Funcionalidades verificadas

Cada punto de esta lista se corresponde con codigo concreto del repositorio.

| Funcionalidad | Donde esta implementada |
|---|---|
| Cuentas de usuario con registro, acceso y cambio de contrasena | `src/Service/AuthService.php` |
| Aislamiento entre cuentas: cada base de datos tiene un propietario | `src/Repository/ManagedDatabaseRepository.php` |
| Alta y baja de bases de datos MySQL | `src/Service/DatabaseService.php` |
| Constructor de tablas con 11 tipos de dato | `src/Service/TableService.php`, `src/Database/ColumnType.php` |
| Edicion de la estructura: anadir, cambiar tipo y eliminar columnas | `src/Service/TableService.php` |
| CRUD de registros con formularios generados desde el esquema real | `src/Service/RecordService.php` |
| Listado con paginacion, orden por columna y busqueda en todas las columnas | `src/Service/RecordService.php` |
| Consola SQL de solo lectura con limite de filas y tiempo de ejecucion | `src/Service/QueryService.php`, `src/Service/SqlGuard.php` |
| Exportacion de una tabla en CSV y de una base de datos en `.sql` | `src/Service/ExportService.php` |
| Bitacora de auditoria por usuario | `src/Repository/ActivityRepository.php` |
| Siete paletas de color y modo claro/oscuro | `src/Support/Theme.php`, `public/assets/css/themes.css` |
| Migraciones de esquema versionadas | `database/migrations/`, `src/Database/Migrator.php` |
| Consola de administracion (`migrate`, `routes`, `version`) | `bin/console` |

---

## Arquitectura

La aplicacion no usa un framework y **no implementa MVC, ADR ni ningun otro patron de
plantilla**. Es un controlador sobre rutas con tres capas debajo:

```
Peticion HTTP
  -> public/index.php (front controller)
  -> Application::handle()
       -> Middleware: cabeceras de seguridad, manejo de errores, sesion, rate limit
       -> Router::dispatch()
            -> Middleware de la ruta: autenticacion, token CSRF
            -> Controlador  (src/Http/Controller)
                 -> Servicio (src/Service)      reglas de negocio
                      -> Repositorio (src/Repository)  acceso a datos
                           -> PDO
            -> View::render()  (src/Core/View.php + templates/)
```

| Capa | Directorio | Responsabilidad |
|---|---|---|
| Nucleo | `src/Core/` | Peticion, respuesta, enrutador, cadena de middleware, contenedor de dependencias, plantillas |
| HTTP | `src/Http/` | Controladores y middleware |
| Dominio | `src/Service/` | Reglas de negocio: cuentas, bases de datos, tablas, registros, consultas, exportacion |
| Persistencia | `src/Repository/`, `src/Database/` | Consultas parametrizadas, fabrica de conexiones y migraciones |
| Seguridad | `src/Security/` | CSRF, sesiones, contrasenas, rate limiting, cabeceras, IP de cliente |
| Soporte | `src/Support/` | Validacion, identificadores, tema, bitacora, rutas |

El detalle completo, con la rationale de cada decision, esta en
[docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

---

## Seguridad

Cada afirmacion apunta a un archivo. No hay medidas "deSerie" sin implementacion.

| Capa | Implementacion |
|---|---|
| **CSRF** | Token por accion, generado con `random_bytes(32)`, verificado con `hash_equals()` y consumido tras cada intento, incluidos los fallidos. `src/Security/Csrf.php` |
| **Consultas** | PDO con `ATTR_EMULATE_PREPARES => false`. Los valores viajan como parametros. `src/Database/ConnectionFactory.php` |
| **Identificadores** | PDO no admite parametros para nombres de objeto, asi que todo nombre de base, tabla o columna pasa por una lista blanca (`[A-Za-z0-9_]{1,64}`) antes de interpolarse. `src/Support/Identifier.php` |
| **Contrasenas** | Argon2id cuando la extension esta disponible y bcrypt con cost 12 como respaldo. Un hash con parametros antiguos se recalcula en el siguiente acceso. `src/Security/PasswordHasher.php` |
| **Cabeceras** | CSP con nonce por peticion (`script-src` sin `unsafe-inline`), `X-Content-Type-Options`, `X-Frame-Options: DENY`, `Referrer-Policy`, `Permissions-Policy`, HSTS en produccion. **No se envia `X-XSS-Protection`**: la cabecera esta obsoleta. `src/Security/SecurityHeaders.php` |
| **Rate limiting** | Limite general por IP y limite mas estricto en acceso, con backend Redis o archivos. `src/Security/RateLimiter.php` |
| **Bloqueo de cuenta** | Historial de intentos fallidos por cuenta: bloquea aunque los intentos lleguen desde varias IP. `src/Repository/LoginAttemptRepository.php` |
| **Sesiones** | Cookie `HttpOnly`, `Secure`, `SameSite=Strict`, `use_strict_mode`, regeneracion del identificador al entrar, tiempo muerto de 30 minutos y duracion maxima de 8 horas. `src/Security/SessionManager.php` |
| **IP de cliente** | `X-Forwarded-For` solo se respeta si el remitente esta en la lista de proxies de confianza. `src/Security/ClientIp.php` |
| **Subidas** | Deteccion de tipo por contenido (`finfo`), limite de tamano, lectura de dimensiones y recodificacion con GD. Los archivos se guardan fuera del directorio publico y se sirven por una ruta que comprueba la propiedad. `src/Service/AvatarService.php` |
| **Salida** | Escape en la capa de plantillas con el helper `e()`. No se imprimen excepciones de MySQL al navegador: se registran en el log y se muestra un mensaje generico. `src/Support/Logger.php` |

El modelo de amenazas y las medidas pendientes estan en [SECURITY.md](SECURITY.md).

---

## Instalacion

### Con Docker (recomendado)

Requisitos: Docker Desktop.

```bash
git clone https://github.com/JuanCarlosBarronLopez/DataForge.git
cd DataForge
cp .env.example .env
docker compose up -d --build
```

| Servicio | URL |
|---|---|
| Aplicacion | http://localhost:8080 |
| phpMyAdmin | http://localhost:8081 |
| MySQL (puerto del host) | `localhost:3307` |

El contenedor de la aplicacion aplica las migraciones al arrancar. Para hacerlo a mano:

```bash
docker compose exec app php bin/console migrate
```

> El usuario de MySQL necesita permiso global `CREATE` y `DROP` para poder crear y eliminar
> bases de datos. `docker/mysql-init/01-privileges.sql` lo concede al levantar el volumen por
> primera vez. Si ya tienes el volumen creado, borralo con `docker compose down -v`.

### Sin Docker

Requisitos: PHP 8.4 o superior con `pdo_mysql` y `gd`, y MySQL 8.4.

```bash
composer install
cp .env.example .env      # ajusta DB_HOST, DB_USER, DB_PASS y SYSTEM_DB
php bin/console migrate
php -S localhost:8080 -t public
```

---

## Pruebas y calidad

```bash
composer install
composer check        # estilo + analisis estatico + pruebas
```

| Herramienta | Que comprueba | Comando |
|---|---|---|
| PHPUnit 13 | 130 pruebas unitarias y de integracion | `composer test` |
| PHPStan nivel 8 | Tipos, referencias y ramas no cubiertas | `composer analyse` |
| PHP-CS-Fixer | Norma PSR-12 y `strict_types` | `composer lint` |
| GitHub Actions | Las tres anteriores en cada push y en cada PR | `.github/workflows/ci.yml` |

Las pruebas de integracion necesitan un MySQL accesible. Se puede apuntar a uno con variables
de entorno:

```bash
DB_HOST=127.0.0.1 DB_PORT=3306 \
DB_USER=root DB_PASS=root_secret \
DB_ADMIN_USER=root DB_ADMIN_PASS=root_secret \
SYSTEM_DB=dataforge_test \
vendor/bin/phpunit
```

`DB_ADMIN_USER` y `DB_ADMIN_PASS` son los permisos con los que las pruebas crean y borran sus
propias bases de datos. Sin MySQL, la suite se omite en lugar de fallar.

Para levantar el entorno de herramientas sin PHP en el equipo:

```bash
docker build -t dataforge-dev -f docker/dev.Dockerfile docker
docker run --rm -v "$(pwd)":/app -w /app dataforge-dev composer check
```

---

## Despliegue

`render.yaml` describe el servicio para Render. La base de datos se conecta por
`DB_HOST`/`DB_PORT`/`DB_USER`/`DB_PASS`/`SYSTEM_DB` o por una unica `MYSQL_URL` en formato
`mysql://usuario:clave@host:puerto/base`, que tiene prioridad sobre las demas.

En Render el contenedor arranca Apache en el puerto que indica la plataforma y aplica las
migraciones antes de recibir trafico. `SKIP_MIGRATIONS=1` desactiva ese paso.

---

## Limitaciones conocidas

Estas limitaciones son reales y estan pendientes de resolverse:

1. **Los nombres de base de datos son globales en MySQL.** Si dos usuarios quieren una base con
   el mismo nombre, el segundo alta falla. No hay un espacio de nombres por usuario.
2. **La consola SQL es de solo lectura.** No hay forma de ejecutar `INSERT`, `UPDATE` ni
   `DELETE` desde la interfaz. Los cambios de datos pasan por el CRUD de registros.
3. **No hay verificacion de correo ni restablecimiento de contrasena.** Requieren un proveedor
   de correo que no forma parte de este proyecto.
4. **Los avatares se guardan en disco.** En un despliegue efimero se pierden al redesplegar.
   La solucion definitiva es un almacenamiento externo.
5. **La base de datos de sistema debe existir.** En proveedores que bloquean
   `CREATE DATABASE` (TiDB Cloud Serverless, PlanetScale) hay que crearla a mano.
6. **No hay interaccion en tiempo real.** No hay websockets ni sondeo: la pagina refleja el
   estado en el momento de cargarla.
7. **El busquador de la consola no distingue mayusculas ni acentos.** Usa `LIKE` con el
   collation por defecto de MySQL.
8. **La cuenta de un usuario no puede renombrar sus bases de datos.** Se pueden crear y
   eliminar, no renombrar.

---

## Estructura del proyecto

```
dataforge/
├── public/                # unico directorio expuesto por HTTP
│   ├── index.php          # front controller
│   ├── .htaccess          # reescritura y bloqueos
│   └── assets/            # CSS, JavaScript
├── src/
│   ├── Core/              # peticion, respuesta, router, middleware, vistas
│   ├── Http/              # controladores y middleware
│   ├── Service/           # reglas de negocio
│   ├── Repository/        # acceso a datos
│   ├── Database/          # conexiones, migraciones, tipos de columna
│   ├── Security/          # CSRF, sesiones, contrasenas, rate limit, cabeceras
│   ├── Support/           # validacion, identificadores, tema, rutas, log
│   ├── Exception/         # excepciones del dominio
│   └── Providers/         # registro de dependencias
├── config/                # app, base de datos, seguridad y rutas
├── templates/             # layouts, partials y paginas
├── database/migrations/   # esquema versionado
├── tests/                 # Unit, Feature y Support
├── storage/               # logs, cache, sesiones y avatares (fuera del repo)
├── bin/console            # consola de administracion
├── docker/                # configuracion de Apache, PHP e inicializacion de MySQL
├── .github/workflows/     # CI
├── composer.json
├── docker-compose.yml
├── Dockerfile
├── render.yaml
└── phpstan.neon
```

---

## Documentacion adicional

- [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md): arquitectura y decisiones de diseno
- [SECURITY.md](SECURITY.md): modelo de amenazas y medidas pendientes
- [CONTRIBUTING.md](CONTRIBUTING.md): como ejecutar y probar el proyecto
- [CHANGELOG.md](CHANGELOG.md): cambios por version

---

## Licencia

MIT. Ver [LICENSE](LICENSE).
