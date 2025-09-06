<?php

declare(strict_types=1);

use App\Domain\Automation\ConditionsEvaluator;

it('evaluates equality operator', function () {
    $evaluator = new ConditionsEvaluator();
    $condition = ['path' => 'contact.age', 'op' => '==', 'value' => 30];

    $true = $evaluator->evaluate([$condition], ['age' => 30], []);
    $false = $evaluator->evaluate([$condition], ['age' => 25], []);

    expect($true)->toBeTrue()->and($false)->toBeFalse();
});

it('evaluates inequality operator', function () {
    $evaluator = new ConditionsEvaluator();
    $condition = ['path' => 'contact.age', 'op' => '!=', 'value' => 30];

    $true = $evaluator->evaluate([$condition], ['age' => 25], []);
    $false = $evaluator->evaluate([$condition], ['age' => 30], []);

    expect($true)->toBeTrue()->and($false)->toBeFalse();
});

it('evaluates in operator', function () {
    $evaluator = new ConditionsEvaluator();
    $condition = ['path' => 'event.type', 'op' => 'in', 'value' => ['open', 'click']];

    $true = $evaluator->evaluate([$condition], [], ['type' => 'open']);
    $false = $evaluator->evaluate([$condition], [], ['type' => 'bounce']);

    expect($true)->toBeTrue()->and($false)->toBeFalse();
});

it('evaluates less than or equal operator', function () {
    $evaluator = new ConditionsEvaluator();
    $condition = ['path' => 'contact.score', 'op' => '<=', 'value' => 10];

    $true = $evaluator->evaluate([$condition], ['score' => 5], []);
    $false = $evaluator->evaluate([$condition], ['score' => 15], []);

    expect($true)->toBeTrue()->and($false)->toBeFalse();
});

it('evaluates greater than or equal operator', function () {
    $evaluator = new ConditionsEvaluator();
    $condition = ['path' => 'contact.score', 'op' => '>=', 'value' => 10];

    $true = $evaluator->evaluate([$condition], ['score' => 10], []);
    $false = $evaluator->evaluate([$condition], ['score' => 5], []);

    expect($true)->toBeTrue()->and($false)->toBeFalse();
});

it('evaluates less than operator', function () {
    $evaluator = new ConditionsEvaluator();
    $condition = ['path' => 'contact.score', 'op' => '<', 'value' => 10];

    $true = $evaluator->evaluate([$condition], ['score' => 5], []);
    $false = $evaluator->evaluate([$condition], ['score' => 10], []);

    expect($true)->toBeTrue()->and($false)->toBeFalse();
});

it('evaluates greater than operator', function () {
    $evaluator = new ConditionsEvaluator();
    $condition = ['path' => 'contact.score', 'op' => '>', 'value' => 5];

    $true = $evaluator->evaluate([$condition], ['score' => 10], []);
    $false = $evaluator->evaluate([$condition], ['score' => 5], []);

    expect($true)->toBeTrue()->and($false)->toBeFalse();
});

it('evaluates exists operator', function () {
    $evaluator = new ConditionsEvaluator();
    $condition = ['path' => 'contact.email', 'op' => 'exists'];

    $true = $evaluator->evaluate([$condition], ['email' => 'a@example.com'], []);
    $false = $evaluator->evaluate([$condition], [], []);

    expect($true)->toBeTrue()->and($false)->toBeFalse();
});

it('evaluates contains operator', function () {
    $evaluator = new ConditionsEvaluator();
    $condition = ['path' => 'contact.tags', 'op' => 'contains', 'value' => 'news'];

    $true = $evaluator->evaluate([$condition], ['tags' => ['news', 'sale']], []);
    $false = $evaluator->evaluate([$condition], ['tags' => ['sale']], []);

    expect($true)->toBeTrue()->and($false)->toBeFalse();
});
