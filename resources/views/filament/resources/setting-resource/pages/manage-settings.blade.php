<x-filament-panels::page>
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}
        
        <div class="fi-form-actions">
            {{ $this->getFormActions()['save'] }}
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>
