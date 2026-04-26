<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Choose a Plan - Postzy</title>
    <link rel="icon" type="image/png" href="{{asset('images/postzy-favicon.png')}}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        :root {
            --pz-indigo: #6366f1;
            --pz-indigo-dark: #4f46e5;
            --pz-indigo-light: #818cf8;
            --pz-coral: #ff6b6b;
            --pz-teal: #2dd4bf;
            --pz-slate-50: #f8fafc;
            --pz-slate-100: #f1f5f9;
            --pz-slate-200: #e2e8f0;
            --pz-slate-400: #94a3b8;
            --pz-slate-500: #64748b;
            --pz-slate-600: #475569;
            --pz-slate-800: #1e293b;
            --pz-slate-900: #0f172a;
            --pz-gradient-primary: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%);
            --pz-gradient-hero: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--pz-slate-50);
            color: var(--pz-slate-800);
            line-height: 1.7;
            -webkit-font-smoothing: antialiased;
        }

        ::selection {
            background: var(--pz-indigo);
            color: white;
        }

        /* Hero */
        .billing-hero {
            background: var(--pz-gradient-hero);
            padding: 3rem 0 2.5rem;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .billing-hero::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background:
                radial-gradient(circle at 20% 50%, rgba(99,102,241,0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(168,85,247,0.2) 0%, transparent 50%);
        }

        .billing-hero * { position: relative; z-index: 1; }

        .billing-hero h1 {
            font-size: 2.25rem;
            font-weight: 900;
            letter-spacing: -0.03em;
            margin-bottom: 0.5rem;
        }

        .billing-hero p {
            font-size: 1rem;
            opacity: 0.9;
            font-weight: 300;
        }

        /* Plan Cards */
        .plan-card {
            border: 2px solid transparent;
            border-radius: 20px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.06);
            transition: all 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
            position: relative;
            overflow: hidden;
            background: white;
        }

        .plan-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: var(--pz-slate-200);
            transition: background 0.3s ease;
        }

        .plan-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.1);
        }

        .plan-card:hover::before {
            background: var(--pz-gradient-primary);
        }

        /* Active Plan Highlight */
        .plan-card.plan-active {
            border-color: var(--pz-indigo);
            box-shadow: 0 0 0 2px rgba(99,102,241,0.15), 0 12px 40px rgba(99,102,241,0.12);
        }

        .plan-card.plan-active::before {
            background: var(--pz-gradient-primary);
            height: 4px;
        }

        .plan-badge {
            position: absolute;
            top: 16px;
            right: 16px;
            background: var(--pz-gradient-primary);
            color: white;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.3rem 0.75rem;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            box-shadow: 0 2px 8px rgba(99,102,241,0.3);
        }

        .plan-name {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--pz-slate-900);
            margin-bottom: 0.25rem;
        }

        .plan-price {
            font-size: 2.75rem;
            font-weight: 900;
            color: var(--pz-indigo);
            line-height: 1;
            margin-bottom: 0.15rem;
            letter-spacing: -0.03em;
        }

        .plan-price .currency {
            font-size: 1.25rem;
            font-weight: 600;
            vertical-align: super;
        }

        .plan-interval {
            color: var(--pz-slate-500);
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
        }

        .plan-features {
            list-style: none;
            padding: 0;
            margin-bottom: 2rem;
        }

        .plan-features li {
            padding: 0.45rem 0;
            font-size: 0.9rem;
            color: var(--pz-slate-600);
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .plan-features li .icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            flex-shrink: 0;
        }

        .plan-features li .icon.yes {
            background: #ecfdf5;
            color: #059669;
        }

        .plan-features li .icon.no {
            background: #fef2f2;
            color: #dc2626;
        }

        .btn-subscribe {
            background: var(--pz-gradient-primary);
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 0.95rem;
            color: white;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25,0.46,0.45,0.94);
            box-shadow: 0 4px 15px rgba(99,102,241,0.25);
        }

        .btn-subscribe:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99,102,241,0.35);
        }

        .btn-subscribe:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-current {
            background: var(--pz-slate-100);
            border: 2px solid var(--pz-indigo-light);
            color: var(--pz-indigo);
            padding: 0.75rem 2rem;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 0.95rem;
            width: 100%;
            cursor: default;
        }

        /* Subscription Info Bar */
        .sub-info {
            background: linear-gradient(135deg, rgba(99,102,241,0.05), rgba(168,85,247,0.05));
            border: 1px solid rgba(99,102,241,0.12);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
        }

        .sub-info-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .sub-info-dot.active { background: #22c55e; }
        .sub-info-dot.pending { background: #f59e0b; }
        .sub-info-dot.inactive { background: var(--pz-slate-400); }

        .back-link {
            color: white;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            opacity: 0.8;
            transition: opacity 0.2s;
        }

        .back-link:hover { opacity: 1; color: white; }

        @media (max-width: 768px) {
            .billing-hero h1 { font-size: 1.75rem; }
            .plan-price { font-size: 2.25rem; }
        }
    </style>
</head>
<body>

<section class="billing-hero">
    <div class="container">
        <a href="{{ route('app.dashboard') }}" class="back-link">← Back to Dashboard</a>
        <h1 class="mt-3">Choose Your Plan</h1>
        <p>Simple, transparent pricing. Upgrade anytime.</p>
    </div>
</section>

<div class="container py-5">

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->has('billing'))
        <div class="alert alert-danger">{{ $errors->first('billing') }}</div>
    @endif

    @if ($subscription)
        <div class="sub-info mb-4">
            <span class="sub-info-dot {{ $subscription->status === 'active' ? 'active' : ($subscription->status === 'pending' ? 'pending' : 'inactive') }}"></span>
            <div>
                <strong>Current subscription:</strong>
                {{ $subscription->plan?->name ?? 'Unknown' }} —
                <span class="text-capitalize">{{ $subscription->status }}</span>
                @if($subscription->current_period_end)
                    · Renews {{ $subscription->current_period_end->format('M d, Y') }}
                @endif
                @if($subscription->status === 'active' && $subscription->plan && (float) $subscription->plan->price > 0)
                    <form method="POST" action="{{ route('app.billing.cancel') }}" class="d-inline ms-2" onsubmit="return confirm('Are you sure you want to cancel?')">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:0.75rem;padding:0.2rem 0.6rem;border-radius:9999px">Cancel</button>
                    </form>
                @endif
            </div>
        </div>
    @endif

    <div class="row g-4 justify-content-center">
        @foreach ($plans as $plan)
            @php
                $isActive = isset($activePlanId) && $activePlanId == $plan->id;
                $isFree = (float) $plan->price <= 0;
            @endphp
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card plan-card h-100 {{ $isActive ? 'plan-active' : '' }}">
                    @if($isActive)
                        <span class="plan-badge">✓ Current Plan</span>
                    @endif
                    <div class="card-body d-flex flex-column p-4 pt-5">
                        <h2 class="plan-name">{{ $plan->name }}</h2>
                        <div class="plan-price">
                            @if($isFree)
                                Free
                            @else
                                <span class="currency">{{ $plan->currency }}</span>{{ number_format((float) $plan->price, 0) }}
                            @endif
                        </div>
                        <p class="plan-interval">
                            @if($isFree)
                                Forever free
                            @else
                                per {{ $plan->interval }}
                            @endif
                        </p>

                        <ul class="plan-features">
                            <li>
                                <span class="icon yes">✓</span>
                                <span><strong>{{ $plan->post_limit }}</strong> posts / {{ $plan->interval }}</span>
                            </li>
                            <li>
                                <span class="icon yes">✓</span>
                                <span><strong>{{ $plan->automation_limit ?? '∞' }}</strong> automation(s)</span>
                            </li>
                            <li>
                                <span class="icon {{ $plan->facebook_enabled ? 'yes' : 'no' }}">{{ $plan->facebook_enabled ? '✓' : '✕' }}</span>
                                <span>Facebook Publishing</span>
                            </li>
                            <li>
                                <span class="icon {{ $plan->instagram_enabled ? 'yes' : 'no' }}">{{ $plan->instagram_enabled ? '✓' : '✕' }}</span>
                                <span>Instagram Publishing</span>
                            </li>
                        </ul>

                        @if($isActive)
                            <button type="button" class="btn-current mt-auto" disabled>
                                ✓ Current Plan
                            </button>
                        @elseif($isFree)
                            {{-- Free plan is auto-activated on registration, no checkout needed --}}
                        @else
                            <button
                                type="button"
                                class="btn-subscribe mt-auto subscribe-btn"
                                data-plan-id="{{ $plan->id }}"
                                data-plan-name="{{ $plan->name }}"
                            >
                                Upgrade to {{ $plan->name }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
                name: 'Postzy',
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
                    color: '#6366f1'
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
