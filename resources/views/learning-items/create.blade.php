<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('New Learning Item') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('learning-items.store') }}">
                    @csrf

                    <x-input-label for="title" :value="__('What do you want to learn?')" />
                    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" placeholder="e.g. Python, Machine Learning, Software Architecture" :value="old('title')" required autofocus />
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />

                    <div class="mt-4">
                        <x-input-label for="starting_point" :value="__('Where are you now? (optional)')" />
                        <textarea id="starting_point" name="starting_point" rows="3"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            placeholder="e.g. I know basic syntax and loops, never used classes or virtual environments">{{ old('starting_point') }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">{{ __('Helps the AI skip what you already know.') }}</p>
                        <x-input-error :messages="$errors->get('starting_point')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end mt-6">
                        <x-primary-button>{{ __('Create') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
