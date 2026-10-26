<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::query();

        if ($request->filled('q')) {
            $query->where(function ($w) use ($request) {
                $w->where('author_name', 'like', '%'.$request->q.'%')
                  ->orWhere('content', 'like', '%'.$request->q.'%');
            });
        }

        if ($request->status === 'published') {
            $query->where('is_published', true);
        } elseif ($request->status === 'hidden') {
            $query->where('is_published', false);
        }

        if ($request->filled('rating') && is_numeric($request->rating)) {
            $query->where('rating', (int)$request->rating);
        }

        $reviews = $query->orderBy('sort_order')->latest('review_date')->paginate(20)->withQueryString();

        $stats = [
            'total' => Review::count(),
            'avg_rating' => round(Review::avg('rating') ?: 5.0, 1),
            'published' => Review::where('is_published', true)->count(),
            'hidden' => Review::where('is_published', false)->count(),
        ];

        return view('admin.reviews.index', [
            'reviews' => $reviews,
            'stats' => $stats,
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
