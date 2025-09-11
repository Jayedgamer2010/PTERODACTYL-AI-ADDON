<?php

namespace Blueprint\Extensions\AIAssistant\Repositories;

use Blueprint\Extensions\AIAssistant\Models\AIChatHistory;
use Blueprint\Extensions\AIAssistant\Contracts\Repositories\ChatHistoryRepositoryInterface;
use Pterodactyl\Repositories\Repository;

class ChatHistoryRepository extends Repository implements ChatHistoryRepositoryInterface
{
    protected $model = AIChatHistory::class;

    /**
     * Store a new chat message and response.
     */
    public function store(array $data): AIChatHistory
    {
        return $this->model->newInstance()->create($data);
    }

    /**
     * Get chat history for a user.
     */
    public function getUserHistory(int $userId, ?int $limit = null)
    {
        $query = $this->model->newQuery()
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get chat history for a server.
     */
    public function getServerHistory(int $serverId, ?int $limit = null)
    {
        $query = $this->model->newQuery()
            ->where('server_id', $serverId)
            ->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }
}
