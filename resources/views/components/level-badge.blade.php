@props(['level' => 'not_started'])
@php $i = \App\Models\MasteryRecord::LEVEL_INDEX[$level] ?? 0; @endphp
<span {{ $attributes->merge(['class' => "badge badge-l{$i}"]) }}><span class="dot"></span>{{ \App\Models\MasteryRecord::LEVEL_LABELS[$level] ?? $level }}</span>
