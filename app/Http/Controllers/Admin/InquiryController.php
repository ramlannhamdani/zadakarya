<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Inquiry;
use App\Support\ImageUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class InquiryController extends Controller
{
    public function index(Request $request)
    {
        $inquiries = Inquiry::with('customer')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->q.'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)
                    ->orWhere('company', 'like', $term)
                    ->orWhere('whatsapp', 'like', $term));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.inquiries.index', compact('inquiries'));
    }

    public function show(Inquiry $inquiry)
    {
        return view('admin.inquiries.show', compact('inquiry'));
    }

    public function update(Request $request, Inquiry $inquiry)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Inquiry::STATUSES))],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $inquiry->update($data);

        return back()->with('success', 'Inquiry diperbarui.');
    }

    public function convert(Inquiry $inquiry)
    {
        if ($inquiry->customer_id) {
            return redirect()
                ->route('admin.customers.show', $inquiry->customer_id)
                ->with('success', 'Inquiry sudah terhubung dengan customer.');
        }

        $customer = Customer::create([
            'name' => $inquiry->name,
            'company' => $inquiry->company,
            'whatsapp' => $inquiry->whatsapp,
            'email' => $inquiry->email,
            'notes' => 'Dibuat dari inquiry #'.$inquiry->id,
        ]);

        $inquiry->update(['customer_id' => $customer->id, 'status' => 'deal']);

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('success', 'Inquiry berhasil dikonversi menjadi customer.');
    }

    public function attachment(Inquiry $inquiry)
    {
        abort_unless($inquiry->attachment_path && Storage::disk('local')->exists($inquiry->attachment_path), 404);

        return Storage::disk('local')->download($inquiry->attachment_path, $inquiry->attachment_name ?? 'lampiran');
    }

    public function destroy(Inquiry $inquiry)
    {
        ImageUploader::delete($inquiry->attachment_path, 'local');
        $inquiry->delete();

        return redirect()->route('admin.inquiries.index')->with('success', 'Inquiry dihapus.');
    }
}
