<?php

session_start();


// ==========================
// 入力されたID・パスワード
// ==========================

$userId = $_POST['userId'] ?? '';
$password = $_POST['password'] ?? '';


// ==========================
// CSVファイル
// ==========================

$csvPath = '../../CSV/login.csv';


// ==========================
// CSVを開く
// ==========================

$file = fopen($csvPath, 'r');

if ($file === false) {

    die('CSVファイルを開けませんでした。');

}


// ==========================
// ヘッダーを読み飛ばす
// ==========================

fgetcsv($file);


// ==========================
// ログイン判定
// ==========================

$loginSuccess = false;

$userName = '';


// CSVを1行ずつ確認
while (($data = fgetcsv($file)) !== false) {

    // データが3列未満なら無視
    if (count($data) < 3) {
        continue;
    }


    // CSVから取得
    $csvId = trim($data[0]);
    $csvPassword = trim($data[1]);
    $csvName = trim($data[2]);


    // デバッグ表示
    echo 'CSV ID：' . htmlspecialchars($csvId) . '<br>';
    echo '入力 ID：' . htmlspecialchars($userId) . '<br>';

    echo '<hr>';


    // IDとパスワードを比較
    if (
        $userId === $csvId &&
        $password === $csvPassword
    ) {

        $loginSuccess = true;

        $userName = $csvName;

        break;
    }

}


// CSVを閉じる
fclose($file);


// ==========================
// 結果表示
// ==========================

if ($loginSuccess) {

    // セッションに保存
    $_SESSION['userId'] = $userId;
    $_SESSION['userName'] = $userName;

    // TopMenuへ移動
    header('Location: ../../HTML/Top/top.html');
    exit;

} else {

    // ログイン画面へ戻す
    header('Location: ../../HTML/Login/login.html?error=1');
    exit;

}
?>