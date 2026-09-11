<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Priority / Schedule') }} — {{ $learningItem->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('learning-items.update', $learningItem) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="priority" :value="__('Priority (1 = highest, 5 = lowest)')" />
                        <select id="priority" name="priority" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            @for ($p = 1; $p <= 5; $p++)
                                <option value="{{ $p }}" @selected($learningItem->priority == $p)>{{ $p }}</option>
                            @endfor
                        </select>
                        <x-input-error :messages="$errors->get('priority')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="daily_time_minutes" :value="__('Daily time (minutes, leave empty = not scheduled)')" />
                        <x-text-input id="daily_time_minutes" name="daily_time_minutes" type="number" min="5" max="480" class="mt-1 block w-full" :value="old('daily_time_minutes', $learningItem->daily_time_minutes)" />
                        <x-input-error :messages="$errors->get('daily_time_minutes')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="preferred_time" :value="__('Preferred time of day (optional)')" />
                        <x-text-input id="preferred_time" name="preferred_time" type="time" class="mt-1 block w-full" :value="old('preferred_time', $learningItem->preferred_time)" />
                        <x-input-error :messages="$errors->get('preferred_time')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            @foreach (['active', 'paused', 'archived', 'maintenance'] as $status)
                                <option value="{{ $status }}" @selected($learningItem->status === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end">
                        <x-primary-button>{{ __('Save') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
