<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Emit resources/js/types/widgets.d.ts from config/widgets.php so Vue
 * components stay aligned with the admin registry (feedback.md F10).
 *
 * Run via `php artisan widgets:types` or automatically after
 * `typescript:transform` (composer ide / composer deploy).
 */
class GenerateWidgetTypes extends Command
{
    protected $signature = 'widgets:types';

    protected $description = 'Generate resources/js/types/widgets.d.ts from config/widgets.php';

    public function handle(): int
    {
        $registry = config('widgets', []);
        if (! is_array($registry) || $registry === []) {
            $this->error('config/widgets.php is empty or missing.');

            return self::FAILURE;
        }

        $interfaces = [];
        $typeNames = [];

        foreach ($registry as $key => $def) {
            if (! is_array($def)) {
                continue;
            }
            $typeKey = is_string($def['key'] ?? null) ? $def['key'] : (string) $key;
            $pascal = Str::studly($typeKey);
            $iface = "Widget{$pascal}Data";
            $typeNames[$typeKey] = $iface;
            $fields = is_array($def['fields'] ?? null) ? $def['fields'] : [];
            $props = [];
            foreach ($fields as $field) {
                if (! is_array($field) || ! is_string($field['key'] ?? null)) {
                    continue;
                }
                $props[] = '    '.($field['key']).'?: '.$this->tsTypeForField($field).';';
            }
            $body = $props === [] ? '' : "\n".implode("\n", $props)."\n";
            $interfaces[] = "export interface {$iface} {{$body}}";
        }

        $unionMembers = [];
        foreach ($typeNames as $typeKey => $iface) {
            $unionMembers[] = "    | { id: string; type: '{$typeKey}'; visible?: boolean; data?: {$iface} }";
        }

        $typeUnion = implode("\n", array_map(
            fn ($k, $i) => "    '{$k}': {$i}",
            array_keys($typeNames),
            array_values($typeNames),
        ));

        $out = <<<TS
/**
 * AUTO-GENERATED from config/widgets.php — do not edit by hand.
 * Regenerate: `php artisan widgets:types` (also runs after typescript:transform).
 */

/** Media-library payload accepted by AppImage (string URL still allowed). */
export type WidgetImage =
    | string
    | {
          url?: string | null
          variants?: Record<string, string | { path?: string; width?: number; height?: number } | null> | null
          width?: number | null
          height?: number | null
          alt_text?: string | null
      }
    | null

/** Collection source block on dynamic widgets. */
export interface WidgetCollection {
    mode?: string
    ids?: Array<number | string>
    limit?: number
    page_slug?: string | null
}

{$this->joinInterfaces($interfaces)}

export type WidgetDataByType = {
{$typeUnion}
}

export type PageWidget =
{$this->joinUnion($unionMembers)}

export type WidgetType = keyof WidgetDataByType

TS;

        $path = resource_path('js/types/widgets.d.ts');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        file_put_contents($path, $out);
        $this->info('Wrote '.$path.' ('.count($typeNames).' widget types)');

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $interfaces
     */
    private function joinInterfaces(array $interfaces): string
    {
        return implode("\n\n", $interfaces);
    }

    /**
     * @param  list<string>  $members
     */
    private function joinUnion(array $members): string
    {
        return implode("\n", $members);
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function tsTypeForField(array $field): string
    {
        $type = (string) ($field['type'] ?? 'text');

        return match ($type) {
            'boolean' => 'boolean',
            'number' => 'number',
            'image' => 'WidgetImage',
            'collection' => 'WidgetCollection',
            'repeater' => $this->tsTypeForRepeater($field),
            'textarea', 'richtext', 'text', 'link', 'select' => 'string',
            default => 'unknown',
        };
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function tsTypeForRepeater(array $field): string
    {
        $itemFields = is_array($field['item_fields'] ?? null) ? $field['item_fields'] : [];
        if ($itemFields === []) {
            return 'Array<Record<string, unknown>>';
        }

        $props = [];
        foreach ($itemFields as $item) {
            if (! is_array($item) || ! is_string($item['key'] ?? null)) {
                continue;
            }
            $props[] = ($item['key']).'?: '.$this->tsTypeForField($item);
        }

        return 'Array<{ '.implode('; ', $props).' }>';
    }
}
