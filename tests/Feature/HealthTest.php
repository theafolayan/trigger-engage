<?php

use function Pest\Laravel\get;

it('returns ok', function () {
    get('/health')
        ->assertOk()
        ->assertExactJson(['ok' => true]);
});
