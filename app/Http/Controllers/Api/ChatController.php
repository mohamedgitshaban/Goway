<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\NewChatMessage;
use App\Events\NewConversation;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Trip;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    // ─────────────────────────────────────────────────────────────
    //  SUPPORT CHAT  (client / driver → admin)
    // ─────────────────────────────────────────────────────────────

    /**
     * Start a general support conversation.
     */
    public function startSupport(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        // Reuse existing open support conversation if any
        $conversation = Conversation::where('user_id', $user->id)
            ->where('type', Conversation::TYPE_SUPPORT)
            ->where('status', 'open')
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'type'    => Conversation::TYPE_SUPPORT,
                'user_id' => $user->id,
                'status'  => 'open',
            ]);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $user->id,
            'body'            => $data['message'],
        ]);

        broadcast(new NewChatMessage($message))->toOthers();

        // Notify all admins about the new support conversation
        $this->notifyAdminsNewSupport($conversation, $user);

        return response()->json([
            'status'       => true,
            'message'      => 'Support conversation started',
            'conversation' => new ConversationResource($conversation->load('latestMessage')),
            'chat_channel' => "chat.{$conversation->id}",
        ], 201);
    }

    /**
     * Start a trip-based support conversation.
     */
    public function startTripSupport(Request $request, Trip $trip): JsonResponse
    {
        $user = $request->user();

        // Only trip client or driver can open support for that trip
        if ($user->id !== $trip->client_id && $user->id !== $trip->driver_id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        // Reuse existing open trip-support conversation
        $conversation = Conversation::where('user_id', $user->id)
            ->where('type', Conversation::TYPE_TRIP_SUPPORT)
            ->where('trip_id', $trip->id)
            ->where('status', 'open')
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'type'    => Conversation::TYPE_TRIP_SUPPORT,
                'trip_id' => $trip->id,
                'user_id' => $user->id,
                'status'  => 'open',
            ]);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $user->id,
            'body'            => $data['message'],
        ]);

        broadcast(new NewChatMessage($message))->toOthers();

        // Notify admins about the new trip support conversation
        $this->notifyAdminsNewSupport($conversation, $user);

        return response()->json([
            'status'       => true,
            'message'      => 'Trip support conversation started',
            'conversation' => new ConversationResource($conversation->load('latestMessage')),
            'chat_channel' => "chat.{$conversation->id}",
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────
    //  TRIP CHAT  (client ↔ driver inside an active trip)
    // ─────────────────────────────────────────────────────────────

    /**
     * Start or get the trip chat between client and driver.
     */
    public function startTripChat(Request $request, Trip $trip): JsonResponse
    {
        $user = $request->user();

        if ($user->id !== $trip->client_id && $user->id !== $trip->driver_id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        if (!$trip->driver_id) {
            return response()->json(['status' => false, 'message' => 'No driver assigned yet'], 400);
        }

        // Only allow chat in active trip statuses
        $activeTripStatuses = ['driver_assigned', 'driver_arrived', 'in_progress'];
        if (!in_array($trip->status, $activeTripStatuses)) {
            return response()->json(['status' => false, 'message' => 'Trip is not active'], 400);
        }

        // Reuse existing trip chat conversation
        $conversation = Conversation::where('trip_id', $trip->id)
            ->where('type', Conversation::TYPE_TRIP_CHAT)
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'type'    => Conversation::TYPE_TRIP_CHAT,
                'trip_id' => $trip->id,
                'user_id' => $trip->client_id, // initiator is client by convention
                'status'  => 'open',
            ]);

            // Notify the other party that trip chat is now available
            $otherId = $user->id === $trip->client_id ? $trip->driver_id : $trip->client_id;
            broadcast(new NewConversation($conversation, $otherId));

            $other = User::find($otherId);
            if ($other) {
                $this->notificationService->send(
                    $other,
                    'trip_chat_started',
                    'Trip chat started',
                    $user->name . ' started a chat for your trip',
                    ['conversation_id' => (string) $conversation->id, 'trip_id' => (string) $trip->id]
                );
            }
        }

        return response()->json([
            'status'       => true,
            'conversation' => new ConversationResource($conversation->load('messages.sender')),
            'chat_channel' => "chat.{$conversation->id}",
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  SEND MESSAGE (works for all conversation types)
    // ─────────────────────────────────────────────────────────────

    /**
     * Send a message in a conversation.
     */
    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        // Authorization: user must be a participant
        if (!$this->isParticipant($user, $conversation)) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($conversation->status === 'closed') {
            return response()->json(['status' => false, 'message' => 'Conversation is closed'], 400);
        }

        $data = $request->validate([
            'body'       => 'required|string|max:2000',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('chat/attachments', 'public');
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $user->id,
            'body'            => $data['body'],
            'attachment'      => $attachmentPath,
        ]);

        broadcast(new NewChatMessage($message))->toOthers();

        // Push notification to the other participant(s)
        $this->notifyParticipants($user, $conversation, $data['body']);

        return response()->json([
            'status'  => true,
            'message' => new MessageResource($message->load('sender')),
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────
    //  GET MESSAGES
    // ─────────────────────────────────────────────────────────────

    /**
     * Get messages for a conversation (paginated).
     */
    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if (!$this->isParticipant($user, $conversation)) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        // Mark unread messages as read
        Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = $conversation->messages()
            ->with('sender')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json([
            'status'   => true,
            'messages' => MessageResource::collection($messages),
            'pagination' => [
                'current_page' => $messages->currentPage(),
                'last_page'    => $messages->lastPage(),
                'per_page'     => $messages->perPage(),
                'total'        => $messages->total(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  LIST CONVERSATIONS
    // ─────────────────────────────────────────────────────────────

    /**
     * List user's conversations.
     */
    public function conversations(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Conversation::with('latestMessage')
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);

                // For trip chats, driver is also a participant
                if ($user->isDriver()) {
                    $q->orWhereHas('trip', function ($tq) use ($user) {
                        $tq->where('driver_id', $user->id);
                    });
                }
            })
            ->withCount(['messages as unread_count' => function ($q) use ($user) {
                $q->where('sender_id', '!=', $user->id)->whereNull('read_at');
            }])
            ->orderByDesc('updated_at');

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        $conversations = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'status'        => true,
            'conversations' => ConversationResource::collection($conversations),
            'pagination'    => [
                'current_page' => $conversations->currentPage(),
                'last_page'    => $conversations->lastPage(),
                'per_page'     => $conversations->perPage(),
                'total'        => $conversations->total(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  HELPERS
    // ─────────────────────────────────────────────────────────────

    private function isParticipant($user, Conversation $conversation): bool
    {
        // Direct user (initiator)
        if ($conversation->user_id === $user->id) {
            return true;
        }

        // Admin assigned to support conversation
        if ($conversation->admin_id === $user->id) {
            return true;
        }

        // Driver in trip chat
        if ($conversation->trip_id && $conversation->trip) {
            if ($conversation->trip->driver_id === $user->id || $conversation->trip->client_id === $user->id) {
                return true;
            }
        }

        return false;
    }

    private function notifyParticipants($sender, Conversation $conversation, string $body): void
    {
        $preview = mb_substr($body, 0, 100);

        if ($conversation->isTripChat() && $conversation->trip) {
            // Notify the other party in trip chat
            $recipientId = $sender->id === $conversation->trip->client_id
                ? $conversation->trip->driver_id
                : $conversation->trip->client_id;

            if ($recipientId) {
                $recipient = \App\Models\User::find($recipientId);
                if ($recipient) {
                    $this->notificationService->send(
                        $recipient,
                        'chat_message',
                        'New message from ' . $sender->name,
                        $preview,
                        ['conversation_id' => (string) $conversation->id, 'trip_id' => (string) $conversation->trip_id]
                    );
                }
            }
        } elseif ($conversation->isSupport()) {
            // If user sent → notify admin (if assigned)
            if ($sender->id === $conversation->user_id && $conversation->admin_id) {
                $admin = \App\Models\User::find($conversation->admin_id);
                if ($admin) {
                    $this->notificationService->send(
                        $admin,
                        'support_message',
                        'Support message from ' . $sender->name,
                        $preview,
                        ['conversation_id' => (string) $conversation->id]
                    );
                }
            }
            // If admin sent → notify user
            if ($sender->id === $conversation->admin_id) {
                $user = \App\Models\User::find($conversation->user_id);
                if ($user) {
                    $this->notificationService->send(
                        $user,
                        'support_reply',
                        'Support replied',
                        $preview,
                        ['conversation_id' => (string) $conversation->id]
                    );
                }
            }
        }
    }

    private function notifyAdminsNewSupport(Conversation $conversation, $sender): void
    {
        $admins = User::where('usertype', User::ROLE_ADMIN)->get();

        foreach ($admins as $admin) {
            // Real-time broadcast so admin dashboard updates instantly
            broadcast(new NewConversation($conversation, $admin->id));

            // FCM push notification
            $this->notificationService->send(
                $admin,
                'new_support_chat',
                'New support request',
                $sender->name . ' needs help',
                ['conversation_id' => (string) $conversation->id]
            );
        }
    }
}
