<?php $page = 'income'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- =====================`===
        Start Page Content
    ========================= -->
        
    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content">

            <!-- Start Page Header -->
            <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-1 border-bottom">
                <div class="flex-grow-1">
                    <h4 class="fw-bold mb-0"> Income <span class="badge badge-soft-primary fw-medium border py-1 px-2 border-primary fs-13 ms-1">Total Income : 565</span> </h4>
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
                    <a href="javascript:void(0);" class="btn btn-primary ms-2 fs-13 btn-md" data-bs-toggle="modal" data-bs-target="#add_new_income"><i class="ti ti-plus me-1"></i>New Income </a>
                </div>
            </div>
            <!-- End Page Header -->

            <!--  Start Filter -->
            <div class=" d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="search-set mb-3">
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <div class="table-search d-flex align-items-center mb-0">
                                <div class="search-input">
                                    <a href="javascript:void(0);" class="btn-searchset"></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex table-dropdown mb-3 pb-1 right-content align-items-center flex-wrap row-gap-3">
                    <div class="dropdown me-2">
                        <a href="javascript:void(0);" class="bg-white border rounded btn btn-md text-dark fs-14 py-1 align-items-center d-flex fw-normal" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                            <i class="ti ti-filter text-gray-5 me-1"></i>Filters
                        </a>
                        <div class="dropdown-menu dropdown-lg dropdown-menu-end filter-dropdown p-0" id="filter-dropdown">
                            <div class="d-flex align-items-center justify-content-between border-bottom filter-header">
                                <h4 class="mb-0 fw-bold">Filter</h4>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="link-danger text-decoration-underline">Clear All</a>
                                </div>
                            </div>
                            <form action="#">
                                <div class="filter-body pb-0">
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label mb-1">Received From</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <select class="select2" multiple="multiple">
                                            <option value="m-1" selected>Alberto Ripley</option>
                                            <option value="m-2">Susan Babin</option>
                                            <option value="m-3">Martin Lisa</option>
                                            <option value="m-4">Stella Mary</option>
                                            <option value="m-5">Carol Lam</option>
                                            <option value="m-6">Jesus Adams</option>
                                            <option value="m-7">Ezra Belcher</option>
                                            <option value="m-8">Unit Manager </option>
                                            <option value="m-9">Bernard Griffith</option>
                                            <option value="m-10">John Elsass</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label">Category</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <select class="select2" multiple="multiple">
                                            <option value="m-1" selected>Medical Supplies</option>
                                            <option value="m-2">Laboratory</option>
                                            <option value="m-3">Cleaning Services</option>
                                            <option value="m-4">Medical Equipment Maintenance</option>
                                            <option value="m-5">Staff Salaries & Wages </option>
                                            <option value="m-6">Utilities </option>
                                            <option value="m-7">Software & Licensing  </option>
                                            <option value="m-8">Facility Rent  </option>
                                            <option value="m-9">Waste Disposal Services  </option>
                                            <option value="m-10">Insurance Premiums  </option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label">Payment Method</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <select class="select2" multiple="multiple">
                                            <option value="m-1" selected>PayPal</option>
                                            <option value="m-2">Debit Card</option>
                                            <option value="m-3">Cheque</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label mb-1 text-dark fs-14 fw-medium">Date</label>
                                        <div class="input-icon-end position-relative">  
                                            <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                            <span class="input-icon-addon">
                                                <i class="ti ti-calendar"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label">Amount</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <select class="select2" multiple="multiple">
                                            <option value="m-1" selected>$501 - $1000</option>
                                            <option value="m-2">$501 - $1100</option>
                                            <option value="m-3">$701 - $1200</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label">Status</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <select class="select2" multiple="multiple">
                                            <option value="m-1" selected>Received</option>
                                            <option value="m-2">Failed</option>
                                            <option value="m-3">Pending</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="filter-footer d-flex align-items-center justify-content-end border-top">
                                    <a href="javascript:void(0);" class="btn btn-light btn-md me-2 fw-medium" id="close-filter">Close</a>
                                    <button type="submit" class="btn btn-primary btn-md fw-medium">Filter</button>
                                </div>
                            </form>
                        </div>
                    </div>
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
            <!--  End Filter -->

            <!--  Start Table -->
            <div class="table-responsive">
                <table class="table table-nowrap datatable">
                    <thead class="thead-light">
                        <tr>
                            <th class="no-sort">
                                Income Name
                            </th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Received From</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td> <a href="">General Consultation</a></td>
                            <td class="fw-semibold text-dark"> $800</td>
                            <td class="text-dark"> 30 Apr 2025 </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-01.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">James Carter  </a>
                                </div>
                            </td>
                            <td class="text-dark">PayPal</td>
                            <td> <span class="badge badge-soft-success rounded text-success fw-medium border border-success">Received</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_income">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        
                        <tr>
                            <td> <a href="">Blood Test</a></td>
                            <td class="fw-semibold text-dark"> $930</td>
                            <td class="text-dark"> 15 Apr 2025 </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-02.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">Emily Johnson </a>
                                </div>
                            </td>
                            <td class="text-dark">Debit Card</td>
                            <td> <span class="badge badge-soft-warning rounded text-warning fw-medium border border-warning">Pending</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_income">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td> <a href="">MRI Scan</a></td>
                            <td class="fw-semibold text-dark"> $850</td>
                            <td class="text-dark">02 Apr 2025 </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-03.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">Robert Mitchell </a>
                                </div>
                            </td>
                            <td class="text-dark">Cheque</td>
                            <td> <span class="badge badge-soft-success rounded text-success fw-medium border border-success">Received</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_income">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        
                        <tr>
                            <td> <a href=""> CT Scan </a></td>
                            <td class="fw-semibold text-dark"> $700</td>
                            <td class="text-dark"> 27 Mar 2025 </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-04.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">Sophia Miller  </a>
                                </div>
                            </td>
                            <td class="text-dark">Debit Card</td>
                            <td> <span class="badge badge-soft-success rounded text-success fw-medium border border-success">Received </span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_income">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td> <a href="">Allergy Panel</a></td>
                            <td class="fw-semibold text-dark"> $650</td>
                            <td class="text-dark"> 12 Mar 2025 </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-05.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">Daniel Anderson  </a>
                                </div>
                            </td>
                            <td class="text-dark">PayPal</td>
                            <td> <span class="badge badge-soft-warning rounded text-warning fw-medium border border-warning">Pending</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_income">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td> <a href="">PET Scan</a></td>
                            <td class="fw-semibold text-dark"> $430</td>
                            <td class="text-dark"> 05 Mar 2025</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-06.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">Olivia Davis</a>
                                </div>
                            </td>
                            <td class="text-dark">Cheque</td>
                            <td> <span class="badge badge-soft-success rounded text-success fw-medium border border-success">Received</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_income">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td> <a href="">Orthopedic Checkup</a></td>
                            <td class="fw-semibold text-dark"> $300</td>
                            <td class="text-dark"> 24 Feb 2025 </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-07.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">Michael Thompson  </a>
                                </div>
                            </td>
                            <td class="text-dark">Debit Card</td>
                            <td> <span class="badge badge-soft-success rounded text-success fw-medium border border-success">Recerived</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_income">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td> <a href="">Thyroid Test</a></td>
                            <td class="fw-semibold text-dark"> $450</td>
                            <td class="text-dark"> 16 Feb 2025 </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-08.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">Isabella Wilson  </a>
                                </div>
                            </td>
                            <td class="text-dark">Cheque</td>
                            <td> <span class="badge badge-soft-warning rounded text-warning fw-medium border border-warning">Pending</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_income">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td> <a href="">ENT Consultation</a></td>
                            <td class="fw-semibold text-dark"> $570</td>
                            <td class="text-dark"> 01 Feb 2025 </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-09.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">Michael Trade </a>
                                </div>
                            </td>
                            <td class="text-dark">Debit Card</td>
                            <td> <span class="badge badge-soft-success rounded text-success fw-medium border border-success">Received</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_income">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td> <a href="">Cataract Surgery</a></td>
                            <td class="fw-semibold text-dark"> $800</td>
                        <td class="text-dark"> 25 Jan 2025 </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-10.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">Ava Robinson </a>
                                </div>
                            </td>
                            <td class="text-dark">PayPal</td>
                            <td> <span class="badge badge-soft-success rounded text-success fw-medium border border-success">Received</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_income">Edit</a>
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
            <!--  End Table -->

        </div>
        <!-- End Content -->

        @component('components.footer')
        @endcomponent

    </div>

    <!-- ========================
        End Page Content
    ========================= -->

@endsection    