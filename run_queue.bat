@echo off
echo Starting Mail Queue Processor...
:loop
d:\xampp\php\php.exe scripts/process_mail_queue.php
goto loop
