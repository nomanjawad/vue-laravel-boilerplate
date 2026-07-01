<?php

namespace App\Modules\Events;

use App\Modules\Core\AbstractModuleServiceProvider;

class EventsModuleServiceProvider extends AbstractModuleServiceProvider
{
    public string $key = 'events';
    public string $name = 'Events';
    public string $version = '1.0.0';
    public array $dependencies = [];

    public string $manifestPath = __DIR__ . '/module.php';
}
