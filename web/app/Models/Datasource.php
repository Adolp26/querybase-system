<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Http;
use App\Services\EncryptionService;

class Datasource extends Model
{
    use HasUuids;

    protected $table = 'datasources';

    protected $fillable = [
        'slug',
        'name',
        'driver',
        'host',
        'port',
        'database_name',
        'username',
        'password',
        'max_open_conns',
        'max_idle_conns',
        'is_active',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_open_conns' => 'integer',
        'max_idle_conns' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'driver' => 'oracle',
        'max_open_conns' => 25,
        'max_idle_conns' => 5,
        'is_active' => true,
    ];

    public function setPasswordAttribute(?string $value): void
    {
        // if ($value !== null && $value !== '') {
        //     $encryption = app(EncryptionService::class);
        //     $this->attributes['password'] = $encryption->encrypt($value);
        // }

        $this->attributes['password'] = $value;
    }

    // public function getPasswordAttribute(?string $value): ?string
    // {
    //     if ($value === null || $value === '') {
    //         return null;
    //     }

    //     try {
    //         $encryption = app(EncryptionService::class);
    //         return $encryption->decrypt($value);
    //     } catch (\Exception $e) {
    //         return $value;
    //     }
    // }

    public function queries(): HasMany
    {
        return $this->hasMany(Query::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getDriverLabelAttribute(): string
    {
        return match ($this->driver) {
            'oracle' => 'Oracle',
            'postgres' => 'PostgreSQL',
            'mysql' => 'MySQL',
            default => ucfirst($this->driver),
        };
    }

    public function getConnectionStringAttribute(): string
    {
        return "{$this->driver}://{$this->username}@{$this->host}:{$this->port}/{$this->database_name}";
    }

    public function testConnection(): array
    {
        $apiUrl = config('querybase.api_url');
        $timeout = config('querybase.connection_timeout', 30);

        if (empty($apiUrl)) {
            return [
                'success' => false,
                'message' => 'URL da API nao configurada. Verifique QUERYBASE_API_URL no .env',
                'duration_ms' => 0,
            ];
        }

        try {
            $startTime = microtime(true);

            $response = Http::timeout($timeout)
                ->post("{$apiUrl}/api/test-connection", $this->toApiPayload());

            $durationMs = round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message' => $data['message'] ?? 'Conexao OK!',
                    'duration_ms' => $data['duration_ms'] ?? $durationMs,
                    'server_version' => $data['server_version'] ?? null,
                ];
            }

            $errorData = $response->json();
            return [
                'success' => false,
                'message' => $errorData['error'] ?? $errorData['message'] ?? 'Erro desconhecido',
                'duration_ms' => $durationMs,
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'success' => false,
                'message' => 'Nao foi possivel conectar na API Golang. Verifique se esta rodando.',
                'duration_ms' => 0,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro: ' . $e->getMessage(),
                'duration_ms' => 0,
            ];
        }
    }

    public function toApiPayload(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'driver' => $this->driver,
            'host' => $this->host,
            'port' => (int) $this->port,
            'database' => $this->database_name,
            'username' => $this->username,
            'password' => $this->password,
            'max_open_conns' => $this->max_open_conns,
            'max_idle_conns' => $this->max_idle_conns,
        ];
    }
}
