<?php $page = 'invoices'; ?>
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
                    <h4 class="fw-bold mb-0"> Invoices <span class="badge badge-soft-primary fw-medium border py-1 px-2 border-primary fs-13 ms-1">Total Invoices : 565</span> </h4>
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
                    <a href="{{url('add-invoices')}}" class="btn btn-primary ms-2 fs-13 btn-md"><i class="ti ti-plus me-1"></i>New Invoices </a>
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
                                            <option value="m-1" selected>Paid</option>
                                            <option value="m-2">Options Enhanced</option>
                                            <option value="m-3">Unpaid</option>
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
                                Invoice ID
                            </th>
                            <th>Patient</th>
                            <th>Created Date</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td> <a href="{{url('invoices-details')}}">#INV0025</a></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-01.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">James Adair  </a>
                                </div>
                            </td>
                            <td class="text-dark"> 30 Apr 2025</td>
                            <td class="text-dark"> 30 Apr 2025</td>
                            <td class="fw-semibold text-dark"> $800</td>
                            <td> <span class="badge badge-soft-success rounded text-success fw-medium border border-success">Paid</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="{{url('invoices-details')}}" class="dropdown-item d-flex align-items-center">View details</a>
                                    </li>
                                    <li>
                                        <a href="{{url('edit-invoices')}}" class="dropdown-item d-flex align-items-center">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_payment">Download</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td> <a href="{{url('invoices-details')}}">#INV0024</a></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-02.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">Emily Johnson </a>
                                </div>
                            </td>
                            <td class="text-dark"> 15 Apr 2025</td>
                            <td class="text-dark"> 15 Apr 2025</td>
                            <td class="fw-semibold text-dark"> $930</td>
                            <td> <span class="badge badge-soft-warning rounded text-warning fw-medium border border-warning">Partially Paid</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="{{url('invoices-details')}}" class="dropdown-item d-flex align-items-center">View details</a>
                                    </li>
                                    <li>
                                        <a href="{{url('edit-invoices')}}" class="dropdown-item d-flex align-items-center">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_payment">Download</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td> <a href="{{url('invoices-details')}}">#INV0023</a></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-03.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold"> Robert Mitchell </a>
                                </div>
                            </td>
                            <td class="text-dark"> 02 Apr 2025 </td>
                            <td class="text-dark"> 02 Apr 2025 </td>
                            <td class="fw-semibold text-dark"> $850</td>
                            <td> <span class="badge badge-soft-danger rounded text-danger fw-medium border border-danger">Unpaid</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="{{url('invoices-details')}}" class="dropdown-item d-flex align-items-center">View details</a>
                                    </li>
                                    <li>
                                        <a href="{{url('edit-invoices')}}" class="dropdown-item d-flex align-items-center">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_payment">Download</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td> <a href="{{url('invoices-details')}}">#INV0022</a></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-04.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold"> Sophia Miller  </a>
                                </div>
                            </td>
                            <td class="text-dark"> 27 Mar 2025 </td>
                            <td class="text-dark"> 27 Mar 2025 </td>
                            <td class="fw-semibold text-dark"> $700</td>
                            <td> <span class="badge badge-soft-success rounded text-success fw-medium border border-success">Paid</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="{{url('invoices-details')}}" class="dropdown-item d-flex align-items-center">View details</a>
                                    </li>
                                    <li>
                                        <a href="{{url('edit-invoices')}}" class="dropdown-item d-flex align-items-center">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_payment">Download</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td> <a href="{{url('invoices-details')}}">#INV0021</a></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-05.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">Daniel Anderson  </a>
                                </div>
                            </td>
                            <td class="text-dark"> 12 Mar 2025</td>
                            <td class="text-dark"> 12 Mar 2025</td>
                            <td class="fw-semibold text-dark"> $650</td>
                            <td> <span class="badge badge-soft-warning rounded text-warning fw-medium border border-warning">Partially Paid</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="{{url('invoices-details')}}" class="dropdown-item d-flex align-items-center">View details</a>
                                    </li>
                                    <li>
                                        <a href="{{url('edit-invoices')}}" class="dropdown-item d-flex align-items-center">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_payment">Download</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td> <a href="{{url('invoices-details')}}">#INV0020</a></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-06.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">Olivia Davis  </a>
                                </div>
                            </td>
                            <td class="text-dark"> 05 Mar 2025 </td>
                            <td class="text-dark"> 05 Mar 2025 </td>
                            <td class="fw-semibold text-dark"> $430 </td>
                            <td> <span class="badge badge-soft-danger rounded text-danger fw-medium border border-danger">Unpaid</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="{{url('invoices-details')}}" class="dropdown-item d-flex align-items-center">View details</a>
                                    </li>
                                    <li>
                                        <a href="{{url('edit-invoices')}}" class="dropdown-item d-flex align-items-center">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_payment">Download</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td> <a href="{{url('invoices-details')}}">#INV0019</a></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-07.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">Michael Thompson  </a>
                                </div>
                            </td>
                            <td class="text-dark"> 24 Feb 2025 </td>
                            <td class="text-dark"> 24 Feb 2025 </td>
                            <td class="fw-semibold text-dark"> $300</td>
                            <td> <span class="badge badge-soft-success rounded text-success fw-medium border border-success">Paid</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="{{url('invoices-details')}}" class="dropdown-item d-flex align-items-center">View details</a>
                                    </li>
                                    <li>
                                        <a href="{{url('edit-invoices')}}" class="dropdown-item d-flex align-items-center">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_payment">Download</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td> <a href="{{url('invoices-details')}}">#INV0018</a></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-08.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">Isabella Wilson </a>
                                </div>
                            </td>
                            <td class="text-dark"> 16 Feb 2025 </td>
                            <td class="text-dark"> 16 Feb 2025 </td>
                            <td class="fw-semibold text-dark"> $450</td>
                            <td> <span class="badge badge-soft-danger rounded text-danger fw-medium border border-danger">Unpaid</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="{{url('invoices-details')}}" class="dropdown-item d-flex align-items-center">View details</a>
                                    </li>
                                    <li>
                                        <a href="{{url('edit-invoices')}}" class="dropdown-item d-flex align-items-center">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_payment">Download</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td> <a href="{{url('invoices-details')}}">#INV0017</a></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-09.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold"> Michael Trade</a>
                                </div>
                            </td>
                            <td class="text-dark"> 01 Feb 2025 </td>
                            <td class="text-dark"> 01 Feb 2025 </td>
                            <td class="fw-semibold text-dark"> $570</td>
                            <td> <span class="badge badge-soft-success rounded text-success fw-medium border border-success"> Paid</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="{{url('invoices-details')}}" class="dropdown-item d-flex align-items-center">View details</a>
                                    </li>
                                    <li>
                                        <a href="{{url('edit-invoices')}}" class="dropdown-item d-flex align-items-center">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_payment">Download</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td> <a href="{{url('invoices-details')}}">#INV0016</a></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-10.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">Ava Robinson </a>
                                </div>
                            </td>
                            <td class="text-dark"> 25 Jan 2025 </td>
                            <td class="text-dark"> 25 Jan 2025 </td>
                            <td class="fw-semibold text-dark"> $800</td>
                            <td> <span class="badge badge-soft-danger rounded text-danger fw-medium border border-danger">Unpaid</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="{{url('invoices-details')}}" class="dropdown-item d-flex align-items-center">View details</a>
                                    </li>
                                    <li>
                                        <a href="{{url('edit-invoices')}}" class="dropdown-item d-flex align-items-center">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_payment">Download</a>
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