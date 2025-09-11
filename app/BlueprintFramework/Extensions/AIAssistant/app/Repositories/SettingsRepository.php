<?php

namespace Blueprint\Extensions\AIAssistant\Repositories;

use Blueprint\Extensions\AIAssistant\Models\AISetting;
use Blueprint\Extensions\AIAssistant\Contracts\Repositories\SettingsRepositoryInterface;
use Pterodactyl\Repositories\Repository;

class SettingsRepository extends Repository implements SettingsRepositoryInterface
{
    protected $model = AISetting::class;

    /**
     * Get a setting value by key.
     */
    public function get(string $key, $default = null)
    {
        return AISetting::getSetting($key, $default);
    }

    /**
     * Set a setting value.
     */
    public function set(string $key, $value, ?string $description = null): AISetting
    {
        return AISetting::setSetting($key, $value, $description);
    }

    /**
     * Get all settings.
     */
    public function all()
    {
        return $this->model->all()->mapWithKeys(function ($item) {
            return [$item->key => $item->value];
        });
    }
}
