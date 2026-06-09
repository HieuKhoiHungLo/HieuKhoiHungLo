<?php
if (function_exists('opcache_reset')) {
    $res = opcache_reset();
    echo "OPcache Reset: " . ($res ? "SUCCESS" : "FAILED") . "\n";
} else {
    echo "OPcache is not enabled or function not exists.\n";
}
