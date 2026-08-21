<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsappContact;
use App\Models\WhatsappGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WhatsappContactController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Optimized statistics queries using aggregate functions
        $contactStats = WhatsappContact::where('user_id', $user->id)
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN opted_in = 1 THEN 1 ELSE 0 END) as opted_in'),
                DB::raw('SUM(CASE WHEN opted_in = 0 THEN 1 ELSE 0 END) as opted_out')
            )
            ->first();

        $activeGroupsCount = WhatsappGroup::where('user_id', $user->id)->count();

        // Fetch paginated contacts with their groups (Eager Loading)
        $contactsQuery = WhatsappContact::where('user_id', $user->id)->with('groups:id,name');

        // Dynamic filtering
        if ($request->filled('search')) {
            $search = $request->search;
            $contactsQuery->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('group_id')) {
            $contactsQuery->whereHas('groups', function($q) use ($request) {
                $q->where('whatsapp_groups.id', $request->group_id);
            });
        }

        if ($request->filled('opted_in')) {
            $contactsQuery->where('opted_in', $request->opted_in);
        }

        $contacts = $contactsQuery->latest()->paginate(10)->withQueryString();

        // Fetch all groups for filter and groups tab (with member count)
        $groups = WhatsappGroup::where('user_id', $user->id)
            ->withCount('contacts')
            ->latest()
            ->get();

        return view('admin.whatsapp.contacts', compact(
            'contactStats',
            'activeGroupsCount',
            'contacts',
            'groups'
        ));
    }
}
