<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\RazorpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_PENDING])
            ->latest('id')
            ->first();

        return view('app.billing.plans', compact('plans', 'subscription'));
    }

    public function startCheckout(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan_id' => 'required|integer|exists:plans,id',
        ]);

        $plan = Plan::query()
            ->whereKey((int) $data['plan_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $subscription = $this->razorpayService->createSubscriptionForUser($request->user(), $plan);

        return response()->json([
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
        ]);
    }

    public function webhook(Request $request): JsonResponse
    {
        $signature = (string) $request->header('X-Razorpay-Signature', '');
        $rawPayload = (string) $request->getContent();

        try {
            $this->razorpayService->verifyWebhookSignature($rawPayload, $signature);
            $event = $request->json()->all();
            $this->razorpayService->handleWebhook($event);

            return response()->json(['status' => 'ok']);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
