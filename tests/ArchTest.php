<?php

declare(strict_types=1);

arch('sem debug solto')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();

arch('DTOs são readonly e finais')
    ->expect('SamuelTerra\VolpaMail\Data')
    ->toBeFinal()
    ->toBeReadonly();

arch('Enums em namespace próprio')
    ->expect('SamuelTerra\VolpaMail\Enums')
    ->toBeEnums();

arch('Exceptions estendem RuntimeException')
    ->expect('SamuelTerra\VolpaMail\Exceptions')
    ->toExtend(RuntimeException::class);

arch('strict types em todo o pacote')
    ->expect('SamuelTerra\VolpaMail')
    ->toUseStrictTypes();
