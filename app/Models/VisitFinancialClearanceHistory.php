<?php

namespace App\Models;

use App\Enums\VisitFinancialClearanceEvent;
use Illuminate\Database\Eloquent\Model;

class VisitFinancialClearanceHistory extends Model
{
    public $timestamps = false;
    const UPDATED_AT = null;
    protected $table = 'visit_financial_clearance_history';
    protected $guarded = [];
    protected function casts(): array { return ['event_type' => VisitFinancialClearanceEvent::class, 'old_values' => 'array', 'new_values' => 'array', 'performed_at' => 'datetime', 'created_at' => 'datetime']; }
    public function clearance() { return $this->belongsTo(VisitFinancialClearance::class, 'visit_financial_clearance_id'); }
    public function visit() { return $this->belongsTo(Visit::class); }
    public function performer() { return $this->belongsTo(User::class, 'performed_by'); }
}
