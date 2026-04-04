<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\ActivityLogService;
use App\Services\RazorpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Throwable;

class BillingController extends Controller
{
    public function __construct(private readonly RazorpayService $razorpayService)
    {
    }

    public function index()
    {
        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        $subscription = Subscription::query()
            ->where('user_id', Auth::id())
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_AUTHENTICATED, Subscription::STATUS_PENDING])
            ->latest('id')
            ->first();

        return view('app.billing.plans', compact('plans', 'subscription'));
    }

    public function startCheckout(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'plan_id' => 'required|integer|exists:plans,id',
        ]);

        $plan = Plan::query()
            ->whereKey((int) $data['plan_id'])
            ->where('is_active', true)
            ->firstOrFail();

        try {
            $subscription = $this->razorpayService->createSubscriptionForUser($request->user(), $plan);
            app(ActivityLogService::class)->log('billing.checkout.started', $request->user(), [
                'plan_id' => $plan->id,
                'subscription_id' => $subscription->id,
            ]);

            $payload = [
                'success' => true,
                'data' => [
                    'key' => config('services.razorpay.key_id'),
                    'subscription_id' => $subscription->razorpay_subscription_id,
                    'plan' => [
                        'id' => $plan->id,
                        'name' => $plan->name,
                        'price' => $plan->price,
                        'currency' => $plan->currency,
                    ],
                ],
            ];

            if ($request->expectsJson()) {
                return response()->json($payload);
            }

            return redirect()
                ->route('app.billing.plans')
                ->with('success', 'Checkout started. Please complete payment in Razorpay.');
        } catch (Throwable $exception) {
            report($exception);
            $message = $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'Unable to start checkout right now. Please contact support.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return back()->withErrors([
                'billing' => $message,
            ]);
        }
    }

    public function webhook(Request $request): JsonResponse
    {
        $signature = (string) $request->header('X-Razorpay-Signature', '');
        $rawPayload = (string) $request->getContent();
        if ($signature === '') {
            logger()->warning('Razorpay webhook missing signature header.');
            return response()->json(['status' => 'error', 'message' => 'Invalid webhook request.'], 422);
        }

        try {
            $this->razorpayService->verifyWebhookSignature($rawPayload, $signature);
            $event = $request->json()->all();
            if (!is_array($event) || empty($event['event'])) {
                return response()->json(['status' => 'error', 'message' => 'Invalid webhook payload.'], 422);
            }
            $this->razorpayService->handleWebhook($event);
            app(ActivityLogService::class)->log('billing.webhook.processed', null, [
                'event' => (string) ($event['event'] ?? 'unknown'),
                'contains_subscription' => !empty(data_get($event, 'payload.subscription.entity.id')),
            ]);

            return response()->json(['status' => 'ok']);
        } catch (Throwable $exception) {
            report($exception);
            logger()->error('Razorpay webhook handling failed', [
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Webhook processing failed.',
            ], 422);
        }
    }

    public function cancel(Request $request): RedirectResponse
    {
        $subscription = Subscription::query()
            ->where('user_id', Auth::id())
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_AUTHENTICATED, Subscription::STATUS_PENDING])
            ->latest('id')
            ->first();

        if (!$subscription) {
            return back()->with('error', 'No active subscription found.');
        }

        try {
            $this->razorpayService->cancelSubscription($subscription);
            app(ActivityLogService::class)->log('billing.subscription.cancelled', $request->user(), [
                'subscription_id' => $subscription->id,
            ]);

            return back()->with('success', 'Subscription cancelled successfully.');
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Unable to cancel subscription right now. Please contact support.');
        }
    }
}
