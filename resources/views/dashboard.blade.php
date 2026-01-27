<x-layouts.app title="Dashboard" subtitle="Visao geral do sistema">

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <x-stat-card label="Total de Queries" :value="$totalQueries" color="blue" />
        <x-stat-card label="Queries Ativas" :value="$activeQueries" color="green" />
        <x-stat-card label="Datasources" :value="$totalDatasources" color="purple" />
        <x-stat-card label="Datasources Ativos" :value="$activeDatasources" color="green" />
    </div>

    {{-- Execution Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <x-stat-card
            label="Execucoes (7 dias)"
            :value="number_format($executionStats['total_executions'])"
            color="blue"
        />
        <x-stat-card
            label="Taxa de Cache Hit"
            :value="$executionStats['cache_hit_rate'] . '%'"
            color="green"
        />
        <x-stat-card
            label="Tempo Medio"
            :value="$executionStats['avg_duration_ms'] . 'ms'"
            color="yellow"
        />
        <x-stat-card
            label="Taxa de Erro"
            :value="$executionStats['error_rate'] . '%'"
            :color="$executionStats['error_rate'] > 5 ? 'red' : 'green'"
        />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Top Queries --}}
        <x-card title="Queries Mais Executadas">
            @if(count($topQueries) > 0)
                <div class="space-y-4">
                    @foreach($topQueries as $query)
                        <div class="flex items-center justify-between">
                            <div>
                                <a href="{{ route('queries.index', ['search' => $query['query_slug']]) }}"
                                   class="text-blue-600 hover:text-blue-800 font-medium">
                                    {{ $query['query_slug'] }}
                                </a>
                                <p class="text-sm text-gray-500">
                                    {{ number_format($query['executions']) }} execucoes
                                </p>
                            </div>
                            <div class="text-right text-sm text-gray-500">
                                {{ round($query['avg_duration']) }}ms medio
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-center py-4">Nenhuma execucao registrada</p>
            @endif
        </x-card>

        {{-- Recent Queries --}}
        <x-card title="Queries Recentes">
            @if($recentQueries->count() > 0)
                <div class="space-y-4">
                    @foreach($recentQueries as $query)
                        <div class="flex items-center justify-between">
                            <div>
                                <a href="{{ route('queries.show', $query) }}"
                                   class="text-blue-600 hover:text-blue-800 font-medium">
                                    {{ $query->name }}
                                </a>
                                <p class="text-sm text-gray-500">
                                    {{ $query->datasource?->name ?? 'Sem datasource' }}
                                </p>
                            </div>
                            <div class="text-right">
                                @if($query->is_active)
                                    <x-badge color="green">Ativa</x-badge>
                                @else
                                    <x-badge color="red">Inativa</x-badge>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-center py-4">Nenhuma query cadastrada</p>
            @endif
        </x-card>

        {{-- Slowest Queries --}}
        <x-card title="Queries Mais Lentas">
            @if(count($slowestQueries) > 0)
                <div class="space-y-4">
                    @foreach($slowestQueries as $query)
                        <div class="flex items-center justify-between">
                            <div>
                                <a href="{{ route('queries.index', ['search' => $query['query_slug']]) }}"
                                   class="text-blue-600 hover:text-blue-800 font-medium">
                                    {{ $query['query_slug'] }}
                                </a>
                                <p class="text-sm text-gray-500">
                                    {{ number_format($query['executions']) }} execucoes
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="text-red-600 font-medium">
                                    {{ round($query['avg_duration']) }}ms
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-center py-4">Nenhuma execucao registrada</p>
            @endif
        </x-card>

        {{-- Recent Executions --}}
        <x-card title="Ultimas Execucoes" :padding="false">
            @if($recentExecutions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Query</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duracao</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cache</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($recentExecutions as $execution)
                                <tr>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="font-medium">{{ $execution->query_slug }}</span>
                                        <br>
                                        <span class="text-gray-500 text-xs">
                                            {{ $execution->executed_at->diffForHumans() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        {{ $execution->duration_human }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if($execution->cache_hit)
                                            <x-badge color="green">HIT</x-badge>
                                        @else
                                            <x-badge color="yellow">MISS</x-badge>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if($execution->error)
                                            <x-badge color="red">Erro</x-badge>
                                        @else
                                            <x-badge color="green">OK</x-badge>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500 text-center py-8">Nenhuma execucao registrada</p>
            @endif
        </x-card>
    </div>

</x-layouts.app>