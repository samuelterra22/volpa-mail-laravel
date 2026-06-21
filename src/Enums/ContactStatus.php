<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Enums;

enum ContactStatus: string
{
    case Active = 'active';
    case Unsubscribed = 'unsubscribed';
    case Bounced = 'bounced';
    case Complained = 'complained';
}
