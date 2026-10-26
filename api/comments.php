<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']);
    exit;
}

ensureColumn($pdo, 'parent_id', 'INT NULL');
ensureColumn($pdo, 'likes', 'INT NOT NULL DEFAULT 0');

switch ($method) {
    case 'GET':
        getComments($pdo);
        break;
    case 'POST':
        if (isset($_GET['action']) && $_GET['action'] === 'like') {
            likeComment($pdo);
        } else {
            createComment($pdo);
        }
        break;
    case 'DELETE':
        deleteComment($pdo);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}

function ensureColumn($pdo, $name, $definition) {
    $column = $pdo->query("SHOW COLUMNS FROM comments LIKE '$name'");
    if (!$column->fetch()) {
        $pdo->exec("ALTER TABLE comments ADD COLUMN $name $definition");
    }
}

function getValidId() {
    $id = $_GET['id'] ?? null;

    if (!$id || !is_numeric($id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID inválido']);
        return null;
    }

    return (int) $id;
}

function getComments($pdo) {
    $stmt = $pdo->query("SELECT id, author, content, parent_id, likes, created_at FROM comments ORDER BY created_at ASC");
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'comments' => $comments]);
}

function likeComment($pdo) {
    $id = getValidId();
    if ($id === null) return;

    $stmt = $pdo->prepare("UPDATE comments SET likes = likes + 1 WHERE id = ?");
    $stmt->execute([$id]);

    $select = $pdo->prepare("SELECT likes FROM comments WHERE id = ?");
    $select->execute([$id]);
    $row = $select->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Comentario no encontrado']);
        return;
    }

    echo json_encode(['success' => true, 'likes' => (int) $row['likes']]);
}

function createComment($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['author']) || empty($input['content'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Nombre y contenido son requeridos']);
        return;
    }
    
    $author = trim($input['author']);
    $content = trim($input['content']);
    $parentId = null;

    if (isset($input['parent_id']) && $input['parent_id'] !== '' && $input['parent_id'] !== null) {
        if (!is_numeric($input['parent_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID de comentario inválido']);
            return;
        }

        $parentId = (int) $input['parent_id'];
        $check = $pdo->prepare("SELECT id FROM comments WHERE id = ?");
        $check->execute([$parentId]);

        if (!$check->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'El comentario al que querés responder no existe']);
            return;
        }
    }
    
    if (strlen($author) > 100 || strlen($content) > 1000) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Datos exceden el límite permitido']);
        return;
    }
    
    $stmt = $pdo->prepare("INSERT INTO comments (author, content, parent_id, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$author, $content, $parentId]);
    
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
}

function deleteComment($pdo) {
    $id = getValidId();
    if ($id === null) return;

    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode(['success' => true]);
}
