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

        $trashedContacts = WhatsappContact::onlyTrashed()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(10, ['*'], 'trashed_page')
            ->withQueryString();

        return view('admin.whatsapp.contacts', compact(
            'contactStats',
            'activeGroupsCount',
            'contacts',
            'groups',
            'trashedContacts'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'phone_number' => 'required|string|max:50',
            'opted_in' => 'nullable|boolean',
            'group_ids' => 'nullable|array',
            'group_ids.*' => 'exists:whatsapp_groups,id'
        ]);

        $validated['opted_in'] = $request->has('opted_in');
        $validated['user_id'] = $request->user()->id;

        $contact = WhatsappContact::create($validated);

        if ($request->has('group_ids')) {
            $contact->groups()->sync($request->group_ids);
        }

        return redirect()->back()->with('success', 'Contact added successfully.');
    }

    public function update(Request $request, WhatsappContact $contact)
    {
        if ($contact->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'phone_number' => 'required|string|max:50',
            'opted_in' => 'nullable|boolean',
            'group_ids' => 'nullable|array',
            'group_ids.*' => 'exists:whatsapp_groups,id'
        ]);

        $validated['opted_in'] = $request->has('opted_in');

        $contact->update($validated);

        if ($request->has('group_ids')) {
            $contact->groups()->sync($request->group_ids);
        } else {
            $contact->groups()->detach();
        }

        return redirect()->back()->with('success', 'Contact updated successfully.');
    }

    public function destroy(Request $request, WhatsappContact $contact)
    {
        if ($contact->user_id !== $request->user()->id) {
            abort(403);
        }

        $contact->delete();

        return redirect()->back()->with('success', 'Contact moved to trash.');
    }

    public function restoreContact(Request $request, $id)
    {
        $contact = WhatsappContact::onlyTrashed()->where('user_id', $request->user()->id)->findOrFail($id);
        $contact->restore();

        return redirect()->back()->with('success', 'Contact restored successfully.');
    }

    public function forceDeleteContact(Request $request, $id)
    {
        $contact = WhatsappContact::onlyTrashed()->where('user_id', $request->user()->id)->findOrFail($id);
        $contact->forceDelete();

        return redirect()->back()->with('success', 'Contact permanently deleted.');
    }

    public function bulkAddGroups(Request $request)
    {
        $validated = $request->validate([
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'exists:whatsapp_contacts,id',
            'group_id' => 'required|exists:whatsapp_groups,id',
        ]);

        $group = WhatsappGroup::where('user_id', $request->user()->id)->findOrFail($validated['group_id']);
        
        $group->contacts()->syncWithoutDetaching($validated['contact_ids']);

        return redirect()->back()->with('success', 'Contacts added to group successfully.');
    }

    public function downloadSample()
    {
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=sample_contacts.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = array('Name', 'Phone Number');
        $callback = function() use($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            
            fputcsv($file, ['John Doe', '+1234567890']);
            fputcsv($file, ['Jane Smith', '+1987654321']);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function storeGroup(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        WhatsappGroup::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'description' => $validated['description'],
        ]);

        return redirect()->back()->with('success', 'Group created successfully.');
    }

    public function updateGroup(Request $request, WhatsappGroup $group)
    {
        if ($group->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $group->update($validated);

        return redirect()->back()->with('success', 'Group updated successfully.');
    }

    public function destroyGroup(Request $request, WhatsappGroup $group)
    {
        if ($group->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($request->has('delete_contacts')) {
            $group->contacts()->delete();
        }

        $group->delete();

        return redirect()->back()->with('success', 'Group deleted successfully.');
    }

    public function exportGroup(Request $request, WhatsappGroup $group)
    {
        if ($group->user_id !== $request->user()->id) {
            abort(403);
        }

        $contacts = $group->contacts;

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=" . preg_replace('/[^a-zA-Z0-9_]/', '_', $group->name) . "_members.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Name', 'Phone Number', 'Opted In', 'Last Message At'];
        $callback = function() use($contacts, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            
            foreach ($contacts as $contact) {
                fputcsv($file, [
                    $contact->name,
                    $contact->phone_number,
                    $contact->opted_in ? 'Yes' : 'No',
                    $contact->last_message_at ? $contact->last_message_at->toDateTimeString() : 'Never'
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
