<x-app-layout title="پروفایل">
    <div class="page-narrow max-w-xl flex flex-col gap-5">
        <h1 class="m-0 text-[22px] font-bold">پروفایل</h1>

        <div class="card p-6">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="card p-6">
            @include('profile.partials.update-password-form')
        </div>

        <div class="card p-6" style="border-color: color-mix(in srgb, var(--bad) 35%, transparent);">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-app-layout>
