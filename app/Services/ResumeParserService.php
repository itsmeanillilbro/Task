<?php

namespace App\Services;

use App\Models\ScoreRanking;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;

class ResumeParserService
{
    const MIN_NAME_LENGTH = 3;
    public function parse($filePath)
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();

        // Normalize text for better parsing
        $text = $this->normalizeText($text);

        return [
            'name' => $this->extractName($text),
            'email' => $this->extractEmail($text),
            'skills' => $this->extractSkills($text),
            'experience' => $this->extractExperience($text),
            'education' => $this->extractEducation($text),
        ];
    }

    protected function normalizeText($text)
    {
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/(\n|^)(Education|Experience|Skills|Projects|Technical)/i', "\n\n$2", $text);
        $text = preg_replace('/(\d{4})\s*-\s*(\d{4})/', '$1–$2', $text);
        $text = preg_replace('/\b(?:Phone|Mobile|Email)\s*:.*?(?=\n|$)/i', '', $text);

        return trim($text);
    }

    protected function extractName($text)
    {
        $name = $this->scanForName($text);

        if ($name) {
            return $name;
        }
        if (preg_match('/^([A-Z][a-z]+(?:\s+[A-Z][a-z]+)+)/', $text, $matches)) {
            $potentialName = trim($matches[1]);
            if (!preg_match('/[0-9@#]/', $potentialName) && strlen($potentialName) < 30) {
                return $potentialName;
            }
        }
        return 'Unknown Candidate';
    }

    protected function scanForName($text)
    {
        $lines = $this->getTopLines($text, 5);

        foreach ($lines as $line) {
            $line = trim($line);
            if (strlen($line) > 50 || preg_match('/[0-9@#&*]/', $line) || str_contains($line, 'http')) {
                continue;
            }
            $words = preg_split('/\s+/', $line);
            $nameWords = [];

            foreach ($words as $word) {
                if (preg_match('/^[A-Z][a-z]+$/', $word)) {
                    $nameWords[] = $word;
                }
                if (count($nameWords) == 2) {
                    return implode(' ', $nameWords);
                }
            }
        }
        // Fallback to first two words if no title-case match
        $words = preg_split('/\s+/', trim($lines[0]));
        if (count($words) >= 2) {
            return implode(' ', array_slice($words, 0, 2));
        }

        return 'Unknown Candidate';
    }
    /**
     * Get top N lines from text
     */
    protected function getTopLines($text, $count = 5)
    {
        $lines = explode("\n", $text);
        return array_slice($lines, 0, min(count($lines), $count));
    }

    protected function extractEmail($text)
    {
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $text, $matches)) {
            return trim($matches[0]);
        }
        return '';
    }

    protected function extractSkills($text)
    {
        $skills = [];

        $scoringKeywords = ScoreRanking::pluck('keyword')->toArray();
        if (preg_match('/Technical\s+Skills?\s*:?\s*([\s\S]+?)(?=(?:Projects|Experience|Education|$))/i', $text, $sectionMatch)) {
            $skillText = $sectionMatch[1];
            $skillText = preg_replace('/\band\b/i', ',', $skillText);
            $skillText = preg_replace('/\s+/', ' ', $skillText);
            preg_match_all('/
            (?:^|\s|,)                  # Start or comma
            ([A-Za-z\+#][\w\+#\.\-]{2,})  # Skill word
            (?:\s*\/\s*[A-Za-z\+#][\w\+#\.\-]{2,})* # Optional slash-separated variants
        /x', $skillText, $matches);

            $skills = array_map('trim', $matches[1]);
        }
        if (empty($skills) && preg_match_all('/•\s*([^\n]+)/', $text, $matches)) {
            $skills = array_map('trim', $matches[1]);
        }
        foreach ($scoringKeywords as $keyword) {
            if (
                preg_match("/\b" . preg_quote($keyword, '/') . "\b/i", $text) &&
                !in_array($keyword, $skills)
            ) {
                $skills[] = $keyword;
            }
        }

        return array_values(array_unique(array_filter($skills, function ($skill) {
            return strlen($skill) > 2;
        })));
    }

    protected function extractExperience($text)
    {
        $patterns = [
            '/(?:Professional|Work)\s+Experience\s*:?\s*([\s\S]+?)(?=(?:Education|Skills|Projects|References|$))/i',
            '/Experience\s*:?\s*([\s\S]+?)(?=(?:Education|Skills|Projects|References|$))/i',
            '/(.*?\d{4}\s*–\s*(?:\d{4}|Present).*?(?:\n|$))+/i' // Fallback for Lorna's format
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $expText = trim($matches[1]);
                if (preg_match_all('/(\d{4}\s*–\s*(?:\d{4}|Present).*?\n.*?\n)/', $expText, $lornaMatches)) {
                    return implode("\n\n", array_map('trim', $lornaMatches[0]));
                }
                $expText = preg_replace('/\b[\w\.-]+@[\w\.-]+\.\w+\b/', '', $expText);
                $expText = preg_replace('/\b\d{10,}\b/', '', $expText);

                $entries = preg_split('/(?=\d{4}\s*–\s*(?:\d{4}|Present)|[A-Z][a-z]+\s+[A-Z][a-z]+\s*(?:Manager|Developer|Designer))/', $expText);

                $formattedEntries = array_map(function ($entry) {
                    $entry = trim(preg_replace('/•\s*/', "\n- ", $entry));
                    return preg_replace('/\s+/', ' ', $entry); // Normalize spaces
                }, array_filter($entries));

                return implode("\n\n", $formattedEntries);
            }
        }

        return 'Experience not specified';
    }

    protected function extractEducation($text)
    {
        $text = preg_replace('/\b[\w\.-]+@[\w\.-]+\.\w+\b/', '', $text);
        $text = preg_replace('/\b\d{10,}\b/', '', $text);
        $patterns = [
            '/Education\s*:?\s*([\s\S]+?)(?=(?:Experience|Skills|Projects|References|$))/i',
            '/Academic\s+Background\s*:?\s*([\s\S]+?)(?=(?:Experience|Skills|Projects|References|$))/i',
            '/((?:Bachelor|BSc|B\.\w+|Diploma|Certificate|Master|PhD|SLC).*?\d{4}\s*–\s*(?:\d{4}|Present).*?(?=(?:Bachelor|BSc|Experience|$)))/is'
        ];

        $educations = [];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $sectionMatch)) {
                $eduText = trim($sectionMatch[1]);
                if (preg_match_all('/(\d{4}\s*[–-]\s*(?:\d{4}|Present))\s*([^\n]+?)\s+(University|College|School|Institute)\b/i', $eduText, $matches)) {
                    $educations = [];
                    foreach ($matches[0] as $key => $value) {
                        $educations[] = trim("{$matches[2][$key]} at {$matches[3][$key]} {$matches[1][$key]}");
                    }
                    return implode("\n\n", $educations);
                }
                $lines = array_filter(array_map('trim', explode("\n", $eduText)));
                $currentEntry = [];

                foreach ($lines as $line) {
                    if (preg_match('/^(Bachelor|BSc|Diploma|Master|PhD|SLC)/i', $line)) {
                        if (!empty($currentEntry))
                            $educations[] = $currentEntry;
                        $currentEntry = ['degree' => $line];
                    } elseif (preg_match('/^\d{4}\s*–\s*(?:\d{4}|Present)/', $line)) {
                        $currentEntry['dates'] = $line;
                    } elseif (!isset($currentEntry['institution']) && !empty($line)) {
                        $currentEntry['institution'] = $line;
                    }
                }
                if (!empty($currentEntry))
                    $educations[] = $currentEntry;

                break;
            }
        }

        if (empty($educations)) {
            return 'Education not specified';
        }

        $formatted = array_map(function ($edu) {
            $parts = [];
            if (isset($edu['degree']))
                $parts[] = $edu['degree'];
            if (isset($edu['institution']))
                $parts[] = "at {$edu['institution']}";
            if (isset($edu['dates']))
                $parts[] = $edu['dates'];
            return implode(' ', $parts);
        }, $educations);

        return implode("\n\n", array_unique($formatted));
    }
}