<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Enums;

enum BroadcastStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Sending = 'sending';
    case Sent = 'sent';
    case Canceled = 'canceled';
    case Failed = 'failed';

    /**
     * Whether the broadcast has reached a terminal state and can no longer be modified.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::Sent, self::Canceled, self::Failed], true);
    }
}
