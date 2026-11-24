<?php

namespace App\Http\Controllers;

use App\Models\Newsletter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;

class NewsletterController extends Controller
{
    /**
     * 1. Subscribe Email (Public API - Frontend Footer etc.)
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:newsletters,email'],
        ]);

        $newsletter = Newsletter::create([
            'email' => $validated['email'],
        ]);

        $this->clearCache();

        return response()->json([
            'message' => 'Subscribed to newsletter successfully.',
            'data' => $newsletter
        ], 201);
    }

    /**
     * 2. List all subscribers (Admin API)
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 10);
        $page = $request->query('page', 1);
        
        $version = Cache::get('newsletters_cache_version', 1);
        $cacheKey = "newsletters_v{$version}_page_{$page}_limit_{$perPage}";

        $newsletters = Cache::remember($cacheKey, 86400, function () use ($perPage) {
            return Newsletter::select('id', 'email', 'created_at')
                ->latest()
                ->paginate($perPage);
        });

        return response()->json($newsletters);
    }

    /**
     * 3. Delete a subscriber (Admin API)
     */
    public function destroy(Newsletter $newsletter): JsonResponse
    {
        $newsletter->delete();
        $this->clearCache();

        return response()->json([
            'message' => 'Subscriber removed successfully.'
        ]);
    }

    /**
     * Helper: Clear Cache
     */
    private function clearCache()
    {
        Cache::increment('newsletters_cache_version');
    }
}
