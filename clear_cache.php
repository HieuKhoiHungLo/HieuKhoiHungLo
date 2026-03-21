<?php
require 'vendor/autoload.php';
if (class_exists('Dotenv\Dotenv')) { Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad(); }
\App\Core\Cache::forget('email_templates_all');
echo "Cleared email templates cache.\n";
