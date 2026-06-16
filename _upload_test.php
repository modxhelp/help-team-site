<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: text/html; charset=utf-8');

    echo '<h2>POST received</h2>';
    echo '<h3>$_FILES:</h3>';
    echo '<pre>';
    echo htmlspecialchars(print_r($_FILES, true), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo '</pre>';

    echo '<h3>CONTENT_LENGTH:</h3>';
    echo '<pre>';
    echo htmlspecialchars((string) ($_SERVER['CONTENT_LENGTH'] ?? 'not set'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo '</pre>';

    echo '<p><a href="/_upload_test.php">Назад</a></p>';
    exit;
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Upload test</title>
</head>
<body>
    <h1>Upload test</h1>

    <form method="post" enctype="multipart/form-data">
        <input type="file" name="media[]" multiple>
        <button type="submit">Upload test</button>
    </form>
</body>
</html>