<?php
namespace Nava\Pandago\Traits;

use Nava\Pandago\Exceptions\ValidationException;
use Symfony\Component\Validator\Constraints as Assert;
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
        $constraints = $this->parseRules($rules);

        $validator = Validation::createValidator();

        $violations = $validator->validate($data, new Assert\Collection($constraints));

        if (count($violations) > 0) {
            $errors = [];

            foreach ($violations as $violation) {
                $propertyPath          = str_replace(['[', ']'], ['', ''], $violation->getPropertyPath());
                $errors[$propertyPath] = $violation->getMessage();
            }

            throw new ValidationException('Validation failed', $errors);
        }
    }

    /**
     * Parse the rules into Symfony validator constraints.
     *
     * @param array $rules
     * @return array
     */
    protected function parseRules(array $rules): array
    {
        $constraints = [];

        foreach ($rules as $field => $rule) {
            $constraints[$field] = $this->parseRule($rule);
        }

        return $constraints;
    }

    /**
     * Parse a single rule into a Symfony validator constraint.
     *
     * @param string $rule
     * @return Assert\Required|Assert\Optional
     */
    protected function parseRule(string $rule)
    {
        $fieldConstraints = [];
        $rules            = explode('|', $rule);
        $isRequired       = in_array('required', $rules);

        foreach ($rules as $rule) {
            if ('required' === $rule) {
                continue;
            }

            if (strpos($rule, ':') !== false) {
                list($ruleName, $ruleValue) = explode(':', $rule);
                $ruleValues                 = explode(',', $ruleValue);

                switch ($ruleName) {
                    case 'in':
                        $fieldConstraints[] = new Assert\Choice([
                            'choices' => $ruleValues,
                            'message' => 'This value should be one of {{ choices }}.',
                        ]);
                        break;
                    case 'min':
                        $fieldConstraints[] = new Assert\Length([
                            'min'        => (int) $ruleValue,
                            'minMessage' => 'This value is too short. It should have {{ limit }} character or more.',
                        ]);
                        break;
                    case 'max':
                        $fieldConstraints[] = new Assert\Length([
                            'max'        => (int) $ruleValue,
                            'maxMessage' => 'This value is too long. It should have {{ limit }} character or less.',
                        ]);
                        break;
                }
            } else {
                switch ($rule) {
                    case 'string':
                        $fieldConstraints[] = new Assert\Type([
                            'type'    => 'string',
                            'message' => 'This value should be of type {{ type }}.',
                        ]);
                        break;
                    case 'numeric':
                    case 'number':
                        $fieldConstraints[] = new Assert\Type([
                            'type'    => 'numeric',
                            'message' => 'This value should be of type {{ type }}.',
                        ]);
                        break;
                    case 'integer':
                    case 'int':
                        $fieldConstraints[] = new Assert\Type([
                            'type'    => 'integer',
                            'message' => 'This value should be of type {{ type }}.',
                        ]);
                        break;
                    case 'email':
                        $fieldConstraints[] = new Assert\Email([
                            'message' => 'This value is not a valid email address.',
                        ]);
                        break;
                }
            }
        }

        $constraintCollection = new Assert\Collection([
            'fields'           => $fieldConstraints,
            'allowExtraFields' => true,
        ]);

        return $isRequired ? new Assert\Required($constraintCollection) : new Assert\Optional($constraintCollection);
    }
}
