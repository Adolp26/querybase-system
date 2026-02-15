<x-layouts.app :title="'Editar: ' . $query->name" subtitle="Modifique a query SQL">
    <x-slot:actions>
        <a href="{{ route('queries.show', $query) }}" class="text-gray-600 hover:text-gray-800">
            Voltar para detalhes
        </a>
    </x-slot:actions>

    <form action="{{ route('queries.update', $query) }}" method="POST" x-data="queryForm()">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form --}}
            <div class="lg:col-span-2 space-y-6">
                <x-card title="Informacoes Basicas">
                    <div class="space-y-4">
                        <x-form.input name="name" label="Nome da Query" required :value="$query->name" />

                        <x-form.input name="slug" label="Slug (URL)" :value="$query->slug"
                                      help="Alterar o slug pode quebrar integracoes existentes" />

                        <x-form.textarea name="description" label="Descricao" rows="3" :value="$query->description" />
                    </div>
                </x-card>

                <x-card title="SQL Query">
                    <x-form.textarea name="sql_query" label="SQL" required rows="10" :value="$query->sql_query" />
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
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    </div>

                                    <div class="flex items-center pt-6">
                                        <input type="hidden" :name="'parameters['+index+'][is_required]'" value="0">
                                        <input type="checkbox" x-model="param.is_required" :name="'parameters['+index+'][is_required]'" value="1"
                                               :checked="param.is_required"
                                               class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <label class="ml-2 text-sm text-gray-700">Obrigatorio</label>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Descricao</label>
                                        <input type="text" x-model="param.description" :name="'parameters['+index+'][description]'"
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
                        <x-form.select name="datasource_id" label="Datasource"
                                       :options="$datasources->pluck('name', 'id')"
                                       :value="$query->datasource_id"
                                       placeholder="Selecione um datasource..." />

                        <x-form.input name="cache_ttl" label="Cache TTL (segundos)" type="number"
                                      :value="$query->cache_ttl" required />

                        <x-form.input name="timeout_seconds" label="Timeout (segundos)" type="number"
                                      :value="$query->timeout_seconds" required />

                        <x-form.checkbox name="is_active" label="Query ativa" :checked="$query->is_active" />
                    </div>
                </x-card>

                <x-card title="Endpoint Atual">
                    <div class="bg-gray-100 rounded-md p-4">
                        <code class="text-sm text-blue-600 break-all">
                            GET /api/query/{{ $query->slug }}
                        </code>
                    </div>
                </x-card>

                <div class="flex gap-4">
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition text-center">
                        Salvar Alteracoes
                    </button>
                    <a href="{{ route('queries.show', $query) }}"
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
                parameters: @js($query->parameters->map(fn($p) => [
                    'name' => $p->name,
                    'param_type' => $p->param_type,
                    'is_required' => (bool) $p->is_required,
                    'default_value' => $p->default_value ?? '',
                    'description' => $p->description ?? ''
                ])),
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
                }
            }
        }
    </script>

</x-layouts.app>