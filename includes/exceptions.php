<?php

/**
 * Thrown when a request breaks a business rule, for example a duplicate
 * account. Controllers show the message to the user. Every other failure is
 * unexpected: it gets logged and the user sees a generic message.
 */
class ValidationException extends Exception
{
}
