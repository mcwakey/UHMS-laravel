<?php $page = 'two-step-verification-cover'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- Start Content -->
    <div class="container-fuild position-relative z-1">
        <div class="w-100 overflow-hidden position-relative flex-wrap d-block vh-100 bg-white">
            <!-- start row-->
            <div class="row">
                <div class="col-lg-6 p-0">
                    <div class="login-backgrounds login-covers bg-primary d-lg-flex align-items-center justify-content-center d-none flex-wrap p-4 position-relative h-100 z-0">
                        <div class="authentication-card w-100">
                            <div class="authen-overlay-item w-100">
                                <div class="authen-head text-center">
                                    <h1 class="text-white fs-32 fw-bold mb-2">Seamless healthcare access <br> with smart, modern clinic</h1>
                                    <p class="text-light fw-normal text-light"> Experience efficient, secure, and user-friendly healthcare management designed for modern clinics and growing practices.  </p>
                                </div>
                                <div class="mt-4 mx-auto authen-overlay-img">
                                    <img src="{{URL::asset('build/img/auth/cover-imgs-1.png')}}" alt="Img">
                                </div>
                            </div>
                        </div>
                        <img src="{{URL::asset('build/img/auth/cover-imgs-2.png')}}" alt="cover-imgs-2" class="img-fluid cover-img">
                    </div>
                </div> <!-- end row-->
                
                <div class="col-lg-6 col-md-12 col-sm-12">
                    <div class="row justify-content-center align-items-center overflow-auto flex-wrap vh-100">
                        <div class="col-md-8 mx-auto">
                            <form action="{{url('reset-password-cover')}}" class="d-flex justify-content-center align-items-center">
                                <div class="d-flex flex-column justify-content-lg-center p-4 p-lg-0 pb-0 flex-fill">
                                    <div class=" mx-auto mb-4 text-center">
                                        <img src="{{URL::asset('build/img/logo.svg')}}" class="img-fluid" alt="Logo">
                                    </div>
                                    <div class="card border-1 p-lg-3 shadow-md rounded-3">
                                        <div class="card-body">
                                            <div class="text-center mb-3">
                                                <h5 class="mb-1 fs-20 fw-bold">2 Step Verification</h5>
                                                <p class="mb-0">Please enter the OTP received to confirm your account ownership. A code has been send to <span class="text-dark b-block"> ******doe@example.com</span> </p>
                                            </div>
                                            <div class="text-center otp-input">
                                                <div class="d-flex align-items-center justify-content-center mb-3">
                                                    <input type="text" class="border rounded text-center fs-26 fw-bold me-3" id="digit-1" name="digit-1" data-next="digit-2" maxlength="1">
                                                    <input type="text" class="border rounded text-center fs-26 fw-bold me-3" id="digit-2" name="digit-2" data-next="digit-3" data-previous="digit-1" maxlength="1">
                                                    <input type="text" class="border rounded text-center fs-26 fw-bold me-3" id="digit-3" name="digit-3" data-next="digit-4" data-previous="digit-2" maxlength="1">
                                                    <input type="text" class="border rounded text-center fs-26 fw-bold" id="digit-4" name="digit-4" data-next="digit-5" data-previous="digit-3" maxlength="1">
                                                </div>
                                                <div class="d-flex justify-content-center">
                                                    <div class="mb-3 d-flex align-items-center ">
                                                        <p class="text-gray-9 me-4 mb-0">Didn't receive code. <a href="javascript:void(0);" class="text-primary">Resend Code</a></p>
                                                        <span class="text-danger">00:45</span>
                                                    </div>
                                                </div>
                                            </div>
    
                                            <div class="mt-0">
                                                <button type="submit" class="btn bg-primary text-white w-100">Submit </button>
                                            </div>
                                        </div><!-- end card body -->
                                    </div><!-- end card -->
                                </div>
                            </form>
                            <p class="fs-14 text-dark text-center mt-4"> Copyright &copy; 2025 - Preclinic </p>
                        </div> <!-- end row-->
                    </div>
                    
                </div>
            </div>
            <!-- end row-->

        </div>
    </div>
    <!-- End Content -->

@endsection   