<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Choose a Plan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        body { background: #f6f8fb; }
        .plan-card { border: 0; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08); border-radius: 16px; }
        .plan-price { font-size: 2rem; font-weight: 700; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Choose your subscription</h1>
            <p class="text-muted mb-0">Secure checkout via Razorpay. Subscription activates after successful payment webhook.</p>
        </div>
        <a href="{{ route('app.dashboard') }}" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->has('billing'))
        <div class="alert alert-danger">{{ $errors->first('billing') }}</div>
    @endif

    @if (session('success'))
        <p style="color: green;">{{ session('success') }}</p>
    @endif

    @if ($errors->has('billing'))
        <p style="color: red;">{{ $errors->first('billing') }}</p>
    @endif

    @if ($subscription)
        <div class="alert alert-info">
            Current subscription: <strong>{{ $subscription->status }}</strong>
            ({{ $subscription->razorpay_subscription_id }})
        </div>
    @endif

    <div class="row g-4">
        @foreach ($plans as $plan)
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card plan-card h-100">
                    <div class="card-body d-flex flex-column">
                        <h2 class="h4">{{ $plan->name }}</h2>
                        <p class="plan-price mb-1">{{ $plan->currency }} {{ number_format((float) $plan->price, 2) }}</p>
                        <p class="text-muted mb-3">per {{ $plan->interval }}</p>
                        <ul class="mb-4">
                            <li>Post limit: {{ $plan->post_limit }}</li>
                            <li>Facebook: {{ $plan->facebook_enabled ? 'Yes' : 'No' }}</li>
                            <li>Instagram: {{ $plan->instagram_enabled ? 'Yes' : 'No' }}</li>
                            <li>Google Business: {{ $plan->google_business_enabled ? 'Yes' : 'No' }}</li>
                        </ul>
                        <button
                            type="button"
                            class="btn btn-primary mt-auto subscribe-btn"
                            data-plan-id="{{ $plan->id }}"
                            data-plan-name="{{ $plan->name }}"
                        >
                            Buy {{ $plan->name }}
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<script>
    const csrfToken = @json(csrf_token());
    const subscribeUrl = @json(route('app.billing.subscribe'));
    const redirectUrl = @json(route('app.billing.plans'));

    async function startCheckout(planId, button) {
        button.disabled = true;
        const originalText = button.textContent;
        button.textContent = 'Starting checkout...';

        try {
            const response = await fetch(subscribeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: new URLSearchParams({ plan_id: String(planId) }),
            });

            const payload = await response.json();
            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Unable to start checkout.');
            }

            const options = {
                key: payload.data.key,
                subscription_id: payload.data.subscription_id,
                name: 'Explore Glob',
                description: payload.data.plan.name + ' plan',
                handler: function () {
                    window.location.href = redirectUrl;
                },
                modal: {
                    ondismiss: function () {
                        button.disabled = false;
                        button.textContent = originalText;
                    }
                },
                theme: {
                    color: '#0d6efd'
                }
            };

            const rzp = new Razorpay(options);
            rzp.on('payment.failed', function (event) {
                alert(event.error.description || 'Payment failed. Please try again.');
            });
            rzp.open();
        } catch (error) {
            alert(error.message || 'Unable to start checkout.');
            button.disabled = false;
            button.textContent = originalText;
        }
    }

    document.querySelectorAll('.subscribe-btn').forEach((button) => {
        button.addEventListener('click', () => startCheckout(button.dataset.planId, button));
    });
</script>
</body>
</html>
