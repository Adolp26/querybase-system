<x-layouts.app :title="$query->name" subtitle="Detalhes da query">
    <x-slot:actions>
        <div class="flex items-center gap-4">
            <form action="{{ route('queries.toggle', $query) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 border rounded-md {{ $query->is_active ? 'border-red-300 text-red-700 hover:bg-red-50' : 'border-green-300 text-green-700 hover:bg-green-50' }}">
                    {{ $query->is_active ? 'Desativar' : 'Ativar' }}
                </button>
            </form>
            <form action="{{ route('queries.duplicate', $query) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Duplicar
                </button>
            </form>
            <a href="{{ route('queries.edit', $query) }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Editar
            </a>
        </div>
    </x-slot:actions>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Endpoint Info --}}
            <x-card title="Endpoint da API">
                <div class="bg-gray-900 rounded-lg p-4 text-white font-mono text-sm">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="bg-green-600 px-2 py-1 rounded text-xs">GET</span>
                        <span>http://localhost:8080{{ $query->endpoint_url }}</span>
                    </div>
                    @if($query->parameters->count() > 0)
                        <div class="text-gray-400 text-xs mt-2">
                            Parametros:
                            @foreach($query->parameters as $param)
                                <span class="text-yellow-400">{{ $param->name }}</span>={{ $param->param_type }}{{ !$loop->last ? ', ' : '' }}
                            @endforeach
                        </div>
                    @endif
                </div>

                @if($query->parameters->count() > 0)
                    <div class="mt-4">
                        <p class="text-sm text-gray-600 mb-2">Exemplo de chamada:</p>
                        <code class="text-sm bg-gray-100 px-3 py-2 rounded block break-all">
                            curl "http://localhost:8080{{ $query->endpoint_url }}@if($query->parameters->count() > 0)?@foreach($query->parameters as $param){{ $param->name }}=<valor>{{ !$loop->last ? '&' : '' }}@endforeach @endif"
                        </code>
                    </div>
                @endif
            </x-card>

            {{-- SQL Query --}}
            <x-card title="SQL">
                <pre class="bg-gray-900 text-green-400 rounded-lg p-4 text-sm overflow-x-auto font-mono">{{ $query->sql_query }}</pre>
            </x-card>

            {{-- Parameters --}}
            @if($query->parameters->count() > 0)
                <x-card title="Parametros" :padding="false">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Posicao</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Obrigatorio</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Padrao</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($query->parameters as $param)
                                <tr>
                                    <td class="px-6 py-4 text-sm">
                                        <code class="bg-gray-100 px-2 py-1 rounded">:{{ $param->position }}</code>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium">{{ $param->name }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        <x-badge color="blue">{{ $param->type_label }}</x-badge>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        @if($param->is_required)
                                            <x-badge color="red">Sim</x-badge>
                                        @else
                                            <x-badge color="gray">Nao</x-badge>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ $param->default_value ?? '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-card>
            @endif

            {{-- Recent Executions --}}
            <x-card title="Ultimas Execucoes" :padding="false">
                @if($recentExecutions->count() > 0)
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duracao</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cache</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Linhas</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($recentExecutions as $exec)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ $exec->executed_at->format('d/m/Y H:i:s') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        {{ $exec->duration_human }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        @if($exec->cache_hit)
                                            <x-badge color="green">HIT</x-badge>
                                        @else
                                            <x-badge color="yellow">MISS</x-badge>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ number_format($exec->row_count ?? 0) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        @if($exec->error)
                                            <x-badge color="red">Erro</x-badge>
                                        @else
                                            <x-badge color="green">OK</x-badge>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-gray-500 text-center py-8">Nenhuma execucao registrada</p>
                @endif
            </x-card>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Status --}}
            <x-card title="Status">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-gray-600">Status</span>
                    @if($query->is_active)
                        <x-badge color="green">Ativa</x-badge>
                    @else
                        <x-badge color="red">Inativa</x-badge>
                    @endif
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Criada em</span>
                        <span>{{ $query->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Atualizada em</span>
                        <span>{{ $query->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                    @if($query->created_by)
                        <div class="flex justify-between">
                            <span class="text-gray-500">Criada por</span>
                            <span>{{ $query->created_by }}</span>
                        </div>
                    @endif
                </div>
            </x-card>

            {{-- Execution Stats --}}
            <x-card title="Estatisticas">
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Total de execucoes</span>
                        <span class="font-bold">{{ number_format($executionStats['total']) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Ultimas 24h</span>
                        <span class="font-bold">{{ number_format($executionStats['last_24h']) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Cache hit rate</span>
                        <span class="font-bold text-green-600">{{ $executionStats['cache_hit_rate'] }}%</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Tempo medio</span>
                        <span class="font-bold">{{ round($executionStats['avg_duration'] ?? 0) }}ms</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Erros</span>
                        <span class="font-bold {{ $executionStats['error_count'] > 0 ? 'text-red-600' : '' }}">
                            {{ $executionStats['error_count'] }}
                        </span>
                    </div>
                </div>
            </x-card>

            {{-- Config --}}
            <x-card title="Configuracoes">
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Datasource</span>
                        <span>{{ $query->datasource?->name ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Cache TTL</span>
                        <span>{{ $query->cache_ttl_human }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Timeout</span>
                        <span>{{ $query->timeout_seconds }}s</span>
                    </div>
                </div>
            </x-card>

            {{-- Danger Zone --}}
            <x-card title="Zona de Perigo">
                <form action="{{ route('queries.destroy', $query) }}" method="POST"
                      onsubmit="return confirm('Tem certeza que deseja deletar esta query?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition">
                        Deletar Query
                    </button>
                </form>
            </x-card>
        </div>
    </div>

</x-layouts.app>