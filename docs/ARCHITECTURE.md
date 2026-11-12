# Arquitectura de DataForge

Este documento describe como esta construido el proyecto y por que se tomaron estas decisiones.
No describe patrones que el codigo no implemente.

---

## 1. Vision general

DataForge es una aplicacion PHP nativa con un unico punto de entrada y tres capas debajo:

```
Navegador
   |
   v
public/index.php ............ front controller
   |
   v
Application::handle() ....... cadena de middleware
   |
   v
Router::dispatch() .......... resolucion de ruta y middleware por ruta
   |
   v
Controller .................. coordina la peticion y la respuesta
   |
   +--> Service ............. reglas de negocio
   |      |
   |      +--> Repository ... consultas sobre PDO
   |
   +--> View ................. plantillas PHP en templates/
```

**Lo que el proyecto no es.** No es MVC: no hay un modelo, un controlador y una vista con
contratos entre ellos. No es ADR: no hay acciones, despachadores ni manejadores de
respuesta. No hay ORM, ni capa de eventos, ni inyeccion de dependencias por anotaciones.
Afirmar cualquiera de esas cosas seria falso, asi que no se afirma.

Lo que si es: un **front controller con enrutador, cadena de middleware y capas
servicio/repositorio**. El nombre correcto para eso es "aplicacion por capas" (layered
architecture), y es lo que se aplica.

---

## 2. Decisiones y su porque

### 2.1 Sin framework

**Decision.** PHP nativo, sin Laravel, Symfony ni ningun otro esqueleto.

**Por que.** El proyecto es academico y el valor esta en mostrar los mecanismos, no en
aprender una API. Ademas, un framework habria cambiado la pregunta de "como funciona esto"
por "como se configura esto".

**Consecuencia.** Hay que escribir a mano el enrutador, el contenedor, las plantillas y el
manejo de errores. Son unas 900 lineas en total, y cada una esta cubierta por pruebas.

### 2.2 Front controller y directorio publico

**Decision.** Todo el trafico entra por `public/index.php` y Apache sirve `public/` como
`DocumentRoot`. El codigo, las plantillas, las migraciones y los registros quedan fuera del
directorio accesible.

**Por que.** Es la unica forma de que `src/`, `config/` y `storage/` no sean descargables por
error. En la version 3.2 la raiz del proyecto era el `DocumentRoot` y bastaba con bloquear
algunas carpetas con `.htaccess`: un `.htaccess` mal puesto dejaba el codigo expuesto.

**Consecuencia.** Las rutas publicas son absolutas (`/databases`, `/login`). Los enlaces se
construyen con `DataForge\Support\Url` para que la ruta del formulario y la del token CSRF
sean siempre la misma cadena.

### 2.3 Enrutador con parametros de ruta

**Decision.** Las rutas se declaran en `config/routes.php` con marcadores `{nombre}`:

```php
['GET', '/databases/{database}/tables/{table}', [RecordController::class, 'index'], [RequireAuthMiddleware::class]],
```

**Por que.** Declarar las rutas en un solo archivo hace que el mapa de la aplicacion se pueda
leer de un vistazo y que el middleware se declare junto a la ruta que protege.

**Consecuencia.** Los nombres de base de datos y de tablas viajan en la URL. Como los
identificadores no pueden llevar caracteres especiales, se validan contra una lista blanca
antes de interpolarlos en SQL (ver 2.6).

### 2.4 Middleware en dos niveles

**Decision.** Hay un middleware global (cabeceras de seguridad, manejo de errores, sesion,
rate limit) y un middleware por ruta (autenticacion, token CSRF).

**Por que.** La autenticacion y el CSRF solo tienen sentido en ciertas rutas. Aplicarlos a
todo obligaria a marcarlos como excepcion en la portada o en la documentacion, que es
justo el tipo de olvido que produce una fuga.

**Consecuencia.** Cada ruta que modifica datos declara `VerifyCsrfMiddleware` y, si necesita
sesion, `RequireAuthMiddleware`. El orden importa: primero se verifica el token y despues se
comprueba la sesion.

### 2.5 Contenedor de dependencias con reflexion

**Decision.** `src/Core/Container.php` resuelve dependencias por reflexion sobre el
constructor. Lo que no se puede construir solo (la fabrica de conexiones, el logger, la
vista) se registra de forma explicita en `src/Providers/AppServiceProvider.php`.

**Por que.** Evita el wiring manual en cada controlador sin introducir un contenedor de
terceros. El coste es reflection en tiempo de ejecucion, irrelevante frente a una consulta a
MySQL.

**Consecuencia.** Los controladores declaran en su constructor lo que necesitan y no saben de
donde viene. Los servicios, en cambio, reciben colaboradores explicitos: `TableService` no
crea conexiones, las recibe.

### 2.6 Identificadores: lista blanca, no escape

**Decision.** Todo identificador de MySQL (base de datos, tabla, columna) pasa por
`DataForge\Support\Identifier` antes de interpolarse.

