<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';
startApplicationSession();
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function input(): array
{
    $body = file_get_contents('php://input');
    $data = json_decode($body === false ? '' : $body, true);

    if (!is_array($data)) {
        respond(400, ['error' => 'La solicitud no contiene un JSON válido.']);
    }

    return $data;
}

function requiredString(array $data, string $key, int $maxLength): string
{
    $value = $data[$key] ?? null;
    if (!is_string($value)) {
        respond(422, ['error' => 'Completá correctamente todos los campos requeridos.']);
    }

    $value = trim($value);
    if ($value === '' || mb_strlen($value) > $maxLength) {
        respond(422, ['error' => 'Revisá los campos requeridos y sus longitudes.']);
    }

    return $value;
}

function authenticatedUserId(): int
{
    if (!isset($_SESSION['user_id'])) {
        respond(401, ['error' => 'Iniciá sesión para continuar.']);
    }

    return (int) $_SESSION['user_id'];
}

function ownedExam(PDO $db, int $examId, int $userId): array
{
    $statement = $db->prepare(
        'SELECT id, title FROM exams WHERE id = :id AND user_id = :user_id'
    );
    $statement->execute(['id' => $examId, 'user_id' => $userId]);
    $exam = $statement->fetch();

    if (!$exam) {
        respond(404, ['error' => 'No se encontró el examen solicitado.']);
    }

    return $exam;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['error' => 'Método no permitido.']);
}

$data = input();
$action = $data['action'] ?? '';
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!is_string($csrfToken) || !isset($_SESSION['csrf_token'])
    || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    respond(403, ['error' => 'La sesión venció o la solicitud no es válida. Actualizá la página e intentá nuevamente.']);
}

if ($action === 'session') {
    if (!isset($_SESSION['user_id'])) {
        respond(200, ['user' => null]);
    }
    respond(200, ['user' => ['name' => $_SESSION['user_name'] ?? 'Usuario']]);
}

if ($action === 'logout') {
    unset($_SESSION['user_id'], $_SESSION['user_name']);
    session_regenerate_id(true);
    respond(200, ['message' => 'Sesión cerrada.']);
}

