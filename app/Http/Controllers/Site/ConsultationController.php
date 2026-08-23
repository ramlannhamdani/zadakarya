<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\Service;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    public function create()
    {
        return view('site.consultation', [
            'services' => Service::published()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'company' => ['nullable', 'string', 'max:150'],
            'whatsapp' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'service_id' => ['nullable', 'exists:services,id'],
            'estimated_quantity' => ['nullable', 'string', 'max:100'],
            'target_date' => ['nullable', 'date'],
            'description' => ['required', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf,ai,psd,zip'],
            'website' => ['prohibited'], // honeypot
        ], [
            'website.prohibited' => 'Terjadi kesalahan. Silakan coba lagi.',
        ]);

        $service = isset($data['service_id']) ? Service::find($data['service_id']) : null;

        $attachmentPath = null;
        $attachmentName = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('inquiries', 'local');
            $attachmentName = $request->file('attachment')->getClientOriginalName();
        }

        $inquiry = Inquiry::create([
            'name' => $data['name'],
            'company' => $data['company'] ?? null,
            'whatsapp' => $data['whatsapp'],
            'email' => $data['email'] ?? null,
            'service_id' => $service?->id,
            'service_name' => $service?->name,
            'estimated_quantity' => $data['estimated_quantity'] ?? null,
            'target_date' => $data['target_date'] ?? null,
            'description' => $data['description'],
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
        ]);

        $waMessage = sprintf(
            "Halo Zada Karya Production, saya %s ingin berkonsultasi mengenai kebutuhan konveksi%s. (Ref konsultasi #%d)",
            $inquiry->name,
            $service ? ' ('.$service->name.')' : '',
            $inquiry->id
        );

        return redirect()
            ->route('consultation.create')
            ->with('consultation_success', true)
            ->with('wa_url', wa_link($waMessage));
    }
}
