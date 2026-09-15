<?php

namespace App\Http\Controllers;

use App\Services\Engagement\ActivityStats;
use App\Services\Planning\Planner;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request, Planner $planner, ActivityStats $stats): View
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

        $daysSinceLastActivity = $user->streak_last_date ? $user->streak_last_date->diffInDays(today()) : null;

        return view('home', [
            'enrollments' => $enrollments,
            'primary' => $plan['primary'],
            'alternatives' => $plan['alternatives'],
            'todayByEnrollment' => $todayByEnrollment,
            'plannedMinutes' => $plan['items']->sum('duration_minutes'),
            'doneMinutes' => $plan['items']->where('status', 'completed')->sum('duration_minutes'),
            'streakCount' => $user->streak_count,
            'daysSinceLastActivity' => $daysSinceLastActivity,
            'weeklyStats' => $stats->since($user, now()->subDays(7)),
            'monthlyStats' => $stats->since($user, now()->subDays(30)),
        ]);
    }
}
