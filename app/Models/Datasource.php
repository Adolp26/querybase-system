<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        return match($this->driver) {
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
}
