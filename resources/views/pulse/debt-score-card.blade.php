<x-pulse::card :cols="$cols" :rows="$rows" :class="$class">
    <x-pulse::card-header
        name="Debt Score Trend"
        x-bind:title="`Time: {{ number_format($time) }}ms; Run at: ${formatDate('{{ $runAt }}')};`"
        details="past {{ $this->periodForHumans() }}"
    >
        <x-slot:icon>
            <x-pulse::icons.arrow-trending-up />
        </x-slot:icon>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand" wire:poll.5s="">
        @if ($scores->isNotEmpty())
            @php
                $firstKey = $scores->keys()->first();
                $scoreData = $scores[$firstKey]['debt_score'] ?? collect();
                $maxValue = $scoreData->filter()->max() ?? 0;
            @endphp

            <div class="mb-3 text-right text-xs text-gray-500 dark:text-gray-400">
                Peak: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ number_format($maxValue) }}</span>
            </div>

            <div
                wire:ignore
                class="h-24"
                x-data="debtScoreChart({
                    labels: @js($scoreData->keys()),
                    data: @js($scoreData->values()),
                })"
            >
                <canvas x-ref="canvas" class="ring-1 ring-gray-900/5 dark:ring-gray-100/10 bg-gray-50 dark:bg-gray-800 rounded-md shadow-sm"></canvas>
            </div>

            <table class="w-full text-xs mt-4">
                <thead>
                    <tr class="text-left text-gray-400 dark:text-gray-500">
                        <th class="pb-1 font-medium">Period</th>
                        <th class="pb-1 font-medium text-right">Max Score</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($scoreData as $period => $value)
                        @if ($value !== null)
                            <tr class="border-t border-gray-100 dark:border-gray-800">
                                <td class="py-1">{{ $period }}</td>
                                <td class="py-1 text-right font-semibold">{{ number_format($value) }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="h-full flex items-center justify-center p-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    No trend data yet. Run <code class="text-xs">php artisan debt:scan</code> to start tracking score over time.
                </p>
            </div>
        @endif
    </x-pulse::scroll>
</x-pulse::card>

@script
<script>
Alpine.data('debtScoreChart', (config) => ({
    init() {
        let chart = new Chart(
            this.$refs.canvas,
            {
                type: 'line',
                data: {
                    labels: config.labels.map(formatDate),
                    datasets: [
                        {
                            label: 'Debt Score',
                            borderColor: 'rgba(239,68,68,0.7)',
                            backgroundColor: 'rgba(239,68,68,0.1)',
                            data: config.data,
                            fill: true,
                        },
                    ],
                },
                options: {
                    maintainAspectRatio: false,
                    layout: {
                        autoPadding: false,
                        padding: {
                            top: 1,
                        },
                    },
                    datasets: {
                        line: {
                            borderWidth: 2,
                            borderCapStyle: 'round',
                            pointHitRadius: 10,
                            pointStyle: false,
                            tension: 0.2,
                            spanGaps: false,
                            segment: {
                                borderColor: (ctx) => ctx.p0.raw === 0 && ctx.p1.raw === 0 ? 'transparent' : undefined,
                            },
                        },
                    },
                    scales: {
                        x: {
                            display: false,
                        },
                        y: {
                            display: false,
                            min: 0,
                        },
                    },
                    plugins: {
                        legend: {
                            display: false,
                        },
                        tooltip: {
                            mode: 'index',
                            position: 'nearest',
                            intersect: false,
                        },
                    },
                },
            }
        )

        Livewire.on('debt-score-chart-update', ({ scores }) => {
            if (chart === undefined) {
                return
            }

            chart.data.labels = Object.keys(scores).map(formatDate)
            chart.data.datasets[0].data = Object.values(scores)
            chart.update()
        })
    },
}))
</script>
@endscript
