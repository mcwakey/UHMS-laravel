<?php $page = 'delete-account-request'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->
        
    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content">

            <!-- Start Page Header -->
            <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
                <div class="flex-grow-1">
                    <h4 class="fw-bold mb-0">Delete Account Request</h4>
                </div>
            </div>
            <!-- End Page Header -->

            <div class=" d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                <div class="search-set mb-3">
                    <div class="d-flex align-items-center">
                        <div class="table-search d-flex align-items-center mb-0 me-2">
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

            <div class="table-responsive">
                <table class="table table-nowrap datatable">
                    <thead class="thead-light">
                        <tr>
                            <th>User</th>
                            <th>Requisition Date</th>
                            <th>Delete Request Date</th>
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
                            <td>22 Apr 2025</td>
                            <td>30 Apr 2025</td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Available</span></td>
                            <td>
                                <div class="action-item p-2">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#cancel_request">Cancel</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_request">Delete</a>
                                        </li>
                                    </ul>
                                </div>
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
                            <td>12 Apr 2025</td>
                            <td>15 Apr 2025</td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Available</span></td>
                            <td>
                                <div class="action-item p-2">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#cancel_request">Cancel</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_request">Delete</a>
                                        </li>
                                    </ul>
                                </div>
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
                            <td>01 Apr 2025</td>
                            <td>02 Apr 2025</td>
                            <td><span class="badge badge-soft-danger border border-danger px-2 py-1 fs-13 fw-medium">Unavailable</span></td>
                            <td>
                                <div class="action-item p-2">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#cancel_request">Cancel</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_request">Delete</a>
                                        </li>
                                    </ul>
                                </div>
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
                            <td>05 Mar 2025</td>
                            <td>12 Mar 2025</td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Available</span></td>
                            <td>
                                <div class="action-item p-2">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#cancel_request">Cancel</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_request">Delete</a>
                                        </li>
                                    </ul>
                                </div>
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
                            <td>20 Mar 2025</td>
                            <td>27 Mar 2025</td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Available</span></td>
                            <td>
                                <div class="action-item p-2">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#cancel_request">Cancel</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_request">Delete</a>
                                        </li>
                                    </ul>
                                </div>
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
                            <td>01 Mar 2025</td>
                            <td>05 Mar 2025</td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Available</span></td>
                            <td>
                                <div class="action-item p-2">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#cancel_request">Cancel</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_request">Delete</a>
                                        </li>
                                    </ul>
                                </div>
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
                            <td>15 Feb 2025</td>
                            <td>24 Feb 2025</td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Available</span></td>
                            <td>
                                <div class="action-item p-2">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#cancel_request">Cancel</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_request">Delete</a>
                                        </li>
                                    </ul>
                                </div>
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
                            <td>09 Feb 2025</td>
                            <td>16 Feb 2025</td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Available</span></td>
                            <td>
                                <div class="action-item p-2">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#cancel_request">Cancel</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_request">Delete</a>
                                        </li>
                                    </ul>
                                </div>
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
                            <td>01 Feb 2025</td>
                            <td>01 Feb 2025</td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Available</span></td>
                            <td>
                                <div class="action-item p-2">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#cancel_request">Cancel</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_request">Delete</a>
                                        </li>
                                    </ul>
                                </div>
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
                            <td>12 Jan 2025</td>
                            <td>25 Jan 2025</td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Available</span></td>
                            <td>
                                <div class="action-item p-2">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#cancel_request">Cancel</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_request">Delete</a>
                                        </li>
                                    </ul>
                                </div>
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