```php
Identifier::quote('clientes');   // '`clientes`'
Identifier::quote('mi tabla');   // InvalidArgumentException
```

**Por que.** PDO no admite marcadores para nombres de objeto, asi que hay que interpolarlos.
La version 3.2 usaba `real_escape_string()`, que **no escapa el acento grave**, de modo que
un nombre de tabla podia cerrar el identificador y continuar la sentencia. La lista blanca
`[A-Za-z0-9_]{1,64}` elimina esa clase de fallo por completo, y despues se entrecomilla
como segunda barrera.

**Consecuencia.** No se pueden crear tablas ni columnas con acentos, espacios ni guiones
medios. Es una restriccion aceptable a cambio de una garantia simple de revisar.

### 2.7 PDO con prepared statements reales

**Decision.** PDO con `ATTR_EMULATE_PREPARES => false`.

**Por que.** Con la emulacion activada, PDO escapa la consulta en PHP y la envia como texto.
Con la emulacion desactivada, el enunciado llega al servidor con marcadores reales y MySQL
hace el trabajo. La segunda opcion es la que evita que un valor con una comilla rompa la
sentencia.

**Consecuencia.** Los `IN (...)` con numero variable de elementos no se pueden construir con
marcadores. `RecordService` lo resuelve con el filtro de columnas: el conjunto de columnas
que acepta el formulario se cruza con el esquema real de la tabla, asi que la lista es
estable y se puede parametrizar.

### 2.8 Aislamiento entre cuentas

**Decision.** La tabla `managed_databases` registra que usuario es propietario de cada base de
datos. Toda operacion sobre una base comprueba esa propiedad.

**Por que.** En la version 3.2 la aplicacion era "multiusuario" solo en el sentido de que
varias cuentas podian entrar: cualquier usuario autenticado veia, abria, modificaba y
eliminaba **todas** las bases de datos del servidor MySQL. Cuatro consecuencias concretas:

- La base de datos de sistema (`dataforge_system`) no estaba en la lista de bases protegidas,
  asi que `eliminar_db.php` podia ejecutar `DROP DATABASE dataforge_system` y borrar todas las
  cuentas.
- `records.php?dbName=dataforge_system&tableName=users` mostraba el `password_hash` de todos
  los usuarios a cualquiera que hubiera iniciado sesion.
- La tabla `users` se podia editar y borrar desde el CRUD habitual.
- No habia forma de saber quien habia hecho que.

**Consecuencia.** El modelo es mas correcto pero tambien mas restrictivo: una base de datos
creada por fuera de la aplicacion no es visible hasta que su propietario la registre. Hoy no
hay forma de "adoptar" una base existente, y esa es una funcionalidad pendiente.

### 2.9 Sesiones en el sistema de archivos

**Decision.** Sesiones en archivos bajo `storage/framework/sessions`, con
`session.use_strict_mode`, regeneracion del identificador al iniciar sesion, tiempo muerto de
30 minutos y duracion maxima de 8 horas.

**Por que.** El modo estricto impide que un identificador de sesion inventado sea aceptado, y
la regeneracion evita el fijado de sesion: si alguien captura el identificador antes del
acceso, el identificador valido despues del acceso es distinto.

**Consecuencia.** En un despliegue con varias instancias, las sesiones no se comparten. Con
un solo contenedor de aplicacion (el caso de Render en el plan gratuito) no hay problema. Con
varias instancias habria que migrar a Redis o a una tabla.

### 2.10 Tokens CSRF por accion

**Decision.** Cada formulario lleva un token ligado a la ruta de destino. Se genera con
`random_bytes(32)`, se compara con `hash_equals()` y se consume despues de **cada** intento,
incluidos los fallidos.

**Por que.** La version 3.2 usaba un unico token por sesion compartido por todos los
formularios. Eso tiene dos fallos: un token capturado servia para cualquier accion, y un
intento fallido destruia el token de todas las pestanas abiertas, lo que hacia fallar el
formulario que el usuario estuviera usando en otra pestana.

**Consecuencia.** La validacion necesita que la ruta de destino se escriba dos veces: una en
el `action` del formulario y otra en el token. `Url` centraliza la construccion de rutas para
que las dos coincidan.

### 2.11 CSP con nonce

**Decision.** `script-src 'self' 'nonce-...'` con un nonce distinto por peticion. Sin
`unsafe-inline` en los scripts.

**Por que.** `unsafe-inline` en `script-src` desactiva la proteccion principal de la CSP:
cualquier inyeccion de HTML que encuentre un `<script>` se ejecuta. Con nonce, solo el script
marcado con ese valor se ejecuta.

**Consecuencia.** `style-src` si admite `unsafe-inline`, porque el diseno aplica variables
de color de forma dinamica en atributos `style`. El riesgo es menor: CSS no puede ejecutar
codigo. Es una decision consciente y documentada, no un descuido.

### 2.12 Consola SQL de solo lectura

