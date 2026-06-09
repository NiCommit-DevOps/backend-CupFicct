<?php

use App\Providers\AppServiceProvider;
use App\Modules\Access\AccessModuleServiceProvider;
use App\Modules\Administrative\AdministrativeModuleServiceProvider;
use App\Modules\Academics\AcademicsModuleServiceProvider;
use App\Modules\Exams\ExamsModuleServiceProvider;
use App\Modules\Registration\RegistrationModuleServiceProvider;

return [
    AppServiceProvider::class,
    AccessModuleServiceProvider::class,
    AdministrativeModuleServiceProvider::class,
    AcademicsModuleServiceProvider::class,
    ExamsModuleServiceProvider::class,
    RegistrationModuleServiceProvider::class,
];
