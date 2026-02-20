<?php
namespace App\Core;

class Validator {
    protected $errors = [];
    protected $data;

    public function __construct($data) {
        $this->data = $data;
    }

    public function validate($rules) {
        foreach ($rules as $field => $ruleString) {
            $rulesArray = explode('|', $ruleString);
            foreach ($rulesArray as $rule) {
                $value = $this->data[$field] ?? null;
                $this->applyRule($field, $rule, $value);
            }
        }
        return empty($this->errors);
    }

    protected function applyRule($field, $rule, $value) {
        if ($rule === 'required') {
            if (empty($value) && $value !== '0') {
                $this->addError($field, "Trường này là bắt buộc.");
            }
        }

        if ($rule === 'email') {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->addError($field, "Email không hợp lệ.");
            }
        }

        if (strpos($rule, 'min:') === 0) {
            $min = (int) substr($rule, 4);
            if (strlen($value) < $min) {
                $this->addError($field, "Độ dài tối thiểu là $min ký tự.");
            }
        }
        
        if (strpos($rule, 'max:') === 0) {
            $max = (int) substr($rule, 4);
            if (strlen($value) > $max) {
                $this->addError($field, "Độ dài tối đa là $max ký tự.");
            }
        }

        if ($rule === 'numeric') {
            if (!is_numeric($value)) {
                $this->addError($field, "Phải là số.");
            }
        }

        if ($rule === 'confirmed') {
            $confirmField = $field . '_confirmation'; // e.g., password_confirmation
            // Normally handled by checking specific confirm field logic in controller or adding 'confirmed' logic here looking for field_confirm key
            // For simplicity in this lightweight validator, we can assume 'confirm_password' convention or pass it manually. 
            // In AuthController we manually checked password match, so we can keep it simple there or enhance this validator later.
        }
    }

    protected function addError($field, $message) {
        if (empty($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }

    public function getErrors() {
        return $this->errors;
    }

    public function getFirstError() {
        foreach ($this->errors as $field => $msg) {
            return $msg;
        }
        return null;
    }
}
