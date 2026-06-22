<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Enums;

enum ValidationReason: string
{
    case InvalidFormat = 'invalid_format';
    case Disposable = 'disposable';
    case NoMx = 'no_mx';
    case Suppressed = 'suppressed';
}
