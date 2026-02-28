<?php

namespace Database\Seeders;

use App\Models\IceBreakerQuestion;
use Illuminate\Database\Seeder;

class IceBreakerSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            // Fun
            ['question' => 'What song do you shamelessly sing in the shower?', 'category' => 'fun', 'emoji' => '🎤'],
            ['question' => 'If you could have dinner with any fictional character, who?', 'category' => 'fun', 'emoji' => '🍽️'],
            ['question' => 'What is the most useless talent you have?', 'category' => 'fun', 'emoji' => '🎪'],
            ['question' => 'What would your entrance music be if you walked into every room with a theme song?', 'category' => 'fun', 'emoji' => '🎵'],
            ['question' => 'If you could swap lives with someone for a day, who would it be?', 'category' => 'fun', 'emoji' => '🔄'],
            ['question' => 'What is the weirdest food combo you secretly love?', 'category' => 'fun', 'emoji' => '🤤'],
            ['question' => 'If you had to eat one cuisine for the rest of your life, what would it be?', 'category' => 'fun', 'emoji' => '🍜'],

            // Deep
            ['question' => 'What is one belief you held strongly but later changed your mind about?', 'category' => 'deep', 'emoji' => '🧠'],
            ['question' => 'What is something you wish more people understood about you?', 'category' => 'deep', 'emoji' => '💭'],
            ['question' => 'What moment in your life shaped who you are most?', 'category' => 'deep', 'emoji' => '🔮'],
            ['question' => 'What does your ideal Sunday look like?', 'category' => 'deep', 'emoji' => '☀️'],
            ['question' => 'What is a dream you have never told anyone about?', 'category' => 'deep', 'emoji' => '🌙'],
            ['question' => 'If money was not a factor, what would you do with your life?', 'category' => 'deep', 'emoji' => '💫'],
            ['question' => 'What small act of kindness do you remember most?', 'category' => 'deep', 'emoji' => '🤲'],
            ['question' => 'What do you think is your best quality in a relationship?', 'category' => 'deep', 'emoji' => '❤️'],

            // Creative
            ['question' => 'You can only bring 3 items to a deserted island - what are they?', 'category' => 'creative', 'emoji' => '🏝️'],
            ['question' => 'If your life was a movie, what genre would it be?', 'category' => 'creative', 'emoji' => '🎬'],
            ['question' => 'You get to create a new holiday - what is it and how is it celebrated?', 'category' => 'creative', 'emoji' => '🎉'],
            ['question' => 'If you could master any instrument overnight, which one?', 'category' => 'creative', 'emoji' => '🎸'],
            ['question' => 'Design your perfect date in exactly 5 words.', 'category' => 'creative', 'emoji' => '✏️'],
            ['question' => 'If you could teleport anywhere right now, where would you go?', 'category' => 'creative', 'emoji' => '✨'],
            ['question' => 'What superpower would make your daily life infinitely better?', 'category' => 'creative', 'emoji' => '⚡'],

            // Spicy
            ['question' => 'What is the boldest thing you have ever done on a date?', 'category' => 'spicy', 'emoji' => '🔥'],
            ['question' => 'What is your most controversial opinion about dating?', 'category' => 'spicy', 'emoji' => '🌶️'],
            ['question' => 'What is a dealbreaker that most people would find surprising?', 'category' => 'spicy', 'emoji' => '🚫'],
            ['question' => 'What is the fastest you have ever caught feelings?', 'category' => 'spicy', 'emoji' => '💘'],
            ['question' => 'Truth or dare - and why?', 'category' => 'spicy', 'emoji' => '🎯'],
            ['question' => 'What is the most romantic thing someone could do for you?', 'category' => 'spicy', 'emoji' => '🥀'],
            ['question' => 'What is one thing on your bucket list that would surprise people?', 'category' => 'spicy', 'emoji' => '🪣'],
        ];

        foreach ($questions as $q) {
            IceBreakerQuestion::firstOrCreate(
                ['question' => $q['question']],
                $q
            );
        }
    }
}
