<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsappMessage;
use App\Models\WhatsappTemplate;
use App\Models\WhatsappConversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class WhatsappDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Scope for queries to only fetch user's data
        $accountScope = function ($query) use ($user) {
            $query->whereHas('conversation.phoneNumber.account', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        };

        // Total Messages Sent
        $totalMessagesSent = WhatsappMessage::whereHas('conversation.phoneNumber.account', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('direction', 'outbound')
            ->count();

        // Messages Delivered
        $messagesDelivered = WhatsappMessage::whereHas('conversation.phoneNumber.account', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('direction', 'outbound')
            ->whereIn('status', ['delivered', 'read'])
            ->count();
            
        $deliveryRate = $totalMessagesSent > 0 ? round(($messagesDelivered / $totalMessagesSent) * 100, 1) : 0;

        // Active Templates
        $activeTemplates = WhatsappTemplate::whereHas('account', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('status', 'approved')
            ->count();

        // Active Conversations (In 24hr service window - requires inbound message in last 24h)
        $activeConversations = WhatsappConversation::whereHas('phoneNumber.account', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->whereHas('messages', function ($q) {
                $q->where('direction', 'inbound')
                  ->where('created_at', '>=', now()->subHours(24));
            })
            ->count();

        // Recent Messages
        $recentMessages = WhatsappMessage::whereHas('conversation.phoneNumber.account', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->with(['conversation.contact', 'template'])
            ->where('direction', 'outbound')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        // Chart Data - Last 7 days Message Volume
        $today = now()->startOfDay();
        $last7Days = collect(CarbonPeriod::create($today->copy()->subDays(6), $today))
            ->map(fn (Carbon $date) => $date->format('D'))
            ->values();

        $sentData = [];
        $deliveredData = [];

        foreach (CarbonPeriod::create($today->copy()->subDays(6), $today) as $date) {
            $dateString = $date->toDateString();
            
            $sent = WhatsappMessage::whereHas('conversation.phoneNumber.account', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('direction', 'outbound')
            ->whereDate('created_at', $dateString)
            ->count();
            
            $delivered = WhatsappMessage::whereHas('conversation.phoneNumber.account', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('direction', 'outbound')
            ->whereIn('status', ['delivered', 'read'])
            ->whereDate('created_at', $dateString)
            ->count();
            
            $sentData[] = $sent;
            $deliveredData[] = $delivered;
        }

        // Chart Data - Delivery Status Today
        $messagesToday = WhatsappMessage::whereHas('conversation.phoneNumber.account', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('direction', 'outbound')
            ->whereDate('created_at', now()->toDateString())
            ->get();

        $deliveryStatusData = [
            'delivered' => $messagesToday->where('status', 'delivered')->count(),
            'read' => $messagesToday->where('status', 'read')->count(),
            'failed' => $messagesToday->where('status', 'failed')->count(),
            'pending' => $messagesToday->whereIn('status', ['pending', 'sent'])->count(),
        ];

        return view('admin.whatsapp.dashboard', compact(
            'totalMessagesSent',
            'messagesDelivered',
            'deliveryRate',
            'activeTemplates',
            'activeConversations',
            'recentMessages',
            'last7Days',
            'sentData',
            'deliveredData',
            'deliveryStatusData'
        ));
    }
}
