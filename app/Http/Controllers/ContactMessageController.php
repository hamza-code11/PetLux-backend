<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;

class ContactMessageController extends Controller
{
    /**
     * 1. Store a new contact message (Public API)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        $contactMessage = ContactMessage::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => 'unread', // Default status
        ]);

        $this->clearCache();

        return response()->json([
            'message' => 'Your message has been sent successfully.',
            'data' => $contactMessage
        ], 201);
    }

    /**
     * 2. List all messages (Admin API)
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 10);
        $page = $request->query('page', 1);
        $status = $request->query('status'); // Optional filter (read/unread)
        
        $version = Cache::get('contact_messages_cache_version', 1);
        $cacheKey = "contact_messages_v{$version}_page_{$page}_limit_{$perPage}_status_" . ($status ?? 'all');

        $messages = Cache::remember($cacheKey, 86400, function () use ($perPage, $status) {
            $query = ContactMessage::select('id', 'name', 'email', 'subject', 'message', 'status', 'created_at');
            
            if ($status && in_array($status, ['read', 'unread'])) {
                $query->where('status', $status);
            }
            
            return $query->latest()->paginate($perPage);
        });

        return response()->json($messages);
    }

    /**
     * 3. Show single message & mark as read (Admin API)
     */
    public function show(ContactMessage $contactMessage): JsonResponse
    {
        // Jab admin message open kare, toh auto-mark as read
        if ($contactMessage->status === 'unread') {
            $contactMessage->update(['status' => 'read']);
            $this->clearCache();
        }

        return response()->json([
            'data' => $contactMessage
        ]);
    }

    /**
     * 4. Update message status manually (Admin API)
     */
    public function updateStatus(Request $request, ContactMessage $contactMessage): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:read,unread'],
        ]);

        $contactMessage->update(['status' => $validated['status']]);
        $this->clearCache();

        return response()->json([
            'message' => 'Message status updated.',
            'data' => $contactMessage
        ]);
    }

    /**
     * 5. Delete a message (Admin API)
     */
    public function destroy(ContactMessage $contactMessage): JsonResponse
    {
        $contactMessage->delete();
        $this->clearCache();

        return response()->json([
            'message' => 'Message deleted successfully.'
        ]);
    }

    /**
     * Helper: Clear Cache
     */
    private function clearCache()
    {
        Cache::increment('contact_messages_cache_version');
    }
}

