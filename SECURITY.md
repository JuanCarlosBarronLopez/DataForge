# Seguridad

Este documento describe el modelo de amenazas de DataForge, las medidas que estan
implementadas y las que no.

---

## 1. Que protege la aplicacion

DataForge da acceso a un servidor MySQL. El activo mas sensible es el **usuario de MySQL**:
con sus permisos se puede leer, escribir y borrar cualquier base de datos del servidor. Por
eso la mayor parte de las medidas apuntan a dos cosas:

1. Que solo quien creo una base de datos pueda usarla.
2. Que nadie pueda ejecutar una consulta arbitraria.

---

## 2. Las siete capas

Cada capa es una medida concreta con una implementacion identificable. No hay capas de
decorado: si algo no esta en el codigo, no aparece en esta tabla.

### 2.1 Tokens CSRF por accion

- Generados con `random_bytes(32)` y comparados con `hash_equals()`.
- Ligados a la ruta de destino: un token de `/databases` no vale para `/profile/delete`.
- Consumidos despues de cada intento, incluidos los fallidos.
- El token viaja solo en el cuerpo del formulario, nunca en la URL.
- `VerifyCsrfMiddleware` responde `403` sin ejecutar la accion si el token no es valido.

Implementacion: `src/Security/Csrf.php`, `src/Http/Middleware/VerifyCsrfMiddleware.php`.

### 2.2 Consultas parametrizadas e identificadores con lista blanca

- PDO con `ATTR_EMULATE_PREPARES => false`: los marcadores los resuelve el servidor.
- Los identificadores pasan por `Identifier`, que solo admite `[A-Za-z0-9_]{1,64}` y los
  entrecomilla. Ningun nombre de base, tabla o columna llega a la consulta sin pasar por ahi.
- El conjunto de columnas que acepta el formulario de registro se cruza con el esquema real
  de la tabla, asi que no se pueden escribir columnas que no existen.

Implementacion: `src/Database/ConnectionFactory.php`, `src/Support/Identifier.php`,
`src/Service/RecordService.php`.

### 2.3 Contrasenas

- Argon2id con 64 MB de memoria, 4 iteraciones y 2 hilos cuando la extension esta disponible.
- bcrypt con cost 12 como respaldo.
- `password_needs_rehash` recalcula un hash con parametros antiguos en el siguiente inicio de
  sesion.
- El hash nunca se guarda en la sesion ni se devuelve al navegador.

Implementacion: `src/Security/PasswordHasher.php`.

### 2.4 Cabeceras de respuesta

| Cabecera | Valor |
|---|---|
| `Content-Security-Policy` | `default-src 'self'`, `script-src 'self' 'nonce-...'`, `frame-ancestors 'none'`, `object-src 'none'`, `base-uri 'self'`, `form-action 'self'` |
| `X-Content-Type-Options` | `nosniff` |
| `X-Frame-Options` | `DENY` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Permissions-Policy` | `camera=(), microphone=(), geolocation=(), payment=(), usb=()` |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` (solo en produccion) |
| `Cross-Origin-Opener-Policy` | `same-origin` |
| `Cross-Origin-Resource-Policy` | `same-origin` |
| `X-Permitted-Cross-Domain-Policies` | `none` |
| `X-Download-Options` | `noopen` |
| `X-XSS-Protection` | `0` |

Sobre `X-XSS-Protection`: el auditor XSS heredado de los navegadores antiguos introduce
vulnerabilidades por si mismo, y los navegadores modernos lo ignoran. En lugar de omitir la
cabecera, se envia con valor `0`, que es lo que recomienda OWASP: desactiva de forma explicita
el auditor en los navegadores que todavia lo soportan, sin crear el problema.

Implementacion: `src/Security/SecurityHeaders.php`.

### 2.5 Limites de peticiones

| Ambito | Limite por defecto |
|---|---|
| General | 120 peticiones por minuto y IP |
| Acceso (login y registro) | 10 peticiones cada 5 minutos y IP |

El backend es Redis (contador atomico con script de Lua) o archivos (contador con bloqueo
exclusivo). El limite de acceso se combina con el historial de intentos fallidos por cuenta:
una cuenta se bloquea despues de 10 fallos en 15 minutos, con independencia de la IP.

Implementacion: `src/Security/RateLimiter.php`, `src/Repository/LoginAttemptRepository.php`.

### 2.6 Endurecimiento de la sesion

| Medida | Valor |
|---|---|
| Cookie | `HttpOnly`, `Secure` cuando hay HTTPS, `SameSite=Strict` |
| Identificador | `session.use_strict_mode = 1`, 48 caracteres |
| Fijado de sesion | `session_regenerate_id(true)` al iniciar sesion |
| Tiempo muerto | 30 minutos sin actividad |
| Duracion maxima | 8 horas |

La IP de cliente solo se toma de `X-Forwarded-For` cuando la peticion llega desde una IP
incluida en `TRUSTED_PROXIES`. Sin esa lista, la cabecera se ignora y se usa `REMOTE_ADDR`.

Implementacion: `src/Security/SessionManager.php`, `src/Security/ClientIp.php`.

### 2.7 Validacion de entrada y de salida

**Entrada.** `Validator` centraliza las reglas de formato (usuario, correo, contrasena) y
`Identifier` las de los nombres de objeto. La validacion se aplica en el servicio, no en la
vista, para que ninguna ruta pueda saltarsela.

