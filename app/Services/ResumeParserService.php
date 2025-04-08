<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

class ResumeParserService
{
    public function parse($filePath)
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();
        
        // Normalize text for better parsing
        $text = $this->normalizeText($text);
        dd($text);
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
        // Convert to single line breaks and remove excessive spaces
        $text = preg_replace('/\s+/', ' ', $text);
        return $text;
    }

    protected function extractName($text)
    {
        // Pattern 1: Look for "Name:" prefix
        if (preg_match('/Name[:\s]*([A-Z][a-z]+(?:\s+[A-Z][a-z]+)+)/i', $text, $matches)) {
            return trim($matches[1]);
        }

        // Pattern 2: First line after "RESUME" or "CURRICULUM VITAE"
        if (preg_match('/(?:RESUME|CURRICULUM VITAE|CV)[\s:]*\n([A-Z][a-z]+(?:\s+[A-Z][a-z]+)+)/i', $text, $matches)) {
            return trim($matches[1]);
        }

        // Pattern 3: First title-case line with 2-3 words
        if (preg_match('/^([A-Z][a-z]+(?:\s+[A-Z][a-z]+){1,2})/', $text, $matches)) {
            $potentialName = trim($matches[1]);
            // Basic validation to exclude obvious non-names
            if (!preg_match('/[0-9@#]/', $potentialName) && strlen($potentialName) < 30) {
                return $potentialName;
            }
        }

        return 'Unknown Candidate';
    }

    protected function extractEmail($text)
    {
        // Standard email pattern
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $text, $matches)) {
            return trim($matches[0]);
        }
        return '';
    }

    protected function extractSkills($text)
    {
        // Common section headers for skills
        $skillPatterns = [
            '/Skills?\s*:?\s*([^E]+)(?:Experience|Education)/i',
            '/Technical\s+Skills?\s*:?\s*([^E]+)(?:Experience|Education)/i',
            '/Key\s+Skills?\s*:?\s*([^E]+)(?:Experience|Education)/i',
        ];

        $skills = [];
        foreach ($skillPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $skillText = $matches[1];
                // Extract individual skills (comma/line separated)
                if (preg_match_all('/([A-Za-z\+#][\w\+#\.\-]{2,})/', $skillText, $skillMatches)) {
                    $skills = array_merge($skills, $skillMatches[1]);
                }
                break;
            }
        }

        // Fallback: Look for common tech skills anywhere in text
        if (empty($skills)) {
            $techKeywords = ['Laravel', 'PHP', 'JavaScript', 'Python', 'SQL', 'React', 'Vue', 'AWS'];
            foreach ($techKeywords as $keyword) {
                if (preg_match("/\b{$keyword}\b/i", $text)) {
                    $skills[] = $keyword;
                }
            }
        }

        return array_unique(array_map('trim', $skills));
    }

    protected function extractExperience($text)
    {
        // Look for experience section
        if (preg_match('/Experience\s*:?\s*(.+?)(?:Education|Skills|Projects)/is', $text, $matches)) {
            return trim($matches[1]);
        }

        // Fallback: Look for date ranges that typically indicate experience
        if (preg_match('/((?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\s+\d{4}).*?(?:present|now|current)/is', $text, $matches)) {
            return $matches[0];
        }

        return 'Experience not specified';
    }

    protected function extractEducation($text)
    {
        // Look for education section
        if (preg_match('/Education\s*:?\s*(.+?)(?:Experience|Skills|Projects)/is', $text, $matches)) {
            return trim($matches[1]);
        }

        // Look for degree patterns
        if (preg_match('/((?:B\.?S\.?|B\.?A\.?|M\.?S\.?|M\.?A\.?|Ph\.?D\.?).*?(?:University|Institute|College))/i', $text, $matches)) {
            return trim($matches[1]);
        }

        return 'Education not specified';
    }
}