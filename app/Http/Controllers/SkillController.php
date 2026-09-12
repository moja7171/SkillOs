<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use App\Services\Ai\SkillContentGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class SkillController extends Controller
{
    public function show(Skill $skill): View
    {
        $this->authorizeOwner($skill);

        $skill->load([
            'learningItem',
            'prerequisites.masteryRecords',
            'masteryRecords',
            'resources',
            'activities' => fn ($q) => $q->orderByRaw("case when type = 'learn' then 0 else 1 end")->orderBy('id'),
        ]);

        return view('skills.show', compact('skill'));
    }

    public function generateContent(Skill $skill, SkillContentGenerator $generator): RedirectResponse
    {
        $this->authorizeOwner($skill);

        try {
            $generator->generate($skill);
        } catch (RuntimeException $e) {
            report($e);

            return redirect()->route('skills.show', $skill)
                ->with('error', 'AI الان نتونست محتوای این مهارت رو بسازه. دوباره تلاش کن.');
        }

        return redirect()->route('skills.show', $skill)->with('status', 'متن آموزشی و تمرین‌های این مهارت آماده شد.');
    }

    public function storeVideo(Request $request, Skill $skill): RedirectResponse
    {
        $this->authorizeOwner($skill);

        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2000'],
        ]);

        DB::transaction(function () use ($skill, $validated) {
            $skill->resources()->where('type', 'video')->delete();
            $skill->resources()->where('type', 'text')->update(['is_recommended' => false]);

            $skill->resources()->create([
                'learning_item_id' => $skill->learning_item_id,
                'type' => 'video',
                'title' => 'ویدیو',
                'url' => $validated['url'],
                'is_recommended' => true,
            ]);
        });

        return redirect()->route('skills.show', $skill)->with('status', 'لینک ویدیو ذخیره شد.');
    }

    public function destroyVideo(Skill $skill): RedirectResponse
    {
        $this->authorizeOwner($skill);

        DB::transaction(function () use ($skill) {
            $skill->resources()->where('type', 'video')->delete();
            $skill->resources()->where('type', 'text')->update(['is_recommended' => true]);
        });

        return redirect()->route('skills.show', $skill)->with('status', 'لینک ویدیو حذف شد.');
    }

    protected function authorizeOwner(Skill $skill): void
    {
        abort_unless($skill->learningItem->user_id === auth()->id(), 403);
    }
}
