# 💬 App de Comentarios

Proyecto base para el **sprint de Vibe Coding** de la Semana 1.
Trayecto: **Arquitectos de Orquestación e Ingeniería IA**.

> ⚠️ **Este proyecto está incompleto a propósito** — te falta agregar la feature que el docente te pida. Vas a hacerlo vos (con ayuda de OpenCode) durante la actividad. **No mires la solucion de nadie.**

---

## 🚀 Cómo empezar

### 1. Descargar el proyecto

Desde una terminal, **cloná el repositorio** (o descargalo en ZIP y descomprimilo):

```bash
git clone <URL_DEL_REPO> app-comentarios
cd app-comentarios
```

> 🔗 El link del repo lo vas a recibir por mail / cartelera / tema. Si estás en el aula,
> la carpeta también va a estar en `silicon/app-comentarios/`.

### 2. Abrir con OpenCode

En la misma carpeta del proyecto, ejecutá:

```bash
opencode
```

Esto abre OpenCode con el proyecto cargado. Confirmá que el árbol de archivos aparezca
a la izquierda (`api/`, `css/`, `js/`, `sql/`).

### 3. Preparar la base de datos (solo la primera vez)

La app necesitá una base MySQL. Abrí XAMPP (o tu servidor), iniciá **Apache y MySQL**, y desde
`http://localhost/phpmyadmin/` ejecutá el script:

```sql
-- abrí este archivo y ejecutalo en phpMyAdmin
sql/schema.sql
```

> Usa usuario `root` sin contraseña por defecto. Si tu configuración es distinta,
> actualizá los valores en `config.php`.

### 4. Probar que la app funcione

Abrí `http://localhost:8080/app-comentarios-sdd/index.html` en tu navegador.

> **Estado (Semana 5):** el proyecto tiene el andamiaje de los módulos de
> `specs/001-publicar-comentarios/design.md`, sin lógica todavía. La página carga, pero la
> lista no se muestra hasta que se implemente el comportamiento. La base arranca vacía.

---

## ⚙️ Stack del proyecto

| Capa | Tecnología |
|---|---|
| Frontend | HTML5 + CSS nativo + JavaScript vanilla (`fetch`) |
| Backend | PHP 8 (`api/conversacion.php` — único punto de entrada: publicar comentario, publicar respuesta y obtener la conversación) |
| Base de datos | MySQL 8 (`sql/schema.sql`) |
| Estructura | `api/` + `css/` + `js/` + `sql/` |

⚠️ **No usa frameworks, no usa bundlers, no usa librerías externas.** CSS y JS vanilla a propósito.

---

## 📁 Estructura de carpetas

```
app-comentarios/
├── AGENTS.md                      → reglas del proyecto para agentes de IA
├── index.html                     → página principal (formulario + lista)
├── config.php                     → credenciales de la base de datos
├── specs/001-publicar-comentarios/
│   ├── requirements.md            → el QUÉ (features, criterios, reglas)
│   └── design.md                  → el CÓMO (decisiones D-xx, módulos)
├── api/
│   ├── AGENTS.md                  → reglas acotadas a la capa de aplicación
│   ├── conversacion.php           → punto de entrada (3 operaciones)
│   ├── validacion.php             → único punto de validación
│   ├── publicacion_comentario.php → publicación de comentarios
│   ├── publicacion_respuesta.php  → publicación de respuestas
│   ├── consulta_conversacion.php  → consulta ordenada (solo lectura)
│   └── acceso_datos.php           → acceso a la base (consultas preparadas)
├── css/
│   └── style.css                  → estilos nativos, variables CSS
├── js/
│   └── conversacion.js            → interfaz de conversación
└── sql/
    └── schema.sql                 → crea la base vacía (comentario + respuesta)
```

---

## ✏️ La actividad (resumen)

Durante el sprint vas a pedirle a OpenCode que agregue una feature **sin contexto
y sin especificación** (por ejemplo: *"agregá la posibilidad de responder a un comentario"*).
El objetivo es que **falle** para detectar los síntomas del vibe coding.

Anotá en la guía de la actividad (`actividad/index.html`) qué problemas detectás:
si inventa archivos, viola el estilo, genera código que rompe lo ya hecho, no valida
inputs, no escribe tests, etc. Después lo debatimos en grupo.

---

## ❓ FAQ

**¿No pude instalar OpenCode?** Podés hacer el sprint con ChatGPT o Gemini pegando el
contenido del proyecto en el chat (ver FAQ en el material de lectura).

**¿Necesito algo más?** Node.js y Git ya configurados (ver checklist del material de lectura).

**¿Rompo algo?** No pasa nada: **es justamente para eso**. Guardá una copia del repo antes
de empezar si querés, o hacé `git reset --hard` para volver al estado original.

---

*Trayecto: Arquitectos de Orquestación e Ingeniería IA · Mgter. Ing. Agustín Encina · 2026*
