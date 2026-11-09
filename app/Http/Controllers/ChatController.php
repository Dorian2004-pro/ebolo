<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Booking;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index($userId)
    {
        abort_unless($this->canChatWith((int) $userId), 403);

        $messages = Message::where(function ($query) use ($userId) {
                $query->where('sender_id', auth()->id())
                    ->where('receiver_id', $userId);
            })
            ->orWhere(function ($query) use ($userId) {
                $query->where('sender_id', $userId)
                    ->where('receiver_id', auth()->id());
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'content' => 'required|string|max:2000',
        ]);
        abort_unless($this->canChatWith((int) $validated['receiver_id']), 403);

        $message = Message::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $validated['receiver_id'],
            'content' => $validated['content'],
            'is_read' => false,
        ]);

        return response()->json($message, 201);
    }

    private function canChatWith(int $otherUserId): bool
    {
        $user = auth()->user();
        $profileId = $user->artisanProfile?->id;

        if ($user->role?->slug === 'artisan' && $profileId) {
            return Booking::where('artisan_id', $profileId)
                ->where('user_id', $otherUserId)
                ->exists();
        }

        return Booking::where('user_id', $user->id)
            ->whereHas('artisan', fn ($query) => $query->where('user_id', $otherUserId))
            ->exists();
    }
}