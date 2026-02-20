<?php
namespace App\Services;

use App\Core\Database;

class RuleEngine {
    protected $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get active rules for a major (or global rules)
     */
    public function getRulesForMajor($maNganh = null) {
        $sql = "SELECT * FROM admission_rules WHERE is_active = true AND (ma_nganh IS NULL OR ma_nganh = ?) ORDER BY priority DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$maNganh]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Evaluate all rules against candidate data
     */
    public function evaluate($candidateScores, $maNganh = null) {
        $rules = $this->getRulesForMajor($maNganh);
        $results = ['passed' => true, 'failures' => []];

        foreach ($rules as $rule) {
            $condition = json_decode($rule['condition'], true);
            $passed = $this->evaluateCondition($condition, $candidateScores);

            if ($rule['rule_type'] === 'disqualify' && $passed) {
                // Disqualify rule matched = fail
                $results['passed'] = false;
                $results['failures'][] = $rule['message'];
            } elseif ($rule['rule_type'] === 'minimum' && !$passed) {
                // Minimum rule not met = fail
                $results['passed'] = false;
                $results['failures'][] = $rule['message'];
            }
        }

        return $results;
    }

    /**
     * Evaluate a single condition
     */
    protected function evaluateCondition($cond, $data) {
        // AND/OR compound
        if (isset($cond['AND'])) {
            foreach ($cond['AND'] as $sub) {
                if (!$this->evaluateCondition($sub, $data)) return false;
            }
            return true;
        }

        if (isset($cond['OR'])) {
            foreach ($cond['OR'] as $sub) {
                if ($this->evaluateCondition($sub, $data)) return true;
            }
            return false;
        }

        // Simple condition: {field, op, value}
        $field = $cond['field'] ?? null;
        $op = $cond['op'] ?? '=';
        $value = $cond['value'] ?? 0;
        $actual = $data[$field] ?? 0;

        return match($op) {
            '=' => $actual == $value,
            '!=' => $actual != $value,
            '>' => $actual > $value,
            '>=' => $actual >= $value,
            '<' => $actual < $value,
            '<=' => $actual <= $value,
            'in' => in_array($actual, (array)$value),
            default => true
        };
    }

    /**
     * Get all rules for admin
     */
    public function getAllRules() {
        $stmt = $this->db->query("SELECT r.*, n.ten_nganh FROM admission_rules r LEFT JOIN dm_nganh n ON r.ma_nganh = n.ma_nganh ORDER BY r.priority DESC, r.id");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Create or update rule
     */
    public function saveRule($data) {
        if (!empty($data['id'])) {
            $sql = "UPDATE admission_rules SET name = ?, ma_nganh = ?, rule_type = ?, condition = ?, message = ?, is_active = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$data['name'], $data['ma_nganh'] ?: null, $data['rule_type'], $data['condition'], $data['message'], $data['is_active'] ?? true, $data['id']]);
        } else {
            $sql = "INSERT INTO admission_rules (name, ma_nganh, rule_type, condition, message) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$data['name'], $data['ma_nganh'] ?: null, $data['rule_type'], $data['condition'], $data['message']]);
        }
    }

    public function deleteRule($id) {
        $stmt = $this->db->prepare("DELETE FROM admission_rules WHERE id = ?");
        $stmt->execute([$id]);
    }
}