try {
    $db = database();

    switch ($action) {
        case 'register':
            $name = requiredString($data, 'name', 100);
            $email = filter_var(trim((string) ($data['email'] ?? '')), FILTER_VALIDATE_EMAIL);
            $password = $data['password'] ?? null;

            if (!$email || !is_string($password) || strlen($password) < 8 || strlen($password) > 72) {
                respond(422, ['error' => 'Ingresá un correo válido y una contraseña de entre 8 y 72 caracteres.']);
            }

            $statement = $db->prepare(
                'INSERT INTO usuarios (nombre, correo, clave_hash)
                 VALUES (:name, :email, :password_hash)'
            );
            try {
                $statement->execute([
                    'name' => $name,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ]);
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    respond(409, ['error' => 'Ya existe una cuenta registrada con ese correo.']);
                }
                throw $exception;
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $db->lastInsertId();
            $_SESSION['user_name'] = $name;
            respond(201, ['user' => ['name' => $name]]);

        case 'login':
            $email = filter_var(trim((string) ($data['email'] ?? '')), FILTER_VALIDATE_EMAIL);
            $password = $data['password'] ?? null;
            if (!$email || !is_string($password)) {
                respond(422, ['error' => 'Ingresá un correo y una contraseña válidos.']);
            }

            $statement = $db->prepare(
                'SELECT id, nombre AS name, clave_hash AS password_hash
                 FROM usuarios WHERE correo = :email'
            );
            $statement->execute(['email' => $email]);
            $user = $statement->fetch();
            if (!$user || !password_verify($password, $user['password_hash'])) {
                respond(401, ['error' => 'El correo o la contraseña son incorrectos.']);
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_name'] = $user['name'];
            respond(200, ['user' => ['name' => $user['name']]]);

        case 'list_exams':
            $userId = authenticatedUserId();
            $statement = $db->prepare(
                'SELECT e.id, e.titulo AS title, e.descripcion AS description,
                        e.creado_en AS created_at, COUNT(p.id) AS question_count
                 FROM examenes e
                 LEFT JOIN preguntas p ON p.examen_id = e.id
                 WHERE e.usuario_id = :user_id
                 GROUP BY e.id
                 ORDER BY e.creado_en DESC, e.id DESC'
            );
            $statement->execute(['user_id' => $userId]);
            respond(200, ['exams' => $statement->fetchAll()]);

        case 'create_exam':
            $userId = authenticatedUserId();
            $title = requiredString($data, 'title', 150);
            $description = $data['description'] ?? '';
            if (!is_string($description) || mb_strlen(trim($description)) > 5000) {
                respond(422, ['error' => 'La descripción no puede superar los 5000 caracteres.']);
            }

            $statement = $db->prepare(
                'INSERT INTO examenes (usuario_id, titulo, descripcion)
                 VALUES (:user_id, :title, :description)'
            );
            $statement->execute([
                'user_id' => $userId,
                'title' => $title,
                'description' => trim($description) === '' ? null : trim($description),
            ]);
            respond(201, ['id' => (int) $db->lastInsertId()]);

        case 'update_exam':
            $userId = authenticatedUserId();
            $examId = filter_var($data['exam_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$examId || $examId < 1) {
                respond(422, ['error' => 'Seleccioná un examen válido.']);
            }
            ownedExam($db, $examId, $userId);
            $title = requiredString($data, 'title', 150);
            $description = $data['description'] ?? '';
            if (!is_string($description) || mb_strlen(trim($description)) > 5000) {
                respond(422, ['error' => 'La descripción no puede superar los 5000 caracteres.']);
            }

            $statement = $db->prepare(
                'UPDATE examenes SET titulo = :title, descripcion = :description
                 WHERE id = :id AND usuario_id = :user_id'
            );
            $statement->execute([
                'title' => $title,
                'description' => trim($description) === '' ? null : trim($description),
                'id' => $examId,
                'user_id' => $userId,
            ]);
            respond(200, ['message' => 'Examen actualizado.']);

        case 'delete_exam':
            $userId = authenticatedUserId();
            $examId = filter_var($data['exam_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$examId || $examId < 1) {
                respond(422, ['error' => 'Seleccioná un examen válido.']);
            }
            ownedExam($db, $examId, $userId);
            $statement = $db->prepare('DELETE FROM examenes WHERE id = :id AND usuario_id = :user_id');
            $statement->execute(['id' => $examId, 'user_id' => $userId]);
            respond(200, ['message' => 'Examen eliminado.']);

        case 'list_questions':
            $userId = authenticatedUserId();
            $examId = filter_var($data['exam_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$examId || $examId < 1) {
                respond(422, ['error' => 'Seleccioná un examen válido.']);
            }
            ownedExam($db, $examId, $userId);
            $statement = $db->prepare(
                'SELECT id, texto_pregunta AS question_text,
                        texto_respuesta AS answer_text, creado_en AS created_at
                 FROM preguntas WHERE examen_id = :exam_id ORDER BY id DESC'
            );
            $statement->execute(['exam_id' => $examId]);
            respond(200, ['questions' => $statement->fetchAll()]);

        case 'create_question':
            $userId = authenticatedUserId();
            $examId = filter_var($data['exam_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$examId || $examId < 1) {
                respond(422, ['error' => 'Seleccioná un examen válido.']);
            }
            ownedExam($db, $examId, $userId);
            $question = requiredString($data, 'question_text', 20000);
            $answer = $data['answer_text'] ?? '';
            if (!is_string($answer) || mb_strlen(trim($answer)) > 20000) {
                respond(422, ['error' => 'La respuesta no puede superar los 20000 caracteres.']);
            }

            $statement = $db->prepare(
                'INSERT INTO preguntas (examen_id, texto_pregunta, texto_respuesta)
                 VALUES (:exam_id, :question_text, :answer_text)'
            );
            $statement->execute([
                'exam_id' => $examId,
                'question_text' => $question,
                'answer_text' => trim($answer) === '' ? null : trim($answer),
            ]);
            respond(201, ['id' => (int) $db->lastInsertId()]);

        case 'update_question':
            $userId = authenticatedUserId();
            $examId = filter_var($data['exam_id'] ?? null, FILTER_VALIDATE_INT);
            $questionId = filter_var($data['question_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$examId || $examId < 1 || !$questionId || $questionId < 1) {
                respond(422, ['error' => 'Seleccioná una pregunta válida.']);
            }
            ownedExam($db, $examId, $userId);
            $question = requiredString($data, 'question_text', 20000);
            $answer = $data['answer_text'] ?? '';
            if (!is_string($answer) || mb_strlen(trim($answer)) > 20000) {
                respond(422, ['error' => 'La respuesta no puede superar los 20000 caracteres.']);
            }

            $statement = $db->prepare(
                'UPDATE preguntas SET texto_pregunta = :question_text,
                        texto_respuesta = :answer_text
                 WHERE id = :id AND examen_id = :exam_id'
            );
            $statement->execute([
                'question_text' => $question,
                'answer_text' => trim($answer) === '' ? null : trim($answer),
                'id' => $questionId,
                'exam_id' => $examId,
            ]);
            if ($statement->rowCount() === 0) {
                $statement = $db->prepare(
                    'SELECT 1 FROM preguntas WHERE id = :id AND examen_id = :exam_id'
                );
                $statement->execute(['id' => $questionId, 'exam_id' => $examId]);
                if (!$statement->fetchColumn()) {
                    respond(404, ['error' => 'No se encontró la pregunta solicitada.']);
                }
            }
            respond(200, ['message' => 'Pregunta actualizada.']);

        case 'delete_question':
            $userId = authenticatedUserId();
            $questionId = filter_var($data['question_id'] ?? null, FILTER_VALIDATE_INT);
            $examId = filter_var($data['exam_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$questionId || $questionId < 1 || !$examId || $examId < 1) {
                respond(422, ['error' => 'Seleccioná una pregunta válida.']);
            }
            ownedExam($db, $examId, $userId);
            $statement = $db->prepare(
                'DELETE FROM preguntas WHERE id = :id AND examen_id = :exam_id'
            );
            $statement->execute(['id' => $questionId, 'exam_id' => $examId]);
            if ($statement->rowCount() === 0) {
                respond(404, ['error' => 'No se encontró la pregunta solicitada.']);
            }
            respond(200, ['message' => 'Pregunta eliminada.']);

        case 'draw_questions':
            $userId = authenticatedUserId();
            $examId = filter_var($data['exam_id'] ?? null, FILTER_VALIDATE_INT);
            $count = filter_var($data['count'] ?? null, FILTER_VALIDATE_INT);
            if (!$examId || $examId < 1 || !$count || $count < 1) {
                respond(422, ['error' => 'Indicá un examen y una cantidad válida de preguntas.']);
            }
            ownedExam($db, $examId, $userId);

            $statement = $db->prepare('SELECT COUNT(*) FROM preguntas WHERE examen_id = :exam_id');
            $statement->execute(['exam_id' => $examId]);
            $available = (int) $statement->fetchColumn();
            if ($count > $available) {
                respond(422, ['error' => "La cantidad solicitada supera las {$available} preguntas disponibles."]);
            }

            $statement = $db->prepare(
                'SELECT id, texto_pregunta AS question_text,
                        texto_respuesta AS answer_text
                 FROM preguntas WHERE examen_id = :exam_id
                 ORDER BY RAND() LIMIT ' . (int) $count
            );
            $statement->execute(['exam_id' => $examId]);
            respond(200, ['questions' => $statement->fetchAll()]);

        default:
            respond(400, ['error' => 'La acción solicitada no es válida.']);
    }
} catch (Throwable $exception) {
    error_log((string) $exception);
    respond(500, ['error' => 'Ocurrió un error interno. Revisá la configuración del servidor y la base de datos.']);
}
