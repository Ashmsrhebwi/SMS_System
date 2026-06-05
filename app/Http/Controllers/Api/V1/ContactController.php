<?php

namespace App\Http\Controllers\Api\V1;

use App\Exports\ContactsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Http\Resources\ContactResource;
use App\Imports\ContactsImport;
use App\Models\Contact;
use App\Models\ContactNote;
use App\Models\Tag;
use App\Services\ActivityLogger;
use App\Services\AuditLogger;
use App\Services\PhoneNormalizerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ContactController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Contact::class);

        $query = Contact::with('tags')->latest();

        if ($tagId = $request->get('tag')) {
            $query->whereHas('tags', fn($q) => $q->where('tags.id', $tagId));
        }
        if ($optIn = $request->get('opt_in')) {
            $query->where('opted_in', $optIn === '1');
        }
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $contacts = $query->paginate($request->integer('per_page', 50));

        return ContactResource::collection($contacts)->response();
    }

    public function store(StoreContactRequest $request): JsonResponse
    {
        if ($request->filled('phone') && !$request->boolean('force_create')) {
            $normalized = app(PhoneNormalizerService::class)->normalize($request->phone);
            if ($normalized && Contact::where('phone', $normalized)->exists()) {
                return response()->json([
                    'message'    => "Phone number {$normalized} already exists.",
                    'duplicate'  => true,
                    'field'      => 'phone',
                ], 422);
            }
            if ($request->filled('email') && Contact::where('email', $request->email)->exists()) {
                return response()->json([
                    'message'   => "Email {$request->email} is already used by another contact.",
                    'duplicate' => true,
                    'field'     => 'email',
                ], 422);
            }
        }

        $contact = Contact::create([
            'name'     => $request->name,
            'phone'    => $request->phone,
            'email'    => $request->email,
            'notes'    => $request->notes,
            'opted_in' => $request->boolean('opted_in', true),
        ]);

        if ($request->has('tags')) {
            $contact->tags()->sync($request->tags);
        }

        ActivityLogger::contactCreated($contact);
        AuditLogger::log('create_contact', $contact, null, $contact->only(['name', 'phone', 'email']));

        return (new ContactResource($contact->load('tags')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Contact $contact): JsonResponse
    {
        $this->authorize('view', $contact);
        $contact->load(['tags', 'notes.author', 'activities.user']);

        return (new ContactResource($contact))->response();
    }

    public function update(UpdateContactRequest $request, Contact $contact): JsonResponse
    {
        $old       = $contact->only(['name', 'phone', 'email', 'opted_in']);
        $oldTagIds = $contact->tags->pluck('id')->toArray();
        $newTagIds = $request->tags ?? [];

        $contact->update([
            'name'     => $request->name,
            'phone'    => $request->phone,
            'email'    => $request->email,
            'notes'    => $request->notes,
            'opted_in' => $request->boolean('opted_in'),
        ]);

        $contact->tags()->sync($newTagIds);

        $allTags = Tag::whereIn('id', array_unique(array_merge($oldTagIds, $newTagIds)))->get()->keyBy('id');
        foreach (array_diff($newTagIds, $oldTagIds) as $addedId) {
            if ($allTags->has($addedId)) {
                ActivityLogger::tagAdded($contact, $allTags[$addedId]);
            }
        }
        foreach (array_diff($oldTagIds, $newTagIds) as $removedId) {
            if ($allTags->has($removedId)) {
                ActivityLogger::tagRemoved($contact, $allTags[$removedId]);
            }
        }

        ActivityLogger::contactUpdated($contact);
        AuditLogger::log('update_contact', $contact, $old, $contact->fresh()->only(['name', 'phone', 'email', 'opted_in']));

        return (new ContactResource($contact->load('tags')))->response();
    }

    public function destroy(Contact $contact): JsonResponse
    {
        $this->authorize('delete', $contact);
        AuditLogger::log('delete_contact', $contact, $contact->only(['name', 'phone']));
        $contact->delete();

        return response()->json(['message' => 'Contact deleted.']);
    }

    public function toggleOptIn(Contact $contact): JsonResponse
    {
        $this->authorize('toggleOptIn', $contact);
        $contact->update(['opted_in' => !$contact->opted_in]);
        ActivityLogger::contactUpdated($contact);

        return response()->json(['opted_in' => $contact->opted_in]);
    }

    public function notes(Contact $contact): JsonResponse
    {
        $this->authorize('view', $contact);
        $notes = $contact->notes()->with('author')->paginate(20);

        return response()->json($notes);
    }

    public function storeNote(Request $request, Contact $contact): JsonResponse
    {
        $this->authorize('view', $contact);

        $data = $request->validate(['note' => 'required|string|max:2000']);

        $note = $contact->notes()->create([
            'user_id' => auth()->id(),
            'note'    => $data['note'],
        ]);

        return response()->json($note->load('author'), 201);
    }

    public function checkDuplicate(Request $request): JsonResponse
    {
        $phone     = $request->get('phone');
        $email     = $request->get('email');
        $excludeId = $request->get('exclude_id');

        $result = ['phone_exists' => false, 'email_exists' => false, 'contact' => null];

        if ($phone) {
            $normalized = app(PhoneNormalizerService::class)->normalize($phone);
            if ($normalized) {
                $q = Contact::where('phone', $normalized);
                if ($excludeId) {
                    $q->where('id', '!=', $excludeId);
                }
                $existing = $q->first(['id', 'name', 'phone']);
                if ($existing) {
                    $result['phone_exists'] = true;
                    $result['contact']      = ['id' => $existing->id, 'name' => $existing->name];
                }
            }
        }

        if ($email && !$result['phone_exists']) {
            $q = Contact::where('email', $email);
            if ($excludeId) {
                $q->where('id', '!=', $excludeId);
            }
            $existing = $q->first(['id', 'name', 'email']);
            if ($existing) {
                $result['email_exists'] = true;
                $result['contact']      = ['id' => $existing->id, 'name' => $existing->name];
            }
        }

        return response()->json($result);
    }

    public function import(Request $request): JsonResponse
    {
        $this->authorize('import', Contact::class);

        $request->validate([
            'file'             => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'duplicate_action' => 'in:skip,update',
        ]);

        $import = new ContactsImport($request->get('duplicate_action', 'skip'));
        Excel::import($import, $request->file('file'));

        AuditLogger::log('import_contacts', null, null, [
            'imported' => $import->imported,
            'updated'  => $import->updated,
            'skipped'  => $import->skipped,
        ]);

        return response()->json([
            'message'  => 'Import complete.',
            'imported' => $import->imported,
            'updated'  => $import->updated,
            'skipped'  => $import->skipped,
            'errors'   => $import->errors,
        ]);
    }

    public function export(Request $request)
    {
        $this->authorize('export', Contact::class);

        $filter    = $request->get('filter', 'all');
        $segmentId = $request->get('segment_id');
        $tagId     = $request->get('tag_id');
        $format    = $request->get('format', 'xlsx');

        $export   = new ContactsExport($filter, $segmentId ? (int) $segmentId : null, $tagId ? (int) $tagId : null);
        $filename = 'contacts-export-' . now()->format('Y-m-d') . '.' . $format;

        AuditLogger::log('export_contacts', null, null, ['filter' => $filter, 'format' => $format]);

        if ($format === 'csv') {
            return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::CSV);
        }

        return Excel::download($export, $filename);
    }
}
