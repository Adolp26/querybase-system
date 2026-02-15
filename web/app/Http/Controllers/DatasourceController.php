<?php

namespace App\Http\Controllers;

use App\Models\Datasource;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DatasourceController extends Controller
{
    private const SUPPORTED_DRIVERS = [
        'oracle' => 'Oracle',
        'postgres' => 'PostgreSQL',
        'mysql' => 'MySQL',
    ];

    public function index(Request $request): View
    {
        $datasources = Datasource::query()
            ->withCount('queries');

        if ($request->input('status') === 'active') {
            $datasources->active();
        } elseif ($request->input('status') === 'inactive') {
            $datasources->where('is_active', false);
        }

        if ($driver = $request->input('driver')) {
            $datasources->where('driver', $driver);
        }

        $datasources = $datasources->orderBy('name')->paginate(15);

        $drivers = self::SUPPORTED_DRIVERS;

        return view('datasources.index', compact('datasources', 'drivers'));
    }

    public function create(): View
    {
        $drivers = self::SUPPORTED_DRIVERS;
        return view('datasources.create', compact('drivers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:datasources,slug', 'regex:/^[a-z0-9-]+$/'],
            'driver' => ['required', 'string', Rule::in(array_keys(self::SUPPORTED_DRIVERS))],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'string', 'max:10'],
            'database_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'max_open_conns' => ['required', 'integer', 'min:1', 'max:100'],
            'max_idle_conns' => ['required', 'integer', 'min:1', 'max:50'],
            'is_active' => ['boolean'],
        ], [
            'name.required' => 'O nome é obrigatório.',
            'host.required' => 'O host é obrigatório.',
            'password.required' => 'A senha é obrigatória para novos datasources.',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);

            $baseSlug = $validated['slug'];
            $counter = 1;
            while (Datasource::where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = "{$baseSlug}-{$counter}";
                $counter++;
            }
        }

        $datasource = Datasource::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'driver' => $validated['driver'],
            'host' => $validated['host'],
            'port' => $validated['port'],
            'database_name' => $validated['database_name'],
            'username' => $validated['username'],
            'password' => $validated['password'],
            'max_open_conns' => $validated['max_open_conns'],
            'max_idle_conns' => $validated['max_idle_conns'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()
            ->route('datasources.show', $datasource)
            ->with('success', 'Datasource criado com sucesso!');
    }

    public function show(Datasource $datasource): View
    {
        $datasource->loadCount('queries');

        $queries = $datasource->queries()
            ->withCount('executions')
            ->orderBy('name')
            ->limit(10)
            ->get();

        return view('datasources.show', compact('datasource', 'queries'));
    }

    public function edit(Datasource $datasource): View
    {
        $drivers = self::SUPPORTED_DRIVERS;
        return view('datasources.edit', compact('datasource', 'drivers'));
    }

    public function update(Request $request, Datasource $datasource): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:100', Rule::unique('datasources')->ignore($datasource->id), 'regex:/^[a-z0-9-]+$/'],
            'driver' => ['required', 'string', Rule::in(array_keys(self::SUPPORTED_DRIVERS))],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'string', 'max:10'],
            'database_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'max_open_conns' => ['required', 'integer', 'min:1', 'max:100'],
            'max_idle_conns' => ['required', 'integer', 'min:1', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? $datasource->slug,
            'driver' => $validated['driver'],
            'host' => $validated['host'],
            'port' => $validated['port'],
            'database_name' => $validated['database_name'],
            'username' => $validated['username'],
            'max_open_conns' => $validated['max_open_conns'],
            'max_idle_conns' => $validated['max_idle_conns'],
            'is_active' => $validated['is_active'] ?? false,
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = $validated['password'];
        }

        $datasource->update($updateData);

        return redirect()
            ->route('datasources.show', $datasource)
            ->with('success', 'Datasource atualizado com sucesso!');
    }

    public function destroy(Datasource $datasource): RedirectResponse
    {
        if ($datasource->queries()->exists()) {
            return back()->with('error', 'Não é possível deletar: existem queries usando este datasource.');
        }

        $name = $datasource->name;
        $datasource->delete();

        return redirect()
            ->route('datasources.index')
            ->with('success', "Datasource '{$name}' deletado com sucesso.");
    }

    public function toggle(Datasource $datasource): RedirectResponse
    {
        $datasource->update(['is_active' => !$datasource->is_active]);

        $status = $datasource->is_active ? 'ativado' : 'desativado';

        return back()->with('success', "Datasource {$status} com sucesso!");
    }

    public function testConnection(Datasource $datasource): RedirectResponse
    {
        $result = $datasource->testConnection();

        if ($result['success']) {
            $message = "Conexao OK! ({$result['duration_ms']}ms)";
            if (!empty($result['server_version'])) {
                $message .= " - {$result['server_version']}";
            }
            return back()->with('success', $message);
        }

        return back()->with('error', "Falha na conexao: {$result['message']}");
    }
}
