<x-layouts.app title="Queries" subtitle="Gerencie suas queries SQL">
    <x-slot:actions>
        <a href="{{ route('queries.create') }}"
           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nova Query
        </a>
    </x-slot:actions>

    {{-- Filters --}}
    <x-card class="mb-6">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Nome, slug ou descricao..."
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
            </div>

            <div class="w-40">
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <option value="">Todos</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Ativas</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inativas</option>
                </select>
            </div>

            <div class="w-48">
                <label class="block text-sm font-medium text-gray-700 mb-1">Datasource</label>
                <select name="datasource" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <option value="">Todos</option>
                    @foreach($datasources as $ds)
                        <option value="{{ $ds->id }}" {{ request('datasource') === $ds->id ? 'selected' : '' }}>
                            {{ $ds->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition">
                    Filtrar
                </button>
                @if(request()->hasAny(['search', 'status', 'datasource']))
                    <a href="{{ route('queries.index') }}" class="ml-2 px-4 py-2 text-gray-600 hover:text-gray-800">
                        Limpar
                    </a>
                @endif
            </div>
        </form>
    </x-card>

    {{-- Queries Table --}}
    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Query</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Endpoint</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Datasource</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cache</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Execucoes</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acoes</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($queries as $query)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <a href="{{ route('queries.show', $query) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                    {{ $query->name }}
                                </a>
                                @if($query->description)
                                    <p class="text-sm text-gray-500 truncate max-w-xs">{{ Str::limit($query->description, 50) }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <code class="text-sm bg-gray-100 px-2 py-1 rounded">{{ $query->endpoint_url }}</code>
                                @if($query->parameters->count() > 0)
                                    <span class="text-xs text-gray-500 ml-1">({{ $query->parameters->count() }} params)</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $query->datasource?->name ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $query->cache_ttl_human }}
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
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('queries.show', $query) }}" class="text-gray-600 hover:text-gray-800" title="Ver">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <a href="{{ route('queries.edit', $query) }}" class="text-blue-600 hover:text-blue-800" title="Editar">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('queries.toggle', $query) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-yellow-600 hover:text-yellow-800" title="{{ $query->is_active ? 'Desativar' : 'Ativar' }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                Nenhuma query encontrada.
                                <a href="{{ route('queries.create') }}" class="text-blue-600 hover:text-blue-800 ml-1">
                                    Criar primeira query
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($queries->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $queries->links() }}
            </div>
        @endif
    </x-card>

</x-layouts.app>