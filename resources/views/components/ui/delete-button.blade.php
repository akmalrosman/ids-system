{{-- <x-ui.delete-button :action="route('flights.destroy', $flight)" confirm="Delete flight 4001?" /> --}}
@props(['action', 'confirm' => 'Are you sure? This cannot be undone.', 'label' => 'Delete'])

<form method="POST" action="{{ $action }}" onsubmit="return confirm(@js($confirm))" {{ $attributes }}>
    @csrf
    @method('DELETE')
    <x-ui.button type="submit" variant="danger" icon="trash-2">{{ $label }}</x-ui.button>
</form>
