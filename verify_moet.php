<?php
require_once __DIR__ . '/app/Core/Database.php';
require_once __DIR__ . '/app/Services/ExportService.php';

$_ENV['DB_HOST'] = 'aws-1-ap-south-1.pooler.supabase.com';
$_ENV['DB_PORT'] = '6543';
$_ENV['DB_DATABASE'] = 'postgres';
$_ENV['DB_USERNAME'] = 'postgres.oxhuzfqvlpntlymdwfiy';
$_ENV['DB_PASSWORD'] = 'HvuTuyenSinh2026';

$exportService = new \App\Services\ExportService();

echo "Testing exportMoetInfoCsv...\n";
$info = $exportService->exportMoetInfoCsv(['status' => 'Đã duyệt']);
if (!empty($info)) {
    echo "First candidate in Info: " . $info[0]['ho_ten'] . "\n";
    echo "CCCD: " . $info[0]['ddcn'] . "\n";
    echo "Math Step score (example): " . ($info[0]['toan'] ?? 'N/A') . "\n";
} else {
    echo "No candidates found for exportMoetInfoCsv.\n";
}

echo "\nTesting exportMoetTranscriptsCsv...\n";
$transcripts = $exportService->exportMoetTranscriptsCsv(['status' => 'Đã duyệt']);
if (!empty($transcripts)) {
    echo "First candidate in Transcripts: " . $info[0]['ho_ten'] . "\n";
    echo "Math CN (example): " . ($transcripts[0]['Toán CN'] ?? 'N/A') . "\n";
} else {
    echo "No candidates found for exportMoetTranscriptsCsv.\n";
}
