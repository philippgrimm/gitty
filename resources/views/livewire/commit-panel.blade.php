<div 
    x-data="{ showDropdown: false, commitFlash: false, charCount: 0 }" 
    x-init="charCount = $wire.message?.length || 0"
    x-on:committed.window="commitFlash = true; setTimeout(() => commitFlash = false, 200)"
    class="flex flex-col bg-[#eff1f5] text-[#4c4f69] font-mono border-t border-[#ccd0da] p-4 space-y-3"
>
    @if($error)
        <div class="bg-[#d20f39]/10 border border-[#d20f39]/30 text-[#d20f39] px-3 py-2 text-xs font-mono uppercase tracking-wider font-semibold">
            {{ $error }}
        </div>
    @endif

    <div class="space-y-2">
        <flux:textarea 
            wire:model.blur="message" 
            x-on:input="charCount = $event.target.value.length"
            placeholder="Commit message"
            rows="auto"
            resize="vertical"
            class="bg-[#e6e9ef] border-[#ccd0da] text-[#4c4f69] placeholder-[#9ca0b0] font-mono text-sm"
        />
        
        <div class="flex items-center justify-between text-xs">
            <flux:checkbox 
                wire:click="toggleAmend"
                :checked="$isAmend"
                label="Amend previous commit"
                class="text-[#9ca0b0] font-mono"
            />
            <div class="text-[#8c8fa1] font-mono">
                <span x-text="charCount"></span> characters
            </div>
        </div>
    </div>

    <flux:button.group class="w-full">
        <flux:button 
            wire:click="commit"
            wire:loading.attr="disabled"
            wire:target="commit,commitAndPush"
            variant="primary"
            size="sm"
            :disabled="$stagedCount === 0 || empty(trim($message))"
            class="flex-1 font-semibold disabled:!bg-[#ccd0da] disabled:!text-[#8c8fa1] disabled:!border-[#ccd0da] disabled:!shadow-none"
            x-bind:class="{ 
                'animate-commit-flash': commitFlash
            }"
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
                class="disabled:!bg-[#ccd0da] disabled:!text-[#8c8fa1] disabled:!border-[#ccd0da] disabled:!shadow-none"
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
    </flux:button.group>

    @if($stagedCount === 0)
        <div class="text-xs text-[#9ca0b0] uppercase tracking-wider text-center font-medium">
            No staged files
        </div>
    @else
        <div class="text-xs text-[#9ca0b0] font-mono text-center">
            {{ $stagedCount }} {{ Str::plural('file', $stagedCount) }} staged
        </div>
    @endif
</div>
