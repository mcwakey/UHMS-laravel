<?php $page = 'signatures-settings'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->
        
    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content">

            <!-- Page Header -->
            <div class="mb-3 border-bottom pb-3">
                <h4 class="fw-bold mb-0">Settings</h4>
            </div>
            <!-- End Page Header -->

            <div class="card">
                <div class="card-body p-0">
                    <div class="settings-wrapper d-flex">

                        @component('components.settings-sidebar')
                        @endcomponent

                        <div class="card flex-fill mb-0 border-0 bg-light-500 shadow-none">
                            <div class="card-header border-bottom px-0 mx-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <h5 class="fw-bold">Signatures</h5>
                                    <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_reason"><i class="ti ti-plus me-1"></i>New Signature</a>
                                </div>
                            </div>
                            <div class="card-body px-0 mx-3">
                                <!-- Table List -->
                                <div class="table-responsive border">
                                    <table class="table table-nowrap">
                                        <thead class="tablehead-light">
                                            <tr>
                                                <th>Signature Name</th>
                                                <th>Signature</th>
                                                <th>Status</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <p class="mb-0 me-2">Samuel Donatte</p>
                                                        <span class="badge badge-soft-primary border border-primary fw-medium">Default</span>
                                                    </div>
                                                </td>
                                                <td><img src="{{URL::asset('build/img/icons/signature-01.svg')}}" alt="img" class="img-fluid"></td>
                                                <td><span class="badge bg-soft-success fs-13 fw-medium text-success border border-success py-1 px-2">Active</span></td>
                                                <td class="action-item">
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <ul class="dropdown-menu p-2">
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_reason">Edit</a>
                                                        </li>
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_reason">Delete</a>
                                                        </li>
                                                    </ul>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Michael Smith</td>
                                                <td><img src="{{URL::asset('build/img/icons/signature-02.svg')}}" alt="img" class="img-fluid"></td>
                                                <td><span class="badge bg-soft-success fs-13 fw-medium text-success border border-success py-1 px-2">Active</span></td>
                                                <td class="action-item">
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <ul class="dropdown-menu p-2">
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_reason">Edit</a>
                                                        </li>
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_reason">Delete</a>
                                                        </li>
                                                    </ul>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Alberto Alleo</td>
                                                <td><img src="{{URL::asset('build/img/icons/signature-03.svg')}}" alt="img" class="img-fluid"></td>
                                                <td><span class="badge bg-soft-success fs-13 fw-medium text-success border border-success py-1 px-2">Active</span></td>
                                                <td class="action-item">
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <ul class="dropdown-menu p-2">
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_reason">Edit</a>
                                                        </li>
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_reason">Delete</a>
                                                        </li>
                                                    </ul>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Ernesto Janetts</td>
                                                <td><img src="{{URL::asset('build/img/icons/signature-04.svg')}}" alt="img" class="img-fluid"></td>
                                                <td><span class="badge bg-soft-danger fs-13 fw-medium text-danger border border-danger py-1 px-2">Inactive</span></td>
                                                <td class="action-item">
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <ul class="dropdown-menu p-2">
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_reason">Edit</a>
                                                        </li>
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_reason">Delete</a>
                                                        </li>
                                                    </ul>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- /Table List -->
                            </div>
                        </div>
                    </div>

                </div><!-- end card body -->
            </div><!-- end card -->
                            
        </div>
        <!-- End Content -->

        @component('components.footer')
        @endcomponent

    </div>

    <!-- ========================
        End Page Content
    ========================= -->

@endsection    