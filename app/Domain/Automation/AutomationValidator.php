<?php

declare(strict_types=1);

namespace App\Domain\Automation;

use InvalidArgumentException;

class AutomationValidator
{
    /**
     * @param array<int, array{uid: string, next_step_uid: ?string, alt_next_step_uid: ?string}> $steps
     */
    public function validate(array $steps): void
    {
        $uids = array_column($steps, 'uid');

        foreach ($steps as $step) {
            foreach (['next_step_uid', 'alt_next_step_uid'] as $key) {
                $target = $step[$key] ?? null;
                if ($target !== null && ! in_array($target, $uids, true)) {
                    throw new InvalidArgumentException("Invalid reference to {$target}");
                }
            }
        }

        $graph = [];
        foreach ($steps as $step) {
            $graph[$step['uid']] = array_filter([
                $step['next_step_uid'] ?? null,
                $step['alt_next_step_uid'] ?? null,
            ]);
        }

        $visited = [];
        $stack = [];

        $visit = function (string $uid) use (&$visit, &$visited, &$stack, $graph): void {
            if (isset($stack[$uid])) {
                throw new InvalidArgumentException('Cycle detected');
            }

            if (isset($visited[$uid])) {
                return;
            }

            $visited[$uid] = true;
            $stack[$uid] = true;

            foreach ($graph[$uid] ?? [] as $next) {
                $visit($next);
            }

            unset($stack[$uid]);
        };

        foreach (array_keys($graph) as $uid) {
            $visit($uid);
        }
    }
}
