<?php

declare(strict_types=1);

namespace Acme\Payments\Http\Controllers;

use Acme\Payments\Models\Transaction;
use Acme\Payments\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

/**
 * Admin endpoints to push a Manual-gateway transaction to succeeded /
 * failed. Real gateways use webhooks; manual needs a human button.
 */
final class ManualController extends Controller
{
    public function confirm(PaymentService $svc, Transaction $transaction): RedirectResponse
    {
        $this->authorize('payments.manual.confirm');
        abort_unless($transaction->gateway === 'manual', 422, 'Not a manual transaction');

        $svc->markSucceeded($transaction);

        return back()->with('status', 'Transaction marked as paid.');
    }

    public function reject(PaymentService $svc, Transaction $transaction): RedirectResponse
    {
        $this->authorize('payments.manual.confirm');
        abort_unless($transaction->gateway === 'manual', 422, 'Not a manual transaction');

        $svc->markFailed($transaction, 'Manually rejected.');

        return back()->with('status', 'Transaction marked as failed.');
    }
}
