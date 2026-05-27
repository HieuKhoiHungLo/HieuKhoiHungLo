<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\TwoFactorService;
use App\Services\AuditService;
use App\Models\QuanTriVien;

class TwoFactorController extends Controller {

    protected $twoFactor;
    protected $auditService;
    protected $adminModel;

    public function __construct() {
        $this->twoFactor = new TwoFactorService();
        $this->auditService = new AuditService();
        $this->adminModel = new QuanTriVien();
    }

    /**
     * Show 2FA setup page
     */
    public function setup() {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
            return;
        }

        $admin = $this->adminModel->find($_SESSION['admin_id']);
        $isEnabled = $admin['two_factor_enabled'] ?? false;
        
        $secret = null;
        $qrCode = null;
        $backupCodes = null;

        if (!$isEnabled) {
            // Generate new secret for setup
            $secret = $_SESSION['2fa_setup_secret'] ?? $this->twoFactor->generateSecret();
            $_SESSION['2fa_setup_secret'] = $secret;
            
            $qrCode = $this->twoFactor->getQRCodeUrl($secret, $admin['email'] ?? $admin['ten_dang_nhap']);
            $backupCodes = $_SESSION['2fa_backup_codes'] ?? $this->twoFactor->generateBackupCodes();
            $_SESSION['2fa_backup_codes'] = $backupCodes;
        }

        $this->view('admin/2fa/setup', [
            'admin' => $admin,
            'isEnabled' => $isEnabled,
            'secret' => $secret,
            'qrCode' => $qrCode,
            'backupCodes' => $backupCodes
        ]);
    }

    /**
     * Enable 2FA
     */
    public function enable() {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
            return;
        }

        $this->validateCsrf();

        $code = $_POST['code'] ?? '';
        $secret = $_SESSION['2fa_setup_secret'] ?? '';
        $backupCodes = $_SESSION['2fa_backup_codes'] ?? [];

        if (empty($secret)) {
            $this->redirect(url('/admin/2fa/setup?error=invalid_session'));
            return;
        }

        if (!$this->twoFactor->verifyCode($secret, $code)) {
            $this->redirect(url('/admin/2fa/setup?error=invalid_code'));
            return;
        }

        // Enable 2FA
        if ($this->twoFactor->enable($_SESSION['admin_id'], $secret, $backupCodes)) {
            unset($_SESSION['2fa_setup_secret'], $_SESSION['2fa_backup_codes']);
            $this->auditService->log('2FA_ENABLED', 'admin', $_SESSION['admin_id']);
            $this->redirect(url('/admin/2fa/setup?success=enabled'));
        } else {
            $this->redirect(url('/admin/2fa/setup?error=db_error'));
        }
    }

    /**
     * Disable 2FA
     */
    public function disable() {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
            return;
        }

        $this->validateCsrf();

        $password = $_POST['password'] ?? '';
        $admin = $this->adminModel->find($_SESSION['admin_id']);

        if (!password_verify($password, $admin['mat_khau'])) {
            $this->redirect(url('/admin/2fa/setup?error=wrong_password'));
            return;
        }

        if ($this->twoFactor->disable($_SESSION['admin_id'])) {
            $this->auditService->log('2FA_DISABLED', 'admin', $_SESSION['admin_id']);
            $this->redirect(url('/admin/2fa/setup?success=disabled'));
        } else {
            $this->redirect(url('/admin/2fa/setup?error=db_error'));
        }
    }

    /**
     * Show 2FA verification page (after login)
     */
    public function showVerify() {
        if (!isset($_SESSION['2fa_pending'])) {
            $this->redirect(url('/admin/login'));
            return;
        }

        $this->view('admin/2fa/verify');
    }

    /**
     * Verify 2FA code
     */
    public function verify() {
        if (!isset($_SESSION['2fa_pending'])) {
            $this->redirect(url('/admin/login'));
            return;
        }

        $this->validateCsrf();

        $adminId = $_SESSION['2fa_pending'];
        $code = $_POST['code'] ?? '';
        $isBackup = isset($_POST['use_backup']);

        $admin = $this->adminModel->find($adminId);
        
        $verified = false;
        
        if ($isBackup) {
            $verified = $this->twoFactor->verifyBackupCode($adminId, $code);
        } else {
            $secret = $this->twoFactor->getSecret($adminId);
            $verified = $this->twoFactor->verifyCode($secret, $code);
        }

        if ($verified) {
            // Complete login
            unset($_SESSION['2fa_pending']);
            session_regenerate_id(true);
            
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['ho_ten'];
            $_SESSION['admin_role'] = $admin['vai_tro'];
            $_SESSION['admin_role_id'] = $admin['role_id'] ?? 1;
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
            $_SESSION['2fa_verified'] = true;

            $this->auditService->logLogin($admin['ten_dang_nhap'], true);
            $this->auditService->log('LOGIN_2FA', 'admin', $admin['id']);
            
            $redirectUrl = url('/admin/dashboard');
            if (($_SESSION['admin_role_id'] ?? 1) == 2) {
                $redirectUrl = url('/admin/review-management');
            }
            $this->redirect($redirectUrl);
        } else {
            $this->auditService->logLogin($admin['ten_dang_nhap'] ?? 'unknown', false);
            $this->view('admin/2fa/verify', ['error' => 'Mã xác thực không đúng']);
        }
    }
}
