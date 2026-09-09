<x-filament-panels::page>
    <form wire:submit="send">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit">
                ارسال به همه‌ی کاربران
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
