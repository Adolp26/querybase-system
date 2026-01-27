<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueryParameter extends Model
{
    use HasUuids;

    protected $table = 'query_parameters';

    public $timestamps = false;

    protected $fillable = [
        'query_id',
        'name',
        'param_type',
        'is_required',
        'default_value',
        'description',
        'position',
        'validations',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'position' => 'integer',
        'validations' => 'array',
        'created_at' => 'datetime',
    ];

    protected $attributes = [
        'param_type' => 'string',
        'is_required' => false,
        'validations' => '{}',
    ];

    public const TYPES = [
        'string' => 'Texto',
        'integer' => 'Número Inteiro',
        'number' => 'Número Decimal',
        'date' => 'Data (YYYY-MM-DD)',
        'datetime' => 'Data e Hora',
        'boolean' => 'Verdadeiro/Falso',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($param) {
            $param->created_at = $param->created_at ?? now();

            if (empty($param->position)) {
                $maxPosition = static::where('query_id', $param->query_id)
                    ->max('position') ?? 0;
                $param->position = $maxPosition + 1;
            }
        });
    }

    public function parentQuery(): BelongsTo
    {
        return $this->belongsTo(Query::class, 'query_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->param_type] ?? $this->param_type;
    }

    public function getPlaceholderAttribute(): string
    {
        return match($this->param_type) {
            'integer' => 'Ex: 123',
            'number' => 'Ex: 123.45',
            'date' => 'Ex: 2024-01-15',
            'datetime' => 'Ex: 2024-01-15 14:30:00',
            'boolean' => 'true ou false',
            default => 'Digite um valor...',
        };
    }

    public function getSqlPlaceholderAttribute(): string
    {
        return ":{$this->position}";
    }

    public function getValidationRulesAttribute(): array
    {
        if (empty($this->validations)) {
            return [];
        }

        return is_array($this->validations) ? $this->validations : [];
    }

    public function getLaravelValidationRules(): array
    {
        $rules = [];

        $rules[] = $this->is_required ? 'required' : 'nullable';

        $rules[] = match($this->param_type) {
            'integer' => 'integer',
            'number' => 'numeric',
            'date' => 'date_format:Y-m-d',
            'datetime' => 'date_format:Y-m-d H:i:s',
            'boolean' => 'boolean',
            default => 'string',
        };

        $custom = $this->validation_rules;
        if (isset($custom['min'])) {
            $rules[] = "min:{$custom['min']}";
        }
        if (isset($custom['max'])) {
            $rules[] = "max:{$custom['max']}";
        }
        if (isset($custom['regex'])) {
            $rules[] = "regex:{$custom['regex']}";
        }

        return $rules;
    }
}
