{{--
    Label + control + validation error for one form field.
    <x-ui.field label="Flight number" name="flight_number" required>
        <input class="form-control" ...>
    </x-ui.field>
--}}
@props(['label', 'name', 'hint' => null, 'required' => false])

<div {{ $attributes->class('space-y-2') }}>
    <label for="{{ $name }}" class="block text-sm font-medium text-neutral-200">
        {{ $label }}@if ($required)<span class="text-red-400"> *</span>@endif
    </label>

    {{ $slot }}

    @if ($hint)
        <p class="text-xs text-neutral-500">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="text-xs text-red-400">{{ $message }}</p>
    @enderror
</div>
