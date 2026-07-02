<?php

namespace App\Http\Controllers;

use App\Payments\PaymentService;
use App\Payments\XenditGateway;
use App\Support\ApiLogger;
use App\Support\Feature;
use App\Support\PaymentSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Public Xendit invoice webhook. Verified via the x-callback-token header
 * (Xendit does not sign the body itself) before any DB write; the actual
 * apply is idempotent + monotonic through PaymentService::applyEvent (3.0).
 */
class XenditWebhookController extends Controller
{
    public function handle(Request $request, XenditGateway $gateway, PaymentService $payments): Response
    {
        abort_unless(Feature::ecommerce(), 404);

        $token = $request->header('x-callback-token', '');
        $expected = PaymentSettings::xenditKeys()['webhook_token'];

        if ($expected === '' || ! hash_equals($expected, (string) $token)) {
            ApiLogger::inbound('xendit', 'webhooks/xendit', $request->all(), false, 'invalid or missing callback token');

            return response('Invalid callback token', 401);
        }

        $event = $gateway->handleWebhook($request);
        $payments->applyEvent($event);

        ApiLogger::inbound('xendit', 'webhooks/xendit', $request->all(), true);

        return response('OK', 200);
    }
}
