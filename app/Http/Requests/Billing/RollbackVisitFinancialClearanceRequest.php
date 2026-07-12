<?php
namespace App\Http\Requests\Billing;
use Illuminate\Foundation\Http\FormRequest;
class RollbackVisitFinancialClearanceRequest extends FormRequest { public function authorize(): bool{return $this->user()?->can('billing.financial_clearance.settings.rollback')??false;} public function rules():array{return ['reason'=>'required|string|max:500'];} }
