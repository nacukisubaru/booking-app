<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a booking cannot be completed because the room type
 * is not available for the requested dates (e.g. zero quota).
 */
class BookingConflictException extends RuntimeException {}
