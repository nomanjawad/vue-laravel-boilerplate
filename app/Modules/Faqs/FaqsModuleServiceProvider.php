<?php

namespace App\Modules\Faqs;

use App\Modules\Core\AbstractModuleServiceProvider;

class FaqsModuleServiceProvider extends AbstractModuleServiceProvider
{
    public string $key = 'faqs';
    public string $name = 'Faqs';
    public string $version = '1.0.0';
    public array $dependencies = [];

    public string $manifestPath = __DIR__ . '/module.php';
}
