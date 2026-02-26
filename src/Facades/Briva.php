<?php

declare(strict_types=1);

namespace RahmatNurjaman99\BrivaOnline\Facades;

use Illuminate\Support\Facades\Facade;
use RahmatNurjaman99\BrivaOnline\Clients\SnapClient;

class Briva extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SnapClient::class;
    }
}
