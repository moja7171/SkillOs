<?php

namespace App\Http\Controllers;

use App\Services\Planning\Planner;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request, Planner $planner): View
    {
        $user = $request->user();
        $plan = $planner->continueLearning($user);

        $enrollments = $user->enrollments()
            ->with(['course.lessons.masteryRecords' => fn ($q) => $q->where('user_id', $user->id)])
            ->orderBy('priority')
            ->get()
            ->sortBy(fn ($e) => $e->status === 'active' ? 0 : 1)
            ->values();

        $todayByEnrollment = $planner->orderForLearner($plan['items'])->groupBy('enrollment_id');

        return view('home', [
            'enrollments' => $enrollments,
            'primary' => $plan['primary'],
            'alternatives' => $plan['alternatives'],
            'todayByEnrollment' => $todayByEnrollment,
            'plannedMinutes' => $plan['items']->sum('duration_minutes'),
            'doneMinutes' => $plan['items']->where('status', 'completed')->sum('duration_minutes'),
        ]);
    }
}
