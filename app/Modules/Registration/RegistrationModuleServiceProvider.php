<?php

namespace App\Modules\Registration;

use App\Providers\BaseModuleServiceProvider;

class RegistrationModuleServiceProvider extends BaseModuleServiceProvider
{
    protected function modulePath(): string
    {
        return __DIR__;
    }
}
