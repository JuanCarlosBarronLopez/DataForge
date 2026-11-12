# Como contribuir

DataForge es un proyecto academico que se mantiene como portfolio. Las contribuciones son
bienvenidas, con una condicion: **cada cambio llega con su prueba**.

---

## Requisitos

- PHP 8.4 o superior con las extensiones `pdo_mysql` y `gd`.
- Composer 2.
- MySQL 8.4 (opcional, solo para las pruebas de integracion).

No hace falta instalar PHP en el equipo: hay una imagen de desarrollo con todo listo.

```bash
docker build -t dataforge-dev -f docker/dev.Dockerfile docker
```

---

## Puesta en marcha

```bash
git clone https://github.com/JuanCarlosBarronLopez/DataForge.git
cd DataForge
composer install
cp .env.example .env
docker compose up -d --build
```

La aplicacion queda en http://localhost:8080 y phpMyAdmin en http://localhost:8081.

---

## Antes de enviar un cambio

Ejecuta las tres comprobaciones. Son las mismas que corre la integracion continua:

```bash
composer check
```

| Comando | Que hace |
|---|---|
| `composer lint` | Comprueba la norma PSR-12 y que todos los archivos declaren `strict_types` |
| `composer analyse` | Analisis estatico con PHPStan en nivel 8 |
| `composer test` | Ejecuta la suite de PHPUnit |

Si prefieres separarlos:

```bash
composer lint
composer analyse
composer test
```

### Correr las pruebas de integracion

Necesitan un MySQL accesible:

```bash
DB_HOST=127.0.0.1 \
DB_PORT=3306 \
DB_USER=root \
DB_PASS=root_secret \
DB_ADMIN_USER=root \
DB_ADMIN_PASS=root_secret \
SYSTEM_DB=dataforge_test \
vendor/bin/phpunit
```

`DB_ADMIN_USER` y `DB_ADMIN_PASS` son las credenciales con permiso para crear y borrar las
bases de datos que usan las pruebas. Si no hay MySQL, esas pruebas se omiten y el resto de
la suite se ejecuta igual.

---

## Como esta organizado el codigo

```
src/
├── Core/         peticion, respuesta, router, middleware, plantillas
├── Http/         controladores y middleware
├── Service/      reglas de negocio
├── Repository/   acceso a datos
├── Database/     conexiones, migraciones, tipos de columna
├── Security/     CSRF, sesiones, contrasenas, rate limit, cabeceras
├── Support/      validacion, identificadores, tema, rutas, registro
└── Exception/    excepciones del dominio
```

Reglas que se aplican:

- **Los controladores no hablan con PDO.** Piden datos a un servicio y devuelven una
  `Response`.
- **Los servicios no construyen sus dependencias.** Las reciben en el constructor.
- **Los repositorios no contienen reglas de negocio.** Solo consultas.
- **Ningun identificador de MySQL se interpola sin pasar por `Identifier`.**
- **Ninguna excepcion de MySQL llega al navegador.**

---

## Estilo

PSR-12, con estas reglas adicionales:

- `declare(strict_types=1);` en la primera linea de todo archivo PHP.
- Tipos completos en las firmas de metodo, incluidos los parametros y el retorno.
- Comentarios que explican el porque, no el que. Un comentario que repite la linea siguiente
  se borra.
- Sin emojis en el codigo ni en la interfaz.

`composer format` aplica el estilo automaticamente.

---

## Migraciones

Si el cambio toca el esquema:

1. Crea el archivo en `database/migrations/` con prefijo numerico siguiente:
   `006_descripcion_corta.php`.
2. Devuelve el array con `up` y `down`.
3. Comprueba que `php bin/console migrate` y `php bin/console migrate:status` funcionan.

```php
<?php

return [
    'up' => [
        'ALTER TABLE `users` ADD COLUMN `telefono` VARCHAR(30) NULL',
    ],
    'down' => [
        'ALTER TABLE `users` DROP COLUMN `telefono`',
    ],
];
```

---

## Pull requests

1. Crea una rama con un nombre descriptivo.
2. Anade las pruebas que cubren el cambio.
3. Comprueba que `composer check` pasa.
4. Explica en la descripcion **que** cambias y **por que**.

Si el cambio toca la seguridad, actualiza tambien [SECURITY.md](SECURITY.md) y la seccion
correspondiente del [README.md](README.md).
