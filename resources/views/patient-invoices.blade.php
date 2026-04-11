<?php $page = 'patient-invoices'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->
        
    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content content-two">

            <!-- Start Page Header -->
            <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-1 border-bottom">
                <div class="flex-grow-1">
                    <h4 class="fw-bold mb-0"> Invoices <span class="badge badge-soft-primary border pt-1 px-2 border-primary fw-medium ms-2 fw-medium fs-13">Total Invoices : 582</span> </h4>
                </div>
                <div class="text-end d-flex">
                    <!-- dropdown-->
                    <div class="dropdown me-1">
                        <a href="javascript:void(0);" class="btn btn-md fs-14 fw-normal border bg-white rounded text-dark d-inline-flex align-items-center"  data-bs-toggle="dropdown">
                            Export<i class="ti ti-chevron-down ms-2"></i>
                        </a>
                        <ul class="dropdown-menu p-2">
                            <li>
                                <a class="dropdown-item" href="#">Download as PDF</a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#">Download as Excel</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- End Page Header -->

            <div class=" d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                <div class="search-set mb-3">
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <div class="table-search d-flex align-items-center mb-0">
                            <div class="search-input">
                                <a href="javascript:void(0);" class="btn-searchset"></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex table-dropdown mb-3 pb-1 right-content align-items-center flex-wrap row-gap-3">
                    <div class="dropdown">
                        <a href="javascript:void(0);" class="dropdown-toggle btn bg-white btn-md d-inline-flex align-items-center fw-normal rounded border text-dark px-2 py-1 fs-14" data-bs-toggle="dropdown">
                            <span  class="me-1"> Sort By : </span>  Recent
                        </a>
                        <ul class="dropdown-menu  dropdown-menu-end p-2">
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">Recent</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">Oldest</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- start row -->
            <div class="row">
                <div class="col-sm-12">
                    <div>
                        <div class="card-body p-0">
                            <div class="table-responsive table-nowrap">
                                <table class="table border datatable">
                                    <thead class="">
                                        <tr>
                                            <th>Invoice ID</th>
                                            <th>Description</th>
                                            <th>Created Date</th>
                                            <th>Due Date</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <a href=""> #INV0025</a>
                                            </td>
                                            <td>
                                                General Consultation
                                            </td>
                                            <td>
                                                30 Apr 2025
                                            </td>
                                            <td>30 Apr 2025 </td>
                                            <td>$800</td>
                                            <td>
                                                <span class="badge badge-soft-success d-inline-flex align-items-center">
                                                    <i class="ti ti-point-filled me-1"></i>Paid
                                                </span>
                                            </td>
                                            <td class="action-item">
                                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu p-2">
                                                    <li>
                                                        <a href="{{url('patient-invoice-details')}}" class="dropdown-item d-flex align-items-center">View</a>
                                                    </li>
                                                    <li>
                                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                                    </li>
                                                </ul>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td>
                                                <a href=""> #INV0024</a>
                                            </td>
                                            <td>
                                                Dental Cleaning
                                            </td>
                                            <td>
                                                15 Apr 2025
                                            </td>
                                            <td>15 Apr 2025</td>
                                            <td>$930</td>
                                            <td>
                                                <span class="badge badge-soft-warning d-inline-flex align-items-center">
                                                    <i class="ti ti-point-filled me-1"></i>Partially Paid
                                                </span>
                                            </td>
                                            <td class="action-item">
                                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu p-2">
                                                    <li>
                                                        <a href="{{url('patient-invoice-details')}}" class="dropdown-item d-flex align-items-center">View</a>
                                                    </li>
                                                    <li>
                                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                                    </li>
                                                </ul>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td>
                                                <a href=""> #INV0023</a>
                                            </td>
                                            <td>
                                                Eye Checkup
                                            </td>
                                            <td>
                                                02 Apr 2025
                                            </td>
                                            <td>02 Apr 2025</td>
                                            <td>$850</td>
                                            <td>
                                                <span class="badge badge-soft-success d-inline-flex align-items-center">
                                                    <i class="ti ti-point-filled me-1"></i>Paid
                                                </span>
                                            </td>
                                            <td class="action-item">
                                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu p-2">
                                                    <li>
                                                        <a href="{{url('patient-invoice-details')}}" class="dropdown-item d-flex align-items-center">View</a>
                                                    </li>
                                                    <li>
                                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                                    </li>
                                                </ul>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td><a href=""> #INV0022</a></td>
                                            <td>X-Ray</td>
                                            <td>27 Mar 2025</td>
                                            <td>27 Mar 2025</td>
                                            <td>$700</td>
                                            <td>
                                                <span class="badge badge-soft-success d-inline-flex align-items-center">
                                                    <i class="ti ti-point-filled me-1"></i>Paid
                                                </span>
                                            </td>
                                            <td class="action-item">
                                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu p-2">
                                                    <li>
                                                        <a href="{{url('patient-invoice-details')}}" class="dropdown-item d-flex align-items-center">View</a>
                                                    </li>
                                                    <li>
                                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                                    </li>
                                                </ul>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td>
                                                <a href=""> #INV0021</a>
                                            </td>
                                            <td>
                                                Physiotherapy Session
                                            </td>
                                            <td>
                                                12 Mar 2025
                                            </td>
                                            <td>12 Mar 2025 </td>
                                            <td>$650</td>
                                            <td>
                                                <span class="badge badge-soft-warning d-inline-flex align-items-center">
                                                    <i class="ti ti-point-filled me-1"></i>Partially Paid
                                                </span>
                                            </td>
                                            <td class="action-item">
                                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu p-2">
                                                    <li>
                                                        <a href="{{url('patient-invoice-details')}}" class="dropdown-item d-flex align-items-center">View</a>
                                                    </li>
                                                    <li>
                                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                                    </li>
                                                </ul>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td>
                                                <a href=""> #INV0020</a>
                                            </td>
                                            <td>
                                                Cardiac Screening
                                            </td>
                                            <td>
                                                05 Mar 2025
                                            </td>
                                            <td>05 Mar 2025</td>
                                            <td>$430</td>
                                            <td>
                                                <span class="badge badge-soft-danger d-inline-flex align-items-center text-danger">
                                                    <i class="ti ti-point-filled me-1"></i>UnPaid
                                                </span>
                                            </td>
                                            <td class="action-item">
                                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu p-2">
                                                    <li>
                                                        <a href="{{url('patient-invoice-details')}}" class="dropdown-item d-flex align-items-center">View</a>
                                                    </li>
                                                    <li>
                                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                                    </li>
                                                </ul>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td>
                                                <a href=""> #INV0019</a>
                                            </td>
                                            <td>
                                                Skin Allergy Test
                                            </td>
                                            <td>
                                                24 Feb 2025
                                            </td>
                                            <td>24 Feb 2025 </td>
                                            <td>$300</td>
                                            <td>
                                                <span class="badge badge-soft-success d-inline-flex align-items-center">
                                                    <i class="ti ti-point-filled me-1"></i>Paid
                                                </span>
                                            </td>
                                            <td class="action-item">
                                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu p-2">
                                                    <li>
                                                        <a href="{{url('patient-invoice-details')}}" class="dropdown-item d-flex align-items-center">View</a>
                                                    </li>
                                                    <li>
                                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                                    </li>
                                                </ul>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td>
                                                <a href=""> #INV0018</a>
                                            </td>
                                            <td>
                                                Blood Test
                                            </td>
                                            <td>
                                                16 Feb 2025
                                            </td>
                                            <td>16 Feb 2025</td>
                                            <td>$450</td>
                                            <td>
                                                <span class="badge badge-soft-warning d-inline-flex align-items-center">
                                                    <i class="ti ti-point-filled me-1"></i>Partially Paid
                                                </span>
                                            </td>
                                            <td class="action-item">
                                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu p-2">
                                                    <li>
                                                        <a href="{{url('patient-invoice-details')}}" class="dropdown-item d-flex align-items-center">View</a>
                                                    </li>
                                                    <li>
                                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                                    </li>
                                                </ul>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td>
                                                <a href=""> #INV0017</a>
                                            </td>
                                            <td>
                                                ENT Consultation
                                            </td>
                                            <td>
                                                01 Feb 2025
                                            </td>
                                            <td>01 Feb 2025</td>
                                            <td>$750</td>
                                            <td>
                                                <span class="badge badge-soft-success d-inline-flex align-items-center">
                                                    <i class="ti ti-point-filled me-1"></i>Paid
                                                </span>
                                            </td>
                                            <td class="action-item">
                                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu p-2">
                                                    <li>
                                                        <a href="{{url('patient-invoice-details')}}" class="dropdown-item d-flex align-items-center">View</a>
                                                    </li>
                                                    <li>
                                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                                    </li>
                                                </ul>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td>
                                                <a href=""> #INV0017</a>
                                            </td>
                                            <td>
                                                Nutrition Counseling
                                            </td>
                                            <td>
                                                25 Jan 2025
                                            </td>
                                            <td> 25 Jan 2025 </td>
                                            <td>$800</td>
                                            <td>
                                                <span class="badge badge-soft-danger text-danger d-inline-flex align-items-center">
                                                    <i class="ti ti-point-filled me-1"></i>UnPaid
                                                </span>
                                            </td>
                                            <td class="action-item">
                                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu p-2">
                                                    <li>
                                                        <a href="{{url('patient-invoice-details')}}" class="dropdown-item d-flex align-items-center">View</a>
                                                    </li>
                                                    <li>
                                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                                    </li>
                                                </ul>
                                            </td>
                                        </tr>

                                        
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
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