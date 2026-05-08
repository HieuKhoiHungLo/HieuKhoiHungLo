<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class EmailTemplateService {
    protected $db;
    protected $mailer;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->mailer = new MailerService();
    }

    /**
     * Get template by slug
     */
    /**
     * Get template by slug (code)
     */
    public function getTemplate(string $slug): ?array {
        // Schema has 'code' not 'slug', and no 'is_active'
        $stmt = $this->db->prepare("SELECT * FROM email_templates WHERE code = ?");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get all templates
     */
    public function getAllTemplates(): array {
        // Alias subject as name, code as slug
        $stmt = $this->db->query("SELECT *, code as slug, subject as name FROM email_templates ORDER BY code ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update template
     */
    public function updateTemplate(int $id, string $subject, string $body): bool {
        $stmt = $this->db->prepare("UPDATE email_templates SET subject = ?, body = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$subject, $body, $id]);
    }

    /**
     * Render template with data
     */
    public function render(string $slug, array $data): ?array {
        $template = $this->getTemplate($slug);
        if (!$template) {
            return null;
        }

        $subject = $template['subject'];
        $body = $template['body'];

        // Replace placeholders {{var}} with actual data
        foreach ($data as $key => $value) {
            $subject = str_replace('{{' . $key . '}}', htmlspecialchars($value ?? ''), $subject);
            // For body, allow HTML in certain fields like ket_qua_chi_tiet
            if (in_array($key, ['ket_qua_chi_tiet', 'danh_sach_nguyen_vong'])) {
                $body = str_replace('{{' . $key . '}}', $value ?? '', $body);
            } else {
                $body = str_replace('{{' . $key . '}}', htmlspecialchars($value ?? ''), $body);
            }
        }

        return [
            'subject' => $subject,
            'body' => $body
        ];
    }

    /**
     * Send email using template
     */
    public function sendWithTemplate(string $to, string $slug, array $data): mixed {
        $rendered = $this->render($slug, $data);
        if (!$rendered) {
            return "Template '$slug' not found or inactive.";
        }

        return $this->mailer->send($to, $rendered['subject'], $rendered['body'], true, 'system');
    }

    /**
     * Queue email using template
     */
    public function queueWithTemplate(string $to, string $slug, array $data): mixed {
        $rendered = $this->render($slug, $data);
        if (!$rendered) {
            return "Template '$slug' not found or inactive.";
        }

        return $this->mailer->enqueue($to, $rendered['subject'], $rendered['body'], true, 'bulk');
    }

    /**
     * Build review result HTML for email
     */
    public function buildReviewResultHtml(array $sections): string {
        $html = '<ul style="list-style: none; padding: 0; margin: 20px 0;">';
        
        foreach ($sections as $section) {
            $icon = $section['status'] === 'ok' 
                ? '✅' 
                : ($section['status'] === 'missing' ? '❌' : '⚠️');
            $color = $section['status'] === 'ok' ? '#28a745' : '#dc3545';
            
            $html .= '<li style="padding: 10px; border-bottom: 1px solid #eee; display: flex; align-items: center;">';
            $html .= '<span style="font-size: 18px; margin-right: 10px;">' . $icon . '</span>';
            $html .= '<span style="color: ' . $color . '; font-weight: bold;">' . htmlspecialchars($section['name']) . '</span>';
            if (!empty($section['note'])) {
                $html .= '<span style="color: #666; margin-left: 10px; font-size: 13px;">(' . htmlspecialchars($section['note']) . ')</span>';
            }
            $html .= '</li>';
        }
        
        $html .= '</ul>';
        return $html;
    }
}
