<x-layouts.app title="Novo Datasource" subtitle="Configure uma nova fonte de dados">
    <x-slot:actions>
        <a href="{{ route('datasources.index') }}" class="text-gray-600 hover:text-gray-800">
            Voltar para lista
        </a>
    </x-slot:actions>

    <form action="{{ route('datasources.store') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form --}}
            <div class="lg:col-span-2 space-y-6">
                <x-card title="Informacoes Basicas">
                    <div class="space-y-4">
                        <x-form.input name="name" label="Nome do Datasource" required
                                      placeholder="Ex: Oracle Principal" />

                        <x-form.input name="slug" label="Slug" placeholder="Ex: oracle-principal"
                                      help="Deixe vazio para gerar automaticamente" />

                        <x-form.select name="driver" label="Driver" :options="$drivers" required
                                       value="oracle" placeholder="" />
                    </div>
                </x-card>

                <x-card title="Configuracao de Conexao">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-form.input name="host" label="Host" required placeholder="Ex: localhost ou 192.168.1.100" />

                        <x-form.input name="port" label="Porta" required placeholder="Ex: 1521 (Oracle), 5432 (Postgres)" />

                        <x-form.input name="database_name" label="Database/Service" required
                                      placeholder="Ex: XEPDB1 ou nome_do_banco" />

                        <div></div>

                        <x-form.input name="username" label="Usuario" required placeholder="Usuario do banco" />

                        <x-form.input name="password" label="Senha" type="password" required placeholder="Senha do banco" />
                    </div>
                </x-card>

                <x-card title="Pool de Conexoes">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-form.input name="max_open_conns" label="Max Open Connections" type="number"
                                      value="25" required help="Maximo de conexoes simultaneas" />

                        <x-form.input name="max_idle_conns" label="Max Idle Connections" type="number"
                                      value="5" required help="Conexoes mantidas em espera" />
                    </div>
                </x-card>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                <x-card title="Status">
                    <x-form.checkbox name="is_active" label="Datasource ativo" :checked="true" />
                </x-card>

                <x-card title="Portas Comuns">
                    <div class="text-sm space-y-2 text-gray-600">
                        <div class="flex justify-between">
                            <span>Oracle</span>
                            <code class="bg-gray-100 px-2 rounded">1521</code>
                        </div>
                        <div class="flex justify-between">
                            <span>PostgreSQL</span>
                            <code class="bg-gray-100 px-2 rounded">5432</code>
                        </div>
                        <div class="flex justify-between">
                            <span>MySQL</span>
                            <code class="bg-gray-100 px-2 rounded">3306</code>
                        </div>
                    </div>
                </x-card>

                <div class="flex gap-4">
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition text-center">
                        Criar Datasource
                    </button>
                    <a href="{{ route('datasources.index') }}"
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition text-center">
                        Cancelar
                    </a>
                </div>
            </div>
        </div>
    </form>

</x-layouts.app>