{{-- Section Skeleton Placeholder --}}
<div class="flex flex-col">
    @for ($i = 0; $i < 4; $i++)
        <div class="flex items-center gap-2.5 px-3 py-1.5">
            <div class="w-3 h-3 rounded-full bg-[#dce0e8] animate-pulse flex-shrink-0"></div>
            <div class="h-2 bg-[#dce0e8] rounded animate-pulse" style="width: {{ [65, 50, 75, 55][$i] }}%;"></div>
        </div>
    @endfor
</div>
