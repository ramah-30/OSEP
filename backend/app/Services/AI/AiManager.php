<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\AiProvider;
use App\Services\AI\Providers\AnthropicProvider;
use App\Services\AI\Providers\LocalProvider;
use App\Services\AI\Providers\OpenAiProvider;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

/**
 * Resolves the active AI provider. The driver can be switched at runtime from the
 * in-app "mode" toggle (stored in cache) and otherwise falls back to
 * config/ai.php → AI_DRIVER. The whole platform talks to the LLM only through
 * here, so flipping between the offline engine and a hosted model is one call.
 */
class AiManager
{
    private const OVERRIDE_KEY = 'ai.driver.override';

    /** Drivers the platform knows how to build, with their display labels. */
    public const DRIVERS = [
        'local' => 'Offline engine',
        'anthropic' => 'Claude (Anthropic)',
        'openai' => 'OpenAI',
    ];

    private ?AiProvider $resolved = null;

    public function provider(): AiProvider
    {
        return $this->resolved ??= $this->make($this->driverKey());
    }

    /** The active driver key (runtime override, else config default). */
    public function driverKey(): string
    {
        $override = Cache::get(self::OVERRIDE_KEY);

        if (is_string($override) && $this->isConfigured($override)) {
            return $override;
        }

        return config('ai.driver', 'local');
    }

    /** The active provider's own reported name. */
    public function driver(): string
    {
        return $this->provider()->name();
    }

    /** True when a real hosted model is answering (vs the offline engine). */
    public function isLive(): bool
    {
        return $this->driverKey() !== 'local';
    }

    /**
     * Switch the active driver at runtime. Only 'local' or a driver whose API key
     * is configured is allowed — you cannot go live without a key.
     */
    public function setDriver(string $driver): void
    {
        if (! array_key_exists($driver, self::DRIVERS)) {
            throw new InvalidArgumentException("Unknown AI driver [{$driver}].");
        }

        if (! $this->isConfigured($driver)) {
            throw new InvalidArgumentException("The [{$driver}] provider has no API key configured.");
        }

        Cache::forever(self::OVERRIDE_KEY, $driver);
        $this->resolved = null;
    }

    /** Whether a driver is usable (local always is; hosted needs a key). */
    public function isConfigured(string $driver): bool
    {
        if ($driver === 'local') {
            return true;
        }

        return ! empty(config("ai.providers.{$driver}.key"));
    }

    /**
     * The drivers for the mode picker: value, label, whether it's ready to use,
     * and whether it's the active one.
     *
     * @return array<int, array{value:string, label:string, configured:bool, active:bool}>
     */
    public function options(): array
    {
        $active = $this->driverKey();

        return array_map(fn ($key, $label) => [
            'value' => $key,
            'label' => $label,
            'configured' => $this->isConfigured($key),
            'active' => $key === $active,
        ], array_keys(self::DRIVERS), array_values(self::DRIVERS));
    }

    private function make(string $driver): AiProvider
    {
        return match ($driver) {
            'local' => new LocalProvider(),
            'anthropic' => new AnthropicProvider(),
            'openai' => new OpenAiProvider(),
            default => throw new InvalidArgumentException("Unknown AI driver [{$driver}]."),
        };
    }
}
