# Sistema de Gestión de Exámenes y Sorteo de Preguntas

## Información Institucional
* **Institución:** Instituto Superior Gaspar Benavento
* **Carrera:** Tecnicatura Superior en Análisis y Desarrollo de Software
* **Asignatura:** Programación III
* **Docente:** Delfor Hernán Castro

## Autores
* **Estudiante:** Gonzalo Jesús Arreseygor

---

## Descripción del Proyecto
Aplicación web desarrollada como Trabajo Práctico N.° 2 de la cátedra Programación III. El sistema integra **PHP** y **JavaScript** mediante el uso de peticiones **AJAX** y la librería **jQuery**, permitiendo la gestión dinámica de usuarios, exámenes, preguntas y un sistema automatizado de sorteo aleatorio sin repetición.

## Funcionalidades Principales
* **Registro de Usuarios:** Permite dar de alta a nuevos usuarios en el sistema sin necesidad de un logueo previo.
* **Gestión de Exámenes (ABM):** Cada usuario puede crear, editar y eliminar sus propios exámenes de forma aislada y segura.
* **Gestión de Preguntas (ABM):** Alta, edición y eliminación de preguntas asociadas exclusivamente a los exámenes de cada usuario.
* **Sorteo Aleatorio:** Permite al usuario seleccionar la cantidad de preguntas que desea extraer al azar de un examen determinado, garantizando que no se repita ninguna pregunta en el sorteo.



### 1. Base de Datos
1. Para una instalación nueva, importar `database.sql` en MySQL. El archivo crea la base `banco_preguntas` y las tablas `usuarios`, `examenes` y `preguntas`.
2. Si ya tenés la versión anterior instalada, hacé una copia de seguridad de la base y ejecutá una sola vez `migrar_a_espanol.sql`. El script conserva los datos y cambia los nombres de las tablas y columnas.
3. La conexión usa MySQL en `127.0.0.1:3306`, con el usuario `root` y contraseña vacía por defecto. Para cambiar estos datos, definir las variables de entorno `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` y `DB_PASSWORD`.

Las tablas y columnas de la base están en español. La API conserva los nombres de datos que utiliza la interfaz, por lo que no es necesario cambiar el JavaScript.

### 2. Ejecución
* Desplegar el proyecto en un entorno de servidor local (como XAMPP o WampServer).
* Abrir la ruta principal en el navegador web para comenzar a utilizar el sistema.
