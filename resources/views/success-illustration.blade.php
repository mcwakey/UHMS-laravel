<?php $page = 'success-illustration'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- Start Content -->
    <div class="container-fuild position-relative z-1">
        <div class="w-100 overflow-hidden position-relative flex-wrap d-block vh-100 bg-white">
            <!-- start row-->
            <div class="row">
                <div class="col-lg-6 p-0">
                    <div class="login-backgrounds d-lg-flex align-items-center justify-content-center d-none flex-wrap p-4 position-relative h-100 z-0">
                        <img src="{{URL::asset('build/img/auth/success-illustration-img.png')}}" alt="log-illustration-img-01" class="img-fluid img1">
                    </div>
                </div> <!-- end row-->
                
                <div class="col-lg-6 col-md-12 col-sm-12">
                    <div class="row justify-content-center align-items-center overflow-auto flex-wrap vh-100 py-3">
                        <div class="col-md-8 mx-auto">
                            <form action="{{url('login-illustration')}}" class="d-flex justify-content-center align-items-center">
                                <div class="d-flex flex-column justify-content-lg-center p-4 p-lg-0 pb-0 flex-fill">
                                    <div class=" mx-auto mb-4 text-center">
                                        <img src="{{URL::asset('build/img/logo.svg')}}" class="img-fluid" alt="Logo">
                                    </div>
                                    <div class="card border-1 p-lg-3 shadow-md rounded-3 mb-4">
                                        <div class="card-body">
                                            <div class="mb-3 text-center">
                                                <span><i class="ti ti-circle-check-filled fs-48 text-success"></i></span>
                                            </div>
                                            <div class="text-center mb-3">
                                                <h5 class="mb-1 fs-20 fw-bold">Success</h5>
                                                <p class="mb-0"> Your new password has been successfully saved. </p>
                                            </div>
                                            <div class="mt-3">
                                                <button type="submit" class="btn bg-primary text-white w-100">Back to login </button>
                                            </div>
                                        </div><!-- end card body -->
                                    </div><!-- end card -->
                                </div>
                            </form>
                            <p class="text-dark text-center"> Copyright &copy; 2025 - Preclinic </p>
                        </div> <!-- end row-->
                    </div>
                </div>
            </div>
            <!-- end row-->

        </div>
    </div>
    <!-- End Content -->

@endsection   