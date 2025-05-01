<?php
namespace Nava\Pandago\Traits;

use Nava\Pandago\Exceptions\ValidationException;
use Symfony\Component\Validator\Validation;

trait ValidatesParameters
{
    /**
     * Validate the given data against the given rules.
     *
     * @param array $data
     * @param array $rules
     * @throws ValidationException
     */
    protected function validate(array $data, array $rules): void
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            // Check if the field is required and not provided or empty
            if (strpos($rule, 'required') !== false) {
                if (! isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                    $errors[$field] = 'The ' . $field . ' field is required.';
                    continue;
                }
            } elseif (! isset($data[$field])) {
                // Skip validation for optional fields that are not provided
                continue;
            }

            // Validate field against its rules
            $fieldErrors = $this->validateField($field, $data[$field], $rule);
            if (! empty($fieldErrors)) {
                $errors[$field] = $fieldErrors;
            }
        }

        // If we have any errors, throw an exception
        if (! empty($errors)) {
            throw new ValidationException('Validation failed: ' . json_encode($errors), $errors);
        }
    }

    /**
     * Validate a single field against its rules.
     *
     * @param string $field
     * @param mixed $value
     * @param string $ruleString
     * @return string|null
     */
    protected function validateField(string $field, $value, string $ruleString): ?string
    {
        $rules = explode('|', $ruleString);

        foreach ($rules as $rule) {
            // Skip already checked required rule
            if ('required' === $rule) {
                continue;
            }

            // Parse rule with parameters
            if (strpos($rule, ':') !== false) {
                list($ruleName, $ruleParams) = explode(':', $rule, 2);
                $ruleValues                  = explode(',', $ruleParams);

                switch ($ruleName) {
                    case 'in':
                        if (! in_array($value, $ruleValues)) {
                            return "The {$field} must be one of: " . implode(', ', $ruleValues);
                        }
                        break;

                    case 'max':
                        if (is_string($value)) {
                            if (mb_strlen($value) > (int) $ruleValues[0]) {
                                return "The {$field} must not be greater than {$ruleValues[0]} characters.";
                            }
                        } elseif (is_numeric($value)) {
                            if ($value > (float) $ruleValues[0]) {
                                return "The {$field} must not be greater than {$ruleValues[0]}.";
                            }
                        }
                        break;

                    case 'min':
                        if (is_string($value)) {
                            if (mb_strlen($value) < (int) $ruleValues[0]) {
                                return "The {$field} must be at least {$ruleValues[0]} characters.";
                            }
                        } elseif (is_numeric($value)) {
                            if ($value < (float) $ruleValues[0]) {
                                return "The {$field} must be at least {$ruleValues[0]}.";
                            }
                        }
                        break;
                }
            } else {
                // Simple rules without parameters
                switch ($rule) {
                    case 'string':
                        if (! is_string($value)) {
                            return "The {$field} must be a string.";
                        }
                        break;

                    case 'numeric':
                        if (! is_numeric($value)) {
                            return "The {$field} must be numeric.";
                        }
                        break;

                    case 'integer':
                    case 'int':
                        if (! is_int($value) && ! ctype_digit((string) $value)) {
                            return "The {$field} must be an integer.";
                        }
                        break;

                    case 'email':
                        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            return "The {$field} must be a valid email address.";
                        }
                        break;
                }
            }
        }

        return null;
    }
}
