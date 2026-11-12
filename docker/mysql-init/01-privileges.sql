-- ============================================
-- DataForge - Permisos del usuario de la aplicacion
-- ============================================
-- El producto es, por definicion, un administrador de bases de datos: el
-- usuario de MySQL con el que corre la aplicacion necesita poder crear y
-- eliminar bases de datos Y operar dentro de ellas.
--
-- Un permiso global de CREATE basta para crear una base, pero NO da acceso a
-- su contenido: MySQL concede los privileges sobre `*.*` de forma que las bases
-- nuevas tambien los hereda. Por eso se concede ALL PRIVILEGES sobre `*.*` sin
-- GRANT OPTION, para que la aplicacion no pueda repartir permisos a terceros.
--
-- En un servidor compartido con datos sensibles, usa un usuario distinto para
-- la aplicacion y para las tareas administrativas.

GRANT ALL PRIVILEGES ON *.* TO 'dataforge'@'%';

FLUSH PRIVILEGES;
