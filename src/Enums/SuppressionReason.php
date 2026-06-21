<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Enums;

enum SuppressionReason: string
{
    case HardBounce = 'hard_bounce';
    case SoftBounceRepeated = 'soft_bounce_repeated';
    case Complaint = 'complaint';
    case Unsubscribe = 'unsubscribe';
    case Manual = 'manual';
    case InvalidAddress = 'invalid_address';
}
