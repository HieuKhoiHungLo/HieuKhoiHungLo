<?php
require 'app/Core/DotEnv.php';
(new App\Core\DotEnv('.env'))->load();
require 'app/Core/Database.php';
require 'app/Core/Cache.php';

echo "Bắt đầu xóa Cache...\n";
App\Core\Cache::clear();
echo "Đã xóa toàn bộ App Cache\n";

if(function_exists('opcache_reset')) { 
    opcache_reset(); 
    echo "Đã xóa OPcache\n";
}

$files = glob('storage/cache/*');
foreach($files as $file){
  if(is_file($file)) {
      unlink($file);
  }
}
echo "Đã xóa File Cache\n";
