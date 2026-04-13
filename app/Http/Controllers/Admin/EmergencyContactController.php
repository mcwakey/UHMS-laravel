<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmergencyContact;
use App\Models\Patient;
use Illuminate\Http\Request;

class EmergencyContactController extends Controller
{
    public function store(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'phone_secondary' => ['nullable', 'string', 'max:20'],
            'relationship' => ['nullable', 'string', 'max:50'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        if (!empty($data['is_primary'])) {
            $patient->emergencyContacts()->update(['is_primary' => false]);
        }

        $patient->emergencyContacts()->create($data);

        return back()->with('success', 'Emergency contact added.');
    }

    public function update(Request $request, Patient $patient, EmergencyContact $contact)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'phone_secondary' => ['nullable', 'string', 'max:20'],
            'relationship' => ['nullable', 'string', 'max:50'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        if (!empty($data['is_primary'])) {
            $patient->emergencyContacts()->where('id', '!=', $contact->id)->update(['is_primary' => false]);
        }

        $contact->update($data);

        return back()->with('success', 'Emergency contact updated.');
    }

    public function destroy(Patient $patient, EmergencyContact $contact)
    {
        $contact->delete();

        return back()->with('success', 'Emergency contact removed.');
    }
}
