<?php

namespace MxcDropshipInnocigs\Exception;

use RuntimeException;

class ApiException extends RuntimeException {

    public static function fromInvalidCredentials($msg = null) {
        $msg = $msg ?? "Invalid credentials supplied.";
        return new self($msg);
    }
}