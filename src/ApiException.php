<?php

namespace Reviu;

class ApiException extends \RuntimeException
{
    private $httpStatus;
    private $apiCode;
    private $details;

    public function __construct($message, $httpStatus = 0, $apiCode = '', array $details = array(), \Throwable $previous = null)
    {
        parent::__construct($message, $httpStatus, $previous);
        $this->httpStatus = (int) $httpStatus;
        $this->apiCode = (string) $apiCode;
        $this->details = $details;
    }

    public function getHttpStatus()
    {
        return $this->httpStatus;
    }

    public function getApiCode()
    {
        return $this->apiCode;
    }

    public function getDetails()
    {
        return $this->details;
    }
}
