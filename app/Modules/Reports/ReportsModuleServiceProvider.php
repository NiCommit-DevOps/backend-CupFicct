<?php

namespace App\Modules\Reports;

use App\Providers\BaseModuleServiceProvider;

class ReportsModuleServiceProvider extends BaseModuleServiceProvider
{
    protected function modulePath(): string
    {
        return __DIR__;
    }
}
