<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\MasteryRecord;
use App\Services\Engagement\ActivityStats;
use App\Services\Insights\WeakSpots;
use App\Services\Planning\Planner;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request, Planner $planner, ActivityStats $stats, WeakSpots $weakSpots): View
    {
        $user = $request->user();

        $enrollments = $user->enrollments()
            ->with(['course' => fn ($q) => $q->withCount('lessons')])
            ->orderBy('priority')
            ->get()
            ->sortBy(fn (Enrollment $e) => $e->status === 'active' ? 0 : 1)
            ->values();

        // One flat queue in the order the session page's «بعدی» walks it: reviews first,
        // then course priority (DECISIONS.md §68).
        $queue = $planner->orderForLearner($planner->today($user));
        $open = $queue->where('status', 'scheduled')->values();
        $done = $queue->where('status', 'completed');
        // Skipped items leave the day's denominator: «not today» must not read as unfinished.
        $counted = $queue->where('status', '!=', 'skipped');

        $scheduled = $enrollments->filter->isScheduled();
        $lessonIds = Lesson::whereIn('course_id', $scheduled->pluck('course_id'))->select('id');
        $dueReviews = MasteryRecord::where('user_id', $user->id)->whereIn('lesson_id', $lessonIds);

        $backlog = $stats->practiceBacklog($user);

        return view('home', [
            'enrollments' => $enrollments,
            'unscheduled' => $enrollments->where('status', 'active')->reject->isScheduled()->values(),
            'queue' => $queue,
            'next' => $open->first(),
            'openCount' => $open->count(),
            'doneCount' => $done->count(),
            'countedTotal' => $counted->count(),
            'remainingMinutes' => $open->sum('duration_minutes'),
            'extraDueReviews' => max(0, (clone $dueReviews)->where('next_review_due_at', '<=', now())->count() - $open->where('source', 'review')->count()),
            'tomorrowReviews' => (clone $dueReviews)->whereDate('next_review_due_at', today()->addDay())->count(),
            'continueCourse' => $scheduled->where('status', 'active')->first()?->course,
            'streakCount' => $user->currentStreak(),
            'recordedToday' => $user->hasRecordedActivityToday(),
            'daysSinceLastActivity' => $user->streak_last_date ? (int) $user->streak_last_date->diffInDays(today()) : null,
            'weeklyStats' => $stats->since($user, now()->subDays(7)),
            'monthlyStats' => $stats->since($user, now()->subDays(30)),
            'weakSpotCount' => $weakSpots->forUser($user)->count(),
            'practiceBacklog' => $backlog,
        ]);
    }
}
