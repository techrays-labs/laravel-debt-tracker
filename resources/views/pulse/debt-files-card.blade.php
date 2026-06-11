<x-pulse::card :cols="$cols" :rows="$rows" :class="$class">
    <x-pulse::card-header name="Hottest Files">
        <x-slot:icon>
            <x-pulse::icons.sparkles />
        </x-slot:icon>
    </x-pulse::card-header>

    <x-pulse::card-body>
        @if (! empty($files))
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-left text-gray-400 dark:text-gray-500">
                        <th class="pb-1 font-medium">File</th>
                        <th class="pb-1 font-medium text-right">Score</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($files as $file)
                        <tr class="border-t border-gray-100 dark:border-gray-800">
                            <td class="py-1 truncate max-w-xs font-mono text-gray-700 dark:text-gray-300">
                                {{ $file['path'] }}
                            </td>
                            <td class="py-1 text-right font-semibold">{{ number_format($file['score']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">
                No data yet. Run <code class="text-xs">php artisan debt:scan</code> to populate this card.
            </p>
        @endif
    </x-pulse::card-body>
</x-pulse::card>
