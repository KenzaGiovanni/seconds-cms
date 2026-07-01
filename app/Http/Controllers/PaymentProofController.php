<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\Payment;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * Serves a manual payment's proof-of-payment file from the private disk.
 * Admin-only (verification queue "View proof" link) - the file was never
 * meant to be reachable without the orders.manage capability.
 */
class PaymentProofController extends Controller
{
    public function show(Payment $payment): Response
    {
        abort_unless(auth()->user()?->can(Permission::OrdersManage->value), 403);
        abort_if(! $payment->proof_path, 404);

        return Storage::disk('local')->response($payment->proof_path);
    }
}
