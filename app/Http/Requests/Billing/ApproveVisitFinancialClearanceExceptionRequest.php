<?php
namespace App\Http\Requests\Billing;
use Illuminate\Foundation\Http\FormRequest;
class ApproveVisitFinancialClearanceExceptionRequest extends FormRequest { public function authorize(): bool{return $this->user()?->can('visits.financial_clearance_exception.approve')??false;} public function rules():array{return ['approved_amount'=>'required|numeric|min:0.01','reason'=>'required|string|max:1000','status'=>'prohibited'];} }
