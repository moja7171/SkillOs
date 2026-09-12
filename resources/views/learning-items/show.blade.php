@php
    $draft = $learningItem->design_draft;
    $draftSkillsByKey = collect($draft['skills'] ?? [])->keyBy('key');
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $learningItem->title }}
            </h2>
            @if ($learningItem->isDesignApproved())
                <a href="{{ route('learning-items.edit', $learningItem) }}" class="text-sm text-gray-500 hover:text-gray-800 underline">
                    {{ __('Priority / Schedule') }}
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 text-green-800 text-sm rounded-md p-4">
                    {{ session('status') }}
                </div>
            @endif

            @if ($learningItem->starting_point)
                <div class="text-sm text-gray-500">
                    <span class="font-semibold uppercase tracking-wide text-xs">{{ __('Starting point') }}:</span>
                    {{ $learningItem->starting_point }}
                </div>
            @endif

            @if ($learningItem->design_status === 'draft')
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-center">
                    <p class="text-gray-600 mb-4">{{ __('No Outcome or Skill structure yet.') }}</p>
                    <form method="POST" action="{{ route('learning-items.generate-design', $learningItem) }}">
                        @csrf
                        <x-primary-button>{{ __('Generate with AI') }}</x-primary-button>
                    </form>
                </div>
            @endif

            @if ($learningItem->design_status === 'pending_review')
                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4 border-2 border-yellow-300">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-widest text-yellow-700">{{ __('Pending your review') }}</span>
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('learning-items.generate-design', $learningItem) }}">
                                @csrf
                                <button type="submit" class="text-sm text-gray-500 hover:text-gray-800 underline">{{ __('Regenerate') }}</button>
                            </form>
                            <form method="POST" action="{{ route('learning-items.approve-design', $learningItem) }}">
                                @csrf
                                <x-primary-button>{{ __('Approve') }}</x-primary-button>
                            </form>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-1">{{ __('Proposed Outcome') }}</h3>
                        <p class="text-gray-900">{{ $draft['outcome_statement'] ?? '' }}</p>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ __('Proposed Skills') }}</h3>
                        <ol class="space-y-2">
                            @foreach ($draft['skills'] ?? [] as $skill)
                                <li class="border rounded-md p-3">
                                    <div class="font-medium text-gray-900">{{ $skill['name'] }}</div>
                                    <div class="text-sm text-gray-600">{{ $skill['description'] }}</div>
                                    @if (!empty($skill['prerequisite_keys']))
                                        <div class="text-xs text-gray-400 mt-1">
                                            {{ __('Requires') }}:
                                            {{ collect($skill['prerequisite_keys'])->map(fn ($k) => $draftSkillsByKey[$k]['name'] ?? $k)->join(', ') }}
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            @endif

            @if ($learningItem->isDesignApproved())
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-1">{{ __('Outcome') }}</h3>
                    <p class="text-gray-900">{{ $learningItem->outcome_statement }}</p>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">{{ __('Skills') }}</h3>
                    <ol class="space-y-2">
                        @foreach ($learningItem->skills as $skill)
                            <li class="border rounded-md p-3">
                                <div class="font-medium text-gray-900">{{ $skill->name }}</div>
                                @if ($skill->description)
                                    <div class="text-sm text-gray-600">{{ $skill->description }}</div>
                                @endif
                                @if ($skill->prerequisites->isNotEmpty())
                                    <div class="text-xs text-gray-400 mt-1">
                                        {{ __('Requires') }}: {{ $skill->prerequisites->pluck('name')->join(', ') }}
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
