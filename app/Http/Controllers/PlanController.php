<?php

namespace App\Http\Controllers;

use App\Models\PlanItem;
use App\Services\Planning\Planner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function __construct(protected Planner $planner) {}

    public function week(Request $request): View
    {
        return view('week', ['week' => $this->planner->week($request->user())]);
    }

    public function skip(Request $request, PlanItem $planItem): RedirectResponse
    {
        abort_unless($planItem->user_id === $request->user()->id, 403);

        $this->planner->skip($planItem);

        return redirect()->route('home')->with('status', 'باشه، امروز نه.');
    }
}
