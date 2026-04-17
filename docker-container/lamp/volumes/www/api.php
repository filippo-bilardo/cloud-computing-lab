<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/db.php';

$action = $_GET['action'] ?? '';
$input  = (array) (json_decode((string) file_get_contents('php://input'), true) ?? []);

function respond(mixed $data, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function sanitizeStatus(string $s): string
{
    return in_array($s, ['todo', 'inprogress', 'done'], true) ? $s : 'todo';
}

function sanitizePriority(string $p): string
{
    return in_array($p, ['alta', 'media', 'bassa'], true) ? $p : 'media';
}

try {
    $pdo = getDB();

    switch ($action) {

        // ── Health check ─────────────────────────────────────────────────────────
        case 'ping':
            $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
            respond(['status' => 'ok', 'db' => $dbName, 'php' => phpversion()]);

        // ── List all tasks ────────────────────────────────────────────────────────
        case 'list':
            $tasks = $pdo->query(
                "SELECT * FROM tasks ORDER BY
                 FIELD(status,'todo','inprogress','done'), position, created_at"
            )->fetchAll();
            respond(['tasks' => $tasks]);

        // ── Create task ───────────────────────────────────────────────────────────
        case 'create':
            $title = trim($input['title'] ?? '');
            if ($title === '') {
                respond(['error' => 'Il titolo è obbligatorio'], 400);
            }
            $status   = sanitizeStatus($input['status']   ?? 'todo');
            $priority = sanitizePriority($input['priority'] ?? 'media');
            $due      = ($input['due_date'] ?? '') !== '' ? $input['due_date'] : null;
            $tags     = substr(trim($input['tags'] ?? ''), 0, 255);
            $desc     = $input['description'] ?? '';

            $maxPos = (int) $pdo->prepare(
                "SELECT COALESCE(MAX(position), 0) FROM tasks WHERE status = ?"
            )->execute([$status])
                ? $pdo->query("SELECT COALESCE(MAX(position),0) FROM tasks WHERE status='$status'")->fetchColumn()
                : 0;

            $stmt = $pdo->prepare(
                "INSERT INTO tasks (title, description, status, priority, due_date, tags, position)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$title, $desc, $status, $priority, $due, $tags, $maxPos + 1]);
            $id   = (int) $pdo->lastInsertId();
            $task = $pdo->query("SELECT * FROM tasks WHERE id = $id")->fetch();
            respond(['task' => $task], 201);

        // ── Update task ───────────────────────────────────────────────────────────
        case 'update':
            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) {
                respond(['error' => 'ID non valido'], 400);
            }
            $fields  = [];
            $params  = [];

            if (isset($input['title'])) {
                $title = trim($input['title']);
                if ($title === '') respond(['error' => 'Il titolo non può essere vuoto'], 400);
                $fields[] = 'title = ?';
                $params[] = $title;
            }
            if (array_key_exists('description', $input)) {
                $fields[] = 'description = ?';
                $params[] = $input['description'];
            }
            if (isset($input['status'])) {
                $fields[] = 'status = ?';
                $params[] = sanitizeStatus($input['status']);
            }
            if (isset($input['priority'])) {
                $fields[] = 'priority = ?';
                $params[] = sanitizePriority($input['priority']);
            }
            if (array_key_exists('due_date', $input)) {
                $fields[] = 'due_date = ?';
                $params[] = ($input['due_date'] !== '') ? $input['due_date'] : null;
            }
            if (array_key_exists('tags', $input)) {
                $fields[] = 'tags = ?';
                $params[] = substr(trim($input['tags']), 0, 255);
            }
            if (isset($input['position'])) {
                $fields[] = 'position = ?';
                $params[] = (int) $input['position'];
            }

            if (empty($fields)) {
                respond(['error' => 'Nessun campo da aggiornare'], 400);
            }
            $params[] = $id;
            $pdo->prepare("UPDATE tasks SET " . implode(', ', $fields) . " WHERE id = ?")
                ->execute($params);
            $task = $pdo->query("SELECT * FROM tasks WHERE id = $id")->fetch();
            if (!$task) respond(['error' => 'Task non trovato'], 404);
            respond(['task' => $task]);

        // ── Move task to column ───────────────────────────────────────────────────
        case 'move':
            $id     = (int) ($input['id'] ?? 0);
            $status = sanitizeStatus($input['status'] ?? '');
            if ($id <= 0) respond(['error' => 'ID non valido'], 400);
            $maxPos = (int) $pdo->query(
                "SELECT COALESCE(MAX(position),0) FROM tasks WHERE status='$status'"
            )->fetchColumn();
            $pdo->prepare("UPDATE tasks SET status=?, position=? WHERE id=?")
                ->execute([$status, $maxPos + 1, $id]);
            $task = $pdo->query("SELECT * FROM tasks WHERE id = $id")->fetch();
            respond(['task' => $task]);

        // ── Delete task ───────────────────────────────────────────────────────────
        case 'delete':
            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) respond(['error' => 'ID non valido'], 400);
            $rows = $pdo->prepare("DELETE FROM tasks WHERE id = ?")->execute([$id]);
            respond(['deleted' => $id]);

        // ── Unknown action ────────────────────────────────────────────────────────
        default:
            respond(['error' => "Azione sconosciuta: {$action}"], 400);
    }
} catch (PDOException $e) {
    respond(['error' => 'Database error: ' . $e->getMessage()], 500);
} catch (Throwable $e) {
    respond(['error' => $e->getMessage()], 500);
}
