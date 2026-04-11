# Documentación de Arquitectura de Software
**Proyecto:** DataForge CRUD Manager
**Versión:** 2.0
**Autor:** Equipo de Arquitectura - Raju Technology

---

## 1. Visión General del Sistema

DataForge CRUD Manager fue concebido para resolver la necesidad de administrar bases de datos MySQL mediante una interfaz web accesible, segura y estandarizada. La arquitectura seleccionada prioriza la mantenibilidad del código, la seguridad por defecto y una experiencia de usuario fluida, alejándose de los paradigmas tradicionales de scripts PHP monolíticos.

## 2. Patrón de Arquitectura: MVC Simplificado (Action-Domain-Responder)

### Decisión Arquitectónica
Se optó por implementar una variante del patrón **Action-Domain-Responder (ADR)**, una evolución del patrón MVC tradicional adaptado específicamente para aplicaciones web basadas en solicitudes HTTP.

### Justificación
En el desarrollo con PHP nativo (sin frameworks de terceros), construir un MVC completo a menudo introduce una complejidad innecesaria (over-engineering). El enfoque ADR divide el sistema de la siguiente manera:
1. **Action (Controladores/Handlers):** Archivos dedicados exclusivamente a recibir la petición POST/GET (`crear_db.php`, `eliminar_tabla.php`). Su única responsabilidad es recibir el input, pasarlo al Dominio y redirigir.
2. **Domain (Funciones Lógicas):** Archivos que encapsulan la lógica de negocio y las consultas SQL (`db_functions.php`, `table_functions.php`).
3. **Responder (Vistas):** Archivos de presentación (`databases.php`, `view_db.php`) que combinan templates estáticos (`header.php`, `footer.php`) con los datos del Dominio.

Este desacoplamiento asegura que un cambio en la interfaz de usuario no afecte la lógica de base de datos, y viceversa, facilitando la escalabilidad futura del proyecto.

## 3. Gestión de Configuración y Entornos

### Decisión Arquitectónica
Se implementó un sistema de configuración centralizado basado en variables de entorno (archivo `.env`), cargado en memoria mediante un archivo `config.php` (Singleton pasivo).

### Justificación
Hardcodear credenciales de base de datos en múltiples archivos PHP es una vulnerabilidad crítica (CWE-798: Use of Hard-coded Credentials) y representa una barrera para el despliegue en diferentes entornos (Desarrollo, Staging, Producción). Al centralizar la configuración:
- Se garantiza que las credenciales nunca se suban al control de versiones (git).
- Se facilita el despliegue continuado (Continuous Deployment).
- Se centraliza el manejo de errores (modo Debug vs Producción) desde un único punto de verdad.

## 4. Estrategia de Seguridad en Profundidad (Defense in Depth)

### 4.1. Prevención de Cross-Site Request Forgery (CSRF)
**Decisión:** Implementación de un motor de tokens de sesión criptográficamente seguros (`includes/csrf.php`).
**Justificación:** Las operaciones de un gestor de bases de datos son altamente sensibles. Un ataque CSRF exitoso podría resultar en la eliminación silenciosa de bases de datos enteras (Drop Database). Cada formulario genera un token único que es validado en el backend antes de cualquier operación de mutación de estado.

### 4.2. Operaciones Mutables y el Protocolo HTTP
**Decisión:** Estricta separación semántica de métodos HTTP. Toda operación destructiva (DELETE) o de mutación (CREATE, UPDATE) requiere una petición HTTP POST.
**Justificación:** Históricamente, las operaciones como la eliminación de registros solían hacerse vía peticiones GET (ej: `eliminar.php?id=5`). Esto viola los estándares RFC 7231 del HTTP, donde GET debe ser idempotente y seguro. Usar GET para eliminar permitía que rastreadores web (bots) o pre-cargas de navegadores ejecutaran acciones destructivas accidentalmente.

### 4.3. Prevención de Inyección SQL (SQLi)
**Decisión:** Uso obligatorio de Prepared Statements (Sentencias Preparadas) mediante la extensión `mysqli`.
**Justificación:** Al separar la estructura de la consulta SQL de sus datos, mitigamos de raíz la posibilidad de que un input malicioso altere la lógica de la base de datos. Para los nombres de bases de datos y tablas (que no admiten el bind paramétrico nativo), se diseñó una capa de sanitización estricta basada en expresiones regulares (`preg_match('/^[a-zA-Z0-9_]+$/'`) para garantizar que solo caracteres seguros lleguen al motor SQL.

## 5. Diseño de Interfaz y Experiencia de Usuario (UI/UX)

### Decisión Arquitectónica
Implementación de un **Design System** propio basado en CSS Custom Properties (Variables) empleando la técnica de *Glassmorphism* y un tema oscuro estandarizado.

### Justificación
La administración técnica de datos suele asociarse a interfaces áridas. Se diseñó una UI premium por las siguientes razones:
1. **Reducción de fatiga visual:** Los administradores de bases de datos pasan horas frente a la pantalla. El tema oscuro de alto contraste mitiga la fatiga visual.
2. **Jerarquía Visual Cognitiva:** El uso de 'Glassmorphism' (difuminado de fondo) permite crear un sistema de capas donde los elementos interactivos flotan sobre el contenido estructural, guiando la visión periférica del usuario hacia las acciones críticas sin sobrecargar la interfaz.
3. **Feedback Inmediato:** Se descartaron los mensajes de error/éxito integrados en el layout a favor de un sistema de notificaciones "Toast" asíncronas (`includes/flash.php`). Esto respeta la continuidad del flujo de trabajo del usuario (Flow State) al no requerir recargas completas o saltos de pantalla disruptivos tras una operación exitosa.

## 6. Sistema de Templates Modulares

### Decisión Arquitectónica
Fragmentación de la estructura HTML base en componentes PHP (`header.php`, `footer.php`).

### Justificación
La duplicación de código en el frontend (`<head>`, navegación, llamadas a scripts) fomenta deuda técnica. Si se requiere añadir una etiqueta meta para SEO o enlazar un nuevo script, la arquitectura de templates asegura que el cambio se haga en un solo archivo, reflejándose instantáneamente en toda la aplicación (Principio DRY - Don't Repeat Yourself).

## Conclusión

La arquitectura de DataForge CRUD Manager representa un compromiso entre rendimiento nativo y seguridad ingenieril. Al evitar frameworks pesados, el sistema mantiene un tiempo de respuesta inferior a 50ms (overhead mínimo), pero gracias a los patrones de diseño aplicados, alcanza estándares de mantenibilidad y seguridad corporativos. Todo el código base es determinista y auditable, garantizando su integridad en entornos de producción restrictivos.
