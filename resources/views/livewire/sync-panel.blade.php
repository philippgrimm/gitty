<div 
    x-data="{ 
        showOutputLog: false, 
        confirmForcePush: false 
    }"
    class="h-full flex flex-col bg-zinc-950 text-zinc-100 font-mono border-l-2 border-zinc-800"
>
    <style>
        .sync-button {
            @apply flex items-center gap-2 px-4 py-3 bg-zinc-900 border-2 border-zinc-800 hover:border-zinc-700 transition-colors uppercase tracking-wider text-sm font-bold;
        }
        .sync-button:disabled {
            @apply opacity-50 cursor-not-allowed;
        }
        .sync-button-push {
            @apply text-amber-400 hover:text-amber-300;
        }
        .sync-button-pull {
            @apply text-cyan-400 hover:text-cyan-300;
        }
        .sync-button-fetch {
            @apply text-green-400 hover:text-green-300;
        }
    </style>

    <div class="border-b-2 border-zinc-800 px-4 py-3">
        <div class="text-xs uppercase tracking-widest font-bold text-zinc-400">Sync Operations</div>
    </div>

    @if($error)
        <div class="border-b-2 border-red-800 bg-red-950 px-4 py-3">
            <div class="flex items-start justify-between gap-3">
                <div class="flex-1">
                    <div class="text-xs uppercase tracking-wider font-bold text-red-200 mb-1">Error</div>
                    <div class="text-sm text-red-100 font-mono">{{ $error }}</div>
                </div>
                <flux:button 
                    wire:click="$set('error', '')"
                    variant="ghost" 
                    size="sm"
                    class="text-red-400 hover:text-red-300"
                >
                    ✕
                </flux:button>
            </div>
        </div>
    @endif

    <div class="flex-1 flex flex-col p-4 gap-3">
        <button 
            wire:click="syncPush" 
            :disabled="$wire.isOperationRunning"
            class="sync-button sync-button-push"
        >
            <span class="text-lg">↑</span>
            <span>Push</span>
            @if($isOperationRunning && $lastOperation === 'push')
                <span class="ml-auto animate-spin">⟳</span>
            @endif
        </button>

        <button 
            wire:click="syncPull" 
            :disabled="$wire.isOperationRunning"
            class="sync-button sync-button-pull"
        >
            <span class="text-lg">↓</span>
            <span>Pull</span>
            @if($isOperationRunning && $lastOperation === 'pull')
                <span class="ml-auto animate-spin">⟳</span>
            @endif
        </button>

        <button 
            wire:click="syncFetch" 
            :disabled="$wire.isOperationRunning"
            class="sync-button sync-button-fetch"
        >
            <span class="text-lg">↻</span>
            <span>Fetch</span>
            @if($isOperationRunning && $lastOperation === 'fetch')
                <span class="ml-auto animate-spin">⟳</span>
            @endif
        </button>

        <button 
            wire:click="syncFetchAll" 
            :disabled="$wire.isOperationRunning"
            class="sync-button sync-button-fetch"
        >
            <span class="text-lg">⇄</span>
            <span>Fetch All</span>
            @if($isOperationRunning && $lastOperation === 'fetch-all')
                <span class="ml-auto animate-spin">⟳</span>
            @endif
        </button>

        <div class="border-t-2 border-zinc-800 pt-3 mt-2">
            <button 
                @click="confirmForcePush = true"
                :disabled="$wire.isOperationRunning"
                class="sync-button w-full text-orange-400 hover:text-orange-300"
            >
                <span class="text-lg">⚠</span>
                <span>Force Push (Lease)</span>
                @if($isOperationRunning && $lastOperation === 'force-push')
                    <span class="ml-auto animate-spin">⟳</span>
                @endif
            </button>
        </div>

        @if($operationOutput && !$isOperationRunning)
            <div class="border-t-2 border-zinc-800 pt-3 mt-2">
                <button 
                    @click="showOutputLog = !showOutputLog"
                    class="flex items-center justify-between w-full px-3 py-2 bg-zinc-900 border border-zinc-800 hover:border-zinc-700 transition-colors"
                >
                    <span class="text-xs uppercase tracking-wider text-zinc-400">Operation Log</span>
                    <span class="text-zinc-500" x-text="showOutputLog ? '▼' : '▶'"></span>
                </button>

                <div 
                    x-show="showOutputLog"
                    x-transition
                    class="mt-2 p-3 bg-zinc-900 border border-zinc-800 max-h-64 overflow-y-auto"
                >
                    <pre class="text-xs text-zinc-300 whitespace-pre-wrap break-words">{{ $operationOutput }}</pre>
                </div>
            </div>
        @endif
    </div>

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
