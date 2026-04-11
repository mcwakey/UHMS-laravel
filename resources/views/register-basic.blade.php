<?php $page = 'register-basic'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- Start Content -->
    <div class="container-fuild position-relative z-1">
        <div class="w-100 overflow-hidden position-relative flex-wrap d-block vh-100">

            <!-- start row -->
            <div class="row justify-content-center align-items-center vh-100 overflow-auto flex-wrap py-3">
                <div class="col-lg-4 mx-auto">
                    <form action="{{url('login-basic')}}" class="d-flex justify-content-center align-items-center">
                        <div class="d-flex flex-column justify-content-lg-center p-4 p-lg-0 pb-0 flex-fill">
                            <div class=" mx-auto mb-4 text-center">
                                <img src="{{URL::asset('build/img/logo.svg')}}" class="img-fluid" alt="Logo">
                            </div>
                            <div class="card border-1 p-lg-3 shadow-md rounded-3 mb-4">
                                <div class="card-body">
                                    <div class="text-center mb-3">
                                        <h5 class="mb-1 fs-20 fw-bold">Register</h5>
                                        <p class="mb-0">Please enter your details to create account</p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Full Name</label>
                                        <div class="input-group">
                                            <span class="input-group-text border-end-0 bg-white">
                                                <i class="ti ti-user fs-14 text-dark"></i>
                                            </span>
                                            <input type="text" value="" class="form-control border-start-0 ps-0" placeholder="Enter Name">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email Address</label>
                                        <div class="input-group">
                                            <span class="input-group-text border-end-0 bg-white">
                                                <i class="ti ti-mail fs-14 text-dark"></i>
                                            </span>
                                            <input type="text" value="" class="form-control border-start-0 ps-0" placeholder="Enter Email Address">
                                        </div>
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
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="form-check form-check-md mb-0">
                                                <input class="form-check-input" id="remember_me" type="checkbox">
                                                <label for="remember_me" class="form-check-label mt-0 text-dark">I agree to the <a href="{{url('terms-and-conditions')}}" class="text-decoration-underline text-primary"> Terms of Service</a> & <a href="{{url('privacy-policy')}}" class="text-decoration-underline text-primary">Privacy Policy </a></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <button type="submit" class="btn bg-primary text-white w-100">Register</button>
                                    </div>
                                    <div class="login-or position-relative mb-3">
                                        <span class="span-or">OR</span>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-center flex-wrap">
                                            <div class="text-center me-2 flex-fill">
                                                <a href="javascript:void(0);"
                                                    class="br-10 p-1 btn btn-outline-light border d-flex align-items-center justify-content-center">
                                                    <img class="img-fluid m-1" src="{{URL::asset('build/img/icons/facebook-logo.svg')}}" alt="Facebook">
                                                </a>
                                            </div>
                                            <div class="text-center me-2 flex-fill">
                                                <a href="javascript:void(0);"
                                                    class="br-10 p-1 btn btn-outline-light border d-flex align-items-center justify-content-center">
                                                    <img class="img-fluid m-1" src="{{URL::asset('build/img/icons/google-logo.svg')}}" alt="Google">
                                                </a>
                                            </div>
                                            <div class="text-center me-2 flex-fill">
                                                <a href="javascript:void(0);"
                                                    class="br-10 p-1 btn btn-outline-light border d-flex align-items-center justify-content-center">
                                                    <img class="img-fluid m-1" src="{{URL::asset('build/img/icons/apple-logo.svg')}}" alt="apple">
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <h6 class="fw-normal fs-14 text-dark mb-0">Already have an account yet? 
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