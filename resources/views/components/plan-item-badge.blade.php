{{-- Kind badge for a plan item: review / learn / practice --}}
@props(['item'])
@if ($item->source === 'review')
    <span class="badge badge-l2">مرور</span>
@elseif ($item->activity->isLearn())
    <span class="badge badge-l1">یادگیری</span>
@else
    <span class="badge badge-l3">تمرین</span>
@endif
