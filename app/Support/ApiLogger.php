<?php

namespace App\Support;

use App\Models\ApiLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use KiriminAja\Responses\ServiceResponse;

/**
 * Records every outbound call to Xendit/KiriminAja and every inbound webhook
 * from them into `api_logs`, for debugging (Kenza, 07-02: "I need to see
 * every log on that API" - not just failures, everything). Never throws on
 * its own account - a logging failure must never break the actual API call.
 *
 * Three entry points, one per call shape in this codebase:
 *  - http(): wraps a Laravel Http-facade call (Xendit) - returns the Response
 *    untouched so the caller's existing ->failed()/->json() code is unchanged.
 *  - kiriminaja(): wraps a KiriminAja SDK call (returns ServiceResponse) -
 *    returns it untouched for the same reason.
 *  - inbound(): records a webhook receipt (no outbound call to wrap).
 */
class ApiLogger
{
    public static function http(string $provider, string $method, string $endpoint, ?array $request, \Closure $call, ?Model $loggable = null): Response
    {
        $start = microtime(true);

        try {
            $response = $call();

            self::record([
                'provider' => $provider, 'direction' => 'outbound', 'method' => $method, 'endpoint' => $endpoint,
                'request_payload' => $request, 'response_payload' => self::safeJson($response),
                'status_code' => $response->status(), 'successful' => $response->successful(),
                'duration_ms' => self::ms($start), 'loggable' => $loggable,
            ]);

            return $response;
        } catch (\Throwable $e) {
            self::record([
                'provider' => $provider, 'direction' => 'outbound', 'method' => $method, 'endpoint' => $endpoint,
                'request_payload' => $request, 'successful' => false,
                'duration_ms' => self::ms($start), 'error_message' => $e->getMessage(), 'loggable' => $loggable,
            ]);

            throw $e;
        }
    }

    public static function kiriminaja(string $endpoint, array $request, \Closure $call, ?Model $loggable = null): ServiceResponse
    {
        $start = microtime(true);

        try {
            $response = $call();

            self::record([
                'provider' => 'kiriminaja', 'direction' => 'outbound', 'method' => 'POST', 'endpoint' => $endpoint,
                'request_payload' => $request,
                'response_payload' => $response->status ? $response->data : ['message' => $response->message],
                'successful' => $response->status, 'duration_ms' => self::ms($start), 'loggable' => $loggable,
            ]);

            return $response;
        } catch (\Throwable $e) {
            self::record([
                'provider' => 'kiriminaja', 'direction' => 'outbound', 'method' => 'POST', 'endpoint' => $endpoint,
                'request_payload' => $request, 'successful' => false,
                'duration_ms' => self::ms($start), 'error_message' => $e->getMessage(), 'loggable' => $loggable,
            ]);

            throw $e;
        }
    }

    /** @param  mixed  $request  Raw webhook payload (array or Request-derived). */
    public static function inbound(string $provider, string $endpoint, mixed $request, bool $successful, ?string $errorMessage = null, ?Model $loggable = null): void
    {
        self::record([
            'provider' => $provider, 'direction' => 'inbound', 'method' => 'POST', 'endpoint' => $endpoint,
            'request_payload' => $request, 'successful' => $successful,
            'error_message' => $errorMessage, 'loggable' => $loggable,
        ]);
    }

    /** @param  array<string, mixed>  $attributes */
    private static function record(array $attributes): void
    {
        $loggable = $attributes['loggable'] ?? null;
        unset($attributes['loggable']);

        try {
            ApiLog::create([
                ...$attributes,
                'loggable_type' => $loggable ? $loggable::class : null,
                'loggable_id' => $loggable?->getKey(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // A logging failure must never break the actual API call it's wrapping.
            report($e);
        }
    }

    private static function ms(float $start): int
    {
        return (int) ((microtime(true) - $start) * 1000);
    }

    private static function safeJson(Response $response): mixed
    {
        try {
            return $response->json();
        } catch (\Throwable) {
            return $response->body();
        }
    }
}
