<?php
class Top
{

public function displayTop(): void
{
    if (!isset($_SESSION['userId'])) {
        header('Location: ' . BASE_URI_USER . '/');
        exit;
    }

    $this->userId = $_SESSION['userId'];
    $this->userName = $_SESSION['userName'] ?? '';

    $file = __DIR__ . '/../../HTML/Top/top.html';

    if (!file_exists($file)) {
        http_response_code(500);
        exit('Top画面が見つかりません: ' . $file);
    }

    include $file;
    exit;
}



public function displayKaraoke()
{
    if (!isset($_SESSION['userId'])) {
        header('Location: ' . BASE_URI_USER . '/');
        exit;
    }

    $this->userId = $_SESSION['userId'];
    $this->userName = $_SESSION['userName'];

    $file = __DIR__ . '/../../HTML/Karaoke/karaoke.html';

        if (!file_exists($file)) {
            http_response_code(500);
            exit('カラオケ画面が見つかりません: ' . $file);
        }

        include $file;
        exit;
}


public function displayBoard()
{
    if (!isset($_SESSION['userId'])) {
        header('Location: ' . BASE_URI_USER . '/');
        exit;
    }

    $this->userId = $_SESSION['userId'];
    $this->userName = $_SESSION['userName'];

    $file = __DIR__ . '/../../HTML/Board/board.html';

        if (!file_exists($file)) {
            http_response_code(500);
            exit('掲示板画面が見つかりません: ' . $file);
        }

        include $file;
        exit;
}

public function displayDownLoads()
{
    if (!isset($_SESSION['userId'])) {
        header('Location: ' . BASE_URI_USER . '/');
        exit;
    }

    $this->userId = $_SESSION['userId'];
    $this->userName = $_SESSION['userName'];

    $file = __DIR__ . '/../../HTML/Downloads/downloads.html';

        if (!file_exists($file)) {
            http_response_code(500);
            exit('素材ダウンロードが見つかりません: ' . $file);
        }

        include $file;
        exit;
}


    public function jsonUpdateKeisaiKibouFlg()
    {
        if(!isset($_SESSION['userId'])){
            header('Location: ' .BASE_URI_USER . '/');
            exit;
        }

                $userId = $_SESSION['userId'];
        $userName = $_SESSION['userName'];

        // カラオケリストを取得する場合
        // $karaokeList = ...


    }
}

?>

