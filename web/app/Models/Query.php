<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Query extends Model
{
    use HasUuids;

    protected $table = 'queries';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'sql_query',
        'datasource_id',
        'cache_ttl',
        'timeout_seconds',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'cache_ttl' => 'integer',
        'timeout_seconds' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'cache_ttl' => 300,
        'timeout_seconds' => 30,
        'is_active' => true,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($query) {
            if (empty($query->slug)) {
                $query->slug = Str::slug($query->name);
            }
        });
    }

    public function datasource(): BelongsTo
    {
        return $this->belongsTo(Datasource::class);
    }

    public function parameters(): HasMany
    {
        return $this->hasMany(QueryParameter::class)->orderBy('position');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(QueryExecution::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'ILIKE', "%{$term}%")
              ->orWhere('slug', 'ILIKE', "%{$term}%")
              ->orWhere('description', 'ILIKE', "%{$term}%");
        });
    }

    public function getEndpointUrlAttribute(): string
    {
        return "/api/query/{$this->slug}";
    }

    public function getCacheTtlHumanAttribute(): string
    {
        $seconds = $this->cache_ttl;

        if ($seconds < 60) {
            return "{$seconds} segundos";
        }

        if ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            return "{$minutes} " . ($minutes == 1 ? 'minuto' : 'minutos');
        }

        $hours = floor($seconds / 3600);
        return "{$hours} " . ($hours == 1 ? 'hora' : 'horas');
    }

    public function getHasRequiredParametersAttribute(): bool
    {
        return $this->parameters()->where('is_required', true)->exists();
    }

    public function getTotalExecutionsAttribute(): int
    {
        return $this->executions()->count();
    }

    public function getCacheHitRateAttribute(): float
    {
        $recent = $this->executions()->latest('executed_at')->limit(100)->get();

        if ($recent->isEmpty()) {
            return 0.0;
        }

        $hits = $recent->where('cache_hit', true)->count();
        return round(($hits / $recent->count()) * 100, 1);
    }

    public function duplicate(string $newSlug = null): self
    {
        $newSlug = $newSlug ?? $this->slug . '-copy';

        $new = $this->replicate();
        $new->slug = $newSlug;
        $new->name = $this->name . ' (Cópia)';
        $new->is_active = false;
        $new->save();

        foreach ($this->parameters as $param) {
            $newParam = $param->replicate();
            $newParam->query_id = $new->id;
            $newParam->save();
        }

        return $new;
    }
}
