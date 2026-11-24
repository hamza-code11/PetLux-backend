<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    /**
     * Get all approved reviews for a product (Public)
     */
    public function index(string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)->first();

        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $reviews = Review::with('user:id,first_name,last_name')
            ->where('product_id', $product->id)
            ->where('status', 'approved')
            ->latest()
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'user' => $review->user->first_name . ' ' . $review->user->last_name,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'date' => $review->created_at->format('M d, Y'),
                ];
            });

        return response()->json([
            'data' => $reviews,
            'total' => $reviews->count(),
            'average_rating' => $reviews->avg('rating') ?? 0,
        ]);
    }

    /**
     * Store a new review (Authenticated user)
     */
    public function store(Request $request, string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)->first();

        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);

        $existingReview = Review::where('product_id', $product->id)
            ->where('user_id', auth()->id())
            ->first();

        if ($existingReview) {
            return response()->json([
                'message' => 'You have already reviewed this product.',
            ], 422);
        }

        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Review submitted successfully! It will be visible after admin approval.',
            'data' => $review,
        ], 201);
    }

    // ============ ADMIN METHODS ============

    /**
     * Get all reviews for admin panel (with filters)
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = Review::with(['user:id,first_name,last_name,email', 'product:id,name,slug']);

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by rating
        if ($request->filled('rating') && $request->rating !== 'all') {
            $query->where('rating', $request->rating);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('first_name', 'like', "%{$search}%")
                       ->orWhere('last_name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('product', function ($pq) use ($search) {
                    $pq->where('name', 'like', "%{$search}%");
                })->orWhere('comment', 'like', "%{$search}%");
            });
        }

        $reviews = $query->latest()->get()->map(function ($review) {
            return [
                'id' => $review->id,
                'productName' => $review->product->name ?? 'N/A',
                'productSlug' => $review->product->slug ?? '',
                'userName' => trim(($review->user->first_name ?? '') . ' ' . ($review->user->last_name ?? '')),
                'userEmail' => $review->user->email ?? '',
                'rating' => $review->rating,
                'comment' => $review->comment,
                'status' => $review->status,
                'date' => $review->created_at->format('M d, Y'),
                'createdAt' => $review->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $reviews,
            'total' => $reviews->count(),
            'stats' => [
                'total' => Review::count(),
                'pending' => Review::where('status', 'pending')->count(),
                'approved' => Review::where('status', 'approved')->count(),
                'rejected' => Review::where('status', 'rejected')->count(),
            ],
        ]);
    }

    /**
     * Approve a review
     */
    public function approve(int $id): JsonResponse
    {
        $review = Review::findOrFail($id);
        $review->update(['status' => 'approved']);

        return response()->json([
            'message' => 'Review approved successfully.',
            'data' => $review,
        ]);
    }

    /**
     * Reject a review
     */
    public function reject(int $id): JsonResponse
    {
        $review = Review::findOrFail($id);
        $review->update(['status' => 'rejected']);

        return response()->json([
            'message' => 'Review rejected.',
            'data' => $review,
        ]);
    }

    /**
     * Delete a review
     */
    public function destroy(int $id): JsonResponse
    {
        $review = Review::findOrFail($id);
        $review->delete();

        return response()->json(['message' => 'Review deleted successfully.']);
    }
}


