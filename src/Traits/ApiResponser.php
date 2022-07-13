<?php

namespace MediciVN\Core\Traits;

use App\Utilities\ResponseStatus;
use Illuminate\Http\JsonResponse;

trait ApiResponser
{
    use StatusCodeParser;

    /**
     * @param mixed $data
     *
     * @return JsonResponse
     */
    public function successResponse(mixed $data = []): JsonResponse
    {
        $response = [
            'success' => true,
            'data' => $data,
        ];

        return response()->json($response);
    }

    /**
     * @param int $current
     * @param int $total
     * @param array $data
     *
     * @return JsonResponse
     */
    public function responsePagination(int $current, int $total, array $data): JsonResponse
    {
        $response = [
            'success' => true,
            'pagination' => [
                'current' => $current,
                'total' => $total,
            ],
            'data' => $data
        ];

        return response()->json($response);
    }

    /**
     * Render an error response for a request
     *
     * @param string $code
     * @param string $message
     * @param array $errorDetail
     *
     * @return JsonResponse
     */
    public function errorResponse(string $code, string $message = '', array $errorDetail = []): JsonResponse
    {
        $codeBag = $this->parseStatusCode($code);

        $message = $message ?: ResponseStatus::messagesBag($code);

        $error = [
            'code' => $codeBag[1],
            'message' => $message
        ];

        $response = [
            'success' => false,
            'data' => null,
            'error' => empty($errorDetail) ? $error : array_merge($error, $errorDetail)
        ];

        return response()->json($response, $codeBag[0]);
    }

    /**
     * @param CursorPaginator $paginator
     * @return JsonResponse
     */
    public function responseCursorPagination(CursorPaginator $paginator): JsonResponse
    {
        $response = [
            'success' => true,
            'next_cursor' => $paginator->nextCursor()->encode(),
            'prev_cursor' => $paginator->previousCursor()->encode(),
            'data' => $paginator->items(),
        ];

        return response()->json($response);
    }
}
