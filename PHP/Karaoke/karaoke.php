<?php
declare(strict_types=1);

session_start();

if (!isset($_SESSION['userId'])) {
    header('Location: ../Top/top.html');
    exit;
}

$csvFile = __DIR__ . '/../../CSV/songList.csv';
$perPage = 10;

$headers = [
    'title'  => '曲名',
    'artist' => 'アーティスト名',
    'year'   => 'リリース年',
    'genre'  => 'ジャンル',
];

$genres = [
    'J-POP',
    'アニソン',
    'ボカロ',
    'ポケモン',
    '洋楽',
    'その他'
];
$deleteIndex = null;

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function readSongs(string $csvFile): array
{
    if (!file_exists($csvFile)) {
        return [];
    }

    $songs = [];
    $handle = fopen($csvFile, 'r');

    if ($handle === false) {
        return [];
    }

    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) < 4) {
            continue;
        }

        // UTF-8 BOMを除去
        $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', $row[0]);

        // ヘッダー行を除外
        if (
            $row[0] === '曲名' ||
            strtolower($row[0]) === 'title'
        ) {
            continue;
        }

        $songs[] = [
            'title'  => trim((string)$row[0]),
            'artist' => trim((string)$row[1]),
            'year'   => trim((string)$row[2]),
            'genre'  => trim((string)$row[3]),
        ];
    }

    fclose($handle);

    return $songs;
}

function saveSongs(string $csvFile, array $songs): bool
{
    $handle = fopen($csvFile, 'w');

    if ($handle === false) {
        return false;
    }

    // Excelで開くことを考慮してUTF-8 BOMを付ける
    fwrite($handle, "\xEF\xBB\xBF");

    fputcsv(
        $handle,
        ['曲名', 'アーティスト名', 'リリース年', 'ジャンル']
    );

    foreach ($songs as $song) {
        fputcsv($handle, [
            $song['title'],
            $song['artist'],
            $song['year'],
            $song['genre'],
        ]);
    }

    fclose($handle);

    return true;
}

$songs = readSongs($csvFile);

$message = '';
$error = '';

/*
|--------------------------------------------------------------------------
| 曲追加処理・CSV保存処理
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

if ($action === 'delete') {
        $deleteIndex = filter_input(
            INPUT_POST,
            'delete_index',
            FILTER_VALIDATE_INT
        );

        if (
            $deleteIndex === false ||
            $deleteIndex === null ||
            !isset($songs[$deleteIndex])
        ) {
            $error = '削除対象が見つかりません。';
        } else {
            $deletedTitle = $songs[$deleteIndex]['title'];

            array_splice($songs, $deleteIndex, 1);

            if (saveSongs($csvFile, $songs)) {
                $message = '「' . $deletedTitle . '」を削除しました。';
            } else {
                $error = 'CSVの保存に失敗しました。';
            }
        }
    }

    if ($action === 'add') {
        $newSong = [
            'title'  => trim((string)($_POST['title'] ?? '')),
            'artist' => trim((string)($_POST['artist'] ?? '')),
            'year'   => trim((string)($_POST['year'] ?? '')),
            'genre'  => trim((string)($_POST['genre'] ?? '')),
        ];

        if (
            $newSong['title'] === '' ||
            $newSong['artist'] === '' ||
            $newSong['year'] === '' ||
            $newSong['genre'] === ''
        ) {
            $error = 'すべての項目を入力してください。';
        } elseif (
            filter_var($newSong['year'], FILTER_VALIDATE_INT) === false ||
            (int)$newSong['year'] < 0 ||
            (int)$newSong['year'] > 9999
        ) {
            $error = 'リリース年は正しい数字で入力してください。';
        } else {
            $songs[] = $newSong;

            if (saveSongs($csvFile, $songs)) {
                $message = '曲を追加し、CSVを更新しました。';
            } else {
                $error = 'CSVの保存に失敗しました。';
            }
        }
    }

    if ($action === 'save') {
        $postedSongs = $_POST['songs'] ?? [];
        $songsToSave = [];

        foreach ($postedSongs as $song) {
            if (!is_array($song)) {
                continue;
            }

            $songsToSave[] = [
                'title'  => trim((string)($song['title'] ?? '')),
                'artist' => trim((string)($song['artist'] ?? '')),
                'year'   => trim((string)($song['year'] ?? '')),
                'genre'  => trim((string)($song['genre'] ?? '')),
            ];
        }

        if (saveSongs($csvFile, $songsToSave)) {
            $songs = $songsToSave;
            $message = '一覧表の内容でCSVを上書き保存しました。';
        } else {
            $error = 'CSVの保存に失敗しました。';
        }
    }
}

/*
|--------------------------------------------------------------------------
| 絞り込み
|--------------------------------------------------------------------------
*/
$filters = [
    'title'  => trim((string)($_GET['title'] ?? '')),
    'artist' => trim((string)($_GET['artist'] ?? '')),
    'year'   => trim((string)($_GET['year'] ?? '')),
    'genre'  => trim((string)($_GET['genre'] ?? '')),
];

$filteredSongs = array_filter(
    $songs,
    function (array $song) use ($filters): bool {
        foreach ($filters as $key => $filter) {
            if (
                $filter !== '' &&
                mb_stripos($song[$key], $filter, 0, 'UTF-8') === false
            ) {
                return false;
            }
        }

        return true;
    }
);

$filteredSongs = array_values($filteredSongs);

/*
|--------------------------------------------------------------------------
| ソート
|--------------------------------------------------------------------------
*/
$sort = (string)($_GET['sort'] ?? '');
$order = strtolower((string)($_GET['order'] ?? 'asc'));

if (array_key_exists($sort, $headers)) {
    usort(
        $filteredSongs,
        function (array $a, array $b) use ($sort, $order): int {
            if ($sort === 'year') {
                $result = (int)$a[$sort] <=> (int)$b[$sort];
            } else {
                $result = strnatcasecmp($a[$sort], $b[$sort]);
            }

            return $order === 'desc' ? -$result : $result;
        }
    );
}

/*
|--------------------------------------------------------------------------
| ページング
|--------------------------------------------------------------------------
*/
$totalSongs = count($filteredSongs);
$totalPages = max(1, (int)ceil($totalSongs / $perPage));

$page = filter_input(
    INPUT_GET,
    'page',
    FILTER_VALIDATE_INT,
    [
        'options' => [
            'default' => 1,
            'min_range' => 1,
        ],
    ]
);

$page = max(1, min((int)$page, $totalPages));

$offset = ($page - 1) * $perPage;

$displaySongs = array_slice(
    $filteredSongs,
    $offset,
    $perPage
);

function buildUrl(array $params = []): string
{
    $current = $_GET;

    foreach ($params as $key => $value) {
        if ($value === null || $value === '') {
            unset($current[$key]);
        } else {
            $current[$key] = $value;
        }
    }

    return '?' . http_build_query($current);
}

function sortUrl(string $column): string
{
    global $sort, $order;

    $nextOrder = 'asc';

    if ($sort === $column && $order === 'asc') {
        $nextOrder = 'desc';
    }

    return buildUrl([
        'sort'  => $column,
        'order' => $nextOrder,
        'page'  => 1,
    ]);
}

function sortMark(string $column): string
{
    global $sort, $order;

    if ($sort !== $column) {
        return '';
    }

    return $order === 'asc' ? ' ▲' : ' ▼';
}

/*
|--------------------------------------------------------------------------
| 最後にHTMLを読み込む
|--------------------------------------------------------------------------
*/
include __DIR__ . '/../../HTML/Karaoke/karaoke.html';
