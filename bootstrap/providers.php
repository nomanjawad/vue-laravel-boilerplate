<?php

use App\Providers\AppServiceProvider;
use App\Providers\ModulesServiceProvider;
use App\Providers\TypeScriptTransformerServiceProvider;

return [
    AppServiceProvider::class,
    ModulesServiceProvider::class,
    TypeScriptTransformerServiceProvider::class,
];
