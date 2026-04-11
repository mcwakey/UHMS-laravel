<?php $page = 'payroll-2'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- =====================`===
        Start Page Content
    ========================= -->
        
    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content">

            <!-- start row -->
            <div class="row">
                <div class="col-lg-10 mx-auto">
                    <!-- Start Page Header -->
                    <div class="mb-3">
                            <h6 class="fs-14 fw-bold mb-3 me-2"><a href="{{url('payroll')}}"><i class="ti ti-chevron-left me-1"></i>Payroll</a></h6>

                            <div class="card rounded-5">
                                <div class="card-header px-0 mx-3">
                                    <h4 class="fw-bold text-center mb-3">Payslip for the Month of March 2025</h4>
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <h6 class="fs-14 fw-semibold mb-0">Salary Month: March,2025</h6>
                                        <div class="d-flex align-items-center">
                                            <h6 class="fs-14 fw-semibold mb-0 me-2">Status:</h6>
                                            <span class="badge bg-success fw-medium">Sucess</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                                        <div>
                                            <div class="invoice-logo mb-2">
                                                <img src="{{URL::asset('build/img/logo.svg')}}" class="logo-white" alt="logo">
                                                <img src="{{URL::asset('build/img/logo-white.svg')}}" class="logo-dark" alt="logo">
                                            </div>
                                            <p class="mb-0">3864 Quiet Valley Lane,</p>
                                            <p class="mb-0">Sherman Oaks, CA, 91403</p>
                                            <p class="mb-0">GST No:2914035</p>
                                        </div>
                                        <div class="text-end">
                                            <h6 class="fs-14 fw-semibold mb-1">Andrew Fletcher</h6>
                                            <p class="mb-0">Employee ID : <span class="text-dark fw-medium">ST-0001</span></p>
                                            <p class="mb-0">Joining Date : <span class="text-dark fw-medium">7 May 2015</span></p>
                                        </div>
                                    </div>
                                    
                                    <!-- start row -->
                                    <div class="row row-gap-2 mb-3">
                                        <div class="col-md-6">
                                            <h5 class="mb-3 fw-bold">Earnings</h5>
                                            <div class="mb-3">
                                                <label class="form-label">Basic Salary</label>
                                                <input type="text" class="form-control">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">House Rent Allowance (H.R.A)</label>
                                                <input type="text" class="form-control">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Conveyance</label>
                                                <input type="text" class="form-control">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Other Allowance</label>
                                                <input type="text" class="form-control">
                                            </div>
                                            <div class="mb-0">
                                                <label class="form-label">Total Earnings</label>
                                                <input type="text" class="form-control">
                                            </div>
                                        </div><!-- end col -->
                                        <div class="col-md-6">
                                            <h5 class="mb-3 fw-bold">Deductions</h5>
                                            <div class="mb-3">
                                                <label class="form-label">Tax Deducted at Sources (T.D.S)</label>
                                                <input type="text" class="form-control">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Provident Fund</label>
                                                <input type="text" class="form-control">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">ESI</label>
                                                <input type="text" class="form-control">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Loan</label>
                                                <input type="text" class="form-control">
                                            </div>
                                            <div class="mb-0">
                                                <label class="form-label">Total Deductions</label>
                                                <input type="text" class="form-control">
                                            </div>
                                        </div><!-- end col -->
                                    </div>
                                    <!-- end row -->

                                    <h6 class="fs-14 fw-semibold mb-2">Net Salary: $9698 (Nine Thousand Six Hundred and Ninety Eight Only)</h6>
                                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus sed dictum ligula, cursus blandit risus. Maecenas eget metus non tellus dignissim aliquam ut a ex. Maecenas sed vehicula dui, ac suscipit lacus.</p>
                                </div><!-- end card body -->
                                
                                <div class="card-footer px-0 mx-3">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <a href="javascript:void(0);" class="btn btn-primary me-2">Send Payslip</a>
                                        <a href="javascript:void(0);" class="btn btn-light"><i class="ti ti-printer me-1"></i>Print</a>
                                    </div>
                                </div>
                            </div><!-- end card -->
                    </div>
                    <!-- End Page Header -->
                </div><!-- end col -->
            </div>
            <!-- end row -->

            
                            
        </div>
        <!-- End Content -->

        @component('components.footer')
        @endcomponent

    </div>

    <!-- ========================
        End Page Content
    ========================= -->

@endsection    