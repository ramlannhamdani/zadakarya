<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class OrderAttachmentController extends Controller
{
    public function store(Request $request, Order $order)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,ai,psd,zip,xlsx,xls,doc,docx'],
            'category' => ['required', Rule::in(array_keys(OrderAttachment::CATEGORIES))],
        ]);

        $file = $request->file('file');
        $path = $file->store('attachments/'.$order->id, 'local');

        $order->attachments()->create([
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'category' => $request->input('category'),
            'size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        $order->logActivity('File "'.$file->getClientOriginalName().'" diunggah');

        return back()->with('success', 'File berhasil diunggah.');
    }

    public function download(OrderAttachment $attachment)
    {
        abort_unless(Storage::disk('local')->exists($attachment->file_path), 404);

        return Storage::disk('local')->download($attachment->file_path, $attachment->original_name);
    }

    public function destroy(OrderAttachment $attachment)
    {
        if (Storage::disk('local')->exists($attachment->file_path)) {
            Storage::disk('local')->delete($attachment->file_path);
        }

        $order = $attachment->order;
        $attachment->delete();
        $order->logActivity('File "'.$attachment->original_name.'" dihapus');

        return back()->with('success', 'File dihapus.');
    }
}
