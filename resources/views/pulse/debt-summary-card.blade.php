<x-pulse::card :cols="$cols" :rows="$rows" :class="$class">
    <x-pulse::card-header
        name="Debt Summary"
        x-bind:title="`Time: {{ number_format($time) }}ms; Run at: ${formatDate('{{ $runAt }}')};`"
    >
        <x-slot:icon>
            <x-pulse::icons.bug-ant />
        </x-slot:icon>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand" wire:poll.5s="">
        @if ($summary)
            <div class="flex items-center justify-between mb-4">
                <div class="text-3xl font-bold {{ match($summary['grade'] ?? 'D') {
                    'A' => 'text-green-500',
                    'B' => 'text-cyan-500',
                    'C' => 'text-yellow-500',
                    default => 'text-red-500',
                } }}">
                    {{ $summary['grade'] ?? 'N/A' }}
                </div>
                <div class="text-right text-sm text-gray-500 dark:text-gray-400">
                    <div>Score: <span class="font-semibold text-gray-800 dark:text-gray-200">{{ number_format($summary['score'] ?? 0) }}</span></div>
                    <div>Est. Hours: <span class="font-semibold text-gray-800 dark:text-gray-200">{{ number_format($summary['hours'] ?? 0, 1) }}h</span></div>
                    <div>Items: <span class="font-semibold text-gray-800 dark:text-gray-200">{{ number_format($summary['items'] ?? 0) }}</span></div>
                </div>
            </div>

            @if (! empty($summary['byCategory']))
                <table class="w-full text-xs">
                    <thead>
                        <tr class="text-left text-gray-400 dark:text-gray-500">
                            <th class="pb-1 font-medium">Category</th>
                            <th class="pb-1 font-medium text-right">Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($summary['byCategory'] as $category => $score)
                            <tr class="border-t border-gray-100 dark:border-gray-800">
                                <td class="py-1 capitalize">{{ str_replace('_', ' ', $category) }}</td>
                                <td class="py-1 text-right font-semibold">{{ number_format($score ?? 0) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @else
            <div class="h-full flex items-center justify-center p-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    No data yet. Run <code class="text-xs">php artisan debt:scan</code> to populate this card.
                </p>
            </div>
        @endif
    </x-pulse::scroll>
</x-pulse::card>
