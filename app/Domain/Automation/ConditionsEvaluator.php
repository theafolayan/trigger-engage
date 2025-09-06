<?php

declare(strict_types=1);

namespace App\Domain\Automation;

use Illuminate\Support\Arr;

class ConditionsEvaluator
{
    public function evaluate(array $conditions, array $contact, array $event): bool
    {
        $data = ['contact' => $contact, 'event' => $event];

        foreach ($conditions as $condition) {
            $path = $condition['path'] ?? null;
            $operator = $condition['op'] ?? null;
            $value = $condition['value'] ?? null;
            $actual = data_get($data, $path);

            switch ($operator) {
                case '==':
                    if ($actual !== $value) {
                        return false;
                    }

                    break;
                case '!=':
                    if ($actual === $value) {
                        return false;
                    }

                    break;
                case 'in':
                    if (! is_array($value) || ! in_array($actual, $value, true)) {
                        return false;
                    }

                    break;
                case '<=':
                    if (! ($actual <= $value)) {
                        return false;
                    }

                    break;
                case '>=':
                    if (! ($actual >= $value)) {
                        return false;
                    }

                    break;
                case '<':
                    if (! ($actual < $value)) {
                        return false;
                    }

                    break;
                case '>':
                    if (! ($actual > $value)) {
                        return false;
                    }

                    break;
                case 'exists':
                    if (! Arr::has($data, $path)) {
                        return false;
                    }

                    break;
                case 'contains':
                    if (is_array($actual)) {
                        if (! in_array($value, $actual, true)) {
                            return false;
                        }
                    } elseif (is_string($actual) && is_string($value)) {
                        if (! str_contains($actual, $value)) {
                            return false;
                        }
                    } else {
                        return false;
                    }

                    break;
                default:
                    return false;
            }
        }

        return true;
    }
}
