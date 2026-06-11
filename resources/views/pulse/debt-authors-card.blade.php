<x-pulse::card :cols="$cols" :rows="$rows" :class="$class">
    <x-pulse::card-header
        name="Top Debt Authors"
        x-bind:title="`Time: {{ number_format($time) }}ms; Run at: ${formatDate('{{ $runAt }}')};`"
    >
        <x-slot:icon>
            <x-pulse::icons.queue-list />
        </x-slot:icon>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand" wire:poll.5s="">
        @if (! empty($authors))
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-left text-gray-400 dark:text-gray-500">
                        <th class="pb-1 font-medium">#</th>
                        <th class="pb-1 font-medium">Author</th>
                        <th class="pb-1 font-medium text-right">Score</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($authors as $author => $score)
                        <tr class="border-t border-gray-100 dark:border-gray-800">
                            <td class="py-1 text-gray-400 dark:text-gray-500 w-6">{{ $loop->iteration }}</td>
                            <td class="py-1 text-gray-700 dark:text-gray-300">{{ $author ?? 'Unknown' }}</td>
                            <td class="py-1 text-right font-semibold">{{ number_format($score ?? 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="h-full flex items-center justify-center p-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    No data yet. Run <code class="text-xs">php artisan debt:scan</code> to populate this card.
                </p>
            </div>
        @endif
    </x-pulse::scroll>
</x-pulse::card>
