<?php

namespace App\Http\Controllers;

use Auth;
use App\Problem;
use App\User;
use App\Hint;
use Illuminate\Http\Request;

class ProblemController extends Controller
{
    public function all()
    {
        $problemList = Problem::with('hints')->get();
        $problemRelations = $this->get_user_problems();
        $problem = 'All Types';
        return view('languages.index', compact('problemList', 'problemRelations', 'problem'));
    }
    
    public function index($problem)
    {
        $problemList = Problem::where('type', $problem)->with('hints')->get();
        $problemRelations = $this->get_user_problems();
        
        return view('languages.index', compact('problemList', 'problemRelations', 'problem'));
    }
    
    public function show($problem)
    {
        $problem = Problem::where('id', $problem)->with('hints')->first();
        $problemRelations = $this->get_user_problems();
        
        return view('languages.show', compact('problem', 'problemRelations'));
    }
    
    public function create()
    {
        if (Auth::check() && Auth::user()->user_level == 10) {
            return view('forms.problemadd');
        }
        return redirect()->route('home')->with('status', 'warning')
                         ->with('message', 'You are not allowed to view that resource.');
    }
    
    public function store(Request $request)
    {
        if (Auth::check() && Auth::user()->user_level == 10) {
            try {
                $problem = new Problem;
                
                $problem->question = $request->problem;
                $problem->type = $request->type;
                $problem->points = $request->points;
                $problem->save();
                
                $hint = $request->hint;
                $newHint = Hint::updateOrCreate([
                    'problem_id' => $problem->id,
                ], [
                    'hint' => $hint,
                ]);
                
                if ($problem) {
                    return back()->with('status', 'success')->with('title', 'Good Job!')->with('message', 'New Problem Created!');
                }
            } catch (\Illuminate\Database\QueryException $e) {
                return back()->with('status', 'warning')->with('title', 'Uh Oh!')
                             ->with('message', 'Something went wrong adding the problem.');
            }
        } else {
            return redirect()->route('home')->with('status', 'warning')->with('title', 'Uh Oh!')
                             ->with('message', 'You are not allowed to view that resource.');
        }
    }
    
    public function leaders()
    {
        $users = User::all();
        $leaders = [];
        foreach ($users as $user) {
            $leaders[] = [
                'user'   => $user,
                'points' => $user->problems->sum('points'),
            ];
        }
        
        usort($leaders, 'sort_by_score');
        
        return view('leaderboard', compact('leaders'));
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Problem $problem
     * @return \Illuminate\Http\Response
     */
    public function edit(Problem $problem, $id)
    {
        if (Auth::check() && Auth::user()->user_level == 10) {
            $question = Problem::where('id', $id)->with('hints')->first();
            return view('forms.problemedit', compact('question'));
        }
        return redirect()->route('home')->with('status', 'warning')->with('title', 'Uh Oh!')
                         ->with('message', 'You are not allowed to view that resource.');
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param Problem                   $problem
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Problem $problem, $id)
    {
        if (Auth::check() && Auth::user()->user_level == 10) {
            $problem = Problem::findOrFail($id);
            $problem->question = $request->problem;
            $problem->type = $request->type;
            $problem->points = $request->points;
            $problem->save();
            
            
            $hint = $request->hint;
            $newHint = Hint::updateOrCreate([
                'problem_id' => $problem->id,
            ], [
                'hint' => $hint,
            ]);
            
            if ($problem) {
                return back()->with('status', 'success')->with('title', 'Good Job!')->with('message', 'Problem Updated.');
            }
            
            return back()->with('status', 'warning')->with('title', 'Uh Oh!')
                         ->with('message', 'Something went wrong updating the problem.');
        }
        return redirect()->route('home')->with('status', 'warning')->with('title', 'Uh Oh!')
                         ->with('message', 'You are not allowed to view that resource.');
    }
    
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Problem $problem
     * @return \Illuminate\Http\Response
     */
    public function destroy(Problem $problem)
    {
        //
    }
    
    public function search(Request $request)
    {
        $problemList = Problem::search($request->search_term)->get();
        $problemRelations = $this->get_user_problems();
        
        return view('languages.search-results', compact('problemList', 'problemRelations'));
    }
    
    /**
     * @return mixed
     */
    protected function get_user_problems()
    {
        $user = Auth::user();
        $problemRelations = $user->problems()->get();
        return $problemRelations;
    }
}
