<?php

session_start();
require_once __DIR__ . '/topClass.php';

$top = new Top();

switch (getPathInfo(1)) {
    case 'karaoke':
        validPathInfo(1);
        $top->displayKaraoke();
        break;

    case 'keisai':
        validPathInfo(2);
        $top->jsonUpdateKeisaiKibouFlg();
        break;

    case 'kokai':
        validPathInfo(2);
        $top->jsonUpdateKokaiSyoninFlg();
        break;

    case '':
        validPathInfo(1);
        $top->displayTop();
        break;

    default:
        displayNotFound();
}
