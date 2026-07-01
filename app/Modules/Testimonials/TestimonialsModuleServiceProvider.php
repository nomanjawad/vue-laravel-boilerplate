<?php

namespace App\Modules\Testimonials;

use App\Modules\Core\AbstractModuleServiceProvider;

class TestimonialsModuleServiceProvider extends AbstractModuleServiceProvider
{
    public string $key = 'testimonials';
    public string $name = 'Testimonials';
    public string $version = '1.0.0';
    public array $dependencies = [];

    public string $manifestPath = __DIR__ . '/module.php';
}
