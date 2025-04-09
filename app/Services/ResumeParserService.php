<?php

namespace App\Services;

use App\Models\ScoreRanking;
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
        // Works fine for your case
        if (preg_match('/^([A-Z][a-z]+(?:\s+[A-Z][a-z]+)+)/', $text, $matches)) {
            $potentialName = trim($matches[1]);
            if (!preg_match('/[0-9@#]/', $potentialName) && strlen($potentialName) < 30) {
                return $potentialName;
            }
        }
        return 'Unknown Candidate';
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
        if (preg_match("/\b" . preg_quote($keyword, '/') . "\b/i", $text) && 
            !in_array($keyword, $skills)) {
            $skills[] = $keyword;
        }
    }
    
    // Final cleanup
    return array_values(array_unique(array_filter($skills, function($skill) {
        return strlen($skill) > 2; // Filter out very short "skills"
    })));
}

   protected function extractExperience($text)
{
    // Look for "Professional Experience" or "Work Experience" section
    if (preg_match('/(?:Professional|Work)\s+Experience\s*:?\s*(.+?)(?=(?:Education|Skills|Projects|$))/is', $text, $matches)) {
        return trim($matches[1]);
    }
    
    // Fallback: Look for date ranges with company names
    if (preg_match('/((?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec|[0-9]{4}).*?present.*?\b(?:pvt|ltd|inc|llc)\b.*?)/is', $text, $matches)) {
        return trim($matches[1]);
    }
    
    return 'Experience not specified';
}

    protected function extractEducation($text)
    {
        // Pre-process to remove干扰因素
        $text = preg_replace('/\b[\w\.-]+@[\w\.-]+\.\w+\b/', '', $text); // Remove emails
        $text = preg_replace('/\b\d{10,}\b/', '', $text); // Remove phone numbers
        
        $educations = [];
        
        // Primary extraction from Education section
        if (preg_match('/Education\s*:?\s*([\s\S]+?)(?=(?:Projects|Experience|Skills|Technical|$))/i', $text, $sectionMatch)) {
            $eduText = $sectionMatch[1];
            
            // Handle multiple education entries
            preg_match_all('/
                (\d{4}\s*–\s*(?:\d{4}|Present))  # Date range
                \s*                               # Separator
                (.+?)                             # Location
                \s*                               # Separator
                ((?:BSc|B\.?S\.?|Bachelor|M\.?S\.?|Master|Ph\.?D\.?).*?) # Degree
                \s*                               # Separator
                (?:at\s*)?(.+?)                  # Institution
                (?=\n\n|\n\w|\d{4}|$)            # Boundary
            /ix', $eduText, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                $educations[] = trim(implode(' ', [
                    $match[1],  // Dates
                    $match[3],  // Degree
                    'at',
                    $match[4]   // Institution
                ]));
            }
        }
        
        // Fallback: Degree + Institution pattern
        if (empty($educations)) {
            preg_match_all('/
                (BSc|B\.?S\.?|Bachelor|M\.?S\.?|Master|Ph\.?D\.?)  # Degree
                \s+
                (?:in\s)?(.+?)                     # Field of study
                \s+
                (?:from|at)\s(.+?)                # Institution
                (?=\n|,|$)
            /ix', $text, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                $educations[] = trim("{$match[1]} in {$match[2]} from {$match[3]}");
            }
        }
        
        return empty($educations) ? 'Education not specified' : implode("\n\n", $educations);
    }
}