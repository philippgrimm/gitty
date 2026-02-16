<div 
    x-data="{ showDropdown: false, commitFlash: false, charCount: 0 }" 
    x-init="charCount = $wire.message?.length || 0"
    x-on:committed.window="commitFlash = true; setTimeout(() => commitFlash = false, 200)"
    class="flex flex-col bg-[#eff1f5] text-[#4c4f69] font-mono border-t border-[#ccd0da] p-3 gap-2"
>
    @if($error)
        <div class="bg-[#d20f39]/10 border border-[#d20f39]/30 text-[#d20f39] px-3 py-2 text-xs font-mono uppercase tracking-wider font-semibold">
            {{ $error }}
        </div>
    @endif

    <div class="relative">
        <flux:textarea 
            wire:model.live.debounce.300ms="message" 
            x-on:input="charCount = $event.target.value.length"
            placeholder="Commit message"
            rows="auto"
            resize="vertical"
            class="bg-[#e6e9ef] border-[#ccd0da] text-[#4c4f69] placeholder-[#9ca0b0] font-mono text-sm focus:outline-none focus:ring-2 focus:ring-[#084CCF]/30 focus:border-[#084CCF]"
        />
        <div class="absolute bottom-2 right-2 text-[10px] text-[#9ca0b0] font-mono pointer-events-none select-none" x-text="charCount"></div>
    </div>

    <flux:button.group class="w-full">
        <flux:button 
            wire:click="commit"
            wire:loading.attr="disabled"
            wire:target="commit,commitAndPush,toggleAmend"
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
                <flux:menu.separator />
                <flux:menu.item wire:click="toggleAmend" :icon="$isAmend ? 'check' : ''">
                    Amend Previous Commit
                </flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </flux:button.group>
</div>
