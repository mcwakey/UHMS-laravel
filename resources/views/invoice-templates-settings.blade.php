<?php $page = 'invoice-templates-settings'; ?>
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
                                <h5 class="fw-bold">Invoice Templates</h5>
                            </div>
                            <div class="card-body px-0 mx-3">
                                <!-- start row -->
                            <div class="row gx-3">
                                <div class="col-md-3">
                                    <div class="card invoice-template bg-white">
                                        <div class="card-body p-2">
                                            <div class="invoice-img">
                                                <a href="#">
                                                    <img class="w-100" src="{{URL::asset('build/img/invoice/invoice-template-01.jpg')}}" alt="invoice">
                                                </a>
                                                <a href="#" class="invoice-view-icon" data-bs-toggle="modal" data-bs-target="#invoice_view_1"><i class="ti ti-eye"></i></a>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <a href="javascript:void(0);">General Invoice 1</a>
                                                <a href="javascript:void(0);" class="invoice-star d-flex align-items-center justify-content-center">
                                                    <i class="ti ti-star"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div><!-- end col -->
                                <div class="col-md-3">
                                    <div class="card invoice-template bg-white">
                                        <div class="card-body p-2">
                                            <div class="invoice-img">
                                                <a href="#">
                                                    <img class="w-100" src="{{URL::asset('build/img/invoice/invoice-template-02.jpg')}}" alt="invoice">
                                                </a>
                                                <a href="#" class="invoice-view-icon" data-bs-toggle="modal" data-bs-target="#invoice_view_2"><i class="ti ti-eye"></i></a>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <a href="javascript:void(0);">General Invoice 2</a>
                                                <a href="javascript:void(0);" class="invoice-star d-flex align-items-center justify-content-center">
                                                    <i class="ti ti-star"></i>
                                                </a>
                                            </div>
                                        </div><!-- end card body -->
                                    </div><!-- end card -->
                                </div><!-- end col -->
                                <div class="col-md-3">
                                    <div class="card invoice-template bg-white">
                                        <div class="card-body p-2">
                                            <div class="invoice-img">
                                                <a href="#">
                                                    <img class="w-100" src="{{URL::asset('build/img/invoice/invoice-template-03.jpg')}}" alt="invoice">
                                                </a>
                                                <a href="#" class="invoice-view-icon" data-bs-toggle="modal" data-bs-target="#invoice_view_3"><i class="ti ti-eye"></i></a>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <a href="javascript:void(0);">General Invoice 3</a>
                                                <a href="javascript:void(0);" class="invoice-star d-flex align-items-center justify-content-center">
                                                    <i class="ti ti-star"></i>
                                                </a>
                                            </div>
                                        </div><!-- end card body -->
                                    </div><!-- end card -->
                                </div><!-- end col -->
                                <div class="col-md-3">
                                    <div class="card invoice-template bg-white">
                                        <div class="card-body p-2">
                                            <div class="invoice-img">
                                                <a href="#">
                                                    <img class="w-100" src="{{URL::asset('build/img/invoice/invoice-template-04.jpg')}}" alt="invoice">
                                                </a>
                                                <a href="#" class="invoice-view-icon" data-bs-toggle="modal" data-bs-target="#invoice_view_4"><i class="ti ti-eye"></i></a>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <a href="javascript:void(0);">General Invoice 4</a>
                                                <a href="javascript:void(0);" class="invoice-star d-flex align-items-center justify-content-center">
                                                    <i class="ti ti-star"></i>
                                                </a>
                                            </div>
                                        </div><!-- end card body -->
                                    </div><!-- end card -->
                                </div><!-- end col -->
                            </div>
                            <!-- end row -->
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