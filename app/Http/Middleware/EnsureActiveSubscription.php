<?php

namespace App\Http\Middleware;

use App\Services\PlanEnforcementService;
use Closure;
use Illuminate\Http\Request;

class EnsureActiveSubscription
{
    public function __construct(private readonly PlanEnforcementService $planEnforcementService)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->isAdmin() && !$this->planEnforcementService->getActiveSubscription($user)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Active subscription required to access posting features.',
                ], 402);
            }

            return redirect()->route('app.billing.plans')
                ->with('error', 'Active subscription required to access posting features.');
        }

        return $next($request);
    }
}
