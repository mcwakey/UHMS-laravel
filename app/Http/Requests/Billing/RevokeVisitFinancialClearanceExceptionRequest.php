<?php
namespace App\Http\Requests\Billing;
use Illuminate\Foundation\Http\FormRequest;
class RevokeVisitFinancialClearanceExceptionRequest extends FormRequest { public function authorize(): bool{return $this->user()?->can('visits.financial_clearance_exception.revoke')??false;} public function rules():array{return ['reason'=>'required|string|max:1000','status'=>'prohibited'];} }
