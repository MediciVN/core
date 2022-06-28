<?php

namespace MediciVN\Core\Contracts;

use Throwable;
use Symfony\Component\HttpFoundation\JsonResponse;

interface ShouldRenderExceptionRes
{
    public function renderExceptionResponse($e): JsonResponse;
}