<?php

declare(strict_types=1);

namespace IdentifyOrg;

class IdentifyOrgApiError extends \RuntimeException
{
    public int $status;
    public ?string $apiCode;

    public function __construct(int $status, ?string $apiCode, string $message)
    {
        $this->status = $status;
        $this->apiCode = $apiCode;
        parent::__construct(sprintf('IdentifyOrg API error (%s): %s', $apiCode ?? (string) $status, $message));
    }
}
