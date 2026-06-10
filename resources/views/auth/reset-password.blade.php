@extends('layouts.auth')
@section('title', __('common.reset_password'))

@section('content')
<div class="container-fuild position-relative z-1">
    <div class="w-100 overflow-hidden position-relative flex-wrap d-block vh-100">
        <div class="row justify-content-center align-items-center vh-100 overflow-auto flex-wrap">
            <div class="col-lg-4 mx-auto">
                <div class="d-flex flex-column justify-content-lg-center p-4 p-lg-0 pb-0 flex-fill">
                    <div class="mx-auto mb-4 text-center">
                        <img src="{{ URL::asset('build/img/logo.svg') }}" class="img-fluid" alt="UHMS">
                    </div>
                    <div class="card border-1 p-lg-3 shadow-md rounded-3 mb-4">
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <h5 class="mb-1 fs-20 fw-bold">{{ __('common.reset_password') }}</h5>
                                <p class="mb-0">{{ __('common.enter_new_password_below') }}</p>
                            </div>

                            @if($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                @foreach($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            @endif

                            <form method="POST" action="{{ route('password.update') }}">
                                @csrf
                                <input type="hidden" name="token" value="{{ $token }}">

                                <div class="mb-3">
                                    <label class="form-label">{{ __('common.email_address') }}</label>
                                    <div class="input-group">
                                        <span class="input-group-text border-end-0 bg-white">
                                            <i class="ti ti-mail fs-14 text-dark"></i>
                                        </span>
                                        <input type="email" name="email" value="{{ $email ?? old('email') }}" class="form-control border-start-0 ps-0" placeholder="{{ __('common.enter_email_address') }}" required readonly>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ __('common.new_password') }}</label>
                                    <div class="pass-group input-group position-relative border rounded">
                                        <span class="input-group-text bg-white border-0">
                                            <i class="ti ti-lock text-dark fs-14"></i>
                                        </span>
                                        <input type="password" name="password" class="pass-input form-control ps-0 border-0" placeholder="****************" required>
                                        <span class="input-group-text bg-white border-0">
                                            <i class="ti toggle-password ti-eye-off text-dark fs-14"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ __('common.confirm_password') }}</label>
                                    <div class="pass-group input-group position-relative border rounded">
                                        <span class="input-group-text bg-white border-0">
                                            <i class="ti ti-lock text-dark fs-14"></i>
                                        </span>
                                        <input type="password" name="password_confirmation" class="form-control ps-0 border-0" placeholder="****************" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <button type="submit" class="btn bg-primary text-white w-100">{{ __('common.reset_password') }}</button>
                                </div>
                                <div class="text-center">
                                    <h6 class="fw-normal fs-14 text-dark mb-0">{{ __('common.return_to') }}
                                        <a href="{{ route('login') }}" class="hover-a">{{ __('common.login') }}</a>
                                    </h6>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <p class="text-dark text-center">Copyright &copy; <script>document.write(new Date().getFullYear())</script> - UHMS</p>
            </div>
        </div>
    </div>
</div>

<img src="{{ URL::asset('build/img/auth/auth-bg-top.png') }}" alt="" class="img-fluid element-01">
<img src="{{ URL::asset('build/img/auth/auth-bg-bot.png') }}" alt="" class="img-fluid element-02">
@endsection
