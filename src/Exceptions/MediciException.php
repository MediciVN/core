<?php

namespace MediciVN\Core\Exceptions;

use MediciVN\Core\Traits\ApiResponser;
use MediciVN\Core\Traits\StatusCodeParser;
use App\Utilities\ResponseStatus;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediciException extends Exception
{
    use ApiResponser, StatusCodeParser;

    protected $message;

    protected $code;

    protected array $codeBag;

    public array $errorDetail;

    /**
     * @throws MediciException
     */
    public function __construct(string $code, $message = '', array $errorDetail = [])
    {
        $this->code = $code;

        $this->codeBag = $this->parseStatusCode($this->code);

        $this->message = $message ?: ResponseStatus::messagesBag($code);

        $this->errorDetail = $errorDetail;

        parent::__construct($this->message);
    }

        /**
     * Render the exception into an HTTP response.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function render(Request $request): JsonResponse
    {
        return $this->errorResponse($this->code, $this->message);
    }

    /**
     * Report the exception.
     *
     * @return void
     */
    public function report(): void
    {
        Log::emergency($this->message);
    }

}
