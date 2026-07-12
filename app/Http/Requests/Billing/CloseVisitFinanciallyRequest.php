<?php
namespace App\Http\Requests\Billing;
use Illuminate\Foundation\Http\FormRequest;
class CloseVisitFinanciallyRequest extends FormRequest { public function authorize(): bool{return $this->user()?->can('visits.financial_clearance.close')??false;} public function rules():array{return ['reason'=>'required|string|max:500','status'=>'prohibited','basis'=>'prohibited'];} }
