<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasUlids, HasFactory, SoftDeletes;

    protected $fillable = [
        'code', 'name', 'name_en',
        'branch_id', 'manager_employee_id',
        'description', 'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::creating(function (Department $dept) {
            if (empty($dept->code)) {
                $dept->code = self::generateCode();
            }
            if (auth()->check() && empty($dept->created_by)) {
                $dept->created_by = auth()->id();
            }
        });
    }

    public static function generateCode(): string
    {
        $count = self::withTrashed()->count() + 1;
        return 'DEP-' . str_pad((string) $count, 3, '0', STR_PAD_LEFT);
    }

    public function branch()    { return $this->belongsTo(Branch::class); }
    public function manager()   { return $this->belongsTo(Employee::class, 'manager_employee_id'); }
    public function positions() { return $this->hasMany(Position::class); }
    public function employees() { return $this->hasMany(Employee::class); }

    public function scopeActive($query) { return $query->where('is_active', true); }
}
