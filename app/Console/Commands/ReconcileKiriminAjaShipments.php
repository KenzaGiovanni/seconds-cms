<?php

namespace App\Console\Commands;

use App\Delivery\KiriminAjaProvider;
use App\Delivery\ShipmentService;
use App\Enums\ShipmentStatus;
use App\Enums\ShippingProvider;
use App\Models\Shipment;
use Illuminate\Console\Command;

/**
 * Safety net for missed KiriminAja tracking webhooks: re-queries stale, still-
 * in-flight KiriminAja shipments and applies the latest tracking update through
 * the same idempotent ShipmentService::applyTrackingEvent() path a webhook uses.
 */
class ReconcileKiriminAjaShipments extends Command
{
    protected $signature = 'delivery:reconcile';

    protected $description = 'Re-check stale in-flight KiriminAja shipments against KiriminAja and apply the latest status';

    public function handle(KiriminAjaProvider $provider, ShipmentService $shipments): int
    {
        $stale = Shipment::query()
            ->where('provider', ShippingProvider::Kiriminaja->value)
            ->whereIn('status', [
                ShipmentStatus::Booked->value,
                ShipmentStatus::PickedUp->value,
                ShipmentStatus::InTransit->value,
            ])
            ->whereNotNull('external_id')
            ->where('updated_at', '<', now()->subMinutes(5))
            ->get();

        $count = 0;

        foreach ($stale as $shipment) {
            try {
                $updates = $provider->track($shipment);
                $latest = collect($updates)->last();

                if ($latest) {
                    $shipments->advanceManual($shipment, $latest->status);
                    $count++;
                }
            } catch (\Throwable $e) {
                $this->warn("Could not reconcile shipment {$shipment->id}: {$e->getMessage()}");
            }
        }

        $this->info("Reconciled {$count} stale KiriminAja shipment(s).");

        return self::SUCCESS;
    }
}
