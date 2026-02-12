<div 
    x-data="{ showDropdown: false }" 
    class="flex flex-col bg-zinc-950 text-zinc-100 font-mono border-t-2 border-zinc-800 p-4 space-y-3"
>
    @if($error)
        <div class="bg-red-950 border border-red-800 text-red-200 px-3 py-2 text-xs font-mono uppercase tracking-wider">
            {{ $error }}
        </div>
    @endif

    <div class="space-y-2">
        <flux:textarea 
            wire:model.live="message" 
            placeholder="Commit message"
            rows="auto"
            resize="vertical"
            class="bg-zinc-900 border-zinc-800 text-zinc-100 placeholder-zinc-600 font-mono text-sm focus:border-zinc-700 focus:ring-0"
        />
        
        <div class="flex items-center justify-between text-xs">
            <flux:checkbox 
                wire:click="toggleAmend"
                :checked="$isAmend"
                label="Amend previous commit"
                class="text-zinc-400 font-mono"
            />
            <div class="text-zinc-500 font-mono">
                {{ strlen($message) }} characters
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2">
        <flux:button 
            wire:click="commit"
            variant="primary"
            size="sm"
            :disabled="$stagedCount === 0 || empty(trim($message))"
            class="flex-1 uppercase tracking-wider font-bold"
        >
            {{ $isAmend ? 'Amend' : 'Commit' }} (⌘↵)
        </flux:button>

        <flux:dropdown position="top">
            <flux:button 
                icon:trailing="chevron-up"
                variant="primary"
                size="sm"
                square
                :disabled="$stagedCount === 0 || empty(trim($message))"
            />
            <flux:menu>
                <flux:menu.item wire:click="commit" icon="check">
                    {{ $isAmend ? 'Amend' : 'Commit' }} (⌘↵)
                </flux:menu.item>
                <flux:menu.item wire:click="commitAndPush" icon="arrow-up-tray">
                    Commit & Push (⌘⇧↵)
                </flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </div>

    @if($stagedCount === 0)
        <div class="text-xs text-zinc-600 uppercase tracking-wider text-center font-bold">
            No staged files
        </div>
    @else
        <div class="text-xs text-zinc-500 font-mono text-center">
            {{ $stagedCount }} {{ Str::plural('file', $stagedCount) }} staged
        </div>
    @endif
</div>
