<?php

namespace App\Http\Controllers;

use App\Imports\ContactsImport;
use App\Models\Contact;
use App\Services\PhoneNormalizerService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ContactController extends Controller
{
    public function index()
    {
        $contacts = Contact::latest()->paginate(50);
        return view('contacts.index', compact('contacts'));
    }

    public function importForm()
    {
        return view('contacts.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $import = new ContactsImport();
        Excel::import($import, $request->file('file'));

        return redirect()->route('contacts.index')->with('import_result', [
            'imported' => $import->imported,
            'skipped' => $import->skipped,
            'errors' => $import->errors,
        ]);
    }

    public function toggleOptIn(Contact $contact)
    {
        $contact->update(['opted_in' => !$contact->opted_in]);
        return back()->with('success', 'Contact updated.');
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();
        return back()->with('success', 'Contact deleted.');
    }
}