**Salida.** Todas las plantillas imprimen a traves del helper `e()`, que aplica
`htmlspecialchars` con `ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5`. La afirmacion no es una
promesa: `bin/check-escape.php` revisa las 200 expresiones de `templates/` y falla la
integracion continua si alguna imprime un dato sin escapar.

**Subidas.** Tipo detectado por contenido (`finfo`), no por extension; comprobacion de
dimensiones; recodificacion con GD, que elimina metadatos y contenido incrustado; nombre
aleatorio de 32 caracteres; almacenamiento en `storage/uploads/avatars`, fuera del
directorio publico; entrega por una ruta que comprueba la propiedad; motor PHP desactivado en
ese directorio por Apache.

Implementacion: `src/Support/Validator.php`, `src/Support/Identifier.php`, `src/helpers.php`,
`src/Service/AvatarService.php`, `bin/check-escape.php`.

---

## 3. Otras medidas

### Aislamiento entre cuentas

`managed_databases` registra el propietario de cada base de datos. Toda operacion
(`DatabaseService::assertOwnership`) comprueba esa propiedad antes de tocar el servidor.

Efecto: un usuario autenticado no puede ver, abrir, modificar ni eliminar una base de datos
que no creo. Tampoco puede leer la base de datos de sistema, porque no aparece en su
registro.

### Consola SQL de solo lectura

`SqlGuard` es la unica via para ejecutar SQL escrito a mano:

- Un solo enunciado por ejecucion (se rechazan los punto y coma intermedios).
- Solo verbos de lectura: `SELECT`, `SHOW`, `DESCRIBE`, `EXPLAIN`, `WITH`.
- Limite de filas automatico si la consulta no trae `LIMIT`.
- Tiempo de ejecucion maximo (`MAX_EXECUTION_TIME`).
- El historial queda registrado por usuario.

### Manejo de errores

Las excepciones de MySQL no llegan al navegador. `ErrorHandlerMiddleware` las registra en
`storage/logs` (en JSON, con las claves sensibles redactadas) y devuelve una pagina
generica. En modo debug se muestra la traza, y ese modo solo se activa con `APP_DEBUG=true`.

### Registro

Los registros se guardan en JSON, una linea por evento. `Logger` redacta los valores de las
claves que contienen `password`, `secret`, `token`, `authorization`, `cookie` o `key`. Se
conservan 30 archivos diarios como maximo.

---

## 4. Amenazas conocidas y estado

| Amenaza | Estado |
|---|---|
| Inyeccion SQL en valores | Mitigada (consultas parametrizadas) |
| Inyeccion SQL en identificadores | Mitigada (lista blanca) |
| Fijado de sesion | Mitigada (regeneracion y `use_strict_mode`) |
| CSRF | Mitigada (token por accion) |
| Clickjacking | Mitigada (`DENY` y `frame-ancestors 'none'`) |
| XSS por HTML inyectado | Mitigada y verificada automaticamente (`check-escape`) |
| Subida de archivo ejecutable | Mitigada (deteccion por contenido, recodificacion, fuera del directorio publico) |
| Fuerza bruta | Mitigada (limite por IP y bloqueo por cuenta) |
| Escalada entre cuentas | Mitigada (propiedad de bases de datos) |
| Filtracion de errores al navegador | Mitigada (manejo de errores centralizado) |
| Suplantacion de IP para evadir limites | Mitigada (lista de proxies de confianza) |
| Inyeccion en cabeceras HTTP | Mitigada (las redirecciones usan rutas internas validadas) |
| Auditor XSS heredado | Mitigada (`X-XSS-Protection: 0`) |
| Fuga por el codigo fuente | Mitigada (solo `public/` es accesible) |
| Denial of service por consultas pesadas | Parcialmente mitigada (`MAX_EXECUTION_TIME` y limite de filas) |
| Robo de sesion por cookie sin marca `Secure` | Mitigado en produccion (se detecta HTTPS y el proxy) |

---

## 5. Lo que falta

Estas medidas **no estan implementadas**. Se listan para que nadie las de por supuestas:

1. **Verificacion de correo electronico.** Una cuenta se crea con cualquier correo.
2. **Restablecimiento de contrasena.** No hay flujo de recuperacion.
3. **Sesiones compartidas entre instancias.** Con mas de un contenedor, las sesiones en
   archivos no se ven entre si.
4. **API.** No hay superficie HTTP adicional que proteger, pero tampoco hay tokens de
   aplicacion.
5. **Cifrado en reposo de los datos del usuario.** Solo las contrasenas se guardan
   transformadas.
6. **Alertas de seguridad.** Los eventos sospechose se registran, pero no se notifica a nadie.
7. **Copia de seguridad automatica.** `storage/` y las bases de datos del usuario estan en
   manos del operador.
8. **`style-src 'unsafe-inline'` en la CSP.** Aceptado a conscious: CSS no puede ejecutar
   codigo, pero no es la configuracion mas estricta posible.
9. **Cifrado en transito hacia MySQL por defecto.** Se puede exigir TLS con `DB_SSL_CA`, pero
   no se impone: en el despliegue local MySQL no lo necesita.

---

## 6. Reportar un problema

Si encuentras una vulnerabilidad, no abras un issue publico. Escribe a
`juan.carlos.barron.lopez@outlook.com` con:

- Que ruta o endpoint esta afectado.
- Pasos para reproducirlo.
- El impacto que observaste.

Se confirma la recepcion y se publica una correccion junto con una nota en el
[CHANGELOG.md](CHANGELOG.md).
