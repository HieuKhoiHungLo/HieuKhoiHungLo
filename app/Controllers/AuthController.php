<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\ThiSinhRepository;
use App\Repositories\AdminRepository;
use App\Services\AuditService;
use App\Services\EmailTemplateService;

class AuthController extends Controller
{

    protected AuditService $auditService;
    protected ThiSinhRepository $thiSinhRepo;
    protected AdminRepository $adminRepo;

    public function __construct()
    {
        $this->auditService = new AuditService();
        $this->thiSinhRepo = new ThiSinhRepository();
        $this->adminRepo = new AdminRepository();
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            // Rate limiting check
            if ($this->auditService->isRateLimited(5, 15)) {
                $this->view('auth/login', ['error' => 'Quá nhiều lần đăng nhập thất bại. Vui lòng thử lại sau 15 phút.']);
                return;
            }

            $validator = new \App\Core\Validator($_POST);
            $rules = [
                'cccd' => 'required',
                'password' => 'required'
            ];

            if (!$validator->validate($rules)) {
                $this->view('auth/login', ['error' => $validator->getFirstError(), 'old' => $_POST]);
                return;
            }

            $cccd = trim($_POST['cccd']);
            $password = $_POST['password'];

            // Use Repository
            $user = $this->thiSinhRepo->findByCCCD($cccd);

            if ($user && password_verify($password, $user['mat_khau'])) {
                // Regenerate session ID after successful login
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['ho_va_ten'];
                $_SESSION['cccd'] = $user['so_cccd'];
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time();

                // --- REMEMBER ME LOGIC ---
                if (isset($_POST['remember']) && $_POST['remember'] === 'on') {
                    $token = bin2hex(random_bytes(32)); // Generate secure 64-char hex token
                    $hash = hash('sha256', $token);
                    $this->thiSinhRepo->updateRememberToken($user['id'], $hash);
                    // Set cookie for 30 days
                    setcookie('remember_ts', $token, [
                        'expires' => time() + (30 * 24 * 60 * 60),
                        'path' => '/',
                        'domain' => '',
                        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]);
                }

                // Clear rate limiting on successful login
                $this->auditService->clearLoginAttempts();

                // Log successful login
                $this->auditService->logLogin($cccd, true);

                $this->redirect(url('/'));
            } else {
                // Record failed attempt in session
                $this->auditService->recordFailedAttempt();

                // Log failed login
                $this->auditService->logLogin($cccd, false);
                $this->view('auth/login', ['error' => 'Thông tin đăng nhập không chính xác.']);
            }
        } else {
            $this->view('auth/login');
        }
    }

    public function adminLogin(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            // Rate limiting check
            if ($this->auditService->isRateLimited(5, 15)) {
                $this->auditService->logLogin($username, false);
                $this->view('auth/admin_login', ['error' => 'Quá nhiều lần đăng nhập thất bại. Vui lòng thử lại sau 15 phút.']);
                return;
            }

            // Use Repository
            $admin = $this->adminRepo->findByUsername($username);

            if ($admin && password_verify($password, $admin['mat_khau'])) {
                // Check if account is active
                if (isset($admin['is_active']) && !$admin['is_active']) {
                    $this->auditService->logLogin($username, false);
                    $this->view('auth/admin_login', ['error' => 'Tài khoản đã bị vô hiệu hóa.']);
                    return;
                }

                // Check if 2FA is enabled
                if (!empty($admin['two_factor_enabled']) && $admin['two_factor_enabled'] == 1) {
                    // Store pending 2FA state
                    $_SESSION['2fa_pending'] = $admin['id'];
                    $_SESSION['2fa_username'] = $username;
                    $this->redirect(url('/admin/2fa/verify'));
                    return;
                }

                // No 2FA - complete login
                session_regenerate_id(true);

                // Set session with secure flags
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['ho_ten'];
                $_SESSION['admin_avatar'] = $admin['avatar'] ?? null;
                $_SESSION['admin_role'] = $admin['vai_tro'];
                $_SESSION['admin_role_id'] = $admin['role_id'] ?? 1;
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time();

                // --- REMEMBER ME LOGIC ---
                if (isset($_POST['remember']) && $_POST['remember'] === 'on') {
                    $token = bin2hex(random_bytes(32));
                    $hash = hash('sha256', $token);
                    $this->adminRepo->updateRememberToken($admin['id'], $hash);
                    // Set cookie for 30 days
                    setcookie('remember_admin', $token, [
                        'expires' => time() + (30 * 24 * 60 * 60),
                        'path' => '/',
                        'domain' => '',
                        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]);
                }

                // Log successful login
                $this->auditService->logLogin($username, true);
                $this->auditService->log('LOGIN', 'admin', $admin['id']);

                $this->redirect(url('/admin/dashboard'));
            } else {
                // Log failed login attempt
                $this->auditService->logLogin($username, false);
                $this->view('auth/admin_login', ['error' => 'Thông tin đăng nhập quản trị không đúng.']);
            }
        } else {
            $this->view('auth/admin_login');
        }
    }

    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            $validator = new \App\Core\Validator($_POST);
            $rules = [
                'cccd' => 'required|numeric|min:9|max:12',
                'fullname' => 'required|min:3',
                'password' => 'required|min:6',
                'confirm_password' => 'required',
                'phone' => 'required|numeric',
                'email' => 'required|email'
            ];

            if (!$validator->validate($rules)) {
                $this->view('auth/register', ['error' => $validator->getFirstError(), 'old' => $_POST]);
                return;
            }

            $cccd = $_POST['cccd'];
            $password = $_POST['password'];
            $confirmPassword = $_POST['confirm_password'];

            if ($password !== $confirmPassword) {
                $this->view('auth/register', ['error' => 'Mật khẩu xác nhận không khớp.', 'old' => $_POST]);
                return;
            }

            // Use Repository check
            if ($this->thiSinhRepo->findByCCCD($cccd)) {
                $this->view('auth/register', ['error' => 'Tài khoản CCCD này đã tồn tại trong hệ thống. Vui lòng chuyển sang trang Đăng nhập để tạo/sửa hồ sơ. (Sử dụng tính năng Quên mật khẩu nếu không nhớ thông tin).', 'old' => $_POST]);
                return;
            }

            // Use bcrypt with strong cost factor
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $data = [
                'cccd' => $cccd,
                'fullname' => normalize_name($_POST['fullname']),
                'password' => $hashedPassword,
                'phone' => trim($_POST['phone']),
                'email' => trim($_POST['email'])
            ];

            if ($this->thiSinhRepo->create($data)) {
                $this->auditService->log('REGISTER', 'candidate', null, null, ['cccd' => $cccd]);

                // Send welcome email with credentials
                try {
                    $emailService = new \App\Services\EmailTemplateService();
                    $emailService->sendWithTemplate(trim($_POST['email']), 'registration_success', [
                        'ho_ten' => trim($_POST['fullname']),
                        'cccd' => $cccd,
                        'mat_khau' => $password, // Plain password before hash
                        'login_url' => url('/login', true)
                    ]);
                } catch (\Exception $e) {
                    // Log error but don't block registration
                    error_log("Failed to send registration email: " . $e->getMessage());
                }

                $this->redirect(url('/login?registered=1'));
            } else {
                $this->view('auth/register', ['error' => 'Lỗi hệ thống. Vui lòng thử lại.']);
            }
        } else {
            $this->view('auth/register');
        }
    }

    public function logout(): void
    {
        $this->auditService->log('LOGOUT', 'user', $_SESSION['user_id'] ?? null);

        // --- REMEMBER ME LOGIC (Clear) ---
        if (isset($_SESSION['user_id'])) {
            $this->thiSinhRepo->updateRememberToken($_SESSION['user_id'], null);
        }
        if (isset($_COOKIE['remember_ts'])) {
            setcookie('remember_ts', '', [
                'expires' => time() - 3600,
                'path' => '/'
            ]);
        }

        session_destroy();
        $this->redirect(url('/'));
    }

    public function adminLogout(): void
    {
        $this->auditService->log('LOGOUT', 'admin', $_SESSION['admin_id'] ?? null);

        // --- REMEMBER ME LOGIC (Clear) ---
        if (isset($_SESSION['admin_id'])) {
            $this->adminRepo->updateRememberToken($_SESSION['admin_id'], null);
        }
        if (isset($_COOKIE['remember_admin'])) {
            setcookie('remember_admin', '', [
                'expires' => time() - 3600,
                'path' => '/'
            ]);
        }

        session_destroy();
        $this->redirect(url('/admin/login'));
    }

    public function forgotPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            $email = trim($_POST['email'] ?? '');
            $cccd = trim($_POST['cccd'] ?? '');

            if (empty($email) || empty($cccd)) {
                $this->view('auth/forgot-password', ['error' => 'Vui lòng nhập đầy đủ Email và số CCCD.']);
                return;
            }

            $resetService = new \App\Services\PasswordResetService();
            $token = $resetService->createToken($email, $cccd);

            if ($token) {
                // Send email
                $sent = $resetService->sendResetEmail($email, $token, $cccd);
                $this->auditService->log('PASSWORD_RESET_REQUEST', 'candidate', null, null, ['email' => $email, 'cccd' => $cccd, 'sent' => $sent]);
            }

            // Always show success message (don't reveal if email exists)
            $this->view('auth/forgot-password', [
                'success' => "Hệ thống đã gửi hướng dẫn đặt lại mật khẩu của bạn qua Email: <b>$email</b>\n\nVui lòng kiểm tra Hộp thư đến. \nNếu không thấy, hãy kiểm tra mục <b>Thư rác (Spam)</b> hoặc <b>Quảng cáo</b>."
            ]);
        } else {
            $this->view('auth/forgot-password');
        }
    }

    /**
     * Reset password via email token
     */
    public function resetPasswordEmail()
    {
        $token = $_GET['token'] ?? $_POST['token'] ?? '';
        $cccd = $_GET['cccd'] ?? $_POST['cccd'] ?? '';

        if (empty($token) || empty($cccd)) {
            $this->redirect(url('/forgot-password?error=invalid_token'));
            return;
        }

        $resetService = new \App\Services\PasswordResetService();
        $tokenData = $resetService->validateToken($token);

        if (!$tokenData) {
            $this->view('auth/reset-password-email', [
                'error' => 'Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.',
                'expired' => true
            ]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($password !== $confirmPassword) {
                $this->view('auth/reset-password-email', ['error' => 'Mật khẩu xác nhận không khớp.', 'token' => $token]);
                return;
            }

            if (strlen($password) < 6) {
                $this->view('auth/reset-password-email', ['error' => 'Mật khẩu tối thiểu 6 ký tự.', 'token' => $token]);
                return;
            }

            if ($resetService->resetPassword($token, $password, $cccd)) {
                $this->redirect(url('/login?reset=1'));
            } else {
                $this->view('auth/reset-password-email', ['error' => 'Lỗi cập nhật. Vui lòng thử lại.', 'token' => $token, 'cccd' => $cccd]);
            }
        } else {
            $this->view('auth/reset-password-email', ['token' => $token, 'email' => $tokenData['email'], 'cccd' => $cccd]);
        }
    }

    public function resetPassword()
    {
        if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_expires'])) {
            $this->redirect(url('/forgot-password'));
            return;
        }

        // Check if token expired
        if (time() > $_SESSION['reset_expires']) {
            unset($_SESSION['reset_email'], $_SESSION['reset_token'], $_SESSION['reset_expires']);
            $this->redirect(url('/forgot-password?error=expired'));
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($password !== $confirmPassword) {
                $this->view('auth/reset-password', ['error' => 'Mật khẩu xác nhận không khớp.']);
                return;
            }

            if (strlen($password) < 6) {
                $this->view('auth/reset-password', ['error' => 'Mật khẩu tối thiểu 6 ký tự.']);
                return;
            }

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            if ($this->thiSinhRepo->updatePasswordByEmail($_SESSION['reset_email'], $hashedPassword)) {
                $this->auditService->log('PASSWORD_RESET', 'candidate', null, null, ['email' => $_SESSION['reset_email']]);
                unset($_SESSION['reset_email'], $_SESSION['reset_token'], $_SESSION['reset_expires']);
                $this->redirect(url('/login?reset=1'));
            } else {
                $this->view('auth/reset-password', ['error' => 'Lỗi cập nhật. Vui lòng thử lại.']);
            }
        } else {
            $this->view('auth/reset-password');
        }
    }
}
