<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Contact;
use Illuminate\Http\Request;
use App\Http\Resources\Collection;
use App\Http\Requests\ContactRequest;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->all('search', 'trashed');

        return Inertia::render('Contact/Index', [
            'filters'  => $filters,
            'contacts' => new Collection(
                Contact::filter($filters)->orderByDesc('id')->paginate()->withQueryString()
            ),
        ]);
    }

    public function create()
    {
        return Inertia::render('Contact/Form');
    }

    public function store(ContactRequest $request)
    {
        Contact::create($request->validated());

        return redirect()->route('contacts.index')->with('message', __choice('action_text', ['record' => 'Contact', 'action' => 'created']));
    }

    public function edit(Contact $contact)
    {
        return Inertia::render('Contact/Form', ['edit' => $contact]);
    }

    public function update(ContactRequest $request, Contact $contact)
    {
        $contact->update($request->validated());

        return back()->with('message', __choice('action_text', ['record' => 'Contact', 'action' => 'updated']));
    }

    public function destroy(Contact $contact)
    {
        if ($contact->del()) {
            return redirect()->route('contacts.index')->with('message', __choice('action_text', ['record' => 'Contact', 'action' => 'deleted']));
        }

        return back()->with('error', __('The record can not be deleted.'));
    }

    public function restore(Contact $contact)
    {
        $contact->restore();

        return back()->with('message', __choice('action_text', ['record' => 'Contact', 'action' => 'restored']));
    }

    public function destroyPermanently(Contact $contact)
    {
        if ($contact->delP()) {
            return redirect()->route('contacts.index')->with('message', __choice('action_text', ['record' => 'Contact', 'action' => 'deleted']));
        }

        return back()->with('error', __('The record can not be deleted.'));
    }
}
