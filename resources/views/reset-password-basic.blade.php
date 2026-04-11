<?php $page = 'reset-password-basic'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- Start Content -->
    <div class="container-fuild position-relative z-1">
        <div class="w-100 overflow-hidden position-relative flex-wrap d-block vh-100">

            <!-- start row -->
            <div class="row justify-content-center align-items-center vh-100 overflow-auto flex-wrap ">
                <div class="col-lg-4 mx-auto">
                    <form action="{{url('success-basic')}}" class="d-flex justify-content-center align-items-center">
                        <div class="d-flex flex-column justify-content-lg-center p-4 p-lg-0 pb-0 flex-fill">
                            <div class=" mx-auto mb-4 text-center">
                                <img src="{{URL::asset('build/img/logo.svg')}}" class="img-fluid" alt="Logo">
                            </div>
                            <div class="card border-1 p-lg-3 shadow-md rounded-3 mb-4">
                                <div class="card-body">
                                    <div class="text-center mb-3">
                                        <h5 class="mb-1 fs-20 fw-bold">Reset Password</h5>
                                        <p class="mb-0">Your new password must be different from previous used passwords.</p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <div class="position-relative">
                                            <div class="pass-group input-group position-relative border rounded">
                                                <span class="input-group-text bg-white border-0">
                                                    <i class="ti ti-lock text-dark fs-14"></i>
                                                </span>
                                                <input type="password" class="pass-input form-control ps-0 border-0" placeholder="****************">
                                                <span class="input-group-text bg-white border-0">
                                                    <i class="ti toggle-password ti-eye-off text-dark fs-14"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Confirm Password</label>
                                        <div class="position-relative">
                                            <div class="pass-group input-group position-relative border rounded">
                                                <span class="input-group-text bg-white border-0">
                                                    <i class="ti ti-lock text-dark fs-14"></i>
                                                </span>
                                                <input type="password" class="pass-inputs form-control ps-0 border-0" placeholder="****************">
                                                <span class="input-group-text bg-white border-0">
                                                    <i class="ti toggle-passwords ti-eye-off text-dark fs-14"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <button type="submit" class="btn bg-primary text-white w-100">Submit </button>
                                    </div>
                                    <div class="text-center">
                                        <h6 class="fw-normal fs-14 text-dark mb-0">Return to
                                            <a href="{{url('login-basic')}}" class="hover-a"> Login</a>
                                        </h6>
                                    </div>
                                </div><!-- end card body -->
                            </div><!-- end card -->
                        </div>
                    </form>
                    <p class="text-dark text-center">Copyright &copy; 2025 - Preclinic.</p>
                </div><!-- end col -->
            </div>
            <!-- end row -->

        </div>
    </div>
    <!-- End Content -->

    <!-- Start Bg Content -->
    <img src="{{URL::asset('build/img/auth/auth-bg-top.png')}}" alt="" class="img-fluid element-01">
    <img src="{{URL::asset('build/img/auth/auth-bg-bot.png')}}" alt="" class="img-fluid element-02">
    <!-- End Bg Content -->

@endsection   