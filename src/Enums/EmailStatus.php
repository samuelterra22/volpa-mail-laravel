<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Enums;

enum EmailStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Deferred = 'deferred';
    case Bounced = 'bounced';
    case Complained = 'complained';
    case Failed = 'failed';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Delivered, self::Bounced, self::Failed, self::Complained], true);
    }
}
