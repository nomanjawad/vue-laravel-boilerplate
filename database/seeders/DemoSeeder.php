<?php

namespace Database\Seeders;

use App\Models\Career;
use App\Models\CaseStudy;
use App\Models\Category;
use App\Models\Post;
use App\Models\Team;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Realistic demo content so a fresh project looks finished from day one —
 * ideal for client demos. Placeholder images are generated locally with GD
 * (no network needed). Run via `php artisan template:init` or:
 *
 *   php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $userId = User::orderBy('id')->value('id');

        Category::uncategorized();

        $categories = collect(['Insights', 'Guides', 'Company News'])->map(
            fn ($name) => Category::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name])
        );

        $posts = [
            ['Five Ways to Improve Your Website Performance', 'Practical, low-effort changes — from image compression to caching — that make a measurable difference to load times.'],
            ['Why Every Business Needs a Content Strategy', 'Publishing without a plan wastes effort. Here is a simple framework for deciding what to write and when.'],
            ['Behind the Scenes: How We Build Client Sites', 'A walkthrough of our process from kickoff to launch, and why each step exists.'],
            ['The Complete Guide to Local SEO', 'Everything a small business needs to rank in local search results, explained without jargon.'],
            ['Announcing Our New Service Offerings', 'We are expanding what we offer. Here is what is new and what it means for our clients.'],
            ['How to Choose the Right Hosting Plan', 'Shared, VPS, or managed? A plain-English comparison to help you pick what actually fits.'],
        ];

        foreach ($posts as $i => [$title, $excerpt]) {
            $post = Post::firstOrCreate(['slug' => Str::slug($title)], [
                'user_id' => $userId,
                'title' => $title,
                'excerpt' => $excerpt,
                'body' => $this->demoBody($excerpt),
                'featured_image' => $this->placeholderImage(Str::slug($title), 1200, 630, $i),
                'status' => 'published',
                'published_at' => now()->subDays(3 + $i * 4),
            ]);
            if ($post->categories()->count() === 0) {
                $post->categories()->sync([$categories[$i % 3]->id]);
            }
        }

        $team = [
            ['Alex Morgan', 'Founder & Creative Director'],
            ['Sam Patel', 'Lead Developer'],
            ['Jordan Lee', 'Design Lead'],
            ['Casey Nguyen', 'Project Manager'],
        ];

        foreach ($team as $i => [$name, $position]) {
            Team::firstOrCreate(['name' => $name], [
                'position' => $position,
                'bio' => "{$name} has spent the last decade building digital products for businesses of every size.",
                'photo' => $this->placeholderImage('team-'.Str::slug($name), 600, 600, $i + 20),
                'is_active' => true,
                'sort_order' => $i,
            ]);
        }

        foreach ([
            ['Senior Laravel Developer', 'Engineering', 'Remote', 'full-time'],
            ['Content Marketing Specialist', 'Marketing', 'Sydney', 'part-time'],
        ] as $i => [$title, $department, $location, $type]) {
            Career::firstOrCreate(['slug' => Str::slug($title)], [
                'title' => $title,
                'department' => $department,
                'location' => $location,
                'type' => $type,
                'description' => '<p>Join a small, senior team shipping client work you can be proud of.</p>',
                'requirements' => '<ul><li>3+ years relevant experience</li><li>Strong communication skills</li></ul>',
                'is_active' => true,
                'sort_order' => $i,
            ]);
        }

        foreach ([
            ['Rebuilding SkyTech\'s Online Presence', 'SkyTech', 'A ground-up rebuild that doubled organic traffic in four months.'],
            ['Launching Maison Oshi\'s Online Store', 'Maison Oshi', 'From zero to a fully stocked e-commerce store in six weeks.'],
        ] as $i => [$title, $client, $excerpt]) {
            CaseStudy::firstOrCreate(['slug' => Str::slug($title)], [
                'title' => $title,
                'client_name' => $client,
                'excerpt' => $excerpt,
                'body' => $this->demoBody($excerpt),
                'featured_image' => $this->placeholderImage('case-'.Str::slug($title), 1200, 630, $i + 30),
                'results' => ['Traffic' => '+105%', 'Conversion rate' => '+38%', 'Page speed' => '0.9s LCP'],
                'is_active' => true,
                'sort_order' => $i,
            ]);
        }

        $this->command?->info('Demo content seeded.');
    }

    private function demoBody(string $lead): string
    {
        return "<p><strong>{$lead}</strong></p>"
            .'<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante venenatis dapibus posuere velit aliquet. Cras mattis consectetur purus sit amet fermentum.</p>'
            .'<h2>The details</h2>'
            .'<p>Donec ullamcorper nulla non metus auctor fringilla. Vestibulum id ligula porta felis euismod semper. Praesent commodo cursus magna, vel scelerisque nisl consectetur et.</p>'
            .'<p>Maecenas faucibus mollis interdum. Nullam quis risus eget urna mollis ornare vel eu leo.</p>';
    }

    /**
     * Generate a flat-colour placeholder with GD and import it through
     * MediaService so demo content creates real Media rows (no parallel
     * storage/demo/ namespace).
     */
    private function placeholderImage(string $name, int $width, int $height, int $seed): string
    {
        $img = imagecreatetruecolor($width, $height);
        $palette = [[79, 70, 229], [16, 185, 129], [245, 158, 11], [239, 68, 68], [59, 130, 246], [168, 85, 247]];
        [$r, $g, $b] = $palette[$seed % count($palette)];
        imagefill($img, 0, 0, imagecolorallocate($img, $r, $g, $b));

        // Subtle diagonal band for texture.
        $overlay = imagecolorallocatealpha($img, 255, 255, 255, 100);
        imagefilledpolygon($img, [0, $height, (int) ($width * 0.6), 0, $width, 0, $width, $height], $overlay);

        ob_start();
        imagepng($img);
        $contents = (string) ob_get_clean();

        $media = app(MediaService::class)->importFromContents($contents, "{$name}.png", User::orderBy('id')->value('id'));

        return $media->url;
    }
}