**Decision.** `SqlGuard` acepta un unico enunciado que empiece por `SELECT`, `SHOW`,
`DESCRIBE`, `EXPLAIN` o `WITH`. Rechaza cualquier otro, aplica un limite de filas y fija un
tiempo de ejecucion maximo.

**Por que.** Una consola que ejecuta lo que se le escriba es una puerta abierta: cualquier
error de autorizacion en el resto de la aplicacion se convierte en escritura arbitraria. El
limite de filas evita que un `SELECT` sin `WHERE` tumbe el servidor.

**Consecuencia.** Para cambiar datos hay que usar el CRUD de registros, que aplica las
reglas de propiedad y deja rastro en la bitacora.

### 2.13 Plantillas en PHP plano

**Decision.** Las plantillas son archivos PHP con `extract()` y un entorno con `$content`,
`$pageTitle`, `$currentUser` y `$flash`.

**Por que.** Es lo que permite usar PHP en la vista sin un motor de plantillas mas. El
escape se hace con el helper `e()`.

**Consecuencia.** El escape es responsabilidad de quien escribe la plantilla. La mitigacion es
que el helper `e()` esta disponible en todas las vistas y que las pruebas de integracion
comprueban que un nombre de usuario con `<script>` no llega sin escapar.

---

## 3. Estructura de la base de datos

### Base de datos de sistema

Es la unica que la aplicacion administra por si misma. Contiene:

| Tabla | Para que sirve |
|---|---|
| `users` | Cuentas, tema, modo de color y avatar |
| `managed_databases` | Propiedad de cada base de datos (aislamiento entre cuentas) |
| `activity_log` | Bitacora de auditoria por usuario |
| `login_attempts` | Intentos de acceso, por cuenta y por IP |
| `query_history` | Consultas ejecutadas desde la consola |
| `schema_migrations` | Migraciones aplicadas |

### Bases de datos del usuario

Cada usuario crea las suyas con el permiso `CREATE` global del usuario de MySQL. La
aplicacion solo registra de quien es cada una; no las migra ni las modifica por su cuenta.

---

## 4. Migraciones

`database/migrations/` contiene archivos PHP que devuelven las sentencias `up` y `down`.
`Migrator` las aplica en orden alfabetico y registra cada una en `schema_migrations`.

```php
<?php

return [
    'up' => [
        'CREATE TABLE IF NOT EXISTS `ejemplo` (...)',
    ],
    'down' => [
        'DROP TABLE IF EXISTS `ejemplo`',
    ],
];
```

El orden es el del nombre del archivo, con prefijo numerico para insertar una migracion en
medio. La consola aplica las pendientes:

```bash
php bin/console migrate
php bin/console migrate:status
```

La version 3.2 creaba el esquema con `CREATE TABLE IF NOT EXISTS` dentro del propio registro
y anadia columnas con `SHOW COLUMNS` a mano. Eso no tiene historial, no se puede deshacer y
dos instalaciones podian divergir.

---

## 5. Registro de dependencias

`src/Providers/AppServiceProvider.php` declara que se puede resolver y como:

| Dependencia | Como se resuelve |
|---|---|
| `ConnectionFactory` | Configuracion (`config/database.php`) |
| `\PDO` | Conexion a la base de datos de sistema |
| Repositorios | Con el `\PDO` del sistema |
| `SessionManager` | Tiempos de `config/security.php` |
| `RateLimitStore` | Redis o archivos, segun `RATE_LIMIT_STORAGE` |
| `View` | Rutas de `config/app.php` |
| `Logger` | `storage/logs` |

Todo lo demas se construye por reflexion.

---

## 6. Ciclo de una peticion

1. `public/index.php` crea la `Application` y llama a `handle()`.
2. `SecurityHeadersMiddleware` genera un nonce y prepara las cabeceras.
3. `ErrorHandlerMiddleware` envuelve el resto: cualquier excepcion se registra y se responde
   con una pagina generica, sin filtrar el mensaje de MySQL al navegador.
4. `StartSessionMiddleware` inicia la sesion con los parametros de endurecimiento.
5. `RateLimitMiddleware` cuenta la peticion contra el limite que corresponda.
6. `Router::dispatch()` busca la ruta y ejecuta su middleware.
7. El controlador pide los datos al servicio y devuelve una `Response`.
8. `View::render()` produce el HTML con el layout.

---

## 7. Decisiones pendientes

No son errores, son decisiones pendientes de resolver:

- **Estado de sesion compartido.** Con varias instancias de la aplicacion habria que migrar
  las sesiones a Redis.
- **Adopcion de bases existentes.** Hoy solo se ven las bases que creo la aplicacion.
- **Sin API.** La consola SQL y el CRUD son la unica interfaz. Una API REST seria el
  siguiente paso natural, y el enrutador y los servicios ya estan preparados para eso.
- **Sin verificacion de correo.** Requiere un proveedor de correo.
- **Sin cola de trabajos.** Las exportaciones grandes se generan de forma sincrona.
