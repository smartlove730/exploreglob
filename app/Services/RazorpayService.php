<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;
use RuntimeException;

class RazorpayService
{
    private Api $api;

    public function __construct()
    {
        $keyId = (string) config('services.razorpay.key_id');
        $keySecret = (string) config('services.razorpay.key_secret');

        if ($keyId === '' || $keySecret === '') {
            throw new RuntimeException('Razorpay credentials are not configured.');
        }

        $this->api = new Api($keyId, $keySecret);
    }

    public function createOrMapRemotePlan(Plan $plan): string
    {
        if (!empty($plan->razorpay_plan_id)) {
            return (string) $plan->razorpay_plan_id;
        }

        $period = $this->mapIntervalToPeriod((string) $plan->interval);

        $remotePlan = $this->api->plan->create([
            'period' => $period,
            'interval' => 1,
            'item' => [
                'name' => $plan->name,
                'description' => 'SaaS '.$plan->name.' plan',
                'amount' => (int) round(((float) $plan->price) * 100),
                'currency' => strtoupper((string) $plan->currency),
            ],
            'notes' => [
                'local_plan_id' => (string) $plan->id,
                'local_plan_slug' => (string) $plan->slug,
            ],
        ]);

        $plan->update(['razorpay_plan_id' => (string) $remotePlan['id']]);

        return (string) $remotePlan['id'];
    }

    public function createSubscriptionForUser(User $user, Plan $plan): Subscription
    {
        $remotePlanId = $this->createOrMapRemotePlan($plan);

        $payload = [
            'plan_id' => $remotePlanId,
            'customer_notify' => 1,
            'total_count' => 120,
            'notes' => [
                'local_user_id' => (string) $user->id,
                'local_plan_id' => (string) $plan->id,
            ],
        ];

        $remoteSubscription = $this->api->subscription->create($payload);

        return Subscription::updateOrCreate(
            ['razorpay_subscription_id' => (string) $remoteSubscription['id']],
            [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => (string) Arr::get($remoteSubscription->toArray(), 'status', Subscription::STATUS_PENDING),
                'current_period_start' => $this->timestampToDateTime(Arr::get($remoteSubscription->toArray(), 'current_start')),
                'current_period_end' => $this->timestampToDateTime(Arr::get($remoteSubscription->toArray(), 'current_end')),
                'posts_used' => 0,
            ]
        );
    }

    public function verifyWebhookSignature(string $payload, string $signature): void
    {
        $secret = (string) config('services.razorpay.webhook_secret');

        if ($secret === '') {
            throw new RuntimeException('Razorpay webhook secret is not configured.');
        }

        try {
            $this->api->utility->verifyWebhookSignature($payload, $signature, $secret);
        } catch (SignatureVerificationError $exception) {
            throw new RuntimeException('Invalid Razorpay webhook signature.', previous: $exception);
        }
    }

    public function handleWebhook(array $event): ?Subscription
    {
        $subscriptionPayload = Arr::get($event, 'payload.subscription.entity');

        if (!is_array($subscriptionPayload) || empty($subscriptionPayload['id'])) {
            return null;
        }

        $razorpaySubscriptionId = (string) $subscriptionPayload['id'];

        return DB::transaction(function () use ($subscriptionPayload, $razorpaySubscriptionId) {
            $subscription = Subscription::query()
                ->where('razorpay_subscription_id', $razorpaySubscriptionId)
                ->first();

            if (!$subscription) {
                Log::warning('Razorpay webhook received for unknown subscription id', [
                    'razorpay_subscription_id' => $razorpaySubscriptionId,
                ]);

                return null;
            }

            $subscription->update([
                'status' => (string) Arr::get($subscriptionPayload, 'status', $subscription->status),
                'current_period_start' => $this->timestampToDateTime(Arr::get($subscriptionPayload, 'current_start')),
                'current_period_end' => $this->timestampToDateTime(Arr::get($subscriptionPayload, 'current_end')),
            ]);

            return $subscription->fresh();
        });
    }

    private function mapIntervalToPeriod(string $interval): string
    {
        return match (strtolower($interval)) {
            'yearly', 'annual', 'annually' => 'yearly',
            'weekly' => 'weekly',
            'daily' => 'daily',
            default => 'monthly',
        };
    }

    private function timestampToDateTime(mixed $timestamp): ?string
    {
        if (!$timestamp) {
            return null;
        }

        return now()->setTimestamp((int) $timestamp)->toDateTimeString();
    }
}
