<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Enums;

enum EmailStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Scheduled = 'scheduled';
    case Processing = 'processing';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Opened = 'opened';
    case Clicked = 'clicked';
    case Deferred = 'deferred';
    case Bounced = 'bounced';
    case SoftBounced = 'soft_bounced';
    case Complained = 'complained';
    case Rejected = 'rejected';
    case Failed = 'failed';
    case Canceled = 'canceled';

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Delivered,
            self::Bounced,
            self::Complained,
            self::Rejected,
            self::Failed,
            self::Canceled,
        ], true);
    }
}
