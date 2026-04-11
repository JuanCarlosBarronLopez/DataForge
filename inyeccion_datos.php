<?php
/**
 * Data Injector / Seeder para DataForge
 * 
 * Este script inyecta tablas de prueba y registros ficticios 
 * únicamente dentro de `dataforge_system` para validar el 
 * gestor CRUD y las métricas del dashboard sin afectar otras bases de datos.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth/auth_functions.php';
require_once __DIR__ . '/database/db_functions.php';

requireLogin();

echo "<h1>Iniciando Inyección de Datos en DataForge_System...</h1>";

try {
    // Solo tocamos dataforge_system
    $conn = getDbConnection(SYSTEM_DB);

    // 1. Crear tabla de Empleados de prueba
    echo "<p>Creando tabla `system_employees`...</p>";
    $conn->query("CREATE TABLE IF NOT EXISTS `system_employees` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `department` VARCHAR(50),
        `salary` DECIMAL(10,2)
    )");

    // 2. Inyectar registros en Empleados
    echo "<p>Inyectando registros en `system_employees`...</p>";
    $conn->query("TRUNCATE TABLE `system_employees`");
    $conn->query("INSERT INTO `system_employees` (`name`, `department`, `salary`) VALUES 
        ('Ana López', 'IT', 4500.00),
        ('Carlos Ruiz', 'Ventas', 3200.50),
        ('Diana Silva', 'Recursos Humanos', 3800.00),
        ('Jorge Pérez', 'IT', 4100.00),
        ('Lucía Martínez', 'Operaciones', 3500.00)
    ");

    // 3. Crear tabla de Inventario
    echo "<p>Creando tabla `system_inventory`...</p>";
    $conn->query("CREATE TABLE IF NOT EXISTS `system_inventory` (
        `item_id` INT AUTO_INCREMENT PRIMARY KEY,
        `product_name` VARCHAR(150) NOT NULL,
        `stock` INT DEFAULT 0,
        `last_refill` DATE
    )");

    // 4. Inyectar registros en Inventario
    echo "<p>Inyectando registros en `system_inventory`...</p>";
    $conn->query("TRUNCATE TABLE `system_inventory`");
    $conn->query("INSERT INTO `system_inventory` (`product_name`, `stock`, `last_refill`) VALUES 
        ('Laptop Pro 15', 24, '2023-11-01'),
        ('Monitor 4K', 15, '2023-10-15'),
        ('Teclado Mecánico', 80, '2023-12-05'),
        ('Ratón Inalámbrico', 120, '2023-12-05')
    ");

    // 5. Crear tabla Log Histórico (con 15 registros generados en lote)
    echo "<p>Creando tabla `system_audit_dummy` y generando 15 eventos...</p>";
    $conn->query("CREATE TABLE IF NOT EXISTS `system_audit_dummy` (
        `audit_id` INT AUTO_INCREMENT PRIMARY KEY,
        `event_type` VARCHAR(50),
        `status` VARCHAR(20)
    )");
    $conn->query("TRUNCATE TABLE `system_audit_dummy`");
    
    $stmt = $conn->prepare("INSERT INTO `system_audit_dummy` (`event_type`, `status`) VALUES (?, ?)");
    for ($i = 1; $i <= 15; $i++) {
        $evento = "Evento de prueba #" . $i;
        $status = ($i % 2 == 0) ? 'COMPLETED' : 'PENDING';
        $stmt->bind_param("ss", $evento, $status);
        $stmt->execute();
    }
    $stmt->close();

    echo "<h2 style='color:green;'>¡Inyección completada exitosamente!</h2>";
    echo "<p>Se crearon 3 tablas y se inyectaron 24 registros exactos en <b>dataforge_system</b>.</p>";
    echo "<a href='dashboard.php'>Volver al Dashboard</a>";

} catch (Exception $e) {
    echo "<h2 style='color:red;'>Error Crítico durante la Inyección</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
