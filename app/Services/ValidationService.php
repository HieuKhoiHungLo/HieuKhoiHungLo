<?php
namespace App\Services;

/**
 * ValidationService - Centralized server-side validation
 */
class ValidationService {
    
    protected $errors = [];
    protected $data = [];

    public function __construct(array $data = []) {
        $this->data = $data;
    }

    /**
     * Validate data against rules
     */
    public function validate(array $rules): bool {
        $this->errors = [];
        
        foreach ($rules as $field => $ruleString) {
            $rulesArray = explode('|', $ruleString);
            $value = $this->data[$field] ?? null;
            
            foreach ($rulesArray as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }
        
        return empty($this->errors);
    }

    protected function applyRule($field, $value, $rule) {
        $params = [];
        if (strpos($rule, ':') !== false) {
            [$rule, $paramStr] = explode(':', $rule, 2);
            $params = explode(',', $paramStr);
        }

        switch ($rule) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    $this->addError($field, 'Trường này là bắt buộc');
                }
                break;
                
            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, 'Email không hợp lệ');
                }
                break;
                
            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    $this->addError($field, 'Phải là số');
                }
                break;
                
            case 'min':
                $min = (int)($params[0] ?? 0);
                if (strlen($value) < $min) {
                    $this->addError($field, "Tối thiểu $min ký tự");
                }
                break;
                
            case 'max':
                $max = (int)($params[0] ?? 255);
                if (strlen($value) > $max) {
                    $this->addError($field, "Tối đa $max ký tự");
                }
                break;
                
            case 'cccd':
                if (!preg_match('/^[0-9]{9,12}$/', $value)) {
                    $this->addError($field, 'CCCD phải là 9-12 số');
                }
                break;
                
            case 'phone':
                if (!preg_match('/^(0|\+84)[0-9]{9,10}$/', preg_replace('/\s+/', '', $value))) {
                    $this->addError($field, 'Số điện thoại không hợp lệ');
                }
                break;
                
            case 'date':
                if (!empty($value) && !strtotime($value)) {
                    $this->addError($field, 'Ngày không hợp lệ');
                }
                break;
                
            case 'in':
                if (!empty($value) && !in_array($value, $params)) {
                    $this->addError($field, 'Giá trị không hợp lệ');
                }
                break;
                
            case 'regex':
                $pattern = $params[0] ?? '';
                if (!empty($value) && !preg_match($pattern, $value)) {
                    $this->addError($field, 'Định dạng không hợp lệ');
                }
                break;
                
            case 'password_strength':
                if (!empty($value)) {
                    if (strlen($value) < 8) {
                        $this->addError($field, 'Mật khẩu tối thiểu 8 ký tự');
                    }
                    if (!preg_match('/[A-Z]/', $value)) {
                        $this->addError($field, 'Mật khẩu cần có ít nhất 1 chữ hoa');
                    }
                    if (!preg_match('/[0-9]/', $value)) {
                        $this->addError($field, 'Mật khẩu cần có ít nhất 1 số');
                    }
                }
                break;
        }
    }

    protected function addError($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    public function getErrors(): array {
        return $this->errors;
    }

    public function getFirstError(): ?string {
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0] ?? null;
        }
        return null;
    }

    public function hasErrors(): bool {
        return !empty($this->errors);
    }

    /**
     * Sanitize input string
     */
    public static function sanitize($value): string {
        if (is_array($value)) {
            return array_map([self::class, 'sanitize'], $value);
        }
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize all POST data
     */
    public static function sanitizePost(): array {
        $sanitized = [];
        foreach ($_POST as $key => $value) {
            $sanitized[$key] = self::sanitize($value);
        }
        return $sanitized;
    }
}
