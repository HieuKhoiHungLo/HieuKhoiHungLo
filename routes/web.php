<?php

use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\ProfileController;
use App\Controllers\ApiController;
use App\Controllers\ApplicationController;
use App\Controllers\AdminController;

$router = new App\Core\Router();

$router->get('/', 'HomeController@index');
$router->get('/news', 'NewsController@index');
$router->get('/news/detail', 'NewsController@detail');

// Calculator Module
$router->get('/tinh-diem-xet-tuyen', 'CalculatorController@index');
$router->post('/api/tinh-diem-xet-tuyen', 'CalculatorController@calculate');


$router->group(['middleware' => 'rate_limit:30,1'], function ($router) {
    $router->get('/login', 'AuthController@login');
    $router->post('/login', 'AuthController@login');

    $router->get('/register', 'AuthController@register');
    $router->post('/register', 'AuthController@register');

    $router->get('/forgot-password', 'AuthController@forgotPassword');
    $router->post('/forgot-password', 'AuthController@forgotPassword');

    $router->get('/reset-password', 'AuthController@resetPassword');
    $router->post('/reset-password', 'AuthController@resetPassword');

    $router->get('/reset-password-email', 'AuthController@resetPasswordEmail');
    $router->post('/reset-password-email', 'AuthController@resetPasswordEmail');
});

$router->get('/logout', 'AuthController@logout');

$router->get('/profile/step1', 'ProfileController@step1');
$router->post('/profile/step1', 'ProfileController@step1');

$router->get('/profile/step2', 'ProfileController@step2');
$router->post('/profile/step2', 'ProfileController@step2');

$router->get('/profile/step3', 'AcademicController@step3');
$router->post('/profile/step3', 'AcademicController@step3');

$router->get('/profile/step4', 'ProfileController@step4');
$router->post('/profile/step4', 'ProfileController@step4');

$router->get('/profile/step5', 'ApplicationController@step5');
$router->post('/profile/step5', 'ApplicationController@step5');

$router->get('/profile/change-password', 'ProfileController@changePassword');
$router->post('/profile/change-password', 'ProfileController@changePassword');

// Cron API
$router->get('/api/cron/process_email_queue', 'ApiController@processEmailQueue');
$router->get('/api/cron/backup', 'ApiController@scheduledBackup');

// Student Notification API
$router->get('/api/notifications', 'NotificationController@getNotifications');
$router->get('/api/notifications/unread-count', 'NotificationController@getUnreadCount');
$router->post('/api/notifications/mark-read', 'NotificationController@markRead');
$router->post('/api/notifications/mark-all-read', 'NotificationController@markAllRead');

$router->get('/application/results', 'ApplicationController@results');
$router->post('/application/confirm-enrollment', 'ApplicationController@confirmEnrollment');
$router->post('/application/confirm-enrollment-bo', 'ApplicationController@confirmEnrollmentBo');
$router->post('/application/confirm-kinhphi', 'ApplicationController@confirmKinhPhi');
$router->get('/application/view-letter', 'ApplicationController@viewAdmissionLetter');
$router->post('/application/requestEdit', 'ApplicationController@requestEdit');

// API Routes
$router->get('/api/districts', 'ApiController@getDistricts');
$router->get('/api/wards', 'ApiController@getWards');
$router->get('/api/schools', 'ApiController@getSchools');
$router->get('/api/school-details', 'ApiController@getSchoolDetails');

// Admin Review Helper APIs
$router->get('/api/public/wards', 'MasterDataController@apiWards');
$router->get('/api/public/schools', 'MasterDataController@apiSchools');

// Keep specialized routes for deep links if needed, or redirect them
$router->get('/academic', 'AcademicController@step2');
$router->get('/academic/certification', 'AcademicController@step3');
$router->get('/application/choices', 'ApplicationController@step5');

// Dashboard link fallback
$router->get('/application/index', 'ApplicationController@index');

// Tuyến đường Admin
$router->group(['middleware' => 'rate_limit:30,1'], function ($router) {
    $router->get('/admin/login', 'AuthController@adminLogin');
    $router->post('/admin/login', 'AuthController@adminLogin');
});

