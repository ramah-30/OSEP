<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Every endpoint answers with the same envelope so the SPA only ever has to
 * parse one shape:
 *
 *   { "success": bool, "message": string, "data": mixed|null, "errors": object|null }
 */
trait ApiResponse
{
    protected function success(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
        ], $status);
    }

    protected function created(mixed $data = null, string $message = 'Created'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    /**
     * @param  array<string, array<int, string>>|null  $errors
     */
    protected function error(string $message, ?array $errors = null, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ], $status);
    }
}
