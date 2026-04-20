<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\NewChatMessage;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminChatController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * List all support conversations (for admin panel).
     */
    public function index(Request $request): JsonResponse
    {
        $admin = $request->user();

        $query = Conversation::with(['user', 'latestMessage', 'trip'])
            ->support()
            ->withCount(['messages as unread_count' => function ($q) use ($admin) {
                $q->where('sender_id', '!=', $admin->id)->whereNull('read_at');
            }])
            ->orderByDesc('updated_at');

        if ($request->input('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter: assigned to me
        if ($request->boolean('mine')) {
            $query->where('admin_id', $admin->id);
        }

        // Filter: unassigned
        if ($request->boolean('unassigned')) {
            $query->whereNull('admin_id');
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

    /**
     * Show a single conversation with messages.
     */
    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $admin = $request->user();

        // Auto-assign admin if not yet assigned
        if (!$conversation->admin_id) {
            $conversation->update(['admin_id' => $admin->id]);
        }

        // Mark messages as read
        Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $admin->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $conversation->load(['messages.sender', 'user', 'trip']);

        return response()->json([
            'status'       => true,
            'conversation' => new ConversationResource($conversation),
            'chat_channel' => "chat.{$conversation->id}",
        ]);
    }

    /**
     * Admin replies to a support conversation.
     */
    public function reply(Request $request, Conversation $conversation): JsonResponse
    {
        $admin = $request->user();

        if ($conversation->status === 'closed') {
            return response()->json(['status' => false, 'message' => 'Conversation is closed'], 400);
        }

        // Auto-assign admin on first reply
        if (!$conversation->admin_id) {
            $conversation->update(['admin_id' => $admin->id]);
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
            'sender_id'       => $admin->id,
            'body'            => $data['body'],
            'attachment'      => $attachmentPath,
        ]);

        broadcast(new NewChatMessage($message))->toOthers();

        // Notify the user
        $user = $conversation->user;
        if ($user) {
            $this->notificationService->send(
                $user,
                'support_reply',
                'Support replied',
                mb_substr($data['body'], 0, 100),
                ['conversation_id' => (string) $conversation->id]
            );
        }

        return response()->json([
            'status'  => true,
            'message' => new MessageResource($message->load('sender')),
        ], 201);
    }

    /**
     * Close a support conversation.
     */
    public function close(Request $request, Conversation $conversation): JsonResponse
    {
        $conversation->close();

        return response()->json([
            'status'  => true,
            'message' => 'Conversation closed',
        ]);
    }
}
