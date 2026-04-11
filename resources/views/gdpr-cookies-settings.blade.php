<?php $page = 'gdpr-cookies-settings'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->
        
    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content" id="profilePage">

            <!-- Page Header -->
            <div class="mb-3 border-bottom pb-3">
                <h4 class="fw-bold mb-0">Settings</h4>
            </div>
            <!-- End Page Header -->

            <div class="card">
                <div class="card-body p-0">
                    <div class="settings-wrapper d-flex">

                        @component('components.settings-sidebar')
                        @endcomponent

                        <div class="card flex-fill mb-0 border-0 bg-light-500 shadow-none">
                            <div class="card-header border-bottom px-0 mx-3">
                                <h5 class="fw-bold">GDPR Cookies</h5>
                            </div>
                            <div class="card-body px-0 mx-3">
                                <form action="{{url('gdpr-cookies-settings')}}">

                                    <!-- start row -->
                                    <div class="row border-bottom mb-3">
                                        <div class="col-lg-12">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                                <div class="col-lg-4">
                                                    <label class="form-label mb-0">Cookies Position</label>
                                                </div><!-- end col -->
                                                <div class="col-lg-5">
                                                    <select class="select">
                                                        <option>Select</option>
                                                        <option>Select</option>
                                                    </select>
                                                </div><!-- end col -->
                                            </div>
                                            <!-- end row -->

                                        </div><!-- end col -->
                                        <div class="col-lg-12">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                            <div class="col-lg-4">
                                                <label class="form-label mb-0">Agree Button Text</label>
                                            </div><!-- end col -->
                                            <div class="col-lg-5">
                                                <input type="text" class="form-control">
                                            </div><!-- end col -->
                                        </div>
                                        <!-- end row -->

                                        </div><!-- end col -->
                                        <div class="col-lg-12">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                            <div class="col-lg-4">
                                                <label class="form-label mb-0">Decline Button Text</label>
                                            </div><!-- end col -->
                                            <div class="col-lg-5">
                                                <input type="text" class="form-control">
                                            </div><!-- end col -->
                                        </div>
                                        <!-- end row -->

                                        </div><!-- end col -->
                                        <div class="col-lg-12">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                            <div class="col-lg-4">
                                                <label class="form-label mb-0">Show Decline Button</label>
                                            </div><!-- end col -->
                                            <div class="col-lg-8">
                                                <div class="form-check form-switch ps-0">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </div>
                                            </div><!-- end col -->
                                        </div>
                                        <!-- end row -->

                                        </div><!-- end col -->
                                        <div class="col-lg-12">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                            <div class="col-lg-4">
                                                <label class="form-label mb-0">Links for Cookies Page</label>
                                            </div><!-- end col -->
                                            <div class="col-lg-8">
                                                <input type="text" class="form-control">
                                            </div><!-- end col -->
                                        </div>
                                        <!-- end row -->

                                        </div><!-- end col -->
                                        <div class="col-lg-12">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                            <div class="col-lg-4">
                                                <label class="form-label mb-0">Cookies Content Text</label>
                                            </div><!-- end col -->
                                            <div class="col-lg-8">
                                                <div class="editor three-line"></div>
                                            </div><!-- end col -->
                                        </div>
                                        <!-- end row -->

                                        </div><!-- end col -->
                                    </div>
                                    <!-- end row -->

                                    <div class="d-flex align-items-center justify-content-end">
                                        <a href="javascript:void(0);" class="btn btn-light me-3">Cancel</a>
                                        <a href="javascript:void(0);" class="btn btn-primary">Save Changes</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                </div><!-- end card body -->
            </div><!-- end card -->
                            
        </div>
        <!-- End Content -->

        @component('components.footer')
        @endcomponent

    </div>

    <!-- ========================
        End Page Content
    ========================= -->

@endsection    