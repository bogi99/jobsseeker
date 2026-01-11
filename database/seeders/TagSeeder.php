<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        /**
         * @var array<array{name: string, description: string}>
         */
        $tags = [
            [
                'name' => 'Remote',
                'description' => 'Fully remote roles that can be executed from anywhere.',
            ],
            [
                'name' => 'In Office',
                'description' => 'Traditional on-site positions requiring daily office presence.',
            ],
            [
                'name' => 'Hybrid',
                'description' => 'A mix of remote work with scheduled office days.',
            ],
            [
                'name' => 'PHP',
                'description' => 'Backend development opportunities focused on PHP.',
            ],
            [
                'name' => 'MySQL',
                'description' => 'Roles that revolve around MySQL database design and tuning.',
            ],
            [
                'name' => 'PostgreSQL',
                'description' => 'High-performance PostgreSQL roles, covering indexing and replication.',
            ],
            [
                'name' => 'Laravel',
                'description' => 'Building applications with the Laravel framework and ecosystem.',
            ],
            [
                'name' => 'JavaScript',
                'description' => 'Full-stack or frontend work driven by JavaScript tooling.',
            ],
            [
                'name' => 'React',
                'description' => 'Single-page applications crafted with React.',
            ],
            [
                'name' => 'Vue.js',
                'description' => 'Modern UI development using Vue.js and its composition API.',
            ],
            [
                'name' => 'Node.js',
                'description' => 'Server-side JavaScript jobs powered by Node.js.',
            ],
            [
                'name' => 'Full-time',
                'description' => 'Permanent employment with a standard full-time schedule.',
            ],
            [
                'name' => 'Part-time',
                'description' => 'Opportunities with reduced weekly hours or flexible schedules.',
            ],
            [
                'name' => 'Contract',
                'description' => 'Fixed-term engagements, consulting, or project-based roles.',
            ],
            [
                'name' => 'Freelance',
                'description' => 'Independent, client-based work with short-term commitments.',
            ],
            [
                'name' => 'Senior Level',
                'description' => 'Positions seeking experienced contributors and leaders.',
            ],
            [
                'name' => 'Junior Level',
                'description' => 'Entry-level roles focused on learning and growth.',
            ],
            [
                'name' => 'Mid Level',
                'description' => 'Established engineers with proven delivery experience.',
            ],
            [
                'name' => 'Beginner Level',
                'description' => 'Early-career roles where mentorship and ramp-up time are provided.',
            ],
            [
                'name' => 'Python',
                'description' => 'Python-centric engineering for automation or data tasks.',
            ],
            [
                'name' => 'Docker',
                'description' => 'Containerization and DevOps workflows built around Docker.',
            ],
            [
                'name' => 'AWS',
                'description' => 'Cloud-native work that runs on Amazon Web Services.',
            ],
        ];

        foreach ($tags as $tag) {
            $slug = Str::slug($tag['name']);

            Tag::updateOrCreate(
                ['name' => $tag['name']],
                [
                    'slug' => $slug,
                    'description' => $tag['description'],
                ]
            );
        }
    }
}
