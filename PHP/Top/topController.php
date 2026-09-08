<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/topClass.php';

function getPathInfo(int $index): string
{
    $pathInfo = trim(
        (string)($_SERVER['PATH_INFO'] ?? ''),
        '/'
    );

    if ($pathInfo === '') {
        return '';
    }

    $parts = explode('/', $pathInfo);

    return $parts[$index - 1] ?? '';
}

function validPathInfo(int $expectedSegments): void
{
    $pathInfo = trim(
        (string)($_SERVER['PATH_INFO'] ?? ''),
        '/'
    );

    $actualSegments = $pathInfo === ''
        ? 0
        : count(explode('/', $pathInfo));

    if ($actualSegments !== $expectedSegments) {
        displayNotFound();
    }
}

function displayNotFound(): void
{
    http_response_code(404);
    exit('ページが見つかりません。');
}

$top = new Top();

switch (getPathInfo(1)) {
    case 'karaoke':
        validPathInfo(1);
        $top->displayKaraoke();
        break;

    case 'keisai':
        validPathInfo(1);
        $top->displayBoard();
        break;

    case 'kokai':
        validPathInfo(1);
        $top->DisplayDownloads();
        break;

    case '':
        validPathInfo(0);
        $top->displayTop();
        break;

    default:
        displayNotFound();
}
