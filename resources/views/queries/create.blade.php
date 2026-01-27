<x-layouts.app title="Nova Query" subtitle="Cadastre uma nova query SQL">
    <x-slot:actions>
        <a href="{{ route('queries.index') }}" class="text-gray-600 hover:text-gray-800">
            Voltar para lista
        </a>
    </x-slot:actions>

    <form action="{{ route('queries.store') }}" method="POST" x-data="queryForm()">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form --}}
            <div class="lg:col-span-2 space-y-6">
                <x-card title="Informacoes Basicas">
                    <div class="space-y-4">
                        <x-form.input name="name" label="Nome da Query" required placeholder="Ex: Funcionarios por Departamento" />

                        <x-form.input name="slug" label="Slug (URL)" placeholder="Ex: funcionarios-por-departamento"
                                      help="Deixe vazio para gerar automaticamente a partir do nome" />

                        <x-form.textarea name="description" label="Descricao" rows="3"
                                         placeholder="Descreva o que esta query retorna e como usar..." />
                    </div>
                </x-card>

                <x-card title="SQL Query">
                    <x-form.textarea name="sql_query" label="SQL" required rows="10"
                                     placeholder="SELECT * FROM tabela WHERE coluna = :1" />
                    <p class="mt-2 text-sm text-gray-500">
                        Use <code class="bg-gray-100 px-1 rounded">:1</code>, <code class="bg-gray-100 px-1 rounded">:2</code>, etc. para parametros posicionais.
                    </p>
                </x-card>

                <x-card title="Parametros">
                    <div class="space-y-4">
                        <template x-for="(param, index) in parameters" :key="index">
                            <div class="border border-gray-200 rounded-lg p-4 relative">
                                <button type="button" @click="removeParameter(index)"
                                        class="absolute top-2 right-2 text-red-500 hover:text-red-700">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Nome <span class="text-gray-400">(posicao: <span x-text="index + 1"></span>)</span>
                                        </label>
                                        <input type="text" x-model="param.name" :name="'parameters['+index+'][name]'" required
                                               placeholder="Ex: department_id"
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                                        <select x-model="param.param_type" :name="'parameters['+index+'][param_type]'"
                                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                            @foreach($paramTypes as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Valor Padrao</label>
                                        <input type="text" x-model="param.default_value" :name="'parameters['+index+'][default_value]'"
                                               placeholder="Opcional"
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    </div>

                                    <div class="flex items-center pt-6">
                                        <input type="hidden" :name="'parameters['+index+'][is_required]'" value="0">
                                        <input type="checkbox" x-model="param.is_required" :name="'parameters['+index+'][is_required]'" value="1"
                                               class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <label class="ml-2 text-sm text-gray-700">Obrigatorio</label>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Descricao</label>
                                        <input type="text" x-model="param.description" :name="'parameters['+index+'][description]'"
                                               placeholder="Descreva o parametro..."
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    </div>
                                </div>
                            </div>
                        </template>

                        <button type="button" @click="addParameter()"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Adicionar Parametro
                        </button>
                    </div>
                </x-card>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                <x-card title="Configuracoes">
                    <div class="space-y-4">
                        <x-form.select name="datasource_id" label="Datasource" :options="$datasources->pluck('name', 'id')"
                                       placeholder="Selecione um datasource..." />

                        <x-form.input name="cache_ttl" label="Cache TTL (segundos)" type="number" value="300" required
                                      help="Tempo que o resultado fica em cache" />

                        <x-form.input name="timeout_seconds" label="Timeout (segundos)" type="number" value="30" required
                                      help="Tempo maximo de execucao" />

                        <x-form.checkbox name="is_active" label="Query ativa" :checked="true" />
                    </div>
                </x-card>

                <x-card title="Preview do Endpoint">
                    <div class="bg-gray-100 rounded-md p-4">
                        <p class="text-sm text-gray-600 mb-2">A query estara disponivel em:</p>
                        <code class="text-sm text-blue-600 break-all">
                            GET /api/query/<span x-text="slug || 'seu-slug'">seu-slug</span>
                        </code>
                    </div>
                </x-card>

                <div class="flex gap-4">
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition text-center">
                        Criar Query
                    </button>
                    <a href="{{ route('queries.index') }}"
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition text-center">
                        Cancelar
                    </a>
                </div>
            </div>
        </div>
    </form>

    <script>
        function queryForm() {
            return {
                parameters: [],
                slug: '',
                init() {
                    this.$watch('parameters', () => this.updateSlug());
                },
                addParameter() {
                    this.parameters.push({
                        name: '',
                        param_type: 'string',
                        is_required: false,
                        default_value: '',
                        description: ''
                    });
                },
                removeParameter(index) {
                    this.parameters.splice(index, 1);
                },
                updateSlug() {
                    const nameInput = document.querySelector('input[name="name"]');
                    if (nameInput && !document.querySelector('input[name="slug"]').value) {
                        this.slug = nameInput.value.toLowerCase()
                            .replace(/[^a-z0-9]+/g, '-')
                            .replace(/(^-|-$)/g, '');
                    }
                }
            }
        }
    </script>

</x-layouts.app>