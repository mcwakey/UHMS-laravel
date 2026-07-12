<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitFinancialClearanceExceptionHistory extends Model
{
    public $timestamps = false;
    const UPDATED_AT = null;
    protected $table = 'visit_financial_clearance_exception_history';
    protected $guarded = [];
    protected function casts(): array { return ['old_values' => 'array', 'new_values' => 'array', 'performed_at' => 'datetime', 'created_at' => 'datetime']; }
    public function exception() { return $this->belongsTo(VisitFinancialClearanceException::class, 'visit_financial_clearance_exception_id'); }
    public function performer() { return $this->belongsTo(User::class, 'performed_by'); }
}
