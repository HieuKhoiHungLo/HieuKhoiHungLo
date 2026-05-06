<?php
require_once __DIR__ . '/SimpleXLS.php';
$dir = __DIR__ . '/../../storage/imports/';
$files = scandir($dir);
foreach ($files as $file) {
    if (strpos($file, '.xls') !== false) {
        if ($xls = \Shuchkin\SimpleXLS::parse($dir . $file)) {
            $headers = $xls->rows()[0] ?? [];
            foreach ($headers as $h) {
                if (strpos($h, 'tốt nghiệp') !== false && strpos($h, 'Điểm') !== false) {
                    echo "Found in file: $file, Header: $h\n";
                }
            }
        }
    }
}
