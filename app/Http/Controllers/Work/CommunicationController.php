<?php

namespace App\Http\Controllers\Work;

use App\Enums\MessageType;
use App\Http\Controllers\Controller;
use App\Models\CommunicationThread;
use App\Models\Message;
use App\Models\Project;
use App\Models\WorkOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class CommunicationController extends Controller
{
    public function show(Request $request, string $type, int $id)
    {
        $user = $request->user();
        $team = $user->currentTeam;

        $model = $this->getModel($type, $id);

        if (!$model || $model->team_id !== $team->id) {
            abort(404);
        }

        $thread = $model->communicationThread;

        if (!$thread) {
            return response()->json([
                'thread' => null,
                'messages' => [],
            ]);
        }

        $messages = $thread->messages()
            ->with('author')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'thread' => [
                'id' => (string) $thread->id,
                'messageCount' => $thread->message_count,
                'lastActivity' => $thread->last_activity?->toIso8601String(),
            ],
            'messages' => $messages->map(fn (Message $msg) => [
                'id' => (string) $msg->id,
                'authorId' => (string) $msg->author_id,
                'authorName' => $msg->author?->name ?? 'Unknown',
                'authorType' => $msg->author_type->value,
                'timestamp' => $msg->created_at->toIso8601String(),
                'content' => $msg->content,
                'type' => $msg->type->value,
            ]),
        ]);
    }

    public function store(Request $request, string $type, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:10000',
            'type' => 'required|string|in:note,suggestion,decision,question',
        ]);

        $user = $request->user();
        $team = $user->currentTeam;

        $model = $this->getModel($type, $id);

        if (!$model || $model->team_id !== $team->id) {
            abort(404);
        }

        // Get or create thread
        $thread = $model->communicationThread;

        if (!$thread) {
            $thread = CommunicationThread::create([
                'team_id' => $team->id,
                'threadable_type' => get_class($model),
                'threadable_id' => $model->id,
            ]);
        }

        // Add message
        $thread->addMessage(
            $user,
            $validated['content'],
            $validated['type'],
            'human'
        );

        return back();
    }

    private function getModel(string $type, int $id)
    {
        return match ($type) {
            'projects' => Project::find($id),
            'work-orders' => WorkOrder::find($id),
            default => null,
        };
    }
}
