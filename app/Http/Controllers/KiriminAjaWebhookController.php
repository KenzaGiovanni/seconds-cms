<?php

namespace App\Http\Controllers;

use App\Delivery\KiriminAjaProvider;
use App\Delivery\ShipmentService;
use App\Support\ApiLogger;
use App\Support\Feature;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Public KiriminAja tracking webhook. Token verification + payload parsing
 * happens in KiriminAjaProvider::handleWebhook() (returns a 401-worthy
 * RuntimeException on failure); the actual apply is idempotent + monotonic
 * through ShipmentService::applyTrackingEvent (4.0).
 */
class KiriminAjaWebhookController extends Controller
{
    public function handle(Request $request, KiriminAjaProvider $provider, ShipmentService $shipments): Response
    {
        abort_unless(Feature::ecommerce(), 404);

        try {
            $event = $provider->handleWebhook($request);
        } catch (\RuntimeException $e) {
            ApiLogger::inbound('kiriminaja', 'webhooks/kiriminaja', $request->all(), false, $e->getMessage());

            return response($e->getMessage(), 401);
        }

        $shipments->applyTrackingEvent($event);

        ApiLogger::inbound('kiriminaja', 'webhooks/kiriminaja', $request->all(), true);

        return response('OK', 200);
    }
}
