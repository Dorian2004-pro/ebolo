<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\PaginatesAdminData;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    use PaginatesAdminData;

    public function index(Request $request): JsonResponse
    {
        $query = Message::with(['sender.role', 'receiver.role', 'booking.service'])
            ->whereHas('sender.role', fn ($role) => $role->whereIn('slug', ['client', 'artisan', 'salon']))
            ->latest();

        return response()->json($this->paginate(
            $request,
            $query,
            fn (Message $message) => [
                'id' => $message->id,
                'user_id' => $message->sender?->role?->slug === 'admin'
                    ? $message->receiver_id
                    : $message->sender_id,
                'user' => $message->sender?->role?->slug === 'admin'
                    ? $message->receiver?->name
                    : $message->sender?->name,
                'role' => $message->sender?->role?->slug === 'admin'
                    ? $message->receiver?->role?->slug
                    : $message->sender?->role?->slug,
                'booking' => $message->booking?->id,
                'service' => $message->booking?->service?->name,
                'content' => $message->content,
                'is_read' => (bool) $message->is_read,
                'created_at' => $message->created_at?->toIso8601String(),
            ],
        ));
    }

    public function reply(Request $request, int $userId): JsonResponse
    {
        $data = $request->validate(['content' => ['required', 'string', 'max:2000']]);
        $recipient = User::with('role')->findOrFail($userId);
        abort_unless(in_array($recipient->role?->slug, ['client', 'artisan', 'salon'], true), 422, 'Le destinataire doit être une cliente ou une prestataire.');

        $bookingQuery = Booking::query();
        if ($recipient->role?->slug === 'client') {
            $bookingQuery->where('user_id', $recipient->id);
        } else {
            $bookingQuery->whereHas('artisan', fn ($profile) => $profile->where('user_id', $recipient->id));
        }
        $booking = $bookingQuery->latest()->firstOrFail();

        $message = Message::create([
            'sender_id' => $request->user()->id,
            'receiver_id' => $recipient->id,
            'booking_id' => $booking->id,
            'content' => $data['content'],
        ]);

        return response()->json(['data' => $message->load(['sender', 'receiver'])], 201);
    }
}
