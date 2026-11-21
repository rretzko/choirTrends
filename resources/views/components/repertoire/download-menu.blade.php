<flux:dropdown position="bottom" align="end">
    <flux:button type="button" variant="ghost" size="sm" icon="arrow-down-tray" icon:trailing="chevron-down">
        {{ __('Download') }}
    </flux:button>

    <flux:menu>
        <flux:menu.item icon="document-text" wire:click="downloadResults('txt')">{{ __('Text (.txt)') }}</flux:menu.item>
        <flux:menu.item icon="table-cells" wire:click="downloadResults('csv')">{{ __('Spreadsheet (.csv)') }}</flux:menu.item>
        <flux:menu.item icon="document" wire:click="downloadResults('pdf')">{{ __('PDF (.pdf)') }}</flux:menu.item>
    </flux:menu>
</flux:dropdown>
