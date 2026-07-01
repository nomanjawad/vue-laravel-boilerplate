<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Stamp a complete CRUD resource inside a module: model + migration + factory
 * + form requests + admin (and optional public) controller + policy + Inertia
 * pages + route file. Also appends a nav entry + permission block to the
 * module manifest and a PARAMS entry to the smoke tests.
 *
 * Usage:
 *   php artisan make:crud Post --module=Blog --slug --soft-deletes --media --public
 *
 * After running, enable the module from /admin/modules so the routes register
 * and the migration runs.
 */
class MakeCrud extends Command
{
    protected $signature = 'make:crud
                            {name : StudlyCase model name, e.g. Post}
                            {--module= : Module the resource belongs to (StudlyCase)}
                            {--slug : Add a unique slug column and {model:slug} public binding}
                            {--soft-deletes : Add deleted_at + SoftDeletes trait}
                            {--media : Include a media_id foreign key (TODO: wire later)}
                            {--public : Also scaffold a public controller + Show page}';

    protected $description = 'Stamp a CRUD resource inside a module.';

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));
        $module = Str::studly($this->option('module') ?? '');

        if (! $module) {
            $this->error('--module is required (the StudlyCase module name, e.g. --module=Blog).');

            return self::FAILURE;
        }

        $modulePath = app_path("Modules/{$module}");
        if (! is_dir($modulePath)) {
            $this->error("Module [{$module}] not found. Run `php artisan make:module {$module}` first.");

            return self::FAILURE;
        }

        $hasSlug = (bool) $this->option('slug');
        $hasSoftDeletes = (bool) $this->option('soft-deletes');
        $hasPublic = (bool) $this->option('public');

        $singular = Str::lower($name);
        $plural = Str::plural($singular);
        $models = Str::plural($name);                 // PascalCase plural (Post → Posts)
        $resource = Str::kebab($plural);              // posts, case-studies
        $table = Str::snake($plural);                 // posts, case_studies
        $var = Str::camel($singular);                 // post

        $vars = [
            'module' => $module,
            'model' => $name,
            'models' => $models,
            'singular' => $singular,
            'plural' => $plural,
            'resource' => $resource,
            'table' => $table,
            'var' => $var,
            'key' => Str::snake($module),
            'routeKey' => $var,
            'softDeletesImport' => $hasSoftDeletes ? "use Illuminate\\Database\\Eloquent\\SoftDeletes;\n" : '',
            'softDeletesUse' => $hasSoftDeletes ? "    use SoftDeletes;\n" : '',
            'softDeletes' => $hasSoftDeletes ? '            $table->softDeletes();' : '',
            'slugColumn' => $hasSlug ? "            \$table->string('slug', 191)->unique();" : '',
            'slugRule' => $hasSlug ? "            'slug' => ['required', 'string', 'max:191', 'unique:{$table},slug'],\n" : '',
            'slugUpdateRule' => $hasSlug
                ? "            'slug' => ['required', 'string', 'max:191', \\Illuminate\\Validation\\Rule::unique('{$table}', 'slug')->ignore(\$id)],\n"
                : '',
            'slugDefinition' => $hasSlug ? "            'slug' => Str::slug(\$title).'-'.\$this->faker->randomNumber(4)," : '',
            'slugFormField' => $hasSlug ? "    slug: ''," : '',
            'slugFormFieldExisting' => $hasSlug ? "    slug: props.{$var}.slug," : '',
            'slugFormHtml' => $hasSlug
                ? "        <div>\n            <label class=\"block text-sm font-medium text-gray-700\">Slug</label>\n            <input v-model=\"form.slug\" type=\"text\" class=\"mt-1 w-full rounded border border-gray-300 px-3 py-2\">\n            <p v-if=\"form.errors.slug\" class=\"mt-1 text-xs text-rose-600\">{{ form.errors.slug }}</p>\n        </div>"
                : '',
            'casts' => "        'is_active' => 'bool',\n        'published_at' => 'datetime',",
        ];

        // Files to stamp.
        $writes = [
            "Models/{$name}.php" => 'crud/model.stub',
            "Database/Migrations/".date('Y_m_d_His')."_create_{$table}_table.php" => 'crud/migration.stub',
            "Database/Factories/{$name}Factory.php" => 'crud/factory.stub',
            "Http/Requests/Store{$name}Request.php" => 'crud/request.store.stub',
            "Http/Requests/Update{$name}Request.php" => 'crud/request.update.stub',
            "Policies/{$name}Policy.php" => 'crud/policy.stub',
            "Http/Controllers/Admin/{$name}Controller.php" => 'crud/controller.admin.stub',
            "Resources/js/Pages/Admin/{$models}/Index.vue" => 'crud/page.index.vue.stub',
            "Resources/js/Pages/Admin/{$models}/Create.vue" => 'crud/page.create.vue.stub',
            "Resources/js/Pages/Admin/{$models}/Edit.vue" => 'crud/page.edit.vue.stub',
            "Routes/{$resource}.php" => 'crud/routes.stub',
        ];

        if ($hasPublic) {
            $writes["Http/Controllers/Public/{$name}Controller.php"] = 'crud/controller.public.stub';
        }

        foreach ($writes as $rel => $stub) {
            $dest = "{$modulePath}/{$rel}";
            if (file_exists($dest)) {
                $this->warn("skip (exists)  {$rel}");

                continue;
            }
            $dir = dirname($dest);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($dest, $this->stub($stub, $vars));
            $this->line("created        {$rel}");
        }

        // Append nav + permissions to manifest.
        $this->patchManifest($modulePath, $name, $resource, $hasPublic);

        // Append a PARAMS entry to the smoke test if --public so the
        // safety net catches a missing seed / wrong slug binding.
        if ($hasPublic) {
            $this->patchSmokeTest($var, $hasSlug ? Str::slug($singular).'-example' : '1');
        }

        $this->line('');
        $this->info("CRUD for {$name} stamped in module {$module}.");
        $this->line('Next:');
        $this->line('  1. Toggle the module on from /admin/modules to run migrations + register routes.');
        $this->line('  2. Update the manifest if you need extra permissions or nav icons.');

        return self::SUCCESS;
    }

    protected function patchManifest(string $modulePath, string $name, string $resource, bool $public): void
    {
        $manifestPath = "{$modulePath}/module.php";
        if (! file_exists($manifestPath)) {
            return;
        }
        $contents = file_get_contents($manifestPath);

        // Permissions and route names use the plural resource key ({resource}.view)
        // — matches the routes stub's `can:{{resource}}.view` middleware and the
        // existing v2 modules in config/modules.php.
        $permLine = "        '{$resource}' => ['view', 'create', 'update', 'delete'],";
        if (! str_contains($contents, "'{$resource}' =>")) {
            $contents = $this->appendToArrayBlock($contents, 'permissions', $permLine);
        }

        $models = Str::plural($name);
        $navLine = "        ['label' => '{$models}', 'route' => 'admin.{$resource}.index', 'icon' => 'cube', 'permission' => '{$resource}.view'],";
        if (! str_contains($contents, "'admin.{$resource}.index'")) {
            $contents = $this->appendToArrayBlock($contents, 'nav', $navLine);
        }

        file_put_contents($manifestPath, $contents);
    }

    /**
     * Insert $line just before the matching closing bracket of the named
     * top-level array in $contents. Walks brackets so that nested arrays
     * (e.g. an already-stamped permissions block containing `['view', ...],`)
     * don't confuse the closing anchor — the previous non-greedy regex
     * approach broke on the second stamp for exactly that reason.
     */
    protected function appendToArrayBlock(string $contents, string $key, string $line): string
    {
        if (! preg_match("/'{$key}'\\s*=>\\s*\\[/", $contents, $m, PREG_OFFSET_CAPTURE)) {
            return $contents;
        }
        $i = $m[0][1] + strlen($m[0][0]);
        $depth = 1;
        $len = strlen($contents);
        while ($i < $len && $depth > 0) {
            $ch = $contents[$i];
            if ($ch === '[') {
                $depth++;
            } elseif ($ch === ']') {
                $depth--;
                if ($depth === 0) {
                    break;
                }
            }
            $i++;
        }
        if ($depth !== 0) {
            return $contents;
        }

        return substr($contents, 0, $i)."{$line}\n    ".substr($contents, $i);
    }

    protected function patchSmokeTest(string $paramKey, string $value): void
    {
        $path = base_path('tests/Feature/PublicRoutesSmokeTest.php');
        if (! file_exists($path)) {
            return;
        }
        $contents = file_get_contents($path);
        if (str_contains($contents, "'{$paramKey}' =>")) {
            return;
        }
        // Inject right before the closing ']; that ends the PARAMS const.
        $contents = preg_replace(
            "/(private const PARAMS = \\[[\\s\\S]*?)(\\];)/",
            "$1        '{$paramKey}' => '{$value}',\n    $2",
            $contents,
            1,
        );
        file_put_contents($path, $contents);
        $this->line("patched        tests/Feature/PublicRoutesSmokeTest.php (PARAMS['{$paramKey}'])");
    }

    protected function stub(string $relative, array $vars): string
    {
        $path = base_path("stubs/{$relative}");
        $content = file_get_contents($path);
        foreach ($vars as $k => $v) {
            $content = str_replace("{{{$k}}}", $v, $content);
        }

        return $content;
    }
}
