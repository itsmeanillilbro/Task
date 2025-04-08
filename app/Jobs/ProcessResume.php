<?php
namespace App\Jobs;

use App\Models\Candidate;
use App\Models\CandidateSkill;
use App\Models\ScoreRanking;
use App\Services\ResumeParserService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessResume implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $filePath,  // Store path instead of file object
        public string $originalName
    ) {}


    public function handle(ResumeParserService $parser)
    {
        // Check if batch was cancelled
        if ($this->batch()->cancelled()) {
            return;
        }

        try {
            $absolutePath = Storage::path($this->filePath);
            $data = $parser->parse($absolutePath);

            // Calculate score
            $totalScore = 0;
            $skills = [];
            
            foreach ($data['skills'] as $skill) {
                $criterion = ScoreRanking::where('keyword', $skill)->first();
                if ($criterion) {
                    $points = $criterion->points;
                    $totalScore += $points;
                    $skills[] = ['skill' => $skill, 'points' => $points];
                }
            }

            // Create candidate
            Candidate::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'experience' => $data['experience'],
                'education' => $data['education'],
                'total_score' => $totalScore,
                'cv_path' =>$this->filePath
            ])->skills()->createMany($skills);

        } catch (\Exception $e) {
            // Delete the stored file if processing fails
            Storage::delete($this->filePath);
            throw $e;
        }
    }
}