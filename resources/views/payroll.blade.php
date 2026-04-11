<?php $page = 'payroll'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- =====================`===
        Start Page Content
    ========================= -->
        
    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content">

            <!-- Start Page Header -->
            <div class="mb-3 pb-3 border-bottom">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <h4 class="fw-bold mb-0 me-2">Payroll</h4>
                        <span class="badge badge-soft-primary border border-primary fw-medium">Total Department : 33</span>
                    </div>
                    <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_payroll"><i class="ti ti-plus me-1"></i>Add  Employee Salary</a>
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
                    <div class="dropdown me-2">
                        <a href="javascript:void(0);" class="btn btn-white bg-white fs-14 py-1 border d-inline-flex text-dark align-items-center" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                            <i class="ti ti-filter text-gray-5 me-1"></i>Filters
                        </a>
                        <div class="dropdown-menu dropdown-lg dropdown-menu-end filter-dropdown p-0" id="filter-dropdown">
                            <div class="d-flex align-items-center justify-content-between border-bottom filter-header">
                                <h5 class="mb-0 fw-bold">Filter</h5>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="link-danger text-decoration-underline">Clear All</a>
                                </div>
                            </div>
                            <form action="#">
                                <div class="filter-body pb-0">
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label">Employee</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <select class="select2" multiple="multiple">
                                            <option value="m-1" selected>James Allaire</option>
                                            <option value="m-2">Esther Schmidt</option>
                                            <option value="m-3">Judi Lenahan</option>
                                            <option value="m-4">Robert Reid</option>
                                            <option value="m-4">Dottie Sellers</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label">Role</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <select class="select2" multiple="multiple">
                                            <option value="m-1" selected>Admin</option>
                                            <option value="m-2">Reception</option>
                                            <option value="m-3">Nurse (RN)</option>
                                            <option value="m-4">Nurse Practitioner</option>
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
                                            <label class="form-label">Status</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <select class="select2" multiple="multiple">
                                            <option value="m-1" selected>Active</option>
                                            <option value="m-2">Inactive</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="filter-footer d-flex align-items-center justify-content-end border-top">
                                    <a href="javascript:void(0);" class="btn btn-light btn-md me-2" id="close-filter">Close</a>
                                    <button type="submit" class="btn btn-primary btn-md">Filter</button>
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

            <div class="table-responsive">
                <table class="table table-nowrap datatable">
                    <thead class="thead-light">
                        <tr>
                            <th>Employee</th>
                            <th>Email</th>
                            <th>Joining Date</th>
                            <th>Role</th>
                            <th>Salary</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2" data-bs-toggle="modal" data-bs-target="#view_staff">
                                        <img src="{{URL::asset('build/img/users/user-08.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-0 fs-14 fw-semibold"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_staff">James Adair</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>james@gmail.com</td>
                            <td>01 Jan  2024</td>
                            <td>Admin</td>
                            <td>$1200</td>
                            <td><a href="{{url('payroll-2')}}" class="btn btn-white border text-dark">Generate Slip</a></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_payroll">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_payroll">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2" data-bs-toggle="modal" data-bs-target="#view_staff">
                                        <img src="{{URL::asset('build/img/users/user-03.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-0 fs-14 fw-semibold"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_staff">Adam Milne</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>esther@gmail.com</td>
                            <td>04 Jan 2023</td>
                            <td>Reception</td>
                            <td>$2000</td>
                            <td><a href="{{url('payroll-2')}}" class="btn btn-white border text-dark">Generate Slip</a></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_payroll">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_payroll">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2" data-bs-toggle="modal" data-bs-target="#view_staff">
                                        <img src="{{URL::asset('build/img/users/user-04.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-0 fs-14 fw-semibold"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_staff">Richard Clark</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>judi@gmail.com</td>
                            <td>26 Jan 2022</td>
                            <td>Admin</td>
                            <td>$1500</td>
                            <td><a href="{{url('payroll-2')}}" class="btn btn-white border text-dark">Generate Slip</a></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_payroll">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_payroll">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2" data-bs-toggle="modal" data-bs-target="#view_staff">
                                        <img src="{{URL::asset('build/img/users/user-05.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-0 fs-14 fw-semibold"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_staff">Robert Reid</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>robert@gmail.com</td>
                            <td>04 Feb 2022</td>
                            <td>Admin</td>
                            <td>$1200</td>
                            <td><a href="{{url('payroll-2')}}" class="btn btn-white border text-dark">Generate Slip</a></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_payroll">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_payroll">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2" data-bs-toggle="modal" data-bs-target="#view_staff">
                                        <img src="{{URL::asset('build/img/users/user-06.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-0 fs-14 fw-semibold"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_staff">Dottie Jeny</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>dottie@gmail.com</td>
                            <td>03 Mar 2021</td>
                            <td>Nurse</td>
                            <td>$1500</td>
                            <td><a href="{{url('payroll-2')}}" class="btn btn-white border text-dark">Generate Slip</a></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_payroll">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_payroll">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2" data-bs-toggle="modal" data-bs-target="#view_staff">
                                        <img src="{{URL::asset('build/img/users/user-07.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-0 fs-14 fw-semibold"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_staff">Cheryl Bilodeau</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>cheryl@gmail.com</td>
                            <td>08 May 2021</td>
                            <td>Nurse (RN)</td>
                            <td>$2500</td>
                            <td><a href="{{url('payroll-2')}}" class="btn btn-white border text-dark">Generate Slip</a></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_payroll">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_payroll">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2" data-bs-toggle="modal" data-bs-target="#view_staff">
                                        <img src="{{URL::asset('build/img/users/user-02.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-0 fs-14 fw-semibold"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_staff">Valerie Padgett</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>valerie@gmail.com</td>
                            <td>29 Mar 2021</td>
                            <td>Nurse Practitioner</td>
                            <td>$1000</td>
                            <td><a href="{{url('payroll-2')}}" class="btn btn-white border text-dark">Generate Slip</a></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_payroll">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_payroll">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2" data-bs-toggle="modal" data-bs-target="#view_staff">
                                        <img src="{{URL::asset('build/img/users/user-25.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-0 fs-14 fw-semibold"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_staff">Diane Nash</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>diane@gmail.com</td>
                            <td>01 Apr 2020</td>
                            <td>Nurse</td>
                            <td>$1250</td>
                            <td><a href="{{url('payroll-2')}}" class="btn btn-white border text-dark">Generate Slip</a></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_payroll">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_payroll">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2" data-bs-toggle="modal" data-bs-target="#view_staff">
                                        <img src="{{URL::asset('build/img/users/user-29.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-0 fs-14 fw-semibold"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_staff">Sally Cavazos</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>sally@gmail.com</td>
                            <td>01 May 2020</td>
                            <td>Nurse</td>
                            <td>$1550</td>
                            <td><a href="{{url('payroll-2')}}" class="btn btn-white border text-dark">Generate Slip</a></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_payroll">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_payroll">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2" data-bs-toggle="modal" data-bs-target="#view_staff">
                                        <img src="{{URL::asset('build/img/users/user-12.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-0 fs-14 fw-semibold"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_staff">Forest Heath</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>forest@gmail.com</td>
                            <td>27 May 2020</td>
                            <td>Reception</td>
                            <td>$1250</td>
                            <td><a href="{{url('payroll-2')}}" class="btn btn-white border text-dark">Generate Slip</a></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_payroll">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_payroll">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
                            
        </div>
        <!-- End Content -->

        @component('components.footer')
        @endcomponent

    </div>

    <!-- ========================
        End Page Content
    ========================= -->

@endsection    