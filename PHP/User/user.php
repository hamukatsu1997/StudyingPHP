<?php

session_start();

header('Content-Type: application/json; charset=UTF-8');


// ログイン確認
if (!isset($_SESSION['userId'])) {

    echo json_encode([
        'login' => false
    ]);

    exit;
}


// セッションの情報を返す
echo json_encode([
    'login' => true,
    'userId' => $_SESSION['userId'],
    'userName' => $_SESSION['userName']
]);

?>