<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Get all customer users (usertype_id = 3)
        $customerUsers = User::where('usertype_id', 3)->get();

        // Get all available tags
        $allTags = Tag::all();

        foreach ($customerUsers as $user) {
            // Create 5 posts for each customer user
            for ($i = 1; $i <= 5; $i++) {
                $post = Post::create([
                    'user_id' => $user->id,
                    'title' => $this->generateJobTitle(),
                    'content' => $this->generateJobDescription(),
                ]);

                // Attach between 3 to 5 random tags to each post
                $randomTagCount = rand(3, 5);
                $randomTags = $allTags->random($randomTagCount);
                $post->tags()->attach($randomTags->pluck('id'));
            }
        }
    }

    /**
     * Generate a random job title.
     */
    private function generateJobTitle(): string
    {
        $titles = [
            'Senior PHP Developer - Remote Opportunity',
            'Full Stack Laravel Developer - Hybrid',
            'JavaScript Developer - React & Vue.js',
            'Backend Developer - PHP & MySQL',
            'Frontend Developer - Modern JavaScript',
            'Full-time Laravel Engineer Position',
            'Remote PHP Developer - Senior Level',
            'Node.js Developer - Contract Position',
            'Web Developer - Full Stack Role',
            'Software Engineer - PHP Focus',
            'Laravel Developer - Mid Level Position',
            'JavaScript Engineer - Remote Work',
            'Backend PHP Developer - In Office',
            'Full Stack Developer - Laravel & Vue',
            'Senior Web Developer - Hybrid Role',
            'PHP/MySQL Developer - Contract Work',
            'JavaScript Developer - Part-time',
            'Laravel Engineer - Full-time Position',
            'Web Application Developer',
            'Software Developer - Modern Stack',
        ];

        return $titles[array_rand($titles)];
    }

    /**
     * Generate a random job description.
     */
    private function generateJobDescription(): string
    {
        $descriptions = [
            'We are looking for an experienced developer to join our dynamic team. You will work on exciting projects using modern technologies and frameworks.',
            'Join our company as a skilled developer where you will contribute to building scalable web applications. Remote work options available.',
            'Seeking a passionate developer to work with our tech stack. Great opportunity for career growth and skill development.',
            'We need a talented developer to help us build innovative solutions. Competitive salary and excellent benefits package.',
            'Looking for a dedicated developer to join our growing team. You will work on challenging projects with cutting-edge technologies.',
            'Opportunity to work with a leading company on exciting development projects. Flexible work arrangements and professional development.',
            'We are hiring a skilled developer to contribute to our software solutions. Great work-life balance and collaborative environment.',
            'Join our team as a developer and help us create amazing web applications. Opportunity to work with the latest technologies.',
            'Seeking an experienced developer for our innovative projects. Competitive compensation and growth opportunities.',
            'We need a motivated developer to help build our next-generation platform. Excellent team culture and learning environment.',
        ];

        return $descriptions[array_rand($descriptions)];
    }
}
