<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueryExecution extends Model
{
    use HasUuids;

    protected $table = 'query_executions';

    public $timestamps = false;

    protected $guarded = ['*'];

    protected $casts = [
        'executed_at' => 'datetime',
        'duration_ms' => 'integer',
        'cache_hit' => 'boolean',
        'row_count' => 'integer',
        'parameters' => 'array',
    ];

    public function parentQuery(): BelongsTo
    {
        return $this->belongsTo(Query::class, 'query_id');
    }

    public function scopeWithErrors($query)
    {
        return $query->whereNotNull('error');
    }

    public function scopeSuccessful($query)
    {
        return $query->whereNull('error');
    }

    public function scopeCacheHits($query)
    {
        return $query->where('cache_hit', true);
    }

    public function scopeCacheMisses($query)
    {
        return $query->where('cache_hit', false);
    }

    public function scopeSlow($query, int $thresholdMs = 1000)
    {
        return $query->where('duration_ms', '>', $thresholdMs);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('executed_at', today());
    }

    public function scopeLastHour($query)
    {
        return $query->where('executed_at', '>=', now()->subHour());
    }

    public function scopeLastDays($query, int $days)
    {
        return $query->where('executed_at', '>=', now()->subDays($days));
    }

    public function getDurationHumanAttribute(): string
    {
        $ms = $this->duration_ms;

        if ($ms < 1000) {
            return "{$ms}ms";
        }

        $seconds = round($ms / 1000, 2);
        return "{$seconds}s";
    }

    public function getStatusAttribute(): string
    {
        return $this->error ? 'error' : 'success';
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->error ? 'Erro' : 'Sucesso';
    }

    public function getStatusColorAttribute(): string
    {
        return $this->error ? 'red' : 'green';
    }

    public function getIsSlowAttribute(): bool
    {
        return $this->duration_ms > 1000;
    }

    public function getParametersDisplayAttribute(): string
    {
        if (empty($this->parameters)) {
            return '-';
        }

        $params = is_array($this->parameters) ? $this->parameters : [];

        return collect($params)
            ->map(fn($value, $key) => "{$key}={$value}")
            ->implode(', ');
    }

    public static function getStats(int $days = 7): array
    {
        $since = now()->subDays($days);

        $executions = static::where('executed_at', '>=', $since);

        $total = (clone $executions)->count();
        $errors = (clone $executions)->withErrors()->count();
        $cacheHits = (clone $executions)->cacheHits()->count();
        $avgDuration = (clone $executions)->successful()->avg('duration_ms');

        return [
            'total_executions' => $total,
            'error_count' => $errors,
            'error_rate' => $total > 0 ? round(($errors / $total) * 100, 1) : 0,
            'cache_hit_rate' => $total > 0 ? round(($cacheHits / $total) * 100, 1) : 0,
            'avg_duration_ms' => round($avgDuration ?? 0),
            'period_days' => $days,
        ];
    }

    public static function getTopQueries(int $limit = 10, int $days = 7): array
    {
        return static::where('executed_at', '>=', now()->subDays($days))
            ->selectRaw('query_slug, COUNT(*) as executions, AVG(duration_ms) as avg_duration')
            ->groupBy('query_slug')
            ->orderByDesc('executions')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public static function getSlowestQueries(int $limit = 10, int $days = 7): array
    {
        return static::where('executed_at', '>=', now()->subDays($days))
            ->successful()
            ->selectRaw('query_slug, AVG(duration_ms) as avg_duration, COUNT(*) as executions')
            ->groupBy('query_slug')
            ->havingRaw('COUNT(*) >= 5')
            ->orderByDesc('avg_duration')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
