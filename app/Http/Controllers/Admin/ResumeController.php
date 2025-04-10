<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessResume;
use App\Models\Candidate;
use App\Models\ScoreRanking;
use App\Services\ResumeParserService;
use Illuminate\Bus\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ResumeController extends Controller
{
    protected $resumeParser;

    public function __construct(ResumeParserService $resumeParser)
    {
        $this->resumeParser = $resumeParser;
    }

    /**
     * Display a listing of the resumes.
     */
    public function index()
    {
        $candidates = Candidate::with('skills')
            ->orderBy('total_score', 'desc')
            ->paginate(10);

        return view('admin.resume.index', compact('candidates'));
    }

    /**
     * Show the form for uploading new resumes.
     */
    public function create()
    {
        return view('admin.resume.create');
    }

    /**
     * Store bulk uploaded resumes.
     */
    public function store(Request $request)
    {
        $request->validate([
            'resumes' => 'required|array|max:20',
            'resumes.*' => 'file|mimes:pdf|max:2048',
        ]);

        $jobs = [];

        foreach ($request->file('resumes') as $file) {
            $filePath = $file->store('temp-resumes');

            $jobs[] = new ProcessResume(
                filePath: $filePath,
                originalName: $file->getClientOriginalName()
            );
        }

        $batch = Bus::batch($jobs)
            ->then(function () {
                Storage::deleteDirectory('temp-resumes');
            })
            ->dispatch();

        return redirect()->route('resume.batch', ['batchId' => $batch->id]);
    }

    public function batch(string $batchId)
    {
        $batch = Bus::findBatch($batchId);
        return view('admin.resume.batch', compact('batch'));
    }

    public function export()
    {
        $candidates = Candidate::with('skills')
            ->orderBy('total_score', 'desc')
            ->get();

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=candidates.csv",
        ];

        $callback = function () use ($candidates) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Rank', 'Name', 'Email', 'Skills', 'Total Score']);

            foreach ($candidates as $index => $candidate) {
                fputcsv($file, [
                    $index + 1,
                    $candidate->name,
                    $candidate->email,
                    $candidate->skills->pluck('skill')->implode(', '),
                    $candidate->total_score
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Display the specified resume.
     */
    public function show(Candidate $resume)
    {
        return view('admin.resume.show', compact('resume'));
    }

    /**
     * Remove the specified resume from storage.
     */
    public function destroy(Candidate $resume)
    {
        Storage::delete($resume->cv_path);
        $resume->delete();
        toastr()->success('Deleted successfully!');
        return redirect()->route('resume.index');
    }
}