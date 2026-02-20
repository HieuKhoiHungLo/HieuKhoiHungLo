<?php
// debug_email_template.php
require_once __DIR__ . '/public/index.php'; 

use App\Services\EmailTemplateService;
use App\Models\MasterData;

echo "--- Debug Email Template ---\n";

$cccd = '025084002299';
$templateCode = 'application_reviewed';

$db = \App\Core\Database::getInstance()->getConnection();

// 1. Check Candidate Email
echo "1. Checking Candidate $cccd...\n";
$stmt = $db->prepare("SELECT ho_va_ten, email FROM thi_sinh WHERE so_cccd = ?");
$stmt->execute([$cccd]);
$candidate = $stmt->fetch(PDO::FETCH_ASSOC);

if ($candidate) {
    echo "   Name: " . $candidate['ho_va_ten'] . "\n";
    echo "   Email: " . ($candidate['email'] ?: '(empty)') . "\n";
} else {
    echo "   Candidate not found!\n";
}

// 2. Check Template
echo "\n2. Checking Template '$templateCode'...\n";
$stmt = $db->prepare("SELECT id, subject, body FROM email_templates WHERE code = ?");
$stmt->execute([$templateCode]);
$template = $stmt->fetch(PDO::FETCH_ASSOC);

if ($template) {
    echo "   Found. Subject: " . $template['subject'] . "\n";
} else {
    echo "   Template NOT FOUND in database!\n";
}

// 3. Test Send
if ($candidate && !empty($candidate['email']) && $template) {
    echo "\n3. Attempting to send template email...\n";
    $service = new EmailTemplateService();
    
    // Mock Data
    $mockData = [
        'ho_ten' => $candidate['ho_va_ten'],
        'ket_qua_chi_tiet' => '<ul><li>✅ Test Item 1</li><li>❌ Test Item 2</li></ul>',
        'ghi_chu' => 'This is a debug test email.'
    ];
    
    $result = $service->sendWithTemplate($candidate['email'], $templateCode, $mockData);
    
    if ($result === true) {
        echo "   SUCCESS: Email sent.\n";
    } else {
        echo "   FAILURE: " . print_r($result, true) . "\n";
    }
} else {
    echo "\n3. Skipping send test due to missing data.\n";
}
