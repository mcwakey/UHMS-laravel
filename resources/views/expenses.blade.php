<?php $page = 'expenses'; ?>
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
                    <h4 class="fw-bold mb-0"> Expenses <span class="badge badge-soft-primary fw-medium border py-1 px-2 border-primary fs-13 ms-1">Total Expenses : 565</span> </h4>
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
                    <a href="javascript:void(0);" class="btn btn-primary ms-2 fs-13 btn-md" data-bs-toggle="modal" data-bs-target="#add_new_expense"><i class="ti ti-plus me-1"></i>New Expense </a>
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
                                            <label class="form-label mb-1">Purchased By</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <select class="select2" multiple="multiple">
                                            <option value="m-1" selected>James Allaire</option>
                                            <option value="m-2">Esther Schmidt</option>
                                            <option value="m-3">Judi Lenahan</option>
                                            <option value="m-4">Robert Reid</option>
                                            <option value="m-5">Dottie Sellers</option>
                                            <option value="m-6">Cheryl Bilodeau</option>
                                            <option value="m-7">Diane Nash</option>
                                            <option value="m-8">Sally Cavazos</option>
                                            <option value="m-9">Forest Heath</option>
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
                                            <option value="m-1" selected>Approved</option>
                                            <option value="m-2">Rejected</option>
                                            <option value="m-3">New</option>
                                            <option value="m-4">Pending</option>
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
                                Expense
                            </th>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Purchased By</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td> <a href="">Gloves & Masks</a></td>
                            <td class="text-dark"> Medical Supplies </td>
                            <td class="fw-semibold text-dark"> $800</td>
                            <td class="text-dark"> 30 Apr 2025 </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-01.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">James Adair  </a>
                                </div>
                            </td>
                            <td class="text-dark">PayPal</td>
                            <td> <span class="badge badge-soft-success rounded text-success fw-medium border border-success">Approved</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_expense">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        
                        <tr>
                            <td> <a href="">Microscope Parts</a></td>
                            <td class="text-dark"> Laboratory </td>
                            <td class="fw-semibold text-dark"> $930</td>
                            <td class="text-dark"> 15 Apr 2025 </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-02.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">Esther Schmidt  </a>
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
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_expense">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td> <a href="">Thermometers</a></td>
                            <td class="text-dark"> Medical Supplies </td>
                            <td class="fw-semibold text-dark"> $850</td>
                            <td class="text-dark">02 Apr 2025 </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-03.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">Judi Lenahan </a>
                                </div>
                            </td>
                            <td class="text-dark">Cheque</td>
                            <td> <span class="badge badge-soft-success rounded text-success fw-medium border border-success">Approved</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_expense">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        
                        <tr>
                            <td> <a href="">Disinfectant Supplies</a></td>
                            <td class="text-dark"> Cleaning Services </td>
                            <td class="fw-semibold text-dark"> $700</td>
                            <td class="text-dark"> 27 Mar 2025 </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-04.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">Robert Reid  </a>
                                </div>
                            </td>
                            <td class="text-dark">Debit Card</td>
                            <td> <span class="badge badge-soft-danger rounded text-danger fw-medium border border-danger">Rejected </span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_expense">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td> <a href="">IV Sets</a></td>
                            <td class="text-dark"> Medical Supplies </td>
                            <td class="fw-semibold text-dark"> $650</td>
                            <td class="text-dark"> 12 Mar 2025 </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/doctors/doctor-01.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">Dottie Sellers  </a>
                                </div>
                            </td>
                            <td class="text-dark">PayPal</td>
                            <td> <span class="badge badge-soft-primary rounded text-primary fw-medium border border-primary">New</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_expense">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td> <a href="">Reagent Refill</a></td>
                            <td class="text-dark">  Laboratory </td>
                            <td class="fw-semibold text-dark"> $430</td>
                            <td class="text-dark"> 05 Mar 2025</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/doctors/doctor-02.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">Cheryl Bilodeau </a>
                                </div>
                            </td>
                            <td class="text-dark">Cheque</td>
                            <td> <span class="badge badge-soft-danger rounded text-danger fw-medium border border-danger">Rejected</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_expense">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td> <a href="">Syringes & Gauze</a></td>
                            <td class="text-dark"> Medical Supplies </td>
                            <td class="fw-semibold text-dark"> $300</td>
                            <td class="text-dark"> 24 Feb 2025 </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-07.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">Valerie Padgett  </a>
                                </div>
                            </td>
                            <td class="text-dark">Debit Card</td>
                            <td> <span class="badge badge-soft-primary rounded text-primary fw-medium border border-primary">New</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_expense">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td> <a href="">Blood Collection Tubes</a></td>
                            <td class="text-dark"> Laboratory </td>
                            <td class="fw-semibold text-dark"> $450</td>
                            <td class="text-dark"> 16 Feb 2025 </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/doctors/doctor-05.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">Diane Nash  </a>
                                </div>
                            </td>
                            <td class="text-dark">Cheque</td>
                            <td> <span class="badge badge-soft-success rounded text-success fw-medium border border-success">Approved</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_expense">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td> <a href="">Bandages & Tapes</a></td>
                            <td class="text-dark"> Medical Supplies </td>
                            <td class="fw-semibold text-dark"> $570</td>
                            <td class="text-dark"> 01 Feb 2025 </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/doctors/doctor-07.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">Sally Cavazos  </a>
                                </div>
                            </td>
                            <td class="text-dark">Debit Card</td>
                            <td> <span class="badge badge-soft-primary rounded text-primary fw-medium border border-primary">New</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_expense">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td> <a href="">Tissue Slides</a></td>
                            <td class="text-dark"> Laboratory </td>
                            <td class="fw-semibold text-dark"> $800</td>
                        <td class="text-dark"> 25 Jan 2025 </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-09.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">Forest Heath  </a>
                                </div>
                            </td>
                            <td class="text-dark">PayPal</td>
                            <td> <span class="badge badge-soft-success rounded text-success fw-medium border border-success">Approved</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_expense">Edit</a>
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