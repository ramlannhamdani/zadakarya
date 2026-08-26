<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Support\ImageUploader;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index()
    {
        return view('admin.clients.index', [
            'clients' => Client::orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.clients.form', ['client' => new Client]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $logo = $this->resolveLogo($request, $data);

        if (! $logo) {
            return back()->withInput()->withErrors(['logo' => 'Logo wajib diunggah atau dipilih dari galeri.']);
        }

        unset($data['logo_pick']);
        $data['logo_path'] = $logo;
        Client::create($data);

        return redirect()->route('admin.clients.index')->with('success', 'Klien ditambahkan.');
    }

    public function edit(Client $client)
    {
        return view('admin.clients.form', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $data = $this->validated($request);
        $logo = $this->resolveLogo($request, $data);

        unset($data['logo_pick']);
        if ($logo) {
            ImageUploader::delete($client->logo_path);
            $data['logo_path'] = $logo;
        }

        $client->update($data);

        return redirect()->route('admin.clients.index')->with('success', 'Klien diperbarui.');
    }

    public function destroy(Client $client)
    {
        ImageUploader::delete($client->logo_path);
        $client->delete();

        return redirect()->route('admin.clients.index')->with('success', 'Klien dihapus.');
    }

    /** Logo dari upload langsung atau pilihan galeri; disimpan ringan (maks 600px). */
    private function resolveLogo(Request $request, array $data): ?string
    {
        if ($request->hasFile('logo')) {
            [$path] = ImageUploader::store($request->file('logo'), 'clients', 'public', 600, 300);

            return $path;
        }

        if (! empty($data['logo_pick']) && ($res = ImageUploader::fromGalleryId($data['logo_pick'], 'clients', 'public', 600, 300))) {
            return $res[0];
        }

        return null;
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'logo' => ['nullable', 'image', 'mimes:png,webp,jpg,jpeg', 'max:2048'],
            'logo_pick' => ['nullable', 'integer', 'exists:gallery_items,id'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');
        unset($data['logo']);

        return $data;
    }
}