// Admission Letter Public Lookup (không cần đăng nhập — dùng khi email bị chặn)
$router->get('/tra-cuu-trung-tuyen', 'AdmissionLookupController@index');
$router->post('/tra-cuu-trung-tuyen/search', 'AdmissionLookupController@search');
$router->post('/tra-cuu-trung-tuyen/xac-nhan-hvu', 'AdmissionLookupController@confirmHvuAdmission');

// Talent Test Public Lookup
$router->get('/tra-cuu-nang-khieu', 'TalentTestPublicController@index');
$router->post('/tra-cuu-nang-khieu/search', 'TalentTestPublicController@search');

// Enrollment Guide Public Lookup (không cần đăng nhập - Quét QR/CCCD)
$router->get('/huong-dan-nhap-hoc', 'EnrollmentGuideController@index');
$router->post('/huong-dan-nhap-hoc/search', 'EnrollmentGuideController@search');

// Nhóm các route bảo mật bằng AuthMiddleware
$router->group(['middleware' => 'auth'], function ($router) {
    $router->get('/admin', function () {
        $redirectUrl = url('/admin/dashboard');
        if (isset($_SESSION['admin_role_id']) && $_SESSION['admin_role_id'] == 2) {
            $redirectUrl = url('/admin/review-management');
        } else if (!empty($_SESSION['admin_id'])) {
            $adminModel = new \App\Models\QuanTriVien();
            $user = $adminModel->find($_SESSION['admin_id']);
            if ($user && \App\Models\QuanTriVien::hasPermission($user, 'enrollment.process') && !\App\Models\QuanTriVien::hasPermission($user, 'dashboard') && !\App\Models\QuanTriVien::hasPermission($user, 'stats')) {
                $redirectUrl = url('/admin/enrollment/process');
            }
        }
        header('Location: ' . $redirectUrl);
        exit;
    });
    $router->get('/admin/dashboard', 'AdminController@dashboard');
    $router->get('/admin/candidate-management', 'CandidateController@index');
    $router->get('/admin/candidate-management/export', 'CandidateController@exportGhost');
    $router->get('/admin/candidates', 'CandidateController@applications');
    $router->get('/admin/review-management', 'CandidateController@reviewList');
    $router->get('/admin/review', 'AdminController@review');
    $router->get('/admin/review/tab', 'AdminController@reviewTab');
    $router->get('/admin/review/batch-tabs', 'AdminController@reviewBatchTabs');
    $router->post('/admin/review/update-note', 'AdminController@updateReviewNote');
    $router->post('/admin/update-status', 'AdminController@updateStatus');
    $router->post('/admin/review/submit', 'AdminController@submitReview');
    $router->get('/admin/stats', 'AdminController@stats');
    $router->get('/admin/stats/api', 'AdminController@statsApi');
    $router->post('/admin/media/rotate', 'AdminController@rotateImage');
    $router->post('/admin/applications/approve-edit-request', 'AdminController@approveEditRequest');
    $router->post('/admin/review/bulk-approve-file', 'CandidateController@bulkApproveByFile');
    $router->post('/admin/review/bulk-approve-all', 'CandidateController@bulkApproveAll');
    $router->post('/admin/review/bulk-unapprove-all', 'CandidateController@bulkUnapproveAll');
    $router->post('/admin/review/bulk-update-transcript', 'CandidateController@bulkUpdateTranscript');
    $router->get('/admin/review/download-approve-template', 'CandidateController@downloadApproveTemplate');
    $router->get('/admin/review/download-transcript-template', 'CandidateController@downloadTranscriptTemplate');
    $router->post('/admin/review/bulk-update-candidate-info', 'CandidateController@bulkUpdateCandidateInfo');
    $router->get('/admin/review/download-candidate-update-template', 'CandidateController@downloadCandidateUpdateTemplate');
    $router->post('/admin/review/bulk-reset-password-default', 'CandidateController@bulkResetPasswordDefault');

    // Admin Notifications
    $router->get('/admin/notifications', 'AdminNotificationController@index');
    $router->get('/admin/notifications/create', 'AdminNotificationController@create');
    $router->post('/admin/notifications/store', 'AdminNotificationController@store');
    $router->get('/admin/notifications/delete', 'AdminNotificationController@delete');
    $router->get('/admin/notifications/api', 'AdminNotificationController@api');

    // Footer Links Management
    $router->get('/admin/footer-links', 'FooterLinksController@index');
    $router->post('/admin/footer-links/save', 'FooterLinksController@save');

    // Candidate Management
    $router->get('/admin/candidates/edit', 'CandidateController@edit');
    $router->post('/admin/candidates/edit', 'CandidateController@edit');
    $router->get('/admin/candidates/get-template', 'CandidateController@getTemplate');
    $router->post('/admin/candidates/delete', 'CandidateController@delete');
    $router->post('/admin/candidates/bulk-action', 'CandidateController@bulkAction');
    $router->post('/admin/candidates/update', 'CandidateController@update');
    $router->post('/admin/candidates/transfer', 'CandidateController@transfer');
    $router->get('/admin/candidates/trash', 'CandidateController@trash');
    $router->post('/admin/candidates/restore', 'CandidateController@restore');
    $router->post('/admin/candidates/force-delete', 'CandidateController@forceDelete');
    $router->post('/admin/candidates/empty-trash', 'CandidateController@emptyTrash');
    $router->post('/admin/candidates/change-password', 'CandidateController@changePassword');

    // Admin Accounts (RBAC)
    $router->get('/admin/accounts', 'AdminAccountController@index');
    $router->get('/admin/accounts/create', 'AdminAccountController@create');
    $router->post('/admin/accounts/store', 'AdminAccountController@store');
    $router->get('/admin/accounts/edit', 'AdminAccountController@edit');
    $router->post('/admin/accounts/update', 'AdminAccountController@update');
    $router->get('/admin/accounts/delete', 'AdminAccountController@delete');

    // Homepage & Menu Settings
    $router->get('/admin/settings/home', 'AdminController@homeSettings');
    $router->post('/admin/settings/home', 'AdminController@homeSettings');
    
    $router->get('/admin/menus', 'AdminMenuController@index');
    $router->get('/admin/menus/create', 'AdminMenuController@create');
    $router->post('/admin/menus/store', 'AdminMenuController@store');
    $router->get('/admin/menus/edit', 'AdminMenuController@edit');
    $router->post('/admin/menus/update', 'AdminMenuController@update');
    $router->get('/admin/menus/delete', 'AdminMenuController@delete');

    // Master Data Management
    $router->get('/admin/master-data', 'SubjectController@index');
    $router->get('/admin/master-data/subjects', 'SubjectController@index');
    $router->post('/admin/master-data/subjects', 'SubjectController@index');
    $router->get('/admin/master-data/subjects/export', 'SubjectController@export');
    $router->get('/admin/master-data/subjects/template', 'SubjectController@template');

    $router->get('/admin/master-data/combinations', 'CombinationController@index');
    $router->post('/admin/master-data/combinations', 'CombinationController@index');
    $router->get('/admin/master-data/combinations/export', 'CombinationController@export');
    $router->get('/admin/master-data/combinations/template', 'CombinationController@template');

    $router->get('/admin/master-data/majors', 'MajorController@index');
    $router->post('/admin/master-data/majors', 'MajorController@save');
    $router->post('/admin/master-data/majors/delete', 'MajorController@delete');
    $router->post('/admin/master-data/majors/actions', 'MajorController@actions');
    $router->get('/admin/master-data/majors/export', 'MajorController@export');
    $router->get('/admin/master-data/majors/template', 'MajorController@template');
    $router->post('/admin/master-data/majors/toggle-active', 'MajorController@toggleActive');

    $router->get('/admin/master-data/schools', 'SchoolController@index');
    $router->post('/admin/master-data/schools', 'SchoolController@save');
    $router->post('/admin/master-data/schools/actions', 'SchoolController@actions');
    $router->get('/admin/master-data/schools/export', 'SchoolController@export');
    $router->get('/admin/master-data/schools/template', 'SchoolController@template');

    // Danh mục Tỉnh
    $router->get('/admin/master-data/provinces', 'ProvinceController@index');
    $router->post('/admin/master-data/provinces', 'ProvinceController@save');
    $router->post('/admin/master-data/provinces/actions', 'ProvinceController@actions');
    $router->get('/admin/master-data/provinces/export', 'ProvinceController@export');
    $router->get('/admin/master-data/provinces/template', 'ProvinceController@template');

    // Danh mục Xã/Phường
    $router->get('/admin/master-data/wards', 'WardController@index');
    $router->post('/admin/master-data/wards', 'WardController@save');
    $router->post('/admin/master-data/wards/actions', 'WardController@actions');
    $router->get('/admin/master-data/wards/export', 'WardController@export');
    $router->get('/admin/master-data/wards/template', 'WardController@template');

    $router->get('/admin/master-data/phuong-thuc', 'MasterDataController@phuongThuc');
    $router->post('/admin/master-data/phuong-thuc/save', 'MasterDataController@savePhuongThuc');

    $router->get('/admin/master-data/sessions', 'SessionController@index');
    $router->post('/admin/master-data/sessions', 'SessionController@save');
    $router->post('/admin/master-data/sessions/delete', 'SessionController@delete');

    $router->get('/admin/master-data/settings', 'SettingController@index');
    $router->post('/admin/master-data/settings/save', 'SettingController@save');

    $router->get('/admin/master-data/language-rules', 'SettingController@languageRules');
    $router->post('/admin/master-data/language-rules', 'SettingController@saveLanguageRule');
    $router->get('/admin/master-data/language-rules/delete', 'SettingController@deleteLanguageRule');

    $router->get('/admin/master-data/zones', 'ZoneController@index');
    $router->post('/admin/master-data/zones/save', 'ZoneController@save');
    $router->post('/admin/master-data/zones/delete', 'ZoneController@delete');

    // Post Management
    $router->get('/admin/posts', 'AdminPostController@index');
    $router->get('/admin/posts/create', 'AdminPostController@create');
    $router->post('/admin/posts/save', 'AdminPostController@save');
    $router->get('/admin/posts/edit', 'AdminPostController@edit');
    $router->get('/admin/posts/delete', 'AdminPostController@delete');

    // Category Management
    $router->get('/admin/categories', 'AdminCategoryController@index');
    $router->get('/admin/categories/create', 'AdminCategoryController@create');
    $router->post('/admin/categories/save', 'AdminCategoryController@save');
    $router->get('/admin/categories/delete', 'AdminCategoryController@delete');

    // Media Management
    $router->get('/admin/media', 'AdminMediaController@index');
    $router->get('/admin/media/api', 'AdminMediaController@apiList');
    $router->post('/admin/media/upload', 'AdminMediaController@upload');
    $router->get('/admin/media/delete', 'AdminMediaController@delete');

    // Admission Process
    $router->get('/admin/admission/benchmarks', 'AdmissionController@benchmarks');
    $router->post('/admin/admission/benchmarks', 'AdmissionController@saveBenchmarks');
    $router->post('/admin/admission/process', 'AdmissionController@process');
    $router->get('/admin/admission/results', 'AdmissionController@results');
    $router->get('/admin/admission/overview-results', 'AdmissionController@overviewResults');
    $router->get('/admin/admission/results/api', 'AdmissionController@resultsApi');
    $router->get('/admin/admission/results/export', 'AdmissionController@exportResults');
    $router->post('/admin/admission/results/bulk-email', 'AdmissionController@bulkEmail');
    $router->post('/admin/admission/results/import', 'AdmissionController@import');
    $router->post('/admin/admission/results/import-avatars', 'AdmissionController@importAvatarsZip');
    $router->post('/admin/admission/results/sync-drive-avatars', 'AdmissionController@syncDriveAvatars');
    $router->post('/admin/admission/results/clear', 'AdmissionController@clearBatch');
    $router->post('/admin/admission/results/set-template', 'AdmissionController@setSessionTemplate');
    $router->post('/admin/admission/results/toggle-publish', 'AdmissionController@togglePublish');
    $router->post('/admin/admission/results/sync-virtual', 'AdmissionController@syncFromVirtualFilter');
    $router->get('/admin/admission/results/get-template', 'AdmissionController@getTemplate');
    $router->post('/admin/admission/results/save-template', 'AdmissionController@saveTemplate');
    $router->get('/admin/admission/results/download-sample', 'AdmissionController@downloadSampleExcel');
    $router->get('/admin/admission/results/download-result-file', 'AdmissionController@downloadResultFile');
    $router->post('/admin/admission/finalize', 'AdmissionController@finalize');
    $router->post('/admin/admission/notify', 'AdmissionController@notify');

    // Admission Exceptions
    $router->get('/admin/admission/exceptions', 'NgoaiLeController@index');
    $router->post('/admin/admission/exceptions/save', 'NgoaiLeController@save');
    $router->post('/admin/admission/exceptions/delete', 'NgoaiLeController@delete');
    $router->post('/admin/admission/exceptions/import-bo-gd', 'NgoaiLeController@importBoGD');
    $router->post('/admin/admission/exceptions/delete-bo-gd', 'NgoaiLeController@deleteBoGD');

    // Virtual Filter Dashboard (New Grid UI)
    $router->get('/admin/admission/virtual-filter', 'VirtualAdmissionController@index');
    $router->get('/admin/admission/overview-virtual-filter', 'VirtualAdmissionController@overviewVirtualFilter');
    $router->get('/admin/api/vf/load', 'VirtualAdmissionController@loadBatchData');
    $router->post('/admin/api/vf/load', 'VirtualAdmissionController@loadBatchData');
    $router->get('/admin/api/vf/get-cccds', 'VirtualAdmissionController@apiGetCccds');
    $router->post('/admin/api/vf/recalculate', 'VirtualAdmissionController@recalculateScores');
    $router->post('/admin/api/vf/sync', 'VirtualAdmissionController@apiSync');

    $router->get('/admin/api/vf/export', 'VirtualAdmissionController@exportExcel');
    $router->get('/admin/api/vf/export-admitted',     'VirtualAdmissionController@exportAdmitted');
    $router->get('/admin/api/vf/export-failed',       'VirtualAdmissionController@exportFailed');
    $router->get('/admin/api/vf/export-academic-fail','VirtualAdmissionController@exportAcademicFail');
    $router->get('/admin/api/vf/export-virtual-filter', 'VirtualAdmissionController@exportVirtualFilterAdmitted');
    $router->get('/admin/api/vf/export-moet-format',    'VirtualAdmissionController@exportMoetFormat');
    $router->get('/admin/api/vf/stats', 'VirtualAdmissionController@getStats');
    $router->post('/admin/api/vf/sync-notebooklm',      'VirtualAdmissionController@syncNotebookLM');
    $router->get('/admin/api/vf/session-type', 'VirtualAdmissionController@getSessionType');
    $router->post('/admin/api/vf/run', 'VirtualFilterController@runFiltering');
    $router->get('/admin/api/vf/batch-load', 'VirtualFilterController@loadBatchData');
    // BGD Virtual Filter Import (Kết quả lọc ảo liên trường từ Bộ GD&ĐT)
    $router->post('/admin/api/vf/import-bgd', 'VirtualAdmissionController@importBGDResult');
    $router->get('/admin/api/vf/download-bgd-report', 'VirtualAdmissionController@downloadImportReport');
    $router->get('/admin/api/vf/bgd-status', 'VirtualAdmissionController@getBGDImportStatus');
    $router->get('/admin/api/vf/export-admitted-final', 'VirtualAdmissionController@exportAdmittedFinal');
    $router->get('/admin/api/vf/export-eliminated-bgd', 'VirtualAdmissionController@exportEliminatedByBGD');
    $router->get('/admin/api/vf/export-stats', 'VirtualAdmissionController@exportStats');
    $router->get('/admin/api/vf/export-chart-data', 'VirtualAdmissionController@exportChartData');


    // New Admission Management (Year/Session Based)
    $router->get('/admin/admission/management', 'AdmissionManagementController@index');
    $router->get('/admin/admission/management/api-sessions', 'AdmissionManagementController@apiGetSessions');
    $router->get('/admin/admission/management/api-data', 'AdmissionManagementController@apiGetData');
    $router->post('/admin/admission/management/api-save', 'AdmissionManagementController@apiSave');
    $router->get('/admin/admission/management/export', 'AdmissionManagementController@exportExcel');


    // Aptitude Scores
    $router->get('/admin/aptitude-scores', 'AptitudeScoreController@index');
    $router->post('/admin/aptitude-scores/api-list', 'AptitudeScoreController@apiList');
    $router->post('/admin/aptitude-scores/import', 'AptitudeScoreController@import');
    $router->get('/admin/aptitude-scores/template', 'AptitudeScoreController@template');
    $router->get('/admin/aptitude-scores/export', 'AptitudeScoreController@export');
    $router->post('/admin/aptitude-scores/delete', 'AptitudeScoreController@delete');

    // Certificate Scores
    $router->get('/admin/certificate-scores', 'CertificateScoreController@index');
    $router->post('/admin/certificate-scores/api-list', 'CertificateScoreController@apiList');
    $router->post('/admin/certificate-scores/import', 'CertificateScoreController@import');
    $router->get('/admin/certificate-scores/template', 'CertificateScoreController@template');
    $router->get('/admin/certificate-scores/export', 'CertificateScoreController@export');
    $router->post('/admin/certificate-scores/delete', 'CertificateScoreController@delete');

    // Certificate Rules (Conversion Table)
    $router->get('/admin/certificate-rules', 'CertificateRuleController@index');
    $router->post('/admin/certificate-rules/store', 'CertificateRuleController@store');
    $router->post('/admin/certificate-rules/update', 'CertificateRuleController@update');
    $router->post('/admin/certificate-rules/delete', 'CertificateRuleController@delete');

    // Email Settings (Redirect to centralized senders management)
    $router->get('/admin/settings/email', function() {
        header('Location: ' . url('/admin/settings/email-senders'));
        exit;
    });
    $router->post('/admin/settings/email/save', 'EmailSenderController@save');
    $router->post('/admin/settings/email/test', 'EmailConfigController@test');

    $router->get('/admin/settings/email-senders', 'EmailSenderController@index');
    $router->post('/admin/settings/email-senders/save', 'EmailSenderController@save');
    $router->post('/admin/settings/email-senders/delete', 'EmailSenderController@delete');
    $router->post('/admin/settings/email-senders/test', 'EmailSenderController@test');

    $router->get('/admin/settings/email-templates', 'EmailTemplateController@index');
    $router->get('/admin/settings/email-templates/create', 'EmailTemplateController@create');
    $router->post('/admin/settings/email-templates/store', 'EmailTemplateController@store');
    $router->get('/admin/settings/email-templates/edit', 'EmailTemplateController@edit');
    $router->post('/admin/settings/email-templates/save', 'EmailTemplateController@save');
    $router->get('/admin/settings/email-templates/preview', 'EmailTemplateController@preview');
    $router->get('/admin/settings/email-templates/delete', 'EmailTemplateController@delete');

    // Admission Letters
    $router->get('/admin/admission-letters', 'AdmissionLetterController@index');
    $router->get('/admin/admission-letters/import', 'AdmissionLetterController@importForm');
    $router->post('/admin/admission-letters/import', 'AdmissionLetterController@import');
    $router->get('/admin/admission-letters/template', 'AdmissionLetterController@template');
    $router->get('/admin/admission-letters/preview', 'AdmissionLetterController@preview');
    $router->get('/admin/admission-letters/monitor-stats', 'AdmissionLetterController@monitorStats');
    $router->post('/admin/admission-letters/bulk-action', 'AdmissionLetterController@bulkAction');
    $router->post('/admin/admission-letters/delete-all', 'AdmissionLetterController@deleteAll');
    $router->post('/admin/admission-letters/send-test', 'AdmissionLetterController@sendTest');
    $router->post('/admin/admission-letters/send-all', 'AdmissionLetterController@sendAll');
    
    // Legacy redirects for email senders
    $router->get('/admin/admission-letters/senders', function() {
        header('Location: ' . url('/admin/settings/email-senders'));
        exit;
    });
    $router->post('/admin/admission-letters/senders/save', 'EmailSenderController@save');
    $router->post('/admin/admission-letters/senders/delete', 'EmailSenderController@delete');

    // Talent Test (Năng khiếu)
    $router->get('/admin/talent-tests', 'TalentTestController@index');
    $router->get('/admin/talent-tests/create', 'TalentTestController@create');
    $router->post('/admin/talent-tests/store', 'TalentTestController@store');
    $router->get('/admin/talent-tests/edit', 'TalentTestController@edit');
    $router->post('/admin/talent-tests/sync', 'TalentTestController@sync');
    $router->post('/admin/talent-tests/toggle-publish', 'TalentTestController@togglePublish');
    $router->post('/admin/talent-tests/rooms/save', 'TalentTestController@saveRoom');
    $router->post('/admin/talent-tests/auto-assign', 'TalentTestController@autoAssignRooms');
    $router->post('/admin/talent-tests/assign-bags', 'TalentTestController@assignBags');
    $router->get('/admin/talent-tests/scores', 'TalentTestController@scores');
    $router->post('/admin/talent-tests/scores/save', 'TalentTestController@saveScore');
    $router->get('/admin/talent-tests/print-cards', 'TalentTestController@printCards');
    $router->get('/admin/talent-tests/print-photos', 'TalentTestController@printPhotos');
    $router->get('/admin/talent-tests/export-excel', 'TalentTestController@exportExcel');
    $router->get('/admin/talent-tests/dashboard', 'TalentTestController@dashboard');

    // Talent Test V2 - Phase 2: Candidates
    $router->get('/admin/talent-tests/candidates', 'TalentTestController@candidates');
    $router->post('/admin/talent-tests/toggle-eligibility', 'TalentTestController@toggleEligibility');
    $router->post('/admin/talent-tests/remove-candidate', 'TalentTestController@removeCandidate');

    // Talent Test V2 - Phase 3: Exam Numbers
    $router->get('/admin/talent-tests/exam-numbers', 'TalentTestController@examNumbers');
    $router->post('/admin/talent-tests/generate-exam-numbers', 'TalentTestController@generateExamNumbers');
    $router->post('/admin/talent-tests/clear-exam-numbers', 'TalentTestController@clearExamNumbers');

    // Talent Test V2 - Phase 4: Room Assignment
    $router->get('/admin/talent-tests/room-assignment', 'TalentTestController@roomAssignment');
    $router->post('/admin/talent-tests/auto-create-rooms', 'TalentTestController@autoCreateRooms');
    $router->post('/admin/talent-tests/delete-all-rooms', 'TalentTestController@deleteAllRooms');
    $router->post('/admin/talent-tests/delete-room', 'TalentTestController@deleteRoomAction');
    $router->post('/admin/talent-tests/reset-rooms', 'TalentTestController@resetRoomAssignments');
    $router->get('/admin/talent-tests/api/room-candidates', 'TalentTestController@getRoomCandidatesApi');
    $router->post('/admin/talent-tests/move-candidate', 'TalentTestController@moveCandidateRoom');

    // Talent Test V2 - Phase 5: Exam Config & Printing
    $router->get('/admin/talent-tests/exam-config', 'TalentTestController@examConfig');
    $router->post('/admin/talent-tests/save-exam-config', 'TalentTestController@saveExamConfig');
    $router->get('/admin/talent-tests/print-room-list', 'TalentTestController@printRoomList');
    $router->get('/admin/talent-tests/print-exam-notice', 'TalentTestController@printExamNotice');


    // Scoring & Audit
    $router->get('/admin/settings/scoring', 'ScoringSettingsController@index');
    $router->post('/admin/settings/scoring/save', 'ScoringSettingsController@save');
    $router->get('/admin/audit', 'AuditController@index');
    $router->post('/admin/audit/purge', 'AuditController@purge');
    $router->post('/admin/calculate-scores', 'AdminController@calculateScores');

    // Roles & Reports
    $router->get('/admin/roles', 'RoleController@index');
    $router->get('/admin/roles/create', 'RoleController@index');
    $router->post('/admin/roles/store', 'RoleController@store');
    $router->get('/admin/roles/edit', 'RoleController@edit');
    $router->post('/admin/roles/edit', 'RoleController@update');
    $router->post('/admin/roles/update', 'RoleController@update');
    $router->get('/admin/roles/delete', 'RoleController@delete');
    $router->post('/admin/roles/delete', 'RoleController@delete');

    $router->get('/admin/reports', 'ReportController@index');
    $router->get('/admin/reports/export-candidates', 'ReportController@exportCandidates');
    $router->get('/admin/reports/export-admitted', 'ReportController@exportAdmitted');
    $router->get('/admin/reports/export-certificates', 'ReportController@exportCertificates');
    
    // MOET Exports
    $router->get('/admin/reports/export-moet-info', 'ReportController@exportMoetInfo');
    $router->get('/admin/reports/export-moet-wishes', 'ReportController@exportMoetWishes');
    $router->get('/admin/reports/export-moet-transcripts', 'ReportController@exportMoetTranscripts');
    $router->get('/admin/reports/export-aptitude-list', 'ReportController@exportAptitudeList');

    $router->get('/admin/reports/stats-api', 'ReportController@statsApi');
    $router->get('/admin/reports/export-admission', 'ReportController@exportAdmissionReport');
    $router->get('/admin/reports/export-all-admitted', 'ReportController@exportAllAdmittedReport');
    $router->get('/admin/reports/download-photos-aptitude', 'ReportController@downloadAptitudePhotos');
    $router->get('/admin/reports/download-certs', 'ReportController@downloadCertificatePhotos');
    $router->get('/admin/reports/download-data-audit', 'ReportController@downloadDataAudit');


    // Rules
    $router->get('/admin/rules', 'RuleController@index');
    $router->post('/admin/rules/save', 'RuleController@save');
    $router->get('/admin/rules/delete', 'RuleController@delete');

    // Backup & System
    $router->get('/admin/system/backup', 'BackupController@index');
    $router->get('/admin/system/backup/create', 'BackupController@create');
    $router->get('/admin/system/backup/delete', 'BackupController@delete');
    $router->post('/admin/system/backup/bulk-delete', 'BackupController@bulkDelete');
    $router->get('/admin/system/backup/restore', 'BackupController@restore');
    $router->post('/admin/system/backup/restore', 'BackupController@restore');
    $router->get('/admin/system/backup/download', 'BackupController@download');
    $router->post('/admin/system/backup/settings', 'BackupController@saveSettings');

    // 2FA Management
    $router->get('/admin/2fa/setup', 'TwoFactorController@setup');
    $router->post('/admin/2fa/enable', 'TwoFactorController@enable');
    $router->post('/admin/2fa/disable', 'TwoFactorController@disable');
    $router->get('/admin/2fa/verify', 'TwoFactorController@showVerify');
    $router->post('/admin/2fa/verify', 'TwoFactorController@verify');

    // Import & Profile
    $router->get('/admin/import', 'ImportController@index');
    $router->get('/admin/import/progress', 'ImportController@progress');
    $router->post('/admin/import/upload', 'ImportController@upload');
    $router->post('/admin/import/delete-log', 'ImportController@deleteLog');
    $router->post('/admin/import/clear-batch', 'ImportController@clearBatch');
    $router->post('/admin/import/batch/create', 'ImportController@createBatch');

    $router->get('/admin/profile', 'AdminProfileController@index');
    $router->post('/admin/profile/update', 'AdminProfileController@update');
    $router->post('/admin/profile/change-password', 'AdminProfileController@changePassword');

    $router->get('/admin/logout', 'AuthController@adminLogout');

    // Enrollment Management
    $router->get('/admin/enrollment/setup', 'EnrollmentController@setup');
    $router->post('/admin/enrollment/setup/save', 'EnrollmentController@saveSetup');
    $router->get('/admin/enrollment/process', 'EnrollmentController@process');
    $router->get('/admin/enrollment/search', 'EnrollmentController@searchCandidate');
    $router->post('/admin/enrollment/submit', 'EnrollmentController@submitEnrollment');
    $router->get('/admin/enrollment/print', 'EnrollmentController@printReceipt');
    $router->get('/admin/enrollment/stats', 'EnrollmentController@stats');
    $router->get('/admin/enrollment/overview-stats', 'EnrollmentController@overviewStats');
    
    // New Dashboard APIs
    $router->get('/admin/enrollment/api/stats', 'EnrollmentController@apiStats');
    $router->get('/admin/enrollment/api/list', 'EnrollmentController@apiListEnrolled');

    // ─── Phiếu In (Word Template) ────────────────────────────────────────────
    $router->get('/admin/phieu/templates',          'PhieuController@templates');
    $router->post('/admin/phieu/templates/upload',  'PhieuController@uploadTemplate');
    $router->post('/admin/phieu/templates/delete',  'PhieuController@deleteTemplate');
    $router->get('/admin/phieu/list',               'PhieuController@listTemplates');
    $router->get('/admin/phieu/download',           'PhieuController@download');

    // Email Queue Management
    $router->get('/admin/email-queue', 'EmailQueueController@index');
    $router->post('/admin/email-queue/retry', 'EmailQueueController@retry');
    $router->post('/admin/email-queue/delete', 'EmailQueueController@delete');
    $router->post('/admin/email-queue/clear', 'EmailQueueController@clearQueue');
    $router->post('/admin/email-queue/toggle-pause', 'EmailQueueController@togglePause');
    $router->post('/admin/email-queue/purge-old', 'EmailQueueController@purgeOldEmails');
});

// Public Talent Test Route
$router->get('/tra-cuu-nang-khieu', 'TalentTestPublicController@index');
$router->post('/tra-cuu-nang-khieu/search', 'TalentTestPublicController@search');
