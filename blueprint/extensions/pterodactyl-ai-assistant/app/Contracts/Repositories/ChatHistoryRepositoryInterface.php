<?php

namespace Blueprint\Extensions\AIAssistant\Contracts\Repositories;

use Blueprint\Extensions\AIAssistant\Models\AIChatHistory;

interface ChatHistoryRepositoryInterface
{
    public function store(array $data): AIChatHistory;
    public function getUserHistory(int $userId, ?int $limit = null);
    public function getServerHistory(int $serverId, ?int $limit = null);
}
