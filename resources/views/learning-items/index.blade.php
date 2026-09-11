<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Learning Items') }}
            </h2>
            <a href="{{ route('learning-items.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                {{ __('New Learning Item') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if ($learningItems->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-600">
                    {{ __('No Learning Items yet. Create one to get started.') }}
                </div>
            @endif

            @foreach ($learningItems as $item)
                <a href="{{ route('learning-items.show', $item) }}" class="block bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-lg font-semibold text-gray-900">{{ $item->title }}</div>
                            @if ($item->outcome_statement)
                                <div class="text-sm text-gray-500 mt-1">{{ Str::limit($item->outcome_statement, 120) }}</div>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 shrink-0 ms-4">
                            <span @class([
                                'text-xs px-2 py-1 rounded-full font-medium',
                                'bg-gray-100 text-gray-600' => $item->design_status === 'draft',
                                'bg-yellow-100 text-yellow-700' => $item->design_status === 'pending_review',
                                'bg-green-100 text-green-700' => $item->design_status === 'approved',
                            ])>
                                {{ str($item->design_status)->replace('_', ' ')->title() }}
                            </span>
                            @if ($item->daily_time_minutes)
                                <span class="text-xs px-2 py-1 rounded-full bg-blue-50 text-blue-700 font-medium">
                                    {{ $item->daily_time_minutes }} {{ __('min/day') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</x-app-layout>
