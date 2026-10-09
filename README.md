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
* **Gestión de Exámenes (ABM):** Cada usuario puede crear, editar, modificar y eliminar sus propios exámenes de forma aislada y segura.
* **Gestión de Preguntas (ABM):** Alta, baja y modificación de preguntas asociadas exclusivamente a los exámenes de cada usuario.
* **Sorteo Aleatorio:** Permite al usuario seleccionar la cantidad de preguntas que desea extraer al azar de un examen determinado, garantizando que no se repita ninguna pregunta en el sorteo.



### 1. Base de Datos
1. Importar el archivo `database.sql` (ubicado en el repositorio) en tu gestor de base de datos MySQL para crear la estructura relacional de `usuarios`, `examen` y `preguntas`.
2. Configurar las credenciales de conexión a la base de datos en el archivo correspondiente de PHP del proyecto.

### 2. Ejecución
* Desplegar el proyecto en un entorno de servidor local (como XAMPP o WampServer).
* Abrir la ruta principal en el navegador web para comenzar a utilizar el sistema.