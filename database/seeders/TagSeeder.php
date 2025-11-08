<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $tags = [
            'Remote',
            'In Office',
            'Hybrid',
            'PHP',
            'MySQL',
            'Laravel',
            'JavaScript',
            'React',
            'Vue.js',
            'Node.js',
            'Full-time',
            'Part-time',
            'Contract',
            'Freelance',
            'Senior Level',
            'Junior Level',
            'Mid Level',
            'Python',
            'Docker',
            'AWS',
        ];

        foreach ($tags as $tagName) {
            Tag::create([
                'name' => $tagName,
            ]);
        }
    }
}
