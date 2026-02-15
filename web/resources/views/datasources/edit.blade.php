<x-layouts.app :title="'Editar: ' . $datasource->name" subtitle="Modifique a fonte de dados">
    <x-slot:actions>
        <a href="{{ route('datasources.show', $datasource) }}" class="text-gray-600 hover:text-gray-800">
            Voltar para detalhes
        </a>
    </x-slot:actions>

    <form action="{{ route('datasources.update', $datasource) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form --}}
            <div class="lg:col-span-2 space-y-6">
                <x-card title="Informacoes Basicas">
                    <div class="space-y-4">
                        <x-form.input name="name" label="Nome do Datasource" required :value="$datasource->name" />

                        <x-form.input name="slug" label="Slug" :value="$datasource->slug"
                                      help="Alterar o slug pode afetar referencias existentes" />

                        <x-form.select name="driver" label="Driver" :options="$drivers" required
                                       :value="$datasource->driver" placeholder="" />
                    </div>
                </x-card>

                <x-card title="Configuracao de Conexao">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-form.input name="host" label="Host" required :value="$datasource->host" />

                        <x-form.input name="port" label="Porta" required :value="$datasource->port" />

                        <x-form.input name="database_name" label="Database/Service" required :value="$datasource->database_name" />

                        <div></div>

                        <x-form.input name="username" label="Usuario" required :value="$datasource->username" />

                        <x-form.input name="password" label="Senha" type="password"
                                      placeholder="Deixe vazio para manter a atual"
                                      help="Preencha apenas se quiser alterar a senha" />
                    </div>
                </x-card>

                <x-card title="Pool de Conexoes">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-form.input name="max_open_conns" label="Max Open Connections" type="number"
                                      :value="$datasource->max_open_conns" required />

                        <x-form.input name="max_idle_conns" label="Max Idle Connections" type="number"
                                      :value="$datasource->max_idle_conns" required />
                    </div>
                </x-card>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                <x-card title="Status">
                    <x-form.checkbox name="is_active" label="Datasource ativo" :checked="$datasource->is_active" />
                </x-card>

                <x-card title="Informacoes">
                    <div class="text-sm space-y-2 text-gray-600">
                        <div class="flex justify-between">
                            <span>Queries vinculadas</span>
                            <span class="font-medium">{{ $datasource->queries_count ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Criado em</span>
                            <span>{{ $datasource->created_at->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </x-card>

                <div class="flex gap-4">
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition text-center">
                        Salvar Alteracoes
                    </button>
                    <a href="{{ route('datasources.show', $datasource) }}"
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition text-center">
                        Cancelar
                    </a>
                </div>
            </div>
        </div>
    </form>

</x-layouts.app>