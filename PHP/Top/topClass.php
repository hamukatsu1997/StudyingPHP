<?php
class Top
{
    public function displayKaraoke()
    {
        if (!isset($_SESSION['userId'])) {
            header('Location: ' . BASE_URI_USER . '/');
            exit;
        }

        $userId = $_SESSION['userId'];
        $userName = $_SESSION['userName'];

        // カラオケリストを取得する場合
        // $karaokeList = ...

        $this->display(
            'karaoke/index.tpl'
        );
    }
}

?>

