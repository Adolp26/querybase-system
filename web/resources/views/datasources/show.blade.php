<x-layouts.app :title="$datasource->name" subtitle="Detalhes do datasource">
    <x-slot:actions>
        <div class="flex items-center gap-4">
            <form action="{{ route('datasources.toggle', $datasource) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 border rounded-md {{ $datasource->is_active ? 'border-red-300 text-red-700 hover:bg-red-50' : 'border-green-300 text-green-700 hover:bg-green-50' }}">
                    {{ $datasource->is_active ? 'Desativar' : 'Ativar' }}
                </button>
            </form>
            <a href="{{ route('datasources.edit', $datasource) }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Editar
            </a>
        </div>
    </x-slot:actions>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Connection Info --}}
            <x-card title="String de Conexao">
                <div class="bg-gray-900 rounded-lg p-4 text-white font-mono text-sm">
                    {{ $datasource->connection_string }}
                </div>
            </x-card>

            {{-- Connection Details --}}
            <x-card title="Detalhes da Conexao">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-sm text-gray-500">Host</span>
                        <p class="font-medium">{{ $datasource->host }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Porta</span>
                        <p class="font-medium">{{ $datasource->port }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Database/Service</span>
                        <p class="font-medium">{{ $datasource->database_name }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Usuario</span>
                        <p class="font-medium">{{ $datasource->username }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Max Open Connections</span>
                        <p class="font-medium">{{ $datasource->max_open_conns }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Max Idle Connections</span>
                        <p class="font-medium">{{ $datasource->max_idle_conns }}</p>
                    </div>
                </div>
            </x-card>

            {{-- Queries using this datasource --}}
            <x-card title="Queries que usam este Datasource" :padding="false">
                @if($queries->count() > 0)
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Query</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Execucoes</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($queries as $query)
                                <tr>
                                    <td class="px-6 py-4">
                                        <a href="{{ route('queries.show', $query) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                            {{ $query->name }}
                                        </a>
                                        <p class="text-sm text-gray-500">{{ $query->slug }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ number_format($query->executions_count) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($query->is_active)
                                            <x-badge color="green">Ativa</x-badge>
                                        @else
                                            <x-badge color="red">Inativa</x-badge>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-gray-500 text-center py-8">Nenhuma query usa este datasource</p>
                @endif
            </x-card>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Status --}}
            <x-card title="Status">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-gray-600">Status</span>
                    @if($datasource->is_active)
                        <x-badge color="green">Ativo</x-badge>
                    @else
                        <x-badge color="red">Inativo</x-badge>
                    @endif
                </div>
                <div class="flex items-center justify-between mb-4">
                    <span class="text-gray-600">Driver</span>
                    <x-badge color="purple">{{ $datasource->driver_label }}</x-badge>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Criado em</span>
                        <span>{{ $datasource->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Atualizado em</span>
                        <span>{{ $datasource->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </x-card>

            {{-- Stats --}}
            <x-card title="Estatisticas">
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Queries vinculadas</span>
                        <span class="font-bold">{{ $datasource->queries_count }}</span>
                    </div>
                </div>
            </x-card>

            {{-- Test Connection --}}
            <x-card title="Testar Conexao">
                <form action="{{ route('datasources.test-connection', $datasource) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition">
                        Testar Conexao
                    </button>
                </form>
            </x-card>

            {{-- Danger Zone --}}
            <x-card title="Zona de Perigo">
                @if($datasource->queries_count > 0)
                    <p class="text-sm text-gray-500 mb-4">
                        Nao e possivel deletar: existem {{ $datasource->queries_count }} queries usando este datasource.
                    </p>
                    <button disabled class="w-full px-4 py-2 bg-gray-400 text-white rounded-md cursor-not-allowed">
                        Deletar Datasource
                    </button>
                @else
                    <form action="{{ route('datasources.destroy', $datasource) }}" method="POST"
                          onsubmit="return confirm('Tem certeza que deseja deletar este datasource?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition">
                            Deletar Datasource
                        </button>
                    </form>
                @endif
            </x-card>
        </div>
    </div>

</x-layouts.app>