<?php
namespace App\Http\Requests\Billing;
use App\Enums\VisitFinancialClearanceExceptionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreVisitFinancialClearanceExceptionRequest extends FormRequest { public function authorize(): bool{return $this->user()?->can('visits.financial_clearance_exception.request')??false;} public function rules():array{return ['type'=>['required',Rule::enum(VisitFinancialClearanceExceptionType::class)],'requested_amount'=>'required|numeric|min:0.01','request_reason'=>'required|string|max:1000','supporting_reference'=>'nullable|string|max:191','effective_from'=>'nullable|date','expires_at'=>'nullable|date|after_or_equal:effective_from','status'=>'prohibited','approved_amount'=>'prohibited'];} }
