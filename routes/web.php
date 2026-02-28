<?php

use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\ProfileController;
use App\Controllers\ApiController;
use App\Controllers\ApplicationController;
use App\Controllers\AdminController;

$router = new App\Core\Router();

$router->get('/', 'HomeController@index');
$router->get('/news/detail', 'NewsController@detail');

$router->group(['middleware' => 'rate_limit:30,1'], function($router) {
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

// Student Notification API
$router->get('/api/notifications', 'NotificationController@getNotifications');
$router->get('/api/notifications/unread-count', 'NotificationController@getUnreadCount');
$router->post('/api/notifications/mark-read', 'NotificationController@markRead');
$router->post('/api/notifications/mark-all-read', 'NotificationController@markAllRead');

$router->get('/application/results', 'ApplicationController@results');
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
// Tuyến đường Admin
$router->group(['middleware' => 'rate_limit:30,1'], function($router) {
    $router->get('/admin/login', 'AuthController@adminLogin');
    $router->post('/admin/login', 'AuthController@adminLogin');
});

// Nhóm các route bảo mật bằng AuthMiddleware
$router->group(['middleware' => 'auth'], function($router) {
    $router->get('/admin/dashboard', 'AdminController@dashboard');
    $router->get('/admin/review-management', 'AdminController@reviewList');
    $router->get('/admin/review', 'AdminController@review');
    $router->post('/admin/update-status', 'AdminController@updateStatus');
    $router->post('/admin/review/submit', 'AdminController@submitReview');
    $router->get('/admin/stats', 'AdminController@stats');
    $router->get('/admin/stats/api', 'AdminController@statsApi');
    $router->post('/admin/applications/approve-edit-request', 'AdminController@approveEditRequest');
    
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
    $router->post('/admin/candidates/delete', 'CandidateController@delete');
    $router->post('/admin/candidates/bulk-action', 'CandidateController@bulkAction');
    $router->post('/admin/candidates/update', 'CandidateController@update');
    $router->post('/admin/candidates/transfer', 'CandidateController@transfer');
    $router->get('/admin/candidates/trash', 'CandidateController@trash');
    $router->post('/admin/candidates/restore', 'CandidateController@restore');
    $router->post('/admin/candidates/force-delete', 'CandidateController@forceDelete');
    
    // Admin Accounts (RBAC)
    $router->get('/admin/accounts', 'AdminAccountController@index');
    $router->get('/admin/accounts/create', 'AdminAccountController@create');
    $router->post('/admin/accounts/store', 'AdminAccountController@store');
    $router->get('/admin/accounts/edit', 'AdminAccountController@edit');
    $router->post('/admin/accounts/update', 'AdminAccountController@update');
    $router->get('/admin/accounts/delete', 'AdminAccountController@delete');

    // Homepage Settings
    $router->get('/admin/settings/home', 'AdminController@homeSettings');
    $router->post('/admin/settings/home', 'AdminController@homeSettings');

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

    $router->get('/admin/master-data/schools', 'SchoolController@index');
    $router->post('/admin/master-data/schools', 'SchoolController@save');
    $router->get('/admin/master-data/schools/export', 'SchoolController@export');
    $router->get('/admin/master-data/schools/template', 'SchoolController@template');

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

    // Admission Process
    $router->get('/admin/admission/benchmarks', 'AdmissionController@benchmarks');
    $router->post('/admin/admission/benchmarks', 'AdmissionController@saveBenchmarks');
    $router->post('/admin/admission/process', 'AdmissionController@process');
    $router->get('/admin/admission/results', 'AdmissionController@results');
    $router->post('/admin/admission/finalize', 'AdmissionController@finalize');
    $router->post('/admin/admission/notify', 'AdmissionController@notifyAdmitted');

    // Virtual Filter Dashboard
    $router->get('/admin/admission/virtual-filter', 'VirtualFilterController@index');
    $router->get('/admin/admission/virtual-filter/api-load', 'VirtualFilterController@loadBatchData');
    $router->post('/admin/admission/virtual-filter/api-recalculate', 'VirtualFilterController@recalculateScores');
    $router->post('/admin/admission/virtual-filter/api-run', 'VirtualFilterController@runFiltering');

    // Aptitude Scores
    $router->get('/admin/aptitude-scores', 'AptitudeScoreController@index');
    $router->post('/admin/aptitude-scores/import', 'AptitudeScoreController@import');
    $router->get('/admin/aptitude-scores/template', 'AptitudeScoreController@template');

    // Email Settings & Templates
    $router->get('/admin/settings/email', 'EmailConfigController@index');
    $router->post('/admin/settings/email/save', 'EmailConfigController@save');
    $router->post('/admin/settings/email/test', 'EmailConfigController@test');
    
    $router->get('/admin/settings/email-templates', 'EmailTemplateController@index');
    $router->get('/admin/settings/email-templates/edit', 'EmailTemplateController@edit');
    $router->post('/admin/settings/email-templates/save', 'EmailTemplateController@save');
    $router->get('/admin/settings/email-templates/preview', 'EmailTemplateController@preview');

    // Scoring & Audit
    $router->get('/admin/settings/scoring', 'ScoringSettingsController@index');
    $router->post('/admin/settings/scoring/save', 'ScoringSettingsController@save');
    $router->get('/admin/audit', 'AuditController@index');
    $router->post('/admin/calculate-scores', 'AdminController@calculateScores');

    // Roles & Reports
    $router->get('/admin/roles', 'RoleController@index');
    $router->get('/admin/roles/edit', 'RoleController@edit');
    $router->post('/admin/roles/update', 'RoleController@update');

    $router->get('/admin/reports', 'ReportController@index');
    $router->get('/admin/reports/export-candidates', 'ReportController@exportCandidates');
    $router->get('/admin/reports/export-admitted', 'ReportController@exportAdmitted');

    // Rules
    $router->get('/admin/rules', 'RuleController@index');
    $router->post('/admin/rules/save', 'RuleController@save');
    $router->get('/admin/rules/delete', 'RuleController@delete');

    // 2FA Management
    $router->get('/admin/2fa/setup', 'TwoFactorController@setup');
    $router->post('/admin/2fa/enable', 'TwoFactorController@enable');
    $router->post('/admin/2fa/disable', 'TwoFactorController@disable');
    $router->get('/admin/2fa/verify', 'TwoFactorController@showVerify');
    $router->post('/admin/2fa/verify', 'TwoFactorController@verify');

    // Import & Profile
    $router->get('/admin/import', 'ImportController@index');
    $router->post('/admin/import/upload', 'ImportController@upload');
    $router->post('/admin/import/batch/create', 'ImportController@createBatch');

    $router->get('/admin/profile', 'AdminProfileController@index');
    $router->post('/admin/profile/update', 'AdminProfileController@update');
    $router->post('/admin/profile/change-password', 'AdminProfileController@changePassword');

    $router->get('/admin/logout', 'AuthController@adminLogout');
});


