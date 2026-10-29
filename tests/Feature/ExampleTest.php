<?php

test('the root responds with the api name and status', function () {
    $this->get('/')
        ->assertOk()
        ->assertExactJson(['name' => config('app.name'), 'status' => 'ok']);
});

test('the root does not disclose the framework or its version', function () {
    expect($this->get('/')->getContent())
        ->not->toContain('Laravel')
        ->not->toContain(app()->version());
})->skip(fn () => config('app.name') === 'Laravel', 'APP_NAME is still the default "Laravel"');
