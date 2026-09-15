<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Engagement\ActivityStats;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A light, read-only view of everyone on this install (DECISIONS.md §27) — there's no
 * follow/friend graph, just the small closed group this app is built for.
 */
class FriendsController extends Controller
{
    public function __invoke(Request $request, ActivityStats $stats): View
    {
        $users = User::orderByDesc('streak_count')->orderBy('name')->get()
            ->map(fn (User $u) => ['user' => $u, 'week' => $stats->since($u, now()->subDays(7))]);

        return view('friends.index', [
            'users' => $users,
            'me' => $request->user(),
            'registrationCode' => config('app.registration_code'),
        ]);
    }
}
