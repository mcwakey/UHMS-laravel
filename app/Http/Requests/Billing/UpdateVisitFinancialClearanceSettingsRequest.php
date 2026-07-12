<?php
namespace App\Http\Requests\Billing;
use Illuminate\Foundation\Http\FormRequest;
class UpdateVisitFinancialClearanceSettingsRequest extends FormRequest { public function authorize(): bool{return $this->user()?->can('billing.financial_clearance.settings.manage')??false;} public function rules():array{return ['mode'=>'required|in:disabled,observe,active','activation_confirmation'=>'required_if:mode,active|accepted'];} }
