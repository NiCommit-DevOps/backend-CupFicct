<?php

namespace App\Modules\Exams;

use App\Providers\BaseModuleServiceProvider;

class ExamsModuleServiceProvider extends BaseModuleServiceProvider
{
    protected function modulePath(): string
    {
        return __DIR__;
    }
}
