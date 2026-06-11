<x-pulse::card :cols="$cols" :rows="$rows" :class="$class">
    <x-pulse::card-header name="Debt Score Trend">
        <x-slot:icon>
            <x-pulse::icons.arrow-trending-up />
        </x-slot:icon>
    </x-pulse::card-header>

    <x-pulse::card-body>
        @if ($scores->isNotEmpty())
            <x-pulse::chart>
                <x-slot:labels>
                    @foreach ($scores->first()['debt_score'] ?? [] as $date => $_)
                        <x-pulse::chart-label>{{ $date }}</x-pulse::chart-label>
                    @endforeach
                </x-slot:labels>

                <x-pulse::chart-line
                    :data="collect($scores->first()['debt_score'] ?? [])->values()"
                    color="rgba(239,68,68,0.5)"
                />
            </x-pulse::chart>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">
                No trend data yet. Run <code class="text-xs">php artisan debt:scan</code> to start tracking score over time.
            </p>
        @endif
    </x-pulse::card-body>
</x-pulse::card>
