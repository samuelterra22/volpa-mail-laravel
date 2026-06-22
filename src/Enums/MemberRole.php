<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Enums;

enum MemberRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Developer = 'developer';
    case Viewer = 'viewer';
}
