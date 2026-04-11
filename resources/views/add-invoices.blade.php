<?php $page = 'add-invoices'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->
        
    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content">

            <!-- Start Page Header -->
            <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3">
                <div class="flex-grow-1">
                    <h6 class="fw-bold mb-0 d-flex align-items-center"><a href="{{url('invoices')}}" class=""><i class="ti ti-chevron-left me-1 fs-14"></i>Invoices</a></h6>
                </div>
            </div>
            <!-- End Page Header -->

            <div class="card">
                <div class="card-header">
                    <h5 class="fw-bold m-0"> New Invoice </h5>
                </div> <!-- end card-header -->

                <div class="card-body">
                    <form action="add-in">

                        <!-- start row -->
                        <div class="row">
                            <div class="col-lg-6 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label mb-1 text-dark fs-14 fw-medium">Patient Name <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control">
                                    </div>
                                </div> 
                            </div> <!-- end col -->

                            <div class="col-lg-6 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label mb-1 text-dark fs-14 fw-medium">Email <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control">
                                    </div>
                                </div>
                            </div> <!-- end col -->

                            <div class="col-lg-6 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label mb-1 text-dark fs-14 fw-medium">Department <span class="text-danger">*</span></label>
                                    <div class="dropdown">
                                        <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                            Select
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-lg p-2 dropdown-employee w-100">
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    General Medicine
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    Pediatrics
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    Gynecology
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    Cardiology
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    Orthopedics
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    Dermatology
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    ENT
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    Neurology
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    Psychiatry
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    Pulmonology
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div> <!-- end col -->

                            <div class="col-lg-6 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label mb-1 text-dark fs-14 fw-medium">Tax <span class="text-danger">*</span></label>
                                    <div class="dropdown">
                                        <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                            Select
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-lg p-2 dropdown-employee w-100">
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    GST
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    VAT
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    Professional
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    Income
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    TDS
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div> <!-- end col -->

                            <div class="col-lg-6 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label mb-1 text-dark fs-14 fw-medium">Invoice Date <span class="text-danger">*</span></label>
                                    <div class="input-icon-end position-relative">  
                                        <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-calendar"></i>
                                        </span>
                                    </div>
                                </div>
                            </div> <!-- end col -->

                            <div class="col-lg-6 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label mb-1 text-dark fs-14 fw-medium">Due Date <span class="text-danger">*</span></label>
                                    <div class="input-icon-end position-relative">  
                                        <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-calendar"></i>
                                        </span>
                                    </div>
                                </div>
                            </div> <!-- end col -->

                            <div class="col-lg-6 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label mb-1 text-dark fs-14 fw-medium">Patient Address <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <textarea class="form-control" rows="4"></textarea>
                                    </div>
                                </div>
                            </div> <!-- end col -->

                            <div class="col-lg-6 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label mb-1 text-dark fs-14 fw-medium">Billing Address <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <textarea class="form-control" rows="4"></textarea>
                                    </div>
                                </div>
                            </div> <!-- end col -->

                            <div class="col-lg-6 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label mb-1 text-dark fs-14 fw-medium">Payment Method <span class="text-danger">*</span></label>
                                    <div class="dropdown">
                                        <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                            Select
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-lg p-2 dropdown-employee w-100">
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    PayPal
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    Options Enhanced
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    Cheque
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div> <!-- end col -->

                            <div class="col-lg-6 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label mb-1 text-dark fs-14 fw-medium">Payment Status <span class="text-danger">*</span></label>
                                    <div class="dropdown">
                                        <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                            Select
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-lg p-2 dropdown-employee w-100">
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    Inporgress
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    Completed
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    Pending
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div> <!-- end col -->

                            <div class="col-lg-12 col-md-12">

                                <div class="mb-3">
                                    <table class="table invoice-table border">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th>Description</th>
                                                <th>Unit Cost</th>
                                                <th>Qty</th>
                                                <th>Amount</th>
                                                <th></th>
                                            </tr>
                                        </thead>

                                        <tbody class="invoices-list">
                                            <tr class="invoices-list-item">
                                                <td><input type="text" class="form-control"></td>
                                                <td><input type="text" class="form-control"></td>
                                                <td><input type="number" class="form-control"></td>
                                                <td><input type="number" class="form-control"></td>
                                                <td><input type="text" class="form-control" readonly></td>
                                                <td><button class="btn remove-invoices btn-sm border shadow-sm p-2 d-flex align-items-center justify-content-center rounded fs-14"> <i class="ti ti-trash"></i> </button></td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <a href="" class="btn add-invoices border-0 text-dark d-felx align-items-center fs-14"> <i class="ti ti-circle-plus text-primary me-1"></i> Add Invoice</a>
                                                </td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                    
                            </div> <!-- end col -->

                            <div class="col-lg-8 col-md-8"></div> <!-- end col -->

                            <div class="col-lg-4">
                                <div class="">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h6 class="fs-14 fw-normal text-dark">Amount</h6>
                                        <h6 class="fs-14 fw-semibold text-dark">$0</h6>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h6 class="fs-14 fw-normal text-dark">Tax (0%)</h6>
                                        <h6 class="fs-14 fw-semibold text-dark">$0</h6>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h6 class="fs-14 fw-normal text-dark">Discount</h6>
                                        <h6 class="fs-14 fw-semibold text-dark">
                                            <select class="select form-control-sm rounded">
                                                <option>0%</option>
                                                <option>1%</option>
                                                <option>2%</option>
                                                <option>3%</option>
                                                <option>4%</option>
                                            </select>
                                        </h6>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                                        <h6 class="fs-14 fw-normal text-dark d-flex align-items-center">
                                            <label class="d-flex align-items-center form-switch ps-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked="">
                                            </label>
                                            Round Off Total
                                        </h6>
                                        <h6 class="fs-14 fw-semibold text-dark">$0</h6>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h6 class="fs-18 fw-bold">Total (USD)</h6>
                                        <h6 class="fs-18 fw-bold">$0</h6>
                                    </div>
                                </div>
                            </div> <!-- end col -->

                            <div class="col-lg-12 col-md-12">
                                <div class="mb-3">
                                    <label class="form-label mb-1 text-dark fs-14 fw-medium">Other Information <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <textarea rows="3"  class="form-control "></textarea>
                                    </div>
                                </div> 
                            </div> <!-- end col -->
                        </div>
                        <!-- end row -->
                    </form>
                </div> <!-- end card-body -->
                <div class="card-footer">
                    <div class="d-flex gap-2 align-items-center justify-content-end mb-0">
                        <a href="" class="btn btn-md bg-light text-dark fs-13 fw-medium rounded"> Cancel </a>
                        <a href="" class="btn btn-md btn-primary fs-13 fw-medium rounded" data-bs-toggle="modal" data-bs-target="#cancel-reason"> Complete Appointment </a>
                    </div>
                </div> <!-- end card-body -->
            </div> <!-- end card --> 

        </div>
        <!-- End Content -->

        @component('components.footer')
        @endcomponent

    </div>

    <!-- ========================
        End Page Content
    ========================= -->

@endsection    