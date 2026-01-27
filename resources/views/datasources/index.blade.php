<x-layouts.app title="Datasources" subtitle="Gerencie suas fontes de dados">
    <x-slot:actions>
        <a href="{{ route('datasources.create') }}"
           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Novo Datasource
        </a>
    </x-slot:actions>

    {{-- Filters --}}
    <x-card class="mb-6">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="w-40">
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <option value="">Todos</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Ativos</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inativos</option>
                </select>
            </div>

            <div class="w-40">
                <label class="block text-sm font-medium text-gray-700 mb-1">Driver</label>
                <select name="driver" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <option value="">Todos</option>
                    @foreach($drivers as $key => $label)
                        <option value="{{ $key }}" {{ request('driver') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition">
                    Filtrar
                </button>
                @if(request()->hasAny(['status', 'driver']))
                    <a href="{{ route('datasources.index') }}" class="ml-2 px-4 py-2 text-gray-600 hover:text-gray-800">
                        Limpar
                    </a>
                @endif
            </div>
        </form>
    </x-card>

    {{-- Datasources Table --}}
    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Datasource</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conexao</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Driver</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Queries</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acoes</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($datasources as $datasource)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <a href="{{ route('datasources.show', $datasource) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                    {{ $datasource->name }}
                                </a>
                                <p class="text-sm text-gray-500">{{ $datasource->slug }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <code class="bg-gray-100 px-2 py-1 rounded text-xs">
                                    {{ $datasource->host }}:{{ $datasource->port }}/{{ $datasource->database_name }}
                                </code>
                            </td>
                            <td class="px-6 py-4">
                                <x-badge color="purple">{{ $datasource->driver_label }}</x-badge>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $datasource->queries_count }}
                            </td>
                            <td class="px-6 py-4">
                                @if($datasource->is_active)
                                    <x-badge color="green">Ativo</x-badge>
                                @else
                                    <x-badge color="red">Inativo</x-badge>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('datasources.show', $datasource) }}" class="text-gray-600 hover:text-gray-800" title="Ver">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <a href="{{ route('datasources.edit', $datasource) }}" class="text-blue-600 hover:text-blue-800" title="Editar">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('datasources.toggle', $datasource) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-yellow-600 hover:text-yellow-800" title="{{ $datasource->is_active ? 'Desativar' : 'Ativar' }}">
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
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                Nenhum datasource encontrado.
                                <a href="{{ route('datasources.create') }}" class="text-blue-600 hover:text-blue-800 ml-1">
                                    Criar primeiro datasource
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($datasources->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $datasources->links() }}
            </div>
        @endif
    </x-card>

</x-layouts.app>