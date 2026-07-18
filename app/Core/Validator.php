<?php

namespace App\Core;

class Validator {
    private array $errors = [];

    public function validate(array $data, array $rules): bool {
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $rulesList = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;

            foreach ($rulesList as $rule) {
                $params = [];
                if (strpos($rule, ':') !== false) {
                    list($rule, $paramStr) = explode(':', $rule);
                    $params = explode(',', $paramStr);
                }

                $method = 'validate' . ucfirst($rule);
                if (method_exists($this, $method)) {
                    $isValid = $this->$method($field, $value, $params, $data);
                    if (!$isValid) {
                        break;
                    }
                }
            }
        }

        return empty($this->errors);
    }

    public function getErrors(): array {
        return $this->errors;
    }

    public function getFirstError(string $field): ?string {
        return $this->errors[$field][0] ?? null;
    }

    private function validateRequired(string $field, mixed $value): bool {
        if ($value === null || $value === '' || (is_array($value) && count($value) === 0)) {
            $this->addError($field, "The " . str_replace('_', ' ', $field) . " field is required.");
            return false;
        }
        return true;
    }

    private function validateEmail(string $field, mixed $value): bool {
        if (empty($value)) return true;
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "The " . str_replace('_', ' ', $field) . " must be a valid email address.");
            return false;
        }
        return true;
    }

    private function validateDate(string $field, mixed $value): bool {
        if (empty($value)) return true;
        if (strtotime($value) === false) {
            $this->addError($field, "The " . str_replace('_', ' ', $field) . " must be a valid date.");
            return false;
        }
        return true;
    }

    private function validateNumeric(string $field, mixed $value): bool {
        if (empty($value) && $value !== 0 && $value !== '0') return true;
        if (!is_numeric($value)) {
            $this->addError($field, "The " . str_replace('_', ' ', $field) . " must be a number.");
            return false;
        }
        return true;
    }

    private function validateMin(string $field, mixed $value, array $params): bool {
        if (empty($value) && $value !== 0 && $value !== '0') return true;
        $min = (float)($params[0] ?? 0);
        if (is_numeric($value)) {
            if ($value < $min) {
                $this->addError($field, "The " . str_replace('_', ' ', $field) . " must be at least {$min}.");
                return false;
            }
        } else {
            if (strlen((string)$value) < $min) {
                $this->addError($field, "The " . str_replace('_', ' ', $field) . " must be at least {$min} characters.");
                return false;
            }
        }
        return true;
    }

    private function addError(string $field, string $message): void {
        $this->errors[$field][] = $message;
    }
}
