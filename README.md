# 🔷 DataForge CRUD Manager v3.1

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://mysql.com)
[![License: MIT](https://img.shields.io/badge/License-MIT-06b6d4?style=flat-square)](LICENSE)
[![Status](https://img.shields.io/badge/Status-Producci%C3%B3n-10b981?style=flat-square)]()

**DataForge** ha evolucionado en su versión 3.1. Ya no es solo un gestor aislado, sino un **Ecosistema Multi-Usuario** altamente dinámico y profesional. Diseñado en PHP nativo, permite la manipulación relacional de bases de datos bajo identidades fortificadas, una estética desbordantemente moderna e inyección de datos de prueba, garantizando siempre cero tolerancia a fallas de seguridad.

---

## ✨ Novedades Arquitectónicas v3.1

| Característica | Detalles Técnicos |
|---------|-------------|
| 🔐 **Módulo de Identidad Fuerte** | Sistema de registro y login en `/auth/` blindado por `PASSWORD_BCRYPT`. Toda operación en bases de datos requiere validación middleware de sesión. |
| 🏰 **Migraciones Automáticas** | Auto-instala la base de datos `dataforge_system` de manera silenciosa para albergar el arbol de usuarios, auditoría y personalizaciones visuales. |
| 🎨 **Motor de 7 Temas Globales** | Interfaz mutante adaptable a diferentes industrias *(Tech Dark, MediCare, FoodPro, IronForge, LexDesk, EduBase, ShopFlow)*. Los estilos CSS se cargan como variables de alto rendimiento. |
| 🌗 **Light Mode & Alto Contraste** | Interruptor en vivo (`☀️ Claro / 🌙 Oscuro`) inyectado en el DOM con previsualización drag & drop interactiva en el panel de perfil. |
| 📸 **Avatares Live-Preview** | Motor de carga de fotografías locales alojadas de forma persistente (`/uploads/avatars/`). Soporta recorte circular CSS e intercepción visual mediante JavaScript `FileReader` y limpieza dinámica. |
| 🎯 **Matemática de Registros Exacta** | Migración tecnológica en el _Dashboard_ para abandonar el cómputo impreciso de `information_schema`. DataForge ahora efectúa lecturas literales en vivo mediante `SELECT COUNT(*)` para auditorías garantizadas. |
| 🧪 **Inyector DB (Seeder Ficticio)** | Módulo integrado (`inyeccion_datos.php`) para levantar métricas tangibles que nutre tu panel con "Data Dummies" en 3 tablas locales automáticamente, facilitando auditorías del sistema. |

---

## 🏛️ Desglose Estructural CRUD 

Manteniendo nuestra sólida herencia estructural, DataForge permite las funciones tradicionales a las bases de datos de clientes:

*   **🗄️ Entornos Aislados:** Motor puro para crear/eliminar bases de datos locales.
*   **📊 Diseñador de Tablas (UI puro):** Evita la línea de comandos. Interfaz constructora de columnas estricta (INT, VARCHAR, TEXT, DATE, BOOLEAN). Capacidad para destruir columnas ("Drop Column") en vivo.
*   **📝 Gestión Documental:** Formularios inteligentes que detectan tipos locales (`is_int`, `is_float`) evitando violaciones del tipo de dato en sentencias SQL. 
*   **🛡️ Zona de Peligro Estricta:** Requerimiento imperativo de confirmación vía _Browser Confirm_ para destrucción de cuentas y logs en un solo clic.

---

## 📁 Estructura del Proyecto

```
dataforge/
├── config.php               # Singleton de configuración (.env loader)
├── .env.example             # Plantilla de variables de entorno (BD_HOST, USER)
├── index.php / dashboard.php# Landing y Panel Maestro (Ruteo seguro)
├── inyeccion_datos.php      # [NUEVO] Seeder Generador de Mock-Data local.
├── themes.css / style.css   # [UPDATE] Design System dinámico (variables modo claro/oscuro)
│
├── account/                 # [NUEVO] Dominio: Identidad Visual
│   ├── profile.php          # Panel Interactivo (Avatares, Inline Edits, Peligros)
│   ├── update_profile.php   # Interceptor de Fotografía local / Hash y borrado.
│   └── delete_account.php   # Purga eterna (DB Destructor)
│
├── auth/                    # [NUEVO] Dominio: Autenticación
│   └── auth_functions.php   # Handlers CSRF, BCRYPT, Migrador DB (Alter Table automático)
│
├── includes/                # Templates y Micro-Componentes
│   └── flash.php            # Notifications de eventos (Éxito / Fallos destructivos)
│
├── databases/, tables/, records/  # CORE: Formularios CRUD Originales 
│
└── uploads/avatars/         # Fosas inter-operables para profile_pics.
```

---

## 🚀 Despliegue Inmediato 

### Requisitos Mínimos
- Entorno de Apache.
- PHP 8.0+
- Servidor local MySQL 5.7+ / MariaDB 10.4+  *(XAMPP/WAMP).*

### Instalación a Prueba de Balas

1. **Clonar e inyectar al servidor local:**
```bash
git clone https://github.com/JuanCarlosBarronLopez/DataForge.git
cp -r DataForge C:/xampp/htdocs/dataforge_v3
```

2. **Copiar credenciales de entorno:**
```bash
cd C:/xampp/htdocs/dataforge_v3
cp .env.example .env
```

3. **Inyectar credenciales (en `.env`):**
Apunta directamente a tu gestor MySQL (normalmente `root` y clave vacía o tu propia clave).
```ini
DB_HOST=localhost
DB_USER=root
DB_PASS=1924pk77
```

4. **Encendido Automático:**
Simplemente navega a `http://localhost/dataforge_v3/`. Regístrate por primera vez. **¡DataForge auto-construirá su infraestructura interna (`dataforge_system`) sin requerir comandos de creación!** 

5. **Llenar de datos falsos (Opcional):**
Si quieres probar el sistema, abre `http://localhost/dataforge_v3/inyeccion_datos.php` y en un segundo se minarán 3 tablas de prueba.

---

## 🔒 Estándares de Seguridad

- **Cierre del Vector CSRF:** Ningún borrado (ni de registros ni de cuenta) acepta peticiones originadas desde dominios cruzados; todas usan un token hash de un solo uso.
- **Consultas Prepared (PDO/MySQLi):** Prevención matemática contra la inyección de sentencias colaterales `('; DROP TABLE users;)`.
- **Estructuración Aislada:** Modificación al motor principal de `installSystemDb()` para reparar automáticamente columnas faltantes o variables de caché `null` dejadas por versiones anteriores del producto.

---

## 👥 Arquitectura y Visión Técnica

<p align="center">
  Construido y mantenido bajo estándares profesionales por ingenieros de <strong>Raju Technology</strong>. <br> Arquitectura guiada para priorizar la mitigación de errores humanos y deslumbrar mediante visuales de última frontera.
</p>
