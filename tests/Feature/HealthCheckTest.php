<?php

it('serves the health check endpoint', function () {
    $this->getJson('/health')
        ->assertOk()
        ->assertJson([
            'app' => 'Seconds',
            'status' => 'ok',
        ])
        ->assertJsonStructure(['app', 'status', 'time']);
});

it('serves the welcome page', function () {
    $this->get('/')->assertOk();
});
