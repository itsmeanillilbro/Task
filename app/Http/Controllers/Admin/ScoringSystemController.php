<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScoreRanking;
use Illuminate\Http\Request;

class ScoringSystemController extends Controller
{
    public function index()
    {
        $criteria = ScoreRanking::orderBy('keyword')->paginate(10);
        return view('admin.scoring-system.index', compact('criteria'));
    }

    public function create()
    {
        return view('admin.scoring-system.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'keyword' => 'required|string|max:255',
            'points' => 'required|integer|min:1',
        ]);

        ScoreRanking::create($request->only(['keyword', 'points']));
        toastr()->success('Created successfully!');
        return redirect()->route('scoring-system.index')
           ;
    }

    public function destroy(ScoreRanking $scoring_system)
    {
        $scoring_system->delete();
        toastr()->success('Deleted successfully!');
        return redirect()->route('scoring-system.index');
    }
}