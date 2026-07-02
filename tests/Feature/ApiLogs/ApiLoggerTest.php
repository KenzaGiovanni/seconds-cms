<?php

use App\Models\ApiLog;
use App\Models\Order;
use App\Support\ApiLogger;
use Illuminate\Support\Facades\Http;
use KiriminAja\Responses\ServiceResponse;

it('logs a successful outbound http call', function () {
    Http::fake(['*/example' => Http::response(['ok' => true], 200)]);

    $response = ApiLogger::http('xendit', 'POST', 'https://api.xendit.co/example', ['amount' => 1000], function () {
        return Http::post('https://api.xendit.co/example', ['amount' => 1000]);
    });

    expect($response->successful())->toBeTrue();
    expect(ApiLog::count())->toBe(1);

    $log = ApiLog::first();
    expect($log->provider)->toBe('xendit');
    expect($log->direction)->toBe('outbound');
    expect($log->method)->toBe('POST');
    expect($log->request_payload)->toBe(['amount' => 1000]);
    expect($log->response_payload)->toBe(['ok' => true]);
    expect($log->status_code)->toBe(200);
    expect($log->successful)->toBeTrue();
    expect($log->duration_ms)->toBeGreaterThanOrEqual(0);
});

it('logs a failed outbound http call without swallowing the response', function () {
    Http::fake(['*/example' => Http::response(['message' => 'bad request'], 400)]);

    $response = ApiLogger::http('xendit', 'POST', 'https://api.xendit.co/example', [], fn () => Http::post('https://api.xendit.co/example'));

    expect($response->failed())->toBeTrue();
    $log = ApiLog::first();
    expect($log->successful)->toBeFalse();
    expect($log->status_code)->toBe(400);
});

it('logs an exception from an outbound call and rethrows it', function () {
    ApiLogger::http('xendit', 'POST', 'https://api.xendit.co/example', ['x' => 1], function () {
        throw new RuntimeException('connection timed out');
    });
})->throws(RuntimeException::class, 'connection timed out');

it('records the error message and marks the log unsuccessful when a call throws', function () {
    try {
        ApiLogger::http('xendit', 'POST', 'https://api.xendit.co/example', [], function () {
            throw new RuntimeException('connection timed out');
        });
    } catch (RuntimeException $e) {
        // expected
    }

    $log = ApiLog::first();
    expect($log->successful)->toBeFalse();
    expect($log->error_message)->toBe('connection timed out');
});

it('logs a kiriminaja-shaped call (ServiceResponse)', function () {
    $fakeServiceResponse = new ServiceResponse(true, 'ok', ['balance' => 5000]);

    ApiLogger::kiriminaja('shipping_price', ['origin' => 5], fn () => $fakeServiceResponse);

    $log = ApiLog::first();
    expect($log->provider)->toBe('kiriminaja');
    expect($log->endpoint)->toBe('shipping_price');
    expect($log->response_payload)->toBe(['balance' => 5000]);
    expect($log->successful)->toBeTrue();
});

it('logs a failed kiriminaja-shaped call using its message', function () {
    $fakeServiceResponse = new ServiceResponse(false, 'invalid key', null);

    ApiLogger::kiriminaja('shipping_price', ['origin' => 5], fn () => $fakeServiceResponse);

    $log = ApiLog::first();
    expect($log->successful)->toBeFalse();
    expect($log->response_payload)->toBe(['message' => 'invalid key']);
});

it('logs an inbound webhook receipt', function () {
    ApiLogger::inbound('xendit', 'webhooks/xendit', ['id' => 'inv_1', 'status' => 'PAID'], true);

    $log = ApiLog::first();
    expect($log->direction)->toBe('inbound');
    expect($log->provider)->toBe('xendit');
    expect($log->request_payload)->toBe(['id' => 'inv_1', 'status' => 'PAID']);
    expect($log->successful)->toBeTrue();
});

it('logs a rejected inbound webhook with the reason', function () {
    ApiLogger::inbound('xendit', 'webhooks/xendit', ['id' => 'inv_1'], false, 'bad token');

    $log = ApiLog::first();
    expect($log->successful)->toBeFalse();
    expect($log->error_message)->toBe('bad token');
});

it('links a log to a loggable model when given one', function () {
    $order = Order::create([
        'status' => 'pending', 'email' => 'a@example.com', 'customer_name' => 'A',
        'currency' => 'IDR', 'subtotal' => 1000, 'total' => 1000,
    ]);

    ApiLogger::inbound('xendit', 'webhooks/xendit', [], true, loggable: $order);

    $log = ApiLog::first();
    expect($log->loggable_type)->toBe(Order::class);
    expect($log->loggable_id)->toBe($order->id);
    expect($log->loggable->is($order))->toBeTrue();
});
