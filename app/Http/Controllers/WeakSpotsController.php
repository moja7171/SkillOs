<?php

namespace App\Http\Controllers;

use App\Services\Insights\WeakSpots;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WeakSpotsController extends Controller
{
    public function __invoke(Request $request, WeakSpots $weakSpots): View
    {
        return view('weak-spots.index', ['spots' => $weakSpots->forUser($request->user())]);
    }
}
