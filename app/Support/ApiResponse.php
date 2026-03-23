<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(
        mixed $data = null,
        ?string $message = null,
        int $status = 200,
        array $extra = [],
    ): JsonResponse {
        $payload = [];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        if ($data !== null) {
            $payload['data'] = $data;
        }

        return response()->json([
            ...$payload,
            ...$extra,
        ], $status);
    }

    public static function created(
        mixed $data = null,
        ?string $message = null,
        array $extra = [],
    ): JsonResponse {
        return self::success($data, $message, 201, $extra);
    }

    public static function error(
        string $message,
        int $status,
        array $errors = [],
        array $extra = [],
    ): JsonResponse {
        $payload = [
            'message' => $message,
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return response()->json([
            ...$payload,
            ...$extra,
        ], $status);
    }
}
