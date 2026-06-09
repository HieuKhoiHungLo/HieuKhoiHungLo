<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\AdmissionLetterService;
use PDO;

class AdmissionLookupController extends Controller
{
    /**
     * Trang tra cứu thông báo trúng tuyển (public, không cần đăng nhập)
     */
    public function index()
    {
        $this->view('public/admission_lookup/index', [
            'title' => 'Tra cứu Thông báo Trúng tuyển - HVU 2026'
        ]);
    }

    /**
     * Xử lý tra cứu theo CCCD / SBD / Email
     */
    public function search()
    {
        $this->validateCsrf();

        $keyword = trim($_POST['keyword'] ?? '');

        if (empty($keyword)) {
            $this->redirect(url('/tra-cuu-trung-tuyen?error=empty'));
            return;
        }

        // Rate limit đơn giản: chỉ cho phép tra cứu, không cần session phức tạp
        $db = Database::getInstance()->getConnection();

        // Tìm kiếm theo CCCD, SBD hoặc Email trong bảng thu_trung_tuyen
        $stmt = $db->prepare("
            SELECT t.*,
                   n.chi_tieu,
                   n.diem_nam_truoc
            FROM thu_trung_tuyen t
            LEFT JOIN dm_nganh n ON n.ma_nganh = t.ma_nganh
            WHERE (
                t.so_cccd = ?
                OR t.sbd = ?
                OR LOWER(t.email) = LOWER(?)
            )
            ORDER BY t.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$keyword, $keyword, $keyword]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($records)) {
            $this->redirect(url('/tra-cuu-trung-tuyen?error=not_found&q=' . urlencode($keyword)));
            return;
        }

        // Lấy template email ADMISSION_LETTER để render nội dung
        $tplStmt = $db->prepare("SELECT * FROM email_templates WHERE code = 'ADMISSION_LETTER' LIMIT 1");
        $tplStmt->execute();
        $template = $tplStmt->fetch(PDO::FETCH_ASSOC);

        // Render nội dung thư cho mỗi bản ghi
        $service = new AdmissionLetterService();
        $renderedItems = [];
        foreach ($records as $record) {
            $body = $template ? $service->renderTemplate($template['body'], $record) : null;
            $renderedItems[] = [
                'record'   => $record,
                'body'     => $body,
                'template' => $template,
            ];
        }

        $this->view('public/admission_lookup/result', [
            'title'   => 'Kết quả tra cứu Trúng tuyển',
            'keyword' => $keyword,
            'items'   => $renderedItems,
        ]);
    }
}
