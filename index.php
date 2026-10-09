<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';
startApplicationSession();
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
    <title>Banco de preguntas</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" defer></script>
    <script src="assets/app.js" defer></script>
</head>
<body>
    <header class="topbar">
        <a class="brand" href="./" aria-label="Banco de preguntas, inicio">
            <span class="brand-mark">B</span>
            <span>Banco<span class="brand-light"> de preguntas</span></span>
        </a>
        <div class="topbar-user" id="topbar-user" hidden>
            <span id="welcome-name"></span>
            <button class="button button-quiet" id="logout-button" type="button">Cerrar sesión</button>
        </div>
    </header>

    <main class="page-shell">
        <section class="auth-layout" id="auth-panel">
            <div class="intro">
                <p class="eyebrow">ORGANIZÁ · PRACTICÁ · SORTEÁ</p>
                <h1>Tus preguntas,<br><span>listas para el azar.</span></h1>
                <p class="intro-copy">Creá bancos de preguntas para cada examen y armá sorteos únicos en segundos.</p>
                <div class="intro-note"><span class="note-icon">✦</span> Tus exámenes y preguntas son privados.</div>
            </div>
            <div class="auth-card">
                <div class="auth-tabs" role="tablist" aria-label="Acceso">
                    <button class="auth-tab is-active" id="login-tab" type="button" role="tab" aria-selected="true">Iniciar sesión</button>
                    <button class="auth-tab" id="register-tab" type="button" role="tab" aria-selected="false">Crear cuenta</button>
                </div>
                <form id="login-form" class="auth-form">
                    <h2>Qué bueno verte</h2>
                    <p>Ingresá para continuar con tus exámenes.</p>
                    <label for="login-email">Correo electrónico</label>
                    <input id="login-email" name="email" type="email" autocomplete="email" maxlength="190" placeholder="vos@ejemplo.com" required>
                    <label for="login-password">Contraseña</label>
                    <input id="login-password" name="password" type="password" autocomplete="current-password" required>
                    <button class="button button-primary button-wide" type="submit">Ingresar <span aria-hidden="true">→</span></button>
                </form>
                <form id="register-form" class="auth-form" hidden>
                    <h2>Empezá gratis</h2>
                    <p>Creá una cuenta para guardar tus bancos.</p>
                    <label for="register-name">Nombre</label>
                    <input id="register-name" name="name" type="text" autocomplete="name" maxlength="100" placeholder="Tu nombre" required>
                    <label for="register-email">Correo electrónico</label>
                    <input id="register-email" name="email" type="email" autocomplete="email" maxlength="190" placeholder="vos@ejemplo.com" required>
                    <label for="register-password">Contraseña <span class="label-hint">mínimo 8 caracteres</span></label>
                    <input id="register-password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
                    <button class="button button-primary button-wide" type="submit">Crear cuenta <span aria-hidden="true">→</span></button>
                </form>
            </div>
        </section>

        <section id="app-panel" hidden>
            <div class="dashboard-heading">
                <div>
                    <p class="eyebrow">TU ESPACIO DE ESTUDIO</p>
                    <h1>Exámenes y preguntas</h1>
                    <p class="section-subtitle">Creá un examen, completá su banco y sorteá preguntas al azar.</p>
                </div>
                <button class="button button-primary" id="new-exam-button" type="button"><span aria-hidden="true">＋</span> Nuevo examen</button>
            </div>

            <div class="dashboard-grid">
                <aside class="exam-column">
                    <div class="panel-heading">
                        <div><h2>Mis exámenes</h2><span class="muted" id="exam-total">0 exámenes</span></div>
                    </div>
                    <div class="exam-list" id="exam-list"></div>
                    <div class="empty-state" id="empty-exams" hidden>
                        <div class="empty-icon">▤</div>
                        <strong>Tu primer examen empieza acá</strong>
                        <span>Creá un examen para empezar a cargar preguntas.</span>
                        <button class="text-button" id="empty-new-exam-button" type="button">Crear un examen <span aria-hidden="true">→</span></button>
                    </div>
                </aside>

                <section class="workspace" id="exam-workspace">
                    <div class="workspace-empty" id="select-exam-message">
                        <div class="empty-icon">✎</div>
                        <h2>Elegí un examen</h2>
                        <p>Seleccioná un examen de la lista o creá uno nuevo para administrar sus preguntas.</p>
                    </div>
                    <div id="exam-detail" hidden>
                        <div class="exam-title-row">
                            <div>
                                <p class="eyebrow">BANCO DE PREGUNTAS</p>
                                <h2 id="selected-exam-title"></h2>
                                <p id="selected-exam-description" class="section-subtitle"></p>
                            </div>
                            <button class="button button-danger-quiet" id="delete-exam-button" type="button">Eliminar examen</button>
                        </div>

                        <div class="content-grid">
                            <section class="content-card question-card">
                                <div class="panel-heading">
                                    <div><h3>Preguntas</h3><span class="muted" id="question-total">0 preguntas</span></div>
                                </div>
                                <form id="question-form" class="stack-form">
                                    <label for="question-text">Nueva pregunta</label>
                                    <textarea id="question-text" name="question_text" rows="3" maxlength="20000" placeholder="Escribí la consigna de la pregunta..." required></textarea>
                                    <label for="answer-text">Respuesta o apunte <span class="label-hint">opcional</span></label>
                                    <textarea id="answer-text" name="answer_text" rows="2" maxlength="20000" placeholder="Agregá una respuesta para consultar después..."></textarea>
                                    <button class="button button-primary" type="submit">Agregar pregunta <span aria-hidden="true">＋</span></button>
                                </form>
                                <div class="question-list" id="question-list"></div>
                                <div class="empty-inline" id="empty-questions" hidden>Todavía no hay preguntas en este examen.</div>
                            </section>

                            <aside class="draw-card">
                                <div class="draw-icon">⤨</div>
                                <p class="eyebrow">SORTEO ALEATORIO</p>
                                <h3>Armá tu selección</h3>
                                <p class="draw-copy">Elegí cuántas preguntas querés extraer. No se repiten.</p>
                                <form id="draw-form">
                                    <label for="draw-count">Cantidad de preguntas</label>
                                    <div class="draw-input-row">
                                        <input id="draw-count" type="number" min="1" value="1" required>
                                        <span id="available-count">de 0</span>
                                    </div>
                                    <button class="button button-dark button-wide" type="submit">Sortear preguntas <span aria-hidden="true">↗</span></button>
                                </form>
                                <div class="draw-result" id="draw-result" hidden></div>
                            </aside>
                        </div>
                    </div>
                </section>
            </div>
        </section>

        <div class="toast" id="toast" role="status" aria-live="polite" hidden></div>
    </main>

    <dialog class="modal" id="exam-dialog">
        <form id="exam-form" class="modal-content">
            <button class="modal-close" type="button" id="close-exam-dialog" aria-label="Cerrar">×</button>
            <p class="eyebrow">NUEVO BANCO</p>
            <h2>Creá un examen</h2>
            <p class="section-subtitle">Podés agregar las preguntas ahora o volver más tarde.</p>
            <label for="exam-title">Nombre del examen</label>
            <input id="exam-title" name="title" type="text" maxlength="150" placeholder="Por ejemplo: Programación III" required>
            <label for="exam-description">Descripción <span class="label-hint">opcional</span></label>
            <textarea id="exam-description" name="description" rows="3" maxlength="5000" placeholder="Un detalle para identificarlo..."></textarea>
            <div class="modal-actions">
                <button class="button button-quiet" type="button" id="cancel-exam-dialog">Cancelar</button>
                <button class="button button-primary" type="submit">Crear examen</button>
            </div>
        </form>
    </dialog>

    <footer class="site-footer">Un espacio simple para preparar tu próximo examen.</footer>
</body>
</html>
