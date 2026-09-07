<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../PHP/Top/topClass.php';

if (!isset($_SESSION['userId'])) {
    header('Location: /../../HTML/Top/top.html');
    exit;
}

$csvFile = __DIR__ . '/../../CSV/board.csv';
$messages = [];
$error = '';

/*
|--------------------------------------------------------------------------
| CSV読み込み
|--------------------------------------------------------------------------
*/
if (is_file($csvFile) && ($handle = fopen($csvFile, 'rb')) !== false) {
    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) >= 5) {
            $messages[] = [
                'posted_at' => $row[0],
                'user_id'   => $row[1],
                'user_name' => $row[2],
                'ip'        => $row[3],
                'body'      => $row[4],
            ];
        }
    }

    fclose($handle);
}

/*
|--------------------------------------------------------------------------
| 新規投稿
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

        $userId = (string)$_SESSION['userId'];
        $userName = (string)($_SESSION['userName'] ?? '');
        $ipAddress = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');

        $handle = fopen($csvFile, 'ab');

        if ($handle === false) {
            $error = '投稿を保存できませんでした。';
        } else {
            if (flock($handle, LOCK_EX)) {
                fputcsv(
                    $handle,
                    [
                        $postedAt,
                        $userId,
                        $userName,
                        $ipAddress,
                        $body
                    ]
                );

                fflush($handle);
                flock($handle, LOCK_UN);
                fclose($handle);

                header('Location: board.php');
                exit;
            }

            fclose($handle);
            $error = '投稿を保存できませんでした。';
        }
    }
}

/*
|--------------------------------------------------------------------------
| 新しい投稿を上に表示
|--------------------------------------------------------------------------
*/
$messages = array_reverse($messages);

/*
|--------------------------------------------------------------------------
| 最後にHTMLを読み込む
|--------------------------------------------------------------------------
*/
include __DIR__ . '/../../HTML/Board/board.html';
