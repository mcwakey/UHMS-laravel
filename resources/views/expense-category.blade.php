<?php $page = 'expense-category'; ?>
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
                    <h4 class="fw-bold mb-0"> Expense Category </h4>
                </div>
                <div class="text-end d-flex">
                    <a href="javascript:void(0);" class="btn btn-primary ms-2 fs-13 btn-md" data-bs-toggle="modal" data-bs-target="#add_new_category"><i class="ti ti-plus me-1"></i>Add Category</a>
                </div>
            </div>
            <!-- End Page Header -->

            <!--  Start Table -->
            <div class="table-responsive">
                <table class="table table-nowrap">
                    <thead class="thead-light">
                        <tr>
                            <th class="no-sort">
                                Category
                            </th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td> <a href="">Medical Supplies</a></td>
                            <td> <span class="badge badge-soft-success rounded text-success fw-medium border border-success fs-13">Active</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_category">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td> <a href="">Laboratory</a></td>
                            <td> <span class="badge badge-soft-success rounded text-success fw-medium border border-success fs-13">Active</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_category">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td> <a href="">Cleaning Services</a></td>
                            <td> <span class="badge badge-soft-success rounded text-success fw-medium border border-success fs-13">Active</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_category">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td> <a href="">Salary</a></td>
                            <td> <span class="badge badge-soft-success rounded text-success fw-medium border border-success fs-13">Active</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_category">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td> <a href="">Utilities</a></td>
                            <td> <span class="badge badge-soft-danger rounded text-danger fw-medium border border-danger fs-13">Inactive</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_category">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td> <a href="">Medical Supplies</a></td>
                            <td> <span class="badge badge-soft-danger rounded text-danger fw-medium border border-danger fs-13">Inactive</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_new_category">Edit</a>
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