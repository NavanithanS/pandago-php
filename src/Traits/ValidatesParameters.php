<?php
namespace Nava\Pandago\Traits;

use Nava\Pandago\Exceptions\ValidationException;

trait ValidatesParameters
{
    /**
     * Validate the parameters against the given rules.
     *
     * @param array $parameters
     * @param array $rules
     * @throws ValidationException
     */
    protected function validate(array $parameters, array $rules): void
    {
        foreach ($rules as $key => $rule) {
            // Skip if the rule doesn't apply to any parameter
            if (! isset($parameters[$key]) && ! in_array('required', explode('|', $rule))) {
                continue;
            }

            $ruleSet = explode('|', $rule);

            foreach ($ruleSet as $singleRule) {
                $this->validateParameter($key, $parameters[$key] ?? null, $singleRule);
            }
        }
    }

    /**
     * Validate a parameter based on a specific rule.
     *
     * @param string $key
     * @param mixed $value
     * @param string $rule
     * @throws ValidationException
     */
    private function validateParameter(string $key, $value, string $rule): void
    {
        // Handle rule with value (e.g. max:255)
        if (strpos($rule, ':') !== false) {
            list($ruleName, $ruleValue) = explode(':', $rule, 2);
        } else {
            $ruleName  = $rule;
            $ruleValue = null;
        }

        switch ($ruleName) {
            case 'required':
                if (null === $value || '' === $value) {
                    throw new ValidationException("{$key} is required");
                }
                break;

            case 'numeric':
                if (null !== $value && ! is_numeric($value)) {
                    throw new ValidationException("{$key} must be a number");
                }
                break;

            case 'min':
                if (null !== $value && is_numeric($value) && $value < (float) $ruleValue) {
                    throw new ValidationException("{$key} must be at least {$ruleValue}");
                }
                break;

            case 'max':
                if (null !== $value && is_string($value) && strlen($value) > (int) $ruleValue) {
                    throw new ValidationException("{$key} must not exceed {$ruleValue} characters");
                }
                break;

            case 'string':
                if (null !== $value && ! is_string($value)) {
                    throw new ValidationException("{$key} must be a string");
                }
                break;

            case 'enum':
                $allowedValues = explode(',', $ruleValue);
                if (null !== $value && ! in_array($value, $allowedValues)) {
                    throw new ValidationException("{$key} must be one of: " . implode(', ', $allowedValues));
                }
                break;

                // Add more validation rules as needed
        }
    }
}
