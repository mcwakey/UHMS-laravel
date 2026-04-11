<?php $page = 'roles-and-permissions'; ?>
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
                    <h4 class="fw-bold mb-0">Roles</h4>
                </div>
                <div class="text-end d-flex">
                    <a href="javascript:void(0);" class="btn btn-primary ms-2 fs-13 btn-md" data-bs-toggle="modal" data-bs-target="#add_role"><i class="ti ti-plus me-1"></i>New Role</a>
                </div>
            </div>
            <!-- End Page Header -->

            <div class="table-responsive">
                <table class="table table-nowrap">
                    <thead class="thead-light">
                        <tr>
                            <th>Role</th>
                            <th>Created On</th>
                            <th>Status</th>
                            <th></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Admin</td>
                            <td>30 Apr 2025</td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Active</span></td>
                            <td><a href="{{url('permissions')}}" class="btn btn-white border text-dark"><i class="ti ti-shield-half me-1"></i>Permissions</a></td>
                            <td>
                                <div class="action-item">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_role">Edit</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_role">Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>Nurse</td>
                            <td>12 Mar 2025</td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Active</span></td>
                            <td><a href="{{url('permissions')}}" class="btn btn-white border text-dark"><i class="ti ti-shield-half me-1"></i>Permissions</a></td>
                            <td>
                                <div class="action-item">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_role">Edit</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_role">Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>Receptionist</td>
                            <td>27 Mar 2025</td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Active</span></td>
                            <td><a href="{{url('permissions')}}" class="btn btn-white border text-dark"><i class="ti ti-shield-half me-1"></i>Permissions</a></td>
                            <td>
                                <div class="action-item">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_role">Edit</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_role">Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>Lab Technician</td>
                            <td>05 Mar 2025</td>
                            <td><span class="badge badge-soft-danger border border-danger px-2 py-1 fs-13 fw-medium">Inactive</span></td>
                            <td><a href="{{url('permissions')}}" class="btn btn-white border text-dark"><i class="ti ti-shield-half me-1"></i>Permissions</a></td>
                            <td>
                                <div class="action-item">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_role">Edit</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_role">Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>Pharmacist</td>
                            <td>24 Feb 2025</td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Active</span></td>
                            <td><a href="{{url('permissions')}}" class="btn btn-white border text-dark"><i class="ti ti-shield-half me-1"></i>Permissions</a></td>
                            <td>
                                <div class="action-item">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_role">Edit</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_role">Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>Accountant</td>
                            <td>16 Feb 2025</td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Active</span></td>
                            <td><a href="{{url('permissions')}}" class="btn btn-white border text-dark"><i class="ti ti-shield-half me-1"></i>Permissions</a></td>
                            <td>
                                <div class="action-item">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_role">Edit</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_role">Delete</a>
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