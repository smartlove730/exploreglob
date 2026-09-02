<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WhatsappAccount;
use App\Models\WhatsappTemplate;
use App\Models\WhatsappContact;
use App\Models\WhatsappGroup;
use App\Models\WhatsappCampaign;
use Carbon\Carbon;

class WhatsappCampaignController extends Controller
{
    public function index()
    {
        $campaigns = WhatsappCampaign::where('user_id', auth()->id())
            ->with('template')
            ->withCount([
                'messages as total_messages',
                'messages as sent_count' => function ($query) {
                    $query->where('status', 'sent');
                },
                'messages as delivered_count' => function ($query) {
                    $query->where('status', 'delivered');
                },
                'messages as read_count' => function ($query) {
                    $query->where('status', 'read');
                },
                'messages as failed_count' => function ($query) {
                    $query->where('status', 'failed');
                },
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.whatsapp.campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        $account = WhatsappAccount::where('user_id', auth()->id())->first();
        if (!$account) {
            return redirect()->route('admin.whatsapp.settings')->with('error', 'Please connect WhatsApp first.');
        }

        // Active templates (where status is APPROVED)
        // Meta returns APPROVED, but wait, let's load all to be safe, or just APPROVED.
        // Actually, some templates might not have status synced correctly.
        $templates = WhatsappTemplate::where('whatsapp_account_id', $account->id)
            ->whereNotIn('status', ['REJECTED', 'rejected', 'FAILED', 'failed'])
            ->get();
        
        $contacts = WhatsappContact::where('user_id', auth()->id())->get();
        $groups = WhatsappGroup::where('user_id', auth()->id())->get();
        
        // Fetch previous campaigns variables grouped by template
        $previousVariables = [];
        $campaigns = WhatsappCampaign::where('user_id', auth()->id())->whereNotNull('variables')->orderBy('created_at', 'desc')->get();
        foreach($campaigns as $camp) {
            if (!isset($previousVariables[$camp->whatsapp_template_id])) {
                $previousVariables[$camp->whatsapp_template_id] = $camp->variables;
            }
        }

        return view('admin.whatsapp.campaigns.create', compact('account', 'templates', 'contacts', 'groups', 'previousVariables'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'template_id' => 'required|exists:whatsapp_templates,id',
            'contact_ids' => 'nullable|array',
            'group_ids' => 'nullable|array',
            'schedule_at' => 'nullable|date_format:Y-m-d\TH:i',
            'header_variables' => 'nullable|array',
            'body_variables' => 'nullable|array',
        ]);

        if (empty($request->contact_ids) && empty($request->group_ids)) {
            return response()->json(['success' => false, 'error' => 'Please select at least one contact or group.'], 422);
        }

        $account = WhatsappAccount::where('user_id', auth()->id())->firstOrFail();
        $template = WhatsappTemplate::findOrFail($request->template_id);
        
        if ($template->whatsapp_account_id !== $account->id) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $delay = null;
        if (!empty($request->schedule_at)) {
            $delay = Carbon::parse($request->schedule_at);
        }

        // Gather all unique contacts
        $contactIdsToMessage = [];
        if (!empty($request->contact_ids)) {
            if (in_array('all', $request->contact_ids)) {
                $allContacts = WhatsappContact::where('user_id', auth()->id())->pluck('id');
                foreach ($allContacts as $cid) {
                    $contactIdsToMessage[$cid] = true;
                }
            } else {
                foreach ($request->contact_ids as $cid) {
                    $contactIdsToMessage[$cid] = true;
                }
            }
        }
        if (!empty($request->group_ids)) {
            foreach ($request->group_ids as $gid) {
                $group = WhatsappGroup::find($gid);
                if ($group && $group->user_id == auth()->id()) {
                    foreach ($group->contacts as $contact) {
                        $contactIdsToMessage[$contact->id] = true;
                    }
                }
            }
        }
        $contactIdsToMessage = array_keys($contactIdsToMessage);
        
        if (empty($contactIdsToMessage)) {
            return response()->json(['success' => false, 'error' => 'No valid contacts found.'], 422);
        }

        // Build variables for DB
        $variables = [
            'header' => $request->header_variables ?? [],
            'body' => $request->body_variables ?? [],
        ];

        // Build Meta API components
        $components = [];
        if (!empty($variables['header'])) {
            $parameters = [];
            foreach ($variables['header'] as $val) {
                $parameters[] = ['type' => 'text', 'text' => $val];
            }
            if (!empty($parameters)) {
                $components[] = [
                    'type' => 'header',
                    'parameters' => $parameters
                ];
            }
        }
        if (!empty($variables['body'])) {
            $parameters = [];
            foreach ($variables['body'] as $val) {
                $parameters[] = ['type' => 'text', 'text' => $val];
            }
            if (!empty($parameters)) {
                $components[] = [
                    'type' => 'body',
                    'parameters' => $parameters
                ];
            }
        }

        // Generate Campaign
        $campaignIdStr = date('YmdHis');
        $campaign = WhatsappCampaign::create([
            'user_id' => auth()->id(),
            'whatsapp_account_id' => $account->id,
            'campaign_id' => $campaignIdStr,
            'whatsapp_template_id' => $template->id,
            'status' => $delay ? 'scheduled' : 'processing',
            'scheduled_at' => $delay,
            'variables' => $variables,
        ]);

        $queuedCount = 0;
        foreach ($contactIdsToMessage as $contactId) {
            if ($delay) {
                \App\Jobs\SendWhatsappTemplateJob::dispatch($account->id, $template->id, $contactId, $components, $campaign->id)->delay($delay);
            } else {
                \App\Jobs\SendWhatsappTemplateJob::dispatch($account->id, $template->id, $contactId, $components, $campaign->id);
            }
            $queuedCount++;
        }

        return response()->json([
            'success' => true,
            'message' => ($delay ? 'Campaign scheduled for ' . $queuedCount . ' contacts.' : 'Campaign started for ' . $queuedCount . ' contacts.'),
            'campaign_id' => $campaignIdStr,
        ]);
    }

    public function export(WhatsappCampaign $campaign)
    {
        if ($campaign->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $fileName = 'campaign_export_' . $campaign->campaign_id . '.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        // Optimized way using generators to stream the response
        $callback = function() use ($campaign) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Contact Name', 'Phone Number', 'Message Status', 'Error Message', 'Sent At']);

            $campaign->messages()->with('conversation.contact')->chunkById(500, function($messages) use ($file) {
                foreach ($messages as $message) {
                    $contact = $message->conversation->contact ?? null;
                    fputcsv($file, [
                        $contact ? $contact->name : 'N/A',
                        $contact ? $contact->phone_number : 'N/A',
                        strtoupper($message->status),
                        $message->error_message ?? '',
                        $message->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
