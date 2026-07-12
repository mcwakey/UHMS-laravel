@extends('layouts.app')
@section('title', __('visit_financial_clearance.actions.report'))
@section('content')<div class="container-fluid"><h4>{{ __('visit_financial_clearance.actions.report') }}</h4><div class="row">@foreach($metrics as $key=>$value)<div class="col-md-4 mb-3"><div class="card card-body"><small>{{ __('visit_financial_clearance.metrics.'.$key) }}</small><h3>{{ is_numeric($value) ? number_format($value, $key === 'outstanding' ? 2 : 0) : $value }}</h3></div></div>@endforeach</div></div>@endsection
