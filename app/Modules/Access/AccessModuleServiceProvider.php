<?php

namespace App\Modules\Access;

use App\Providers\BaseModuleServiceProvider;

class AccessModuleServiceProvider extends BaseModuleServiceProvider
{
    protected function modulePath(): string
    {
        return __DIR__;
    }
}
