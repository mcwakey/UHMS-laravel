<?php
namespace App\Http\Requests\Billing;
use Illuminate\Foundation\Http\FormRequest;
class AssessVisitFinancialClearanceRequest extends FormRequest { public function authorize(): bool{return $this->user()?->can('visits.financial_clearance.assess')??false;} public function rules():array{return ['status'=>'prohibited','basis'=>'prohibited'];} }
