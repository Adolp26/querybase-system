<?php

namespace App\Http\Controllers;

use App\Models\Query;
use App\Models\Datasource;
use App\Models\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class QueryController extends Controller
{
    public function index(Request $request): View
    {
        $queries = Query::with(['datasource', 'parameters'])
            ->withCount('executions');

        if ($search = $request->input('search')) {
            $queries->search($search);
        }

        if ($request->input('status') === 'active') {
            $queries->active();
        } elseif ($request->input('status') === 'inactive') {
            $queries->where('is_active', false);
        }

        if ($datasourceId = $request->input('datasource')) {
            $queries->where('datasource_id', $datasourceId);
        }

        $sortBy = $request->input('sort', 'updated_at');
        $sortDir = $request->input('dir', 'desc');
        $queries->orderBy($sortBy, $sortDir);

        $queries = $queries->paginate(15)->withQueryString();

        $datasources = Datasource::active()->orderBy('name')->get();

        return view('queries.index', compact('queries', 'datasources'));
    }

    public function create(): View
    {
        $datasources = Datasource::active()->orderBy('name')->get();
        $paramTypes = QueryParameter::TYPES;

        return view('queries.create', compact('datasources', 'paramTypes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:queries,slug', 'regex:/^[a-z0-9-]+$/'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sql_query' => ['required', 'string'],
            'datasource_id' => ['nullable', 'uuid', 'exists:datasources,id'],
            'cache_ttl' => ['required', 'integer', 'min:0', 'max:86400'],
            'timeout_seconds' => ['required', 'integer', 'min:1', 'max:300'],
            'is_active' => ['boolean'],
            'parameters' => ['nullable', 'array'],
            'parameters.*.name' => ['required_with:parameters', 'string', 'max:100', 'regex:/^[a-z_][a-z0-9_]*$/i'],
            'parameters.*.param_type' => ['required_with:parameters', 'string', Rule::in(array_keys(QueryParameter::TYPES))],
            'parameters.*.is_required' => ['boolean'],
            'parameters.*.default_value' => ['nullable', 'string', 'max:255'],
            'parameters.*.description' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.required' => 'O nome da query é obrigatório.',
            'sql_query.required' => 'O SQL da query é obrigatório.',
            'slug.unique' => 'Já existe uma query com este slug.',
            'slug.regex' => 'O slug deve conter apenas letras minúsculas, números e hífens.',
            'parameters.*.name.regex' => 'Nome do parâmetro deve começar com letra e conter apenas letras, números e underscore.',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);

            $baseSlug = $validated['slug'];
            $counter = 1;
            while (Query::where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = "{$baseSlug}-{$counter}";
                $counter++;
            }
        }

        $query = \DB::transaction(function () use ($validated, $request) {
            $query = Query::create([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'description' => $validated['description'] ?? null,
                'sql_query' => $validated['sql_query'],
                'datasource_id' => $validated['datasource_id'] ?? null,
                'cache_ttl' => $validated['cache_ttl'],
                'timeout_seconds' => $validated['timeout_seconds'],
                'is_active' => $validated['is_active'] ?? true,
                'created_by' => auth()->user()?->name ?? 'system',
            ]);

            $parameters = $validated['parameters'] ?? [];
            foreach ($parameters as $index => $paramData) {
                QueryParameter::create([
                    'query_id' => $query->id,
                    'name' => $paramData['name'],
                    'param_type' => $paramData['param_type'],
                    'is_required' => $paramData['is_required'] ?? false,
                    'default_value' => $paramData['default_value'] ?? null,
                    'description' => $paramData['description'] ?? null,
                    'position' => $index + 1,
                ]);
            }

            return $query;
        });

        return redirect()
            ->route('queries.show', $query)
            ->with('success', 'Query criada com sucesso! Endpoint disponível em: /api/query/' . $query->slug);
    }

    public function show(Query $query): View
    {
        $query->load(['datasource', 'parameters']);

        $executionStats = [
            'total' => $query->executions()->count(),
            'last_24h' => $query->executions()->where('executed_at', '>=', now()->subDay())->count(),
            'cache_hit_rate' => $query->cache_hit_rate,
            'avg_duration' => $query->executions()->avg('duration_ms'),
            'error_count' => $query->executions()->withErrors()->count(),
        ];

        $recentExecutions = $query->executions()
            ->latest('executed_at')
            ->limit(20)
            ->get();

        return view('queries.show', compact('query', 'executionStats', 'recentExecutions'));
    }

    public function edit(Query $query): View
    {
        $query->load('parameters');
        $datasources = Datasource::active()->orderBy('name')->get();
        $paramTypes = QueryParameter::TYPES;

        return view('queries.edit', compact('query', 'datasources', 'paramTypes'));
    }

    public function update(Request $request, Query $query): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:100', Rule::unique('queries')->ignore($query->id), 'regex:/^[a-z0-9-]+$/'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sql_query' => ['required', 'string'],
            'datasource_id' => ['nullable', 'uuid', 'exists:datasources,id'],
            'cache_ttl' => ['required', 'integer', 'min:0', 'max:86400'],
            'timeout_seconds' => ['required', 'integer', 'min:1', 'max:300'],
            'is_active' => ['boolean'],
            'parameters' => ['nullable', 'array'],
            'parameters.*.name' => ['required_with:parameters', 'string', 'max:100'],
            'parameters.*.param_type' => ['required_with:parameters', 'string', Rule::in(array_keys(QueryParameter::TYPES))],
            'parameters.*.is_required' => ['boolean'],
            'parameters.*.default_value' => ['nullable', 'string', 'max:255'],
            'parameters.*.description' => ['nullable', 'string', 'max:1000'],
        ]);

        \DB::transaction(function () use ($validated, $query) {
            $query->update([
                'name' => $validated['name'],
                'slug' => $validated['slug'] ?? $query->slug,
                'description' => $validated['description'] ?? null,
                'sql_query' => $validated['sql_query'],
                'datasource_id' => $validated['datasource_id'] ?? null,
                'cache_ttl' => $validated['cache_ttl'],
                'timeout_seconds' => $validated['timeout_seconds'],
                'is_active' => $validated['is_active'] ?? false,
                'updated_by' => auth()->user()?->name ?? 'system',
            ]);

            $query->parameters()->delete();

            $parameters = $validated['parameters'] ?? [];
            foreach ($parameters as $index => $paramData) {
                QueryParameter::create([
                    'query_id' => $query->id,
                    'name' => $paramData['name'],
                    'param_type' => $paramData['param_type'],
                    'is_required' => $paramData['is_required'] ?? false,
                    'default_value' => $paramData['default_value'] ?? null,
                    'description' => $paramData['description'] ?? null,
                    'position' => $index + 1,
                ]);
            }
        });

        return redirect()
            ->route('queries.show', $query)
            ->with('success', 'Query atualizada com sucesso!');
    }

    public function destroy(Query $query): RedirectResponse
    {
        $queryName = $query->name;
        $query->delete();

        return redirect()
            ->route('queries.index')
            ->with('success', "Query '{$queryName}' deletada com sucesso.");
    }

    public function duplicate(Query $query): RedirectResponse
    {
        $newQuery = $query->duplicate();

        return redirect()
            ->route('queries.edit', $newQuery)
            ->with('success', 'Query duplicada! Edite os detalhes e salve.');
    }

    public function toggle(Query $query): RedirectResponse
    {
        $query->update([
            'is_active' => !$query->is_active,
            'updated_by' => auth()->user()?->name ?? 'system',
        ]);

        $status = $query->is_active ? 'ativada' : 'desativada';

        return back()->with('success', "Query {$status} com sucesso!");
    }
}
