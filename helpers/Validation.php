<?php
/**
 * Validation class to validate form inputs
 */
class Validation {
    private $errors = [];

    /**
     * Check if fields are empty in post/get data
     * @param array $fields Array of field names
     * @param array $data
     * @return bool
     */
    public function required($fields, $data) {
        $valid = true;
        foreach ($fields as $field => $displayName) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                $this->errors[$field] = "{$displayName} is required.";
                $valid = false;
            }
        }
        return $valid;
    }

    /**
     * Validate email format
     * @param string $field Field name
     * @param string $email
     * @return bool
     */
    public function email($field, $email) {
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "Invalid email format.";
            return false;
        }
        return true;
    }

    /**
     * Validate phone numbers
     * @param string $field
     * @param string $phone
     * @return bool
     */
    public function phone($field, $phone) {
        // Matches typical phone formats
        if (!empty($phone) && !preg_match('/^[+]?[0-9\s\-()]{7,20}$/', $phone)) {
            $this->errors[$field] = "Invalid phone number format.";
            return false;
        }
        return true;
    }

    /**
     * Check if numeric
     * @param string $field
     * @param mixed $value
     * @return bool
     */
    public function numeric($field, $value) {
        if (!empty($value) && !is_numeric($value)) {
            $this->errors[$field] = "Must be a valid number.";
            return false;
        }
        return true;
    }

    /**
     * Add manual validation error
     * @param string $field
     * @param string $message
     */
    public function addError($field, $message) {
        $this->errors[$field] = $message;
    }

    /**
     * Check if validation passed
     * @return bool
     */
    public function passes() {
        return empty($this->errors);
    }

    /**
     * Get error list
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }
}
