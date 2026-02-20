<?php
namespace App\Services;

use App\Core\Database;
use App\Models\MasterData;

class PdfService {
    protected $db;
    protected $masterData;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->masterData = new MasterData();
    }

    /**
     * Generate Score Report HTML (Print-friendly)
     */
    public function generateScoreReport($soCccd) {
        // Get candidate info
        $stmt = $this->db->prepare("SELECT * FROM thi_sinh WHERE so_cccd = ?");
        $stmt->execute([$soCccd]);
        $candidate = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$candidate) return null;

        // Get aspirations
        $stmt = $this->db->prepare("SELECT nv.*, n.ten_nganh FROM nguyen_vong nv JOIN dm_nganh n ON nv.ma_nganh = n.ma_nganh WHERE nv.so_cccd = ? ORDER BY nv.thu_tu");
        $stmt->execute([$soCccd]);
        $aspirations = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Get scores
        $stmt = $this->db->prepare("SELECT * FROM diem_chi_tiet WHERE so_cccd = ?");
        $stmt->execute([$soCccd]);
        $scores = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $scoresByType = [];
        foreach ($scores as $sc) {
            $scoresByType[$sc['loai_diem']][$sc['ma_mon']] = $sc['diem'];
        }

        return $this->renderReport($candidate, $aspirations, $scoresByType);
    }

    protected function renderReport($candidate, $aspirations, $scores) {
        $schoolName = $this->masterData->getSetting('school_name') ?? 'Trường Đại học Hùng Vương';
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Phiếu Báo Điểm</title>
    <style>
        @page { margin: 2cm; }
        body { font-family: "Times New Roman", serif; font-size: 14px; line-height: 1.6; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; text-transform: uppercase; }
        .header h3 { margin: 5px 0; color: #333; }
        .info-table { width: 100%; margin: 20px 0; }
        .info-table td { padding: 5px 10px; }
        .info-table .label { font-weight: bold; width: 180px; }
        .score-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .score-table th, .score-table td { border: 1px solid #333; padding: 8px; text-align: center; }
        .score-table th { background: #f0f0f0; }
        .aspirations { margin: 20px 0; }
        .signature { margin-top: 40px; text-align: right; }
        .signature p { margin: 5px 0; }
        @media print { body { -webkit-print-color-adjust: exact; } }
    </style>
</head>
<body>
    <div class="header">
        <h2>' . htmlspecialchars($schoolName) . '</h2>
        <h3>PHIẾU BÁO ĐIỂM XÉT TUYỂN</h3>
        <p>Năm học: ' . date('Y') . '</p>
    </div>

    <table class="info-table">
        <tr><td class="label">Họ và tên:</td><td><strong>' . htmlspecialchars($candidate['ho_va_ten']) . '</strong></td></tr>
        <tr><td class="label">Số CCCD:</td><td>' . htmlspecialchars($candidate['so_cccd']) . '</td></tr>
        <tr><td class="label">Ngày sinh:</td><td>' . ($candidate['ngay_sinh'] ?? 'N/A') . '</td></tr>
        <tr><td class="label">Khu vực ưu tiên:</td><td>' . htmlspecialchars($candidate['khu_vuc_uu_tien'] ?? 'Không') . '</td></tr>
        <tr><td class="label">Đối tượng ưu tiên:</td><td>' . htmlspecialchars($candidate['doi_tuong_uu_tien'] ?? 'Không') . '</td></tr>
    </table>

    <h4>I. ĐIỂM THI THPT QUỐC GIA</h4>
    <table class="score-table">
        <tr><th>Môn</th><th>Điểm</th></tr>';

        foreach ($scores['thpt'] ?? [] as $mon => $diem) {
            $html .= "<tr><td>{$mon}</td><td>{$diem}</td></tr>";
        }

        $html .= '</table>

    <h4>II. ĐIỂM HỌC BẠ (Năm 12)</h4>
    <table class="score-table">
        <tr><th>Môn</th><th>Điểm</th></tr>';

        foreach ($scores['hocba_12'] ?? [] as $mon => $diem) {
            $html .= "<tr><td>{$mon}</td><td>{$diem}</td></tr>";
        }

        $html .= '</table>

    <h4>III. NGUYỆN VỌNG ĐĂNG KÝ</h4>
    <table class="score-table">
        <tr><th>TT</th><th>Ngành</th><th>Điểm xét tuyển</th><th>Trạng thái</th></tr>';

        foreach ($aspirations as $nv) {
            $html .= "<tr>
                <td>{$nv['thu_tu']}</td>
                <td style='text-align:left'>" . htmlspecialchars($nv['ten_nganh']) . "</td>
                <td>" . ($nv['diem_xet_tuyen'] ?? '-') . "</td>
                <td>" . htmlspecialchars($nv['trang_thai'] ?? 'Chờ xét') . "</td>
            </tr>";
        }

        $html .= '</table>

    <div class="signature">
        <p>Ngày ' . date('d') . ' tháng ' . date('m') . ' năm ' . date('Y') . '</p>
        <p><strong>PHÒNG TUYỂN SINH</strong></p>
        <br><br><br>
        <p>..............................</p>
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Output PDF as download (using browser print)
     */
    public function output($soCccd) {
        $html = $this->generateScoreReport($soCccd);
        
        if (!$html) {
            return false;
        }

        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
        echo '<script>window.print();</script>';
        exit;
    }
}
