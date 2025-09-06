<?php

declare(strict_types=1);

use App\Domain\Automation\AutomationValidator;

it('rejects missing step references', function () {
    $validator = new AutomationValidator();
    $steps = [
        ['uid' => 'a', 'next_step_uid' => 'missing', 'alt_next_step_uid' => null],
    ];

    expect(fn () => $validator->validate($steps))->toThrow(InvalidArgumentException::class);
});

it('rejects cycles', function () {
    $validator = new AutomationValidator();
    $steps = [
        ['uid' => 'a', 'next_step_uid' => 'b', 'alt_next_step_uid' => null],
        ['uid' => 'b', 'next_step_uid' => 'a', 'alt_next_step_uid' => null],
    ];

    expect(fn () => $validator->validate($steps))->toThrow(InvalidArgumentException::class);
});

it('accepts valid graphs', function () {
    $validator = new AutomationValidator();
    $steps = [
        ['uid' => 'a', 'next_step_uid' => 'b', 'alt_next_step_uid' => null],
        ['uid' => 'b', 'next_step_uid' => null, 'alt_next_step_uid' => null],
    ];

    expect(fn () => $validator->validate($steps))->not->toThrow(InvalidArgumentException::class);
});
