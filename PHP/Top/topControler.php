<?php

session_start();

require_once 'Top.php';

$top = new Top();

switch (getPathInfo(1)) {
    // カラオケリスト
    case 'karaoke':
        validPathInfo(1);
        $top->displayKaraoke();
        break;

    // 掲載希望フラグ切り替え
    case 'keisai':
        validPathInfo(2);
        $top->jsonUpdateKeisaiKibouFlg();
        break;

    // 公開承認フラグ切り替え
    case 'kokai':
        validPathInfo(2);
        $top->jsonUpdateKokaiSyoninFlg();
        break;

    // トップ
    case '':
        validPathInfo(1);
        $top->displayTop();
        break;

    default:
        displayNotFound();
}
