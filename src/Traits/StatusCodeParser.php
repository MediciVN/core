<?php

namespace MediciVN\Core\Traits;

use MediciVN\Core\Exceptions\MediciException;
use MediciVN\Core\Utilities\ResponseStatus;

trait StatusCodeParser
{
    /**
     * Get HTTP status and application code from response status code
     *
     * @param string $code
     * @return array
     * @throws MediciException
     */
	public function parseStatusCode(string $code): array
	{
		$bagCode = explode('|', $code);

        /**
         * @TODO we need verify once again the HTTP status code whether it is valid or not
         */
        if (count($bagCode) > 2) {
            throw new MediciException(ResponseStatus::INVALID_STATUS_CODE);
        }

        if (count($bagCode) == 1) {
            return ['500', '1000'];
        }

		return collect($bagCode)->map(fn($i) => (int) $i)->all();
	}

    public function getAppCode(string $code): int
    {
        return (int) $this->parseStatusCode($code)[1];
    }
}
