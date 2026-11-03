<?php

namespace App\Http\Controllers;

use App\Services\Review\ReviewQueue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * «شروع مرور»: opens the next due review (DECISIONS.md §70).
     */
    public function start(Request $request, ReviewQueue $queue): RedirectResponse
    {
        $attempt = $queue->start($request->user());

        if ($attempt === null) {
            return redirect()->route('home')->with('status', 'الان مروری برای نشون دادن نیست.');
        }

        return redirect()->route('session.show', $attempt);
    }
}
