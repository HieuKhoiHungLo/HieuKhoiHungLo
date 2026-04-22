<?php
$url = 'https://raw.githubusercontent.com/shuchkin/simplexls/master/src/SimpleXLS.php';
$content = file_get_contents($url);
if ($content) {
    file_put_contents(__DIR__ . '/app/Services/SimpleXLS.php', $content);
    echo "Downloaded SimpleXLS.php successfully. Size: " . strlen($content);
} else {
    echo "Failed to download SimpleXLS.php";
}
