<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: text/plain; charset=utf-8');

$base = __DIR__;
$uploads = $base . '/uploads';
$ads = $uploads . '/ads';

echo "__DIR__: " . $base . PHP_EOL;
echo "uploads exists: " . (is_dir($uploads) ? 'yes' : 'no') . PHP_EOL;
echo "uploads writable: " . (is_writable($uploads) ? 'yes' : 'no') . PHP_EOL;

if (!is_dir($ads)) {
    echo "mkdir ads: ";
    var_dump(@mkdir($ads, 0755, true));
}

echo "ads exists: " . (is_dir($ads) ? 'yes' : 'no') . PHP_EOL;
echo "ads writable: " . (is_writable($ads) ? 'yes' : 'no') . PHP_EOL;

$result = @file_put_contents($ads . '/test.txt', 'ok');

echo "write test: ";
var_dump($result);

if (is_file($ads . '/test.txt')) {
    echo "test file exists: yes" . PHP_EOL;
    echo "test file path: " . $ads . '/test.txt' . PHP_EOL;
}