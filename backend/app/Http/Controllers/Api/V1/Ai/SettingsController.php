<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Http\Controllers\Controller;
use App\Services\AI\AiManager;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * The AI "mode" control: which provider OSEP AI is talking to. Switching to a
 * live model requires that provider's API key to be configured - otherwise the
 * option is offered but disabled.
 */
class SettingsController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AiManager $ai) {}

    public function show(): JsonResponse
    {
        return $this->success($this->payload());
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'driver' => ['required', 'in:' . implode(',', array_keys(AiManager::DRIVERS))],
        ]);

        try {
            $this->ai->setDriver($data['driver']);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['driver' => $e->getMessage()]);
        }

        return $this->success(
            $this->payload(),
            $this->ai->isLive() ? 'OSEP AI is now in live mode.' : 'OSEP AI is now using the offline engine.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'driver' => $this->ai->driverKey(),
            'is_live' => $this->ai->isLive(),
            'assistant_name' => config('ai.assistant_name', 'OSEP AI'),
            'options' => $this->ai->options(),
        ];
    }
}
