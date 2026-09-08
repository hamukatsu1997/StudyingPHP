<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../PHP/Top/topClass.php';

if (!isset($_SESSION['userId'])) {
    header('Location: /../../HTML/Top/top.html');
    exit;
}

$messages = [];
$error = '';

$dbFile = __DIR__ . '/data/board.db';

if (!is_dir(dirname($dbFile))) {
    mkdir(dirname($dbFile), 0775, true);
}

$pdo = new PDO('sqlite:' . $dbFile);

$pdo->setAttribute(
    PDO::ATTR_ERRMODE,
    PDO::ERRMODE_EXCEPTION
);

$pdo->setAttribute(
    PDO::ATTR_DEFAULT_FETCH_MODE,
    PDO::FETCH_ASSOC
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        posted_at TEXT NOT NULL,
        user_id TEXT NOT NULL,
        user_name TEXT NOT NULL DEFAULT "",
        ip_address TEXT NOT NULL,
        body TEXT NOT NULL
    )'
);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $postId = filter_input(
            INPUT_POST,
            'post_id',
            FILTER_VALIDATE_INT
        );

        if ($postId === false || $postId === null) {
            $error = '削除対象が見つかりません。';
        } else {
            $stmt = $pdo->prepare(
                'DELETE FROM posts
                 WHERE id = :id
                   AND user_id = :user_id'
            );

            $stmt->execute([
                ':id' => $postId,
                ':user_id' => (string)$_SESSION['userId'],
            ]);

            if ($stmt->rowCount() === 0) {
                $error = '削除できる投稿が見つかりません。';
            } else {
                header('Location: board.php');
                exit;
            }
        }
    }

    if ($action === 'add') {
        $body = trim((string)($_POST['body'] ?? ''));

        if ($body === '') {
            $error = '投稿内容を入力してください。';
        } elseif (mb_strlen($body) > 1000) {
            $error = '投稿内容は1000文字以内で入力してください。';
        } else {
            $postedAt = (new DateTimeImmutable(
                'now',
                new DateTimeZone('Asia/Tokyo')
            ))->format('Y-m-d H:i:s');

            $stmt = $pdo->prepare(
                'INSERT INTO posts
                    (posted_at, user_id, user_name, ip_address, body)
                 VALUES
                    (:posted_at, :user_id, :user_name, :ip_address, :body)'
            );

            $stmt->execute([
                ':posted_at' => $postedAt,
                ':user_id' => (string)$_SESSION['userId'],
                ':user_name' => (string)($_SESSION['userName'] ?? ''),
                ':ip_address' => (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
                ':body' => $body,
            ]);

            header('Location: board.php');
            exit;
        }
    }
}

$stmt = $pdo->query(
    'SELECT
        id,
        posted_at,
        user_id,
        user_name,
        ip_address,
        body
     FROM posts
     ORDER BY posted_at DESC, id DESC'
);

$messages = $stmt->fetchAll();

include __DIR__ . '/../../HTML/Board/board.html';
