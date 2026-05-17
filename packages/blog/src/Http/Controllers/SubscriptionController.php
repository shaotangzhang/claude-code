<?php

declare(strict_types=1);

namespace Acme\Blog\Http\Controllers;

use Acme\Blog\Events\SubscriberConfirmed;
use Acme\Blog\Models\Subscription;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

final class SubscriptionController extends Controller
{
    public function subscribe(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);
        $email  = strtolower((string) $request->input('email'));
        $locale = app()->getLocale();

        Subscription::query()->updateOrCreate(
            ['email' => $email, 'locale' => $locale],
            ['token' => Str::random(80), 'confirmed_at' => null, 'unsubscribed_at' => null],
        );

        // Hook for downstream listeners to actually send the confirmation email.
        // We intentionally do not enqueue mail in M4 — host project decides delivery.

        return back()->with('status', 'Check your inbox to confirm.');
    }

    public function confirm(Request $request, Dispatcher $events, string $token): RedirectResponse
    {
        $sub = Subscription::query()->where('token', $token)->firstOrFail();

        if (! $sub->confirmed_at) {
            $sub->confirmed_at = now();
            $sub->save();
            $events->dispatch(new SubscriberConfirmed($sub->id, $sub->email, $sub->locale));
        }

        return redirect(config('acme.blog.route_prefix', 'blog'))->with('status', 'Subscription confirmed.');
    }

    public function unsubscribe(string $token): RedirectResponse
    {
        $sub = Subscription::query()->where('token', $token)->firstOrFail();
        $sub->unsubscribed_at = now();
        $sub->save();

        return redirect(config('acme.blog.route_prefix', 'blog'))->with('status', 'Unsubscribed.');
    }
}
