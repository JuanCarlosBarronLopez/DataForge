# Changelog

Formato basado en Keep a Changelog. Las versiones siguen
[Semantic Versioning](https://semver.org/lang/es/).

## [4.0.0]

Reescritura completa del nucleo. La version 3.2 sigue disponible en el historial de Git.

### Anadido

- Front controller con enrutador de rutas con parametros, cadena de middleware y contenedor
  de dependencias por reflexion.
- **Tiempo real**: canal de Server-Sent Events (`GET /events`) que mantiene al dia las metricas
  y la bitacora del panel sin recargar la pagina.
- Aislamiento entre cuentas: cada base de datos tiene un propietario y solo su propietario
  puede usarla.
- Consola SQL de solo lectura con limite de filas, tiempo de ejecucion maximo e historial.
- Exportacion de tablas en CSV y de bases de datos en `.sql`.
- Bitacora de auditoria por usuario con vista propia.
- Migraciones de esquema versionadas, aplicadas con `php bin/console migrate`.
- Consola de administracion (`migrate`, `migrate:status`, `routes`, `version`).
- Siete paletas de color con modo claro y oscuro, con contraste verificado contra WCAG AA.
- Exportacion e importacion de avatares validados por contenido y recodificados con GD.
- **Comprobacion automatica del escape de la salida** (`bin/check-escape.php`): revisa las
  expresiones de las plantillas y falla la integracion continua si alguna imprime un dato sin
  pasar por `e()`.
- Suite de 135 pruebas unitarias y de integracion, PHPStan en nivel 8, PHP-CS-Fixer y
  integracion continua en GitHub Actions.
- Documentacion: README, arquitectura, modelo de amenazas y guia de contribucion.

### Cambiado

- **PHP 8.5.12** como version de ejecucion y requisito minimo: `composer.json` exige
  `>=8.5`. Es la estable mas reciente publicada por el proyecto PHP e incluye las
  correcciones de seguridad de octubre y noviembre de 2026. El analisis estatico apunta a
  `phpVersion: 80500` y la CI ejecuta la suite sobre 8.5.
- `actions/checkout` pasa de v4 a v7 en la integracion continua.
- MySQLi por PDO con `ATTR_EMULATE_PREPARES => false`.
- Sesiones en `storage/framework/sessions`, con regeneracion del identificador, tiempo
  muerto y duracion maxima.
- Contrasenas con Argon2id y recalculo de hashes antiguos.
- Contenedores en PHP 8.5 y MySQL 8.4, con script de inicializacion que concede los
  permisos `CREATE` y `DROP` al usuario de la aplicacion.
- Interfaz redisenada: se retiraron los emojis, los degradados y los patrones decorativos.

### Corregido

- **La base de datos de sistema era eliminable.** `dataforge_system` no estaba en la lista
  de bases protegidas, asi que cualquier usuario autenticado podia ejecutar
  `DROP DATABASE dataforge_system` y borrar todas las cuentas.
- **Los hashes de contrasena eran visibles.** `records.php` permitia abrir la tabla `users`
  de la base de datos de sistema y leer la columna `password_hash`.
- **Inyeccion SQL en los nombres de tabla y columna.** `real_escape_string()` no escapa el
  acento grave, de modo que un nombre podia cerrar el identificador y continuar la
  sentencia. Ahora todo identificador pasa por una lista blanca.
- **Cierre de sesion y seeder de datos sin token CSRF.** Ambos eran accesibles con un `GET`.
- **El token CSRF era unico por sesion.** Servia para cualquier accion y un intento fallido
  invalidaba el token de las demas pestanas abiertas.
- **Fijado de sesion.** No se regeneraba el identificador al iniciar sesion.
- **Filtracion de errores de MySQL al navegador.** Se mostraba el mensaje original de la
  excepcion en varias pantallas.
- **La subida de avatar no validaba el contenido.** Se comprobaba solo la extension y el
  nombre se generaba con `md5(time())`, que es predecible.
- **El avatar antiguo se borraba antes de guardar el nuevo**, dejando al usuario sin
  imagen si el guardado fallaba.
- **El perfil no escapaba el nombre de usuario ni la ruta del avatar** al insertarlos en
  HTML.
- **El selector de tema no hacia nada.** `change_theme.php` era inalcanzable y
  `update_profile.php` ignoraba el campo `theme`.
- **La cabecera `X-XSS-Protection` seguia activa.** La cabecera esta obsoleta; ahora se envia
  con valor `0`, que es lo que recomienda OWASP: desactiva el auditor heredado de los
  navegadores antiguos sin crear el problema.
- **La cabecera `X-Forwarded-For` se aceptaba sin comprobar el proxy**, lo que permitia
  evadir el rate limit falseando la IP.
- **La cuenta no se bloqueaba por intentos fallidos**, solo por IP.

### Eliminado

- Las clases de `src/` que solo delegaban en funciones globales y no las usaba ninguna
  pagina.
- `inyeccion_datos.php`, un seeder que truncaba y volvia a poblar tres tablas en cada
  peticion, accesible por `GET` y sin token.
- `documentacion.php` y `sobre_nosotros.php` como paginas sueltas con su propio HTML: ahora
  son plantillas que comparten el layout.
- La carpeta `uploads/` en la raiz: los archivos se guardan en `storage/uploads/`.
- El video de demostracion de 19 MB.
- El blueprint de Render (`render.yaml`) y la seccion de despliegue asociada.
- Toda atribucion a la empresa ficticia de la dinamica del curso. La autoria es de OMNARA,
  fundada por el autor del proyecto.

## [3.2.0]

- Bases de datos, tablas y registros con formularios dinamicos.
- Siete temas visuales con modo claro y oscuro.
- Consola de administracion basica.
- Suites de pruebas PHPUnit para autenticacion, CRUD y seguridad.

## [3.0.0]

- Reorganizacion del proyecto en modulos `auth/`, `database/`, `tables/`, `records/` y
  `account/`.
- Cabeceras de seguridad y CSP basica.
- Rate limiting por IP con almacenamiento en archivos.

## [2.0.0]

- Gestion de bases de datos MySQL desde el navegador.
- Cuentas de usuario y sesiones.
