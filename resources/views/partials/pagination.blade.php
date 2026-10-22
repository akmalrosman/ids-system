{{-- Used as $paginator->links('partials.pagination') --}}
@if ($paginator->hasPages())
    <nav class="mt-4 flex items-center justify-between text-sm text-neutral-400" aria-label="Pagination">
        <p>Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}</p>
        <div class="flex gap-2">
            @if ($paginator->onFirstPage())
                <x-ui.button variant="outline" size="sm" disabled icon="chevron-left">Previous</x-ui.button>
            @else
                <x-ui.button variant="outline" size="sm" :href="$paginator->previousPageUrl()" icon="chevron-left">Previous</x-ui.button>
            @endif
            @if ($paginator->hasMorePages())
                <x-ui.button variant="outline" size="sm" :href="$paginator->nextPageUrl()">Next <x-icon name="chevron-right" class="size-4" /></x-ui.button>
            @else
                <x-ui.button variant="outline" size="sm" disabled>Next <x-icon name="chevron-right" class="size-4" /></x-ui.button>
            @endif
        </div>
    </nav>
@endif
