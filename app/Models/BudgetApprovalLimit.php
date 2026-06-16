<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetApprovalLimit extends Model
{
    protected $fillable = ['approval_type', 'role_id', 'user_id', 'limit_amount', 'is_active'];

    protected function casts(): array
    {
        return ['limit_amount' => 'decimal:2', 'is_active' => 'boolean'];
    }
}
