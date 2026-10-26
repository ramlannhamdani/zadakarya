<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab');
        $city = $request->query('city');

        $customers = Customer::withCount('orders')
            ->withSum(['orders as total_spent' => fn ($q) => $q->where('status', '!=', 'cancelled')], 'grand_total')
            ->when($tab === 'repeat', fn ($q) => $q->has('orders', '>=', 2))
            ->when($tab === 'instansi', fn ($q) => $q->whereNotNull('company')->where('company', '!=', ''))
            ->when($city && $city !== 'all', fn ($q) => $q->where('city', $city))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->q.'%';
                $query->where(fn ($w) => $w
                    ->where('name', 'like', $term)
                    ->orWhere('company', 'like', $term)
                    ->orWhere('whatsapp', 'like', $term)
                    ->orWhere('city', 'like', $term)
                    ->orWhere('email', 'like', $term));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => Customer::count(),
            'repeat' => Customer::has('orders', '>=', 2)->count(),
            'b2b' => Customer::whereNotNull('company')->where('company', '!=', '')->count(),
            'total_spent' => \App\Models\Order::where('status', '!=', 'cancelled')->sum('grand_total'),
        ];

        $cities = Customer::whereNotNull('city')->where('city', '!=', '')->distinct()->pluck('city');

        return view('admin.customers.index', compact('customers', 'stats', 'tab', 'city', 'cities'));
    }

    public function create()
    {
        return view('admin.customers.form', ['customer' => new Customer]);
    }

    public function store(Request $request)
    {
        $customer = Customer::create($this->validated($request));

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('success', 'Customer berhasil dibuat.');
    }

    public function show(Customer $customer)
    {
        $customer->load(['orders' => fn ($q) => $q->latest(), 'inquiries' => fn ($q) => $q->latest()]);

        return view('admin.customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('admin.customers.form', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $customer->update($this->validated($request));

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('success', 'Customer berhasil diperbarui.');
    }

    public function destroy(Customer $customer)
    {
        if ($customer->orders()->exists()) {
            return back()->with('error', 'Customer memiliki pesanan dan tidak dapat dihapus.');
        }

        $customer->delete();

        return redirect()->route('admin.customers.index')->with('success', 'Customer dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'company' => ['nullable', 'string', 'max:150'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
