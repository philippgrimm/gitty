@props(['tree', 'staged' => false, 'level' => 0])

<div class="divide-y divide-zinc-800">
    @foreach($tree as $node)
        @if($node['type'] === 'directory')
            <div 
                x-data="{ expanded: true }"
                class="border-b border-zinc-800/50"
            >
                <div 
                    @click="expanded = !expanded"
                    class="group px-4 py-2 hover:bg-zinc-800/30 cursor-pointer transition-colors flex items-center gap-2"
                    style="padding-left: {{ ($level * 16) + 16 }}px"
                >
                    <div 
                        class="text-zinc-400 transition-transform duration-200"
                        :class="expanded ? 'rotate-90' : ''"
                    >
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </div>
                    <div class="text-amber-500/60 text-sm">◆</div>
                    <div class="text-sm font-medium text-zinc-200 group-hover:text-zinc-100 transition-colors">
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
                class="group px-4 py-2.5 hover:bg-zinc-800/30 cursor-pointer transition-colors flex items-center justify-between gap-3"
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
                        <div class="text-sm truncate text-zinc-200 group-hover:text-zinc-100 transition-colors">
                            {{ $node['name'] }}
                        </div>
                    </flux:tooltip>
                </div>
                
                @if($staged)
                    <flux:button 
                        wire:click.stop="unstageFile('{{ $node['path'] }}')"
                        variant="ghost" 
                        size="sm"
                        class="opacity-0 group-hover:opacity-100 transition-opacity"
                    >
                        <span class="text-xs">−</span>
                    </flux:button>
                @else
                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <flux:button 
                            wire:click.stop="stageFile('{{ $node['path'] }}')"
                            variant="ghost" 
                            size="sm"
                        >
                            <span class="text-xs">+</span>
                        </flux:button>
                        <flux:button 
                            @click.stop="showDiscardModal = true; discardAll = false; discardTarget = '{{ $node['path'] }}'"
                            variant="ghost" 
                            size="sm"
                            class="text-red-400 hover:text-red-300"
                        >
                            <span class="text-xs">×</span>
                        </flux:button>
                    </div>
                @endif
            </div>
        @endif
    @endforeach
</div>
