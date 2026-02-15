@props(['tree', 'staged' => false, 'level' => 0])

<div class="divide-y divide-[#ccd0da]">
    @foreach($tree as $node)
        @if($node['type'] === 'directory')
            <div 
                x-data="{ expanded: true }"
                class="border-b border-[#dce0e8]"
            >
                <div 
                    @click="expanded = !expanded"
                    class="group px-4 py-2 hover:bg-[#dce0e8] cursor-pointer transition-colors flex items-center gap-2"
                    style="padding-left: {{ ($level * 16) + 16 }}px"
                >
                    <div 
                        class="text-[#9ca0b0] transition-transform duration-200"
                        :class="expanded ? 'rotate-90' : ''"
                    >
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </div>
                    <div class="text-[#df8e1d]/60 text-sm">◆</div>
                    <div class="text-sm font-medium text-[#5c5f77] group-hover:text-[#4c4f69] transition-colors">
                        {{ $node['name'] }}
                    </div>
                    <flux:badge variant="solid" color="zinc" class="font-mono text-xs ml-1">
                        {{ count($node['children']) }}
                    </flux:badge>
                </div>
                
                <div x-show="expanded" x-collapse>
                    <x-file-tree :tree="$node['children']" :staged="$staged" :level="$level + 1" />
                </div>
            </div>
        @else
            <div 
                wire:click="selectFile('{{ $node['path'] }}', {{ $staged ? 'true' : 'false' }})"
                class="group px-4 py-2.5 hover:bg-[#dce0e8] cursor-pointer transition-colors flex items-center justify-between gap-3"
                style="padding-left: {{ ($level * 16) + 16 }}px"
            >
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    @php
                        $status = $staged ? $node['indexStatus'] : ($node['worktreeStatus'] ?? $node['indexStatus']);
                        $statusConfig = match($status) {
                            'M' => ['label' => 'M', 'color' => 'yellow', 'icon' => '●'],
                            'A' => ['label' => 'A', 'color' => 'green', 'icon' => '+'],
                            'D' => ['label' => 'D', 'color' => 'red', 'icon' => '−'],
                            'R' => ['label' => 'R', 'color' => 'blue', 'icon' => '→'],
                            'U' => ['label' => 'U', 'color' => 'orange', 'icon' => 'U'],
                            '?' => ['label' => 'U', 'color' => 'green', 'icon' => '?'],
                            default => ['label' => '?', 'color' => 'zinc', 'icon' => '?'],
                        };
                    @endphp
                    <flux:badge variant="solid" color="{{ $statusConfig['color'] }}" class="font-mono text-xs w-6 h-6 flex items-center justify-center">
                        {{ $statusConfig['icon'] }}
                    </flux:badge>
                    <flux:tooltip :content="$node['path']">
                        <div class="text-sm truncate text-[#5c5f77] group-hover:text-[#4c4f69] transition-colors">
                            {{ $node['name'] }}
                        </div>
                    </flux:tooltip>
                </div>
                
                @if($staged)
                    <flux:button 
                        wire:click.stop="unstageFile('{{ $node['path'] }}')"
                        variant="ghost" 
                        size="xs"
                        square
                        class="opacity-0 group-hover:opacity-100 transition-opacity"
                    >
                        <x-phosphor-minus class="w-3.5 h-3.5" />
                    </flux:button>
                @else
                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <flux:button 
                            wire:click.stop="stageFile('{{ $node['path'] }}')"
                            variant="ghost" 
                            size="xs"
                            square
                        >
                            <x-phosphor-plus class="w-3.5 h-3.5" />
                        </flux:button>
                        <flux:button 
                            @click.stop="showDiscardModal = true; discardAll = false; discardTarget = '{{ $node['path'] }}'"
                            variant="ghost" 
                            size="xs"
                            square
                            class="text-[#d20f39] hover:text-[#d20f39]"
                        >
                            <x-phosphor-arrow-counter-clockwise class="w-3.5 h-3.5" />
                        </flux:button>
                    </div>
                @endif
            </div>
        @endif
    @endforeach
</div>
