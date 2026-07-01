<?php

namespace Tests\Feature;

use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * Modules must stay self-contained: a Vue file inside app/Modules/{X}/Resources/js
 * may import from Atoms/Molecules/Organisms and Composables, but never from
 * another module's Resources folder. Cross-module imports re-couple what the
 * module architecture is designed to isolate.
 *
 * The check is a plain grep so it stays fast and needs no build step.
 */
class NoCrossModuleImportsTest extends TestCase
{
    public function test_module_vue_files_never_import_from_another_module(): void
    {
        $modulesRoot = base_path('app/Modules');
        if (! is_dir($modulesRoot)) {
            $this->assertTrue(true, 'No modules folder yet.');

            return;
        }

        $moduleDirs = array_values(array_filter(
            scandir($modulesRoot) ?: [],
            fn (string $entry) => $entry !== '.'
                && $entry !== '..'
                && is_dir($modulesRoot.DIRECTORY_SEPARATOR.$entry)
        ));

        // Core is the always-on shell — it's allowed to be referenced.
        $otherModules = array_values(array_filter(
            $moduleDirs,
            fn (string $m) => $m !== 'Core'
        ));

        $violations = [];

        foreach ($moduleDirs as $moduleName) {
            $jsRoot = $modulesRoot.DIRECTORY_SEPARATOR.$moduleName.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'js';
            if (! is_dir($jsRoot)) {
                continue;
            }

            $finder = (new Finder)->files()->in($jsRoot)->name(['*.vue', '*.ts', '*.js']);

            foreach ($finder as $file) {
                $contents = $file->getContents();
                foreach ($otherModules as $other) {
                    if ($other === $moduleName) {
                        continue;
                    }

                    // Match `@modules/Other/...` alias or a literal path back into
                    // another module folder. Both are cross-module imports.
                    $aliasRegex = '#@modules/'.preg_quote($other, '#').'/#';
                    $literalRegex = '#app/Modules/'.preg_quote($other, '#').'/Resources#';

                    if (preg_match($aliasRegex, $contents) || preg_match($literalRegex, $contents)) {
                        $violations[] = sprintf(
                            '%s imports from module [%s]',
                            str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getRealPath()),
                            $other,
                        );
                    }
                }
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Cross-module Vue/TS imports are not allowed:\n".implode("\n", $violations),
        );
    }
}
