<?php

namespace App\Helpers;

class TextHumanizer
{
    public static function humanize(string $text): string
    {
        if (empty(trim($text))) {
            return $text;
        }

        // 1️⃣ Normalize (AI text is too clean)
        $text = preg_replace('/\s+/', ' ', $text);

        // 2️⃣ Kill formal / AI-heavy words
        $aiWords = [
            'utilize' => 'use',
            'commence' => 'start',
            'terminate' => 'end',
            'approximately' => 'about',
            'numerous' => 'many',
            'facilitate' => 'help',
            'moreover' => 'also',
            'therefore' => 'so',
            'in addition' => 'also',
            'in order to' => 'to',
        ];

        $text = str_ireplace(
            array_keys($aiWords),
            array_values($aiWords),
            $text
        );

        // 3️⃣ Add contractions (humans use these constantly)
        $contractions = [
            'do not' => "don’t",
            'does not' => "doesn’t",
            'cannot' => "can’t",
            'will not' => "won’t",
            'it is' => "it’s",
            'that is' => "that’s",
            'there is' => "there’s",
            'we are' => "we’re",
        ];

        $text = str_ireplace(
            array_keys($contractions),
            array_values($contractions),
            $text
        );

        // 4️⃣ Sentence rhythm destruction (VERY IMPORTANT)
        $sentences = preg_split('/(?<=[.!?])\s+/', $text);
        $new = [];

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);

            // Randomly shorten sentences
            if (strlen($sentence) > 140 && rand(0, 1)) {
                $parts = preg_split('/,|;|—/', $sentence, 2);
                $new[] = $parts[0] . '.';
                if (!empty($parts[1])) {
                    $new[] = ucfirst(trim($parts[1])) . '.';
                }
            } else {
                $new[] = $sentence;
            }

            // Occasionally insert micro-pause
            if (rand(0, 6) === 0) {
                $new[] = 'Let that sink in.';
            }
        }

        $text = implode(' ', $new);

        // 5️⃣ Opinion & human hooks (AI avoids these)
        $hooks = [
            "Let’s be honest—",
            "Here’s the thing:",
            "Most people get this wrong.",
            "This sounds simple, but it isn’t.",
            "And yes, this actually matters.",
        ];

        if (rand(0, 1)) {
            $text = $hooks[array_rand($hooks)] . ' ' . $text;
        }

        // 6️⃣ Imperfect formatting (humans don’t write perfect blocks)
        $text = preg_replace('/\. /', ".\n\n", $text, rand(1, 3));

        // 7️⃣ Mild uncertainty (AI is too confident)
        if (rand(0, 3) === 0) {
            $text .= "\n\nThat said, this isn’t a one-size-fits-all situation.";
        }
 $textfinal = self::basicNormalize($text);
        return trim($textfinal);
    }
    public static function basicNormalize(string $text): string
{
    $text = str_replace([',', '-'], ' ', $text);

    // remove extra spaces created by replacement
    $text = preg_replace('/\s+/', ' ', $text);

    return trim($text);
}

}
