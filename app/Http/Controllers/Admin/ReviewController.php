<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index()
    {
        return view('admin.reviews.index', [
            'reviews' => Review::orderBy('sort_order')->latest('review_date')->paginate(20),
        ]);
    }

    public function create()
    {
        return view('admin.reviews.form', ['review' => new Review(['rating' => 5])]);
    }

    public function store(Request $request)
    {
        Review::create($this->validated($request));

        return redirect()->route('admin.reviews.index')->with('success', 'Ulasan berhasil ditambahkan.');
    }

    public function edit(Review $review)
    {
        return view('admin.reviews.form', compact('review'));
    }

    public function update(Request $request, Review $review)
    {
        $review->update($this->validated($request));

        return redirect()->route('admin.reviews.index')->with('success', 'Ulasan diperbarui.');
    }

    public function destroy(Review $review)
    {
        $review->delete();

        return redirect()->route('admin.reviews.index')->with('success', 'Ulasan dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'author_name' => ['required', 'string', 'max:150'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'content' => ['required', 'string', 'max:3000'],
            'review_date' => ['nullable', 'date'],
            'is_published' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['is_published'] = $request->boolean('is_published');
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['source'] = 'google';

        return $data;
    }
}
