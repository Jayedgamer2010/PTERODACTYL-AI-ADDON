<?php

namespace Blueprint\Extensions\AIAssistant\Contracts\Repositories;

use Blueprint\Extensions\AIAssistant\Models\AISetting;

interface SettingsRepositoryInterface
{
    public function get(string $key, $default = null);
    public function set(string $key, $value, ?string $description = null): AISetting;
    public function all();
}
