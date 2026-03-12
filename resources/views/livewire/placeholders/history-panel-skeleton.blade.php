{{-- History Panel Skeleton Placeholder --}}
<div class="flex flex-col h-full bg-white">
    {{-- Header --}}
    <div class="px-4 py-2 border-b border-[#ccd0da] flex items-center gap-2">
        <div class="h-3 bg-[#dce0e8] rounded animate-pulse" style="width: 120px;"></div>
    </div>

    {{-- Commit rows --}}
    @for ($i = 0; $i < 8; $i++)
        <div class="flex items-center gap-3 px-4 py-2 border-b border-[#eff1f5]">
            {{-- Graph node --}}
            <div class="w-6 h-6 rounded-full bg-[#dce0e8] animate-pulse flex-shrink-0"></div>
            {{-- Commit info --}}
            <div class="flex-1 flex flex-col gap-1">
                <div class="h-2.5 bg-[#dce0e8] rounded animate-pulse" style="width: {{ [75, 60, 85, 55, 70, 80, 50, 65][$i] }}%;"></div>
                <div class="h-2 bg-[#eff1f5] rounded animate-pulse" style="width: 40%;"></div>
            </div>
            {{-- Date --}}
            <div class="h-2 bg-[#dce0e8] rounded animate-pulse flex-shrink-0" style="width: 56px;"></div>
        </div>
    @endfor
</div>
