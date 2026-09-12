<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Interim home (STORIES S-06): my courses with progress. Replaced by the planner-driven Home in M4.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $enrollments = $user->enrollments()
            ->with(['course.lessons.masteryRecords' => fn ($q) => $q->where('user_id', $user->id)])
            ->orderBy('priority')
            ->get()
            ->sortBy(fn ($e) => $e->status === 'active' ? 0 : 1)
            ->values();

        return view('home', compact('enrollments'));
    }
}
