<?php $page = 'form-mask'; ?>
@extends('layout.mainlayout')
@section('content') 

    <!-- ========================
        Start Page Content
    ========================= -->

    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content pb-0">

            <!-- Page Header -->
            <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3">
                <div class="flex-grow-1">
                    <h4 class="fw-bold mb-0">Form Mask</h4>
                </div>
                <div class="text-end">
                    <ol class="breadcrumb m-0 py-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Home</a></li>
                        
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Forms</a></li>
                        
                        <li class="breadcrumb-item active">Form Mask</li>
                    </ol>
                </div>
            </div>
            <!-- End Page Header -->

            <!-- start row -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Form Mask</h5>
                            <p class="sub-header">Input masks can be used to force the user to enter data conform a specific format. Unlike validation, the user can't enter any other key than the ones specified by the mask.</p>
                        </div>
                        <div class="card-body">
                            <form action="#">

                                <!-- start row -->
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Phone</label>
                                        <input type="text" id="phone" class="form-control">
                                        <span class="form-text text-muted">(999) 999-9999</span>
                                    </div><!-- end col -->
                                    <div class="col-md-6">
                                        <label class="form-label">Date</label>
                                        <input type="text" id="date" class="form-control">
                                        <span class="form-text text-muted">dd/mm/yyyy</span>
                                    </div><!-- end col -->
                                    <div class="col-md-6">
                                        <label class="form-label">SSN field 1</label>
                                        <input type="text" id="ssn" class="form-control">
                                        <span class="form-text text-muted">e.g "999-99-9999"</span>
                                    </div><!-- end col -->
                                    <div class="col-md-6">
                                        <label class="form-label">Phone field + ext.</label>
                                        <input type="text" id="phoneExt" class="form-control">
                                        <span class="form-text text-muted">+40 999 999 999</span>
                                    </div><!-- end col -->
                                    <div class="col-md-6">
                                        <label class="form-label">Product Key</label>
                                        <input type="text" id="products" class="form-control">
                                        <span class="form-text text-muted">e.g a*-999-a999</span>
                                    </div><!-- end col -->
                                    <div class="col-md-6">
                                        <label class="form-label">Currency</label>
                                        <input type="text" id="currency" class="form-control">
                                        <span class="form-text text-muted">$ 999,999,999.99</span>
                                    </div><!-- end col -->
                                    <div class="col-md-6">
                                        <label class="form-label">Eye Script</label>
                                        <input type="text" id="eyescript" class="form-control">
                                        <span class="form-text text-muted">~9.99 ~9.99 999</span>
                                    </div><!-- end col -->
                                    <div class="col-md-6">
                                        <label class="form-label">Percent</label>
                                        <input type="text" id="pct" class="form-control">
                                        <span class="form-text text-muted">e.g "99%"</span>
                                    </div><!-- end col -->
                                    <div class="col-md-6">
                                        <label class="form-label">Credit Card Number</label>
                                        <input type="text" class="form-control" id="ccn">
                                        <span class="form-text text-muted">e.g "999.999.999.9999"</span>
                                    </div><!-- end col -->
                                </div>
                                <!-- end row -->

                            </form>
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->
            </div>
            <!-- end row -->

        </div>
        <!--  End Content -->

        @component('components.footer')
        @endcomponent

    </div>
    
    <!-- ========================
        End Page Content
    ========================= -->

@endsection