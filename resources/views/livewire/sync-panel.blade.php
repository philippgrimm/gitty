<div 
    x-data="{ 
        showOutputLog: false, 
        confirmForcePush: false 
    }"
    class="flex items-center gap-1 font-mono"
>
    <flux:tooltip :content="($aheadBehind['ahead'] ?? 0) > 0 ? 'Push (' . $aheadBehind['ahead'] . ')' : 'Push'" position="bottom">
        <div class="relative">
            <flux:button 
                wire:click="syncPush" 
                x-bind:disabled="$wire.isOperationRunning"
                variant="ghost" 
                size="xs"
                square
                class="text-[#8c8fa1] hover:text-[#6c6f85] hover:bg-[#dce0e8] transition-colors flex items-center justify-center"
            >
                @if($isOperationRunning && $lastOperation === 'push')
                    <x-phosphor-circle-notch-light class="w-4 h-4 animate-spin" />
                @else
                    <x-phosphor-arrow-up-light class="w-4 h-4" />
                @endif
            </flux:button>
            @if(($aheadBehind['ahead'] ?? 0) > 0)
                <span style="top: 2px; right: 2px;" class="absolute w-2 h-2 rounded-full bg-[#40a02b] pointer-events-none ring-1 ring-[#eff1f5]"></span>
            @endif
        </div>
    </flux:tooltip>

    <flux:tooltip :content="($aheadBehind['behind'] ?? 0) > 0 ? 'Pull (' . $aheadBehind['behind'] . ')' : 'Pull'" position="bottom">
        <div class="relative">
        <flux:button 
            wire:click="syncPull" 
            x-bind:disabled="$wire.isOperationRunning"
            variant="ghost" 
            size="xs"
            square
            class="text-[#8c8fa1] hover:text-[#6c6f85] hover:bg-[#dce0e8] transition-colors"
        >
            @if($isOperationRunning && $lastOperation === 'pull')
                <x-phosphor-circle-notch-light class="w-4 h-4 animate-spin" />
            @else
                <x-phosphor-arrow-down-light class="w-4 h-4" />
            @endif
        </flux:button>
            @if(($aheadBehind['behind'] ?? 0) > 0)
                <span style="top: 2px; right: 2px;" class="absolute w-2 h-2 rounded-full bg-[#fe640b] pointer-events-none ring-1 ring-[#eff1f5]"></span>
            @endif
        </div>
    </flux:tooltip>

    <flux:tooltip content="Fetch" position="bottom">
        <flux:button 
            wire:click="syncFetch" 
            x-bind:disabled="$wire.isOperationRunning"
            variant="ghost" 
            size="xs"
            square
            class="text-[#8c8fa1] hover:text-[#6c6f85] hover:bg-[#dce0e8] transition-colors"
        >
            @if($isOperationRunning && $lastOperation === 'fetch')
                <x-phosphor-circle-notch-light class="w-4 h-4 animate-spin" />
            @else
                <x-phosphor-arrows-clockwise-light class="w-4 h-4" />
            @endif
        </flux:button>
    </flux:tooltip>

    @if($operationOutput && !$isOperationRunning)
        <flux:modal x-model="showOutputLog" class="space-y-4">
            <div>
                <flux:heading size="lg" class="font-mono uppercase tracking-wider">Operation Log</flux:heading>
                <flux:subheading class="font-mono">
                    Last operation: <span class="text-[#4c4f69] font-semibold">{{ $lastOperation }}</span>
                </flux:subheading>
            </div>

            <div class="p-4 bg-[#e6e9ef] border border-[#ccd0da] max-h-96 overflow-y-auto rounded">
                <pre class="text-xs text-[#6c6f85] whitespace-pre-wrap break-words font-mono">{{ $operationOutput }}</pre>
            </div>

            <div class="flex justify-end">
                <flux:button variant="ghost" @click="showOutputLog = false">Close</flux:button>
            </div>
        </flux:modal>
    @endif

    <flux:modal x-model="confirmForcePush" class="space-y-6">
        <div>
            <flux:heading size="lg" class="font-mono uppercase tracking-wider text-[#fe640b]">Force Push Warning</flux:heading>
            <flux:subheading class="font-mono">
                This will force push with <span class="text-[#fe640b] font-semibold">--force-with-lease</span> to prevent overwriting others' work.
                <br><br>
                <span class="text-[#4c4f69] font-semibold">Are you sure you want to continue?</span>
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
