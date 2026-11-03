<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Services\Insights\WeakSpots;
use App\Services\Review\ReviewQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class HomeController extends Controller
{
    /** Persian week order, Saturday first. */
    private const WEEK_LABELS = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];

    public function __invoke(Request $request, ReviewQueue $reviews, WeakSpots $weakSpots): View
    {
        $user = $request->user();

        // «دوره‌های من», the course with the latest progress first (DECISIONS.md §70).
        $enrollments = $user->enrollments()
            ->with('course')
            ->get()
            ->sortBy([
                fn (Enrollment $a, Enrollment $b) => ($b->last_activity_at?->timestamp ?? 0) <=> ($a->last_activity_at?->timestamp ?? 0),
                fn (Enrollment $a, Enrollment $b) => $a->id <=> $b->id,
            ])
            ->values();

        $mine = $enrollments->map(function (Enrollment $enrollment) use ($user) {
            $course = $enrollment->course->loadCount('lessons');
            $next = $course->nextLessonFor($user);
            $done = $course->doneLessonIdsFor($user)->count();

            return [
                'course' => $course,
                'next' => $next,
                'position' => $next ? $next->order + 1 : null,
                'percent' => $course->lessons_count ? (int) round($done / $course->lessons_count * 100) : 0,
            ];
        });

        $others = Course::withCount('lessons')
            ->whereNotIn('id', $enrollments->pluck('course_id'))
            ->orderByRaw('category_order is null')
            ->orderBy('category_order')
            ->orderBy('title')
            ->get();

        $due = $reviews->due($user);

        return view('home', [
            'mine' => $mine,
            'others' => $others,
            'dueCount' => $due->count(),
            'dueByCourse' => $due->groupBy(fn ($record) => $record->lesson->course_id)
                ->map(fn ($group) => ['course' => $group->first()->lesson->course, 'count' => $group->count()])
                ->values(),
            'reviewsDoneToday' => $reviews->doneToday($user),
            'dueTomorrow' => $reviews->dueTomorrow($user),
            'streakCount' => $user->currentStreak(),
            'week' => $this->weekDots($user),
            'weakSpotCount' => $weakSpots->forUser($user)->count(),
        ]);
    }

    /**
     * This week (Saturday to Friday): which days the learner finished something.
     *
     * @return array<int, array{label: string, state: string}>
     */
    private function weekDots($user): array
    {
        $start = now()->startOfWeek(Carbon::SATURDAY)->startOfDay();
        $active = $user->attempts()
            ->where('completed_at', '>=', $start)
            ->pluck('completed_at')
            ->map(fn ($at) => $at->toDateString())
            ->unique();

        return collect(self::WEEK_LABELS)->map(function (string $label, int $i) use ($start, $active) {
            $day = $start->copy()->addDays($i);

            return ['label' => $label, 'state' => match (true) {
                $active->contains($day->toDateString()) => 'done',
                $day->isToday() => 'today',
                $day->isFuture() => 'future',
                default => 'missed',
            }];
        })->all();
    }
}
