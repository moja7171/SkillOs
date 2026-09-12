<?php

namespace App\Http\Controllers;

use App\Models\LearningItem;
use App\Services\Ai\LearningDesignGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LearningItemController extends Controller
{
    public function index(Request $request): View
    {
        $learningItems = $request->user()->learningItems()->latest()->get();

        return view('learning-items.index', compact('learningItems'));
    }

    public function create(): View
    {
        return view('learning-items.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'starting_point' => ['nullable', 'string', 'max:2000'],
        ]);

        $item = $request->user()->learningItems()->create($validated);

        return redirect()->route('learning-items.show', $item);
    }

    public function show(LearningItem $learningItem): View
    {
        $this->authorizeOwner($learningItem);

        $learningItem->load(['skills.prerequisites']);

        return view('learning-items.show', compact('learningItem'));
    }

    public function edit(LearningItem $learningItem): View
    {
        $this->authorizeOwner($learningItem);

        return view('learning-items.edit', compact('learningItem'));
    }

    public function update(Request $request, LearningItem $learningItem): RedirectResponse
    {
        $this->authorizeOwner($learningItem);

        $validated = $request->validate([
            'priority' => ['required', 'integer', 'min:1', 'max:5'],
            'daily_time_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
            'preferred_time' => ['nullable', 'date_format:H:i'],
            'status' => ['required', 'in:active,paused,archived,maintenance'],
        ]);

        $learningItem->update($validated);

        return redirect()->route('learning-items.show', $learningItem);
    }

    public function generateDesign(LearningItem $learningItem, LearningDesignGenerator $generator): RedirectResponse
    {
        $this->authorizeOwner($learningItem);

        // Once approved, skills carry mastery and attempts; the design can no longer be thrown away.
        abort_if($learningItem->isDesignApproved(), 403, 'The design is already approved.');

        $draft = $generator->generate($learningItem);

        $learningItem->update([
            'design_draft' => $draft,
            'design_status' => 'pending_review',
        ]);

        return redirect()->route('learning-items.show', $learningItem)
            ->with('status', 'AI proposed an Outcome and Skill structure. Review it below.');
    }

    public function approveDesign(LearningItem $learningItem, LearningDesignGenerator $generator): RedirectResponse
    {
        $this->authorizeOwner($learningItem);

        $generator->applyDraft($learningItem);

        return redirect()->route('learning-items.show', $learningItem)
            ->with('status', 'Design approved. Skills are live.');
    }

    protected function authorizeOwner(LearningItem $learningItem): void
    {
        abort_unless($learningItem->user_id === auth()->id(), 403);
    }
}
