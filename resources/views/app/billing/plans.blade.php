<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Plans</title>
</head>
<body>
    <h1>Subscription Plans</h1>

    @if (session('success'))
        <p style="color: green;">{{ session('success') }}</p>
    @endif

    @if ($errors->has('billing'))
        <p style="color: red;">{{ $errors->first('billing') }}</p>
    @endif

    @if ($subscription)
        <p>Current subscription: {{ $subscription->status }} ({{ $subscription->razorpay_subscription_id }})</p>
    @endif

    <ul>
        @foreach ($plans as $plan)
            <li>
                <strong>{{ $plan->name }}</strong>
                - {{ $plan->currency }} {{ number_format((float) $plan->price, 2) }} / {{ $plan->interval }}
                - Post limit: {{ $plan->post_limit }}
                <form method="POST" action="{{ route('app.billing.subscribe') }}">
                    @csrf
                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                    <button type="submit">Start Subscription</button>
                </form>
            </li>
        @endforeach
    </ul>
</body>
</html>
