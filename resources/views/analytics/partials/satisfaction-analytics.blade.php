@php
    $searchFeedback = $searchFeedback ?? [];
    $feedbackSummary = $feedbackSummary ?? ['up' => 0, 'down' => 0, 'total' => 0];
    $feedbackDays = $feedbackDays ?? 30;
    $feedbackAnalytics = $feedbackAnalytics ?? null;

    // Build daily trend from detail rows
    $dailyBuckets = [];
    $detailRows = is_array($feedbackAnalytics['detail'] ?? null) ? $feedbackAnalytics['detail'] : $searchFeedback;
    foreach ($detailRows as $row) {
        $ts = $row['updated_at'] ?? $row['search_created_at'] ?? $row['created_at'] ?? null;
        if (! $ts) continue;
        $day = date('Y-m-d', strtotime($ts));
        if (! isset($dailyBuckets[$day])) {
            $dailyBuckets[$day] = ['up' => 0, 'down' => 0];
        }
        $vote = (int) ($row['vote'] ?? 0);
        if ($vote === 1) $dailyBuckets[$day]['up']++;
        elseif ($vote === -1) $dailyBuckets[$day]['down']++;
    }
    ksort($dailyBuckets);
    $trendLabels = array_keys($dailyBuckets);
    $trendUp = array_map(fn ($b) => $b['up'], array_values($dailyBuckets));
    $trendDown = array_map(fn ($b) => $b['down'], array_values($dailyBuckets));
    $trendSatisfaction = array_map(function ($b) {
        $total = $b['up'] + $b['down'];
        return $total > 0 ? round(($b['up'] / $total) * 100, 1) : null;
    }, array_values($dailyBuckets));

    // Namespace comparison from by_query grouped data
    $byQuery = is_array($feedbackAnalytics['by_query'] ?? null) ? $feedbackAnalytics['by_query'] : [];
    $nsBuckets = [];
    foreach ($byQuery as $q) {
        $ns = $q['namespace'] ?? 'unknown';
        if (! isset($nsBuckets[$ns])) {
            $nsBuckets[$ns] = ['up' => 0, 'down' => 0, 'total' => 0];
        }
        $nsBuckets[$ns]['up'] += (int) ($q['up'] ?? 0);
        $nsBuckets[$ns]['down'] += (int) ($q['down'] ?? 0);
        $nsBuckets[$ns]['total'] += (int) ($q['total'] ?? 0);
    }
    uksort($nsBuckets, 'strcmp');
@endphp

@if(count($trendLabels) > 1)
    <div class="mt-6 border-t border-gray-100 pt-6">
        <h4 class="text-sm font-semibold text-gray-900 mb-3">Satisfaction trend (last {{ $feedbackDays }} days)</h4>
        <div class="h-56">
            <canvas id="satisfactionTrendChart"></canvas>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('satisfactionTrendChart');
            if (!ctx) return;
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($trendLabels),
                    datasets: [
                        {
                            label: 'Satisfaction %',
                            data: @json($trendSatisfaction),
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            fill: true,
                            tension: 0.3,
                            spanGaps: true,
                            yAxisID: 'y',
                        },
                        {
                            label: 'Thumbs up',
                            data: @json($trendUp),
                            borderColor: 'rgb(74, 222, 128)',
                            borderDash: [4, 4],
                            pointRadius: 2,
                            yAxisID: 'y1',
                        },
                        {
                            label: 'Thumbs down',
                            data: @json($trendDown),
                            borderColor: 'rgb(248, 113, 113)',
                            borderDash: [4, 4],
                            pointRadius: 2,
                            yAxisID: 'y1',
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
                    scales: {
                        y: { type: 'linear', position: 'left', min: 0, max: 100, title: { display: true, text: 'Satisfaction %' }, ticks: { callback: v => v + '%' } },
                        y1: { type: 'linear', position: 'right', min: 0, grid: { drawOnChartArea: false }, title: { display: true, text: 'Count' } },
                    },
                },
            });
        });
        </script>
    </div>
@endif

@if(count($nsBuckets) > 1)
    <div class="mt-6 border-t border-gray-100 pt-6">
        <h4 class="text-sm font-semibold text-gray-900 mb-3">Satisfaction by namespace</h4>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Namespace</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ratings</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Up</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Down</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Satisfaction</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($nsBuckets as $ns => $b)
                        @php $satPct = $b['total'] > 0 ? round(($b['up'] / $b['total']) * 100, 1) : null; @endphp
                        <tr>
                            <td class="px-3 py-2 font-mono text-xs">{{ e($ns) }}</td>
                            <td class="px-3 py-2 text-gray-600">{{ $b['total'] }}</td>
                            <td class="px-3 py-2 text-green-700">{{ $b['up'] }}</td>
                            <td class="px-3 py-2 text-red-700">{{ $b['down'] }}</td>
                            <td class="px-3 py-2">
                                @if($satPct !== null)
                                    <div class="flex items-center gap-2">
                                        <div class="w-16 bg-gray-200 rounded-full h-1.5">
                                            <div class="h-1.5 rounded-full {{ $satPct >= 70 ? 'bg-green-500' : ($satPct >= 40 ? 'bg-yellow-500' : 'bg-red-500') }}" style="width: {{ $satPct }}%"></div>
                                        </div>
                                        <span class="text-xs text-gray-700">{{ $satPct }}%</span>
                                    </div>
                                @else
                                    <span class="text-gray-400">&mdash;</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
