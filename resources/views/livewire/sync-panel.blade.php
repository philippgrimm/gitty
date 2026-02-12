<div 
    x-data="{ 
        showOutputLog: false, 
        confirmForcePush: false 
    }"
    class="flex items-center gap-1 font-mono"
>
    <flux:tooltip content="Push" position="bottom">
        <flux:button 
            wire:click="syncPush" 
            x-bind:disabled="$wire.isOperationRunning"
            variant="ghost" 
            size="sm"
            square
            class="text-amber-400 hover:text-amber-300 hover:bg-zinc-800 transition-colors"
        >
            @if($isOperationRunning && $lastOperation === 'push')
                <span class="animate-spin">⟳</span>
            @else
                <span class="text-lg">↑</span>
            @endif
        </flux:button>
    </flux:tooltip>

    <flux:tooltip content="Pull" position="bottom">
        <flux:button 
            wire:click="syncPull" 
            x-bind:disabled="$wire.isOperationRunning"
            variant="ghost" 
            size="sm"
            square
            class="text-cyan-400 hover:text-cyan-300 hover:bg-zinc-800 transition-colors"
        >
            @if($isOperationRunning && $lastOperation === 'pull')
                <span class="animate-spin">⟳</span>
            @else
                <span class="text-lg">↓</span>
            @endif
        </flux:button>
    </flux:tooltip>

    <flux:tooltip content="Fetch" position="bottom">
        <flux:button 
            wire:click="syncFetch" 
            x-bind:disabled="$wire.isOperationRunning"
            variant="ghost" 
            size="sm"
            square
            class="text-green-400 hover:text-green-300 hover:bg-zinc-800 transition-colors"
        >
            @if($isOperationRunning && $lastOperation === 'fetch')
                <span class="animate-spin">⟳</span>
            @else
                <span class="text-lg">↻</span>
            @endif
        </flux:button>
    </flux:tooltip>

    <flux:dropdown position="bottom-end">
        <flux:button 
            variant="ghost" 
            size="sm"
            square
            class="text-zinc-400 hover:text-zinc-300 hover:bg-zinc-800 transition-colors"
        >
            <span class="text-lg">⋯</span>
        </flux:button>

        <flux:menu>
            <flux:menu.item wire:click="syncFetchAll" icon="arrow-path">
                @if($isOperationRunning && $lastOperation === 'fetch-all')
                    <span class="animate-spin">⟳</span>
                @endif
                Fetch All
            </flux:menu.item>
            
            <flux:menu.separator />
            
            <flux:menu.item @click="confirmForcePush = true" variant="danger" icon="exclamation-triangle">
                Force Push (Lease)
            </flux:menu.item>

            @if($operationOutput && !$isOperationRunning)
                <flux:menu.separator />
                <flux:menu.item @click="showOutputLog = !showOutputLog" icon="document-text">
                    <span x-text="showOutputLog ? 'Hide' : 'Show'"></span> Operation Log
                </flux:menu.item>
            @endif
        </flux:menu>
    </flux:dropdown>

    @if($operationOutput && !$isOperationRunning)
        <flux:modal x-model="showOutputLog" class="space-y-4">
            <div>
                <flux:heading size="lg" class="font-mono uppercase tracking-wider">Operation Log</flux:heading>
                <flux:subheading class="font-mono">
                    Last operation: <span class="text-zinc-100 font-bold">{{ $lastOperation }}</span>
                </flux:subheading>
            </div>

            <div class="p-4 bg-zinc-900 border border-zinc-800 max-h-96 overflow-y-auto rounded">
                <pre class="text-xs text-zinc-300 whitespace-pre-wrap break-words font-mono">{{ $operationOutput }}</pre>
            </div>

            <div class="flex justify-end">
                <flux:button variant="ghost" @click="showOutputLog = false">Close</flux:button>
            </div>
        </flux:modal>
    @endif

    <flux:modal x-model="confirmForcePush" class="space-y-6">
        <div>
            <flux:heading size="lg" class="font-mono uppercase tracking-wider text-orange-400">Force Push Warning</flux:heading>
            <flux:subheading class="font-mono">
                This will force push with <span class="text-orange-400 font-bold">--force-with-lease</span> to prevent overwriting others' work.
                <br><br>
                <span class="text-zinc-100 font-bold">Are you sure you want to continue?</span>
            </flux:subheading>
        </div>

        <div class="flex gap-2 justify-end">
            <flux:button variant="ghost" @click="confirmForcePush = false">Cancel</flux:button>
            <flux:button 
                variant="danger" 
                @click="$wire.syncForcePushWithLease(); confirmForcePush = false"
            >
                Force Push
            </flux:button>
        </div>
    </flux:modal>
</div>
