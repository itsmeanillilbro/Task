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
        Log::info('Normalized Text: ' . $text);
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
        // Convert all whitespace to single spaces
        $text = preg_replace('/\s+/', ' ', $text);

        // Add newlines before section headers
        $text = preg_replace('/(\n|^)(Education|Experience|Skills|Projects|Technical)/i', "\n\n$2", $text);

        // Standardize date formats
        $text = preg_replace('/(\d{4})\s*-\s*(\d{4})/', '$1–$2', $text);

        // Remove common noise patterns
        $text = preg_replace('/\b(?:Phone|Mobile|Email)\s*:.*?(?=\n|$)/i', '', $text);

        return trim($text);
    }

    protected function extractName($text)
    {
        // First try the multi-line approach
        $name = $this->scanForName($text);

        if ($name) {
            return $name;
        }

        // Fallback to simple regex if the advanced method fails
        if (preg_match('/^([A-Z][a-z]+(?:\s+[A-Z][a-z]+)+)/', $text, $matches)) {
            $potentialName = trim($matches[1]);
            if (!preg_match('/[0-9@#]/', $potentialName) && strlen($potentialName) < 30) {
                return $potentialName;
            }
        }

        return 'Unknown Candidate';
    }

    /**
     * Self-contained name scanning without any dependencies
     */
    protected function scanForName($text)
    {
        $lines = $this->getTopLines($text, 5);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip lines with numbers, emails, URLs, or excessive length
            if (strlen($line) > 50 || preg_match('/[0-9@#&*]/', $line) || str_contains($line, 'http')) {
                continue;
            }

            $words = preg_split('/\s+/', $line);
            $nameWords = [];

            foreach ($words as $word) {
                if (preg_match('/^[A-Z][a-z]+$/', $word)) {
                    $nameWords[] = $word;
                }
                // Strictly stop at two title-case words
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

        // Get all scoring keywords from database
        $scoringKeywords = ScoreRanking::pluck('keyword')->toArray();

        // Strategy 1: Direct section extraction
        if (preg_match('/Technical\s+Skills?\s*:?\s*([\s\S]+?)(?=(?:Projects|Experience|Education|$))/i', $text, $sectionMatch)) {
            $skillText = $sectionMatch[1];

            // Clean up the text
            $skillText = preg_replace('/\band\b/i', ',', $skillText);
            $skillText = preg_replace('/\s+/', ' ', $skillText);

            // Extract skills with multiple patterns
            preg_match_all('/
            (?:^|\s|,)                  # Start or comma
            ([A-Za-z\+#][\w\+#\.\-]{2,})  # Skill word
            (?:\s*\/\s*[A-Za-z\+#][\w\+#\.\-]{2,})* # Optional slash-separated variants
        /x', $skillText, $matches);

            $skills = array_map('trim', $matches[1]);
        }

        // Strategy 2: Bullet point extraction
        if (empty($skills) && preg_match_all('/•\s*([^\n]+)/', $text, $matches)) {
            $skills = array_map('trim', $matches[1]);
        }

        // Strategy 3: Match against scoring keywords from database
        foreach ($scoringKeywords as $keyword) {
            if (
                preg_match("/\b" . preg_quote($keyword, '/') . "\b/i", $text) &&
                !in_array($keyword, $skills)
            ) {
                $skills[] = $keyword;
            }
        }

        // Final cleanup
        return array_values(array_unique(array_filter($skills, function ($skill) {
            return strlen($skill) > 2; // Filter out very short "skills"
        })));
    }

    protected function extractExperience($text)
{
    // Match "Experience" or "Professional Experience" section more flexibly
    if (preg_match('/(?:Professional|Work)\s*Experience\s*:?\s*([\s\S]+?)(?=(?:Education|Skills|Projects|References|Technical|$))/is', $text, $matches)) {
        $expText = trim($matches[1]);

        // Split into entries using dates or job titles as delimiters, more leniently
        $entries = preg_split('/(\d{4}\s*[–-]\s*(?:\d{4}|Present|Continued)|\n\s*[A-Z][a-z]+(?:\s+[A-Z][a-z]+)*\s*(?:Manager|Developer|Designer))/i', $expText, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $formattedEntries = [];
        $currentEntry = '';

        foreach ($entries as $entry) {
            $entry = trim($entry);
            if (preg_match('/^\d{4}\s*[–-]\s*(?:\d{4}|Present|Continued)$/i', $entry)) {
                // If it’s a date range, append to the current entry
                $currentEntry .= " $entry\n";
            } elseif (preg_match('/^[A-Z][a-z]+(?:\s+[A-Z][a-z]+)*\s*(?:Manager|Developer|Designer)/i', $entry)) {
                // If it’s a job title, start a new entry
                if (!empty($currentEntry)) {
                    $formattedEntries[] = trim($currentEntry);
                }
                $currentEntry = "$entry\n";
            } else {
                // Append additional details (company, description)
                $currentEntry .= "$entry\n";
            }
        }
        // Add the last entry
        if (!empty($currentEntry)) {
            $formattedEntries[] = trim($currentEntry);
        }

        // Clean up and format
        return implode("\n\n", array_map(function ($entry) {
            return preg_replace('/•\s*/', "\n- ", trim($entry));
        }, $formattedEntries));
    }

    return 'Experience not specified';
}

    protected function extractEducation($text)
    {
        $text = preg_replace('/\b[\w\.-]+@[\w\.-]+\.\w+\b/', '', $text); // Remove emails
        $text = preg_replace('/\b\d{10,}\b/', '', $text); // Remove phone numbers
    
        $educations = [];
    
        // Match "Education" section more flexibly
        if (preg_match('/Education\s*:?\s*([\s\S]+?)(?=(?:Experience|Skills|Projects|References|Technical|$))/i', $text, $sectionMatch)) {
            $eduText = trim($sectionMatch[1]);
            $lines = array_filter(array_map('trim', explode("\n", $eduText)));
    
            $currentEntry = [];
            foreach ($lines as $line) {
                if (preg_match('/^Education$/i', $line)) {
                    continue; // Skip header
                }
    
                // Detect degree (more flexible pattern)
                if (preg_match('/^(Bachelor|Diploma|Certificate|Master|Ph\.?D\.?|B\.\w+|M\.\w+|School\s+Leaving).*?(?:\s*\(.*?\))?$/i', $line)) {
                    if (!empty($currentEntry)) {
                        $educations[] = $currentEntry; // Save previous entry
                    }
                    $currentEntry = ['degree' => $line];
                } elseif (!empty($currentEntry)) {
                    // Assign based on context (no strict line count)
                    if (preg_match('/\d{4}\s*[–-]\s*(?:\d{4}|Present|Continued)/i', $line)) {
                        $currentEntry['dates'] = $line;
                    } elseif (preg_match('/(University|College|Institute|School|Campus)/i', $line)) {
                        $currentEntry['institution'] = $line;
                    } elseif (preg_match('/(Nepal|Kathmandu|City|ST)/i', $line)) {
                        $currentEntry['location'] = $line;
                    } else {
                        // Append as additional detail if not a clear field
                        $currentEntry['extra'] = ($currentEntry['extra'] ?? '') . " $line";
                    }
                }
            }
            // Add the last entry
            if (!empty($currentEntry)) {
                $educations[] = $currentEntry;
            }
        }
    
        if (empty($educations)) {
            return 'Education not specified';
        }
    
        // Format the education entries
        $formattedEducations = array_map(function ($edu) {
            $entry = $edu['degree'];
            if (isset($edu['institution'])) {
                $entry .= " at {$edu['institution']}";
            }
            if (isset($edu['dates'])) {
                $entry .= " {$edu['dates']}";
            }
            if (isset($edu['location'])) {
                $entry .= ", {$edu['location']}";
            }
            if (isset($edu['extra'])) {
                $entry .= " {$edu['extra']}";
            }
            return trim($entry);
        }, $educations);
    
        $formattedEducations = array_unique($formattedEducations);
        return implode("\n\n", $formattedEducations); // Use plain text for simplicity
    }
}