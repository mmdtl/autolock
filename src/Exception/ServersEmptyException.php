<?php
namespace autolock\src\Exception;

use \Exception;

/**
 * Exception when greater or equal to half number of servers is invalid
 */
class ServersEmptyException extends ServersBaseException
{
}