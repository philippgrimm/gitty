{{-- Repo Sidebar Skeleton Placeholder --}}
<div class="flex flex-col h-full bg-white border-r border-[#ccd0da]">
    {{-- Header skeleton --}}
    <div class="px-3 py-2 border-b border-[#ccd0da]">
        <div class="h-3 bg-[#dce0e8] rounded animate-pulse" style="width: 70%;"></div>
    </div>

    {{-- Section header --}}
    <div class="bg-[#e6e9ef] px-3 py-1.5">
        <div class="h-2.5 bg-[#dce0e8] rounded animate-pulse" style="width: 40%;"></div>
    </div>

    {{-- Branch items --}}
    @for ($i = 0; $i < 5; $i++)
        <div class="flex items-center gap-2.5 px-3 py-1.5">
            <div class="w-2 h-2 rounded-full bg-[#dce0e8] animate-pulse flex-shrink-0"></div>
            <div class="h-2.5 bg-[#dce0e8] rounded animate-pulse" style="width: {{ [60, 75, 50, 80, 65][$i] }}%;"></div>
        </div>
    @endfor

    {{-- Secondary section header --}}
    <div class="bg-[#e6e9ef] px-3 py-1.5 mt-2">
        <div class="h-2.5 bg-[#dce0e8] rounded animate-pulse" style="width: 35%;"></div>
    </div>

    {{-- Secondary items --}}
    @for ($i = 0; $i < 3; $i++)
        <div class="flex items-center gap-2.5 px-3 py-1.5">
            <div class="w-2 h-2 rounded-full bg-[#dce0e8] animate-pulse flex-shrink-0"></div>
            <div class="h-2 bg-[#dce0e8] rounded animate-pulse" style="width: {{ [55, 70, 45][$i] }}%;"></div>
        </div>
    @endfor
</div>
