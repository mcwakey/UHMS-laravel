<?php $page = 'tax-rates-settings'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->
        
    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content" id="profilePage">

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

                        <!-- start row -->
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card flex-fill mb-0 border-0 bg-light-500 shadow-none">
                                    <div class="card-header border-bottom px-0 mx-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h5 class="fw-bold">Tax Rates</h5>
                                            <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_tax_rate"><i class="ti ti-plus me-1"></i>New Bank Account</a>
                                        </div>
                                    </div>
                                    <div class="card-body px-0 mx-3">
                                        <!-- Table List -->
                                        <div class="table-responsive border">
                                            <table class="table table-nowrap">
                                                <thead class="tablehead-light">
                                                    <tr>
                                                        <th>Tax Name</th>
                                                        <th>Tax Rate</th>
                                                        <th>Created On</th>
                                                        <th>Status</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>VAT</td>
                                                        <td>10%</td>
                                                        <td>30 Apr 2025</td>
                                                        <td><span class="badge bg-soft-success fs-13 fw-medium text-success border border-success py-1 px-2">Active</span></td>
                                                        <td class="action-item">
                                                            <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                                                <i class="ti ti-dots-vertical"></i>
                                                            </a>
                                                            <ul class="dropdown-menu p-2">
                                                                <li>
                                                                    <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_tax_rate">Edit</a>
                                                                </li>
                                                                <li>
                                                                    <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_tax_rate">Delete</a>
                                                                </li>
                                                            </ul>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>CGST</td>
                                                        <td>08%</td>
                                                        <td>15 Apr 2025</td>
                                                        <td><span class="badge bg-soft-success fs-13 fw-medium text-success border border-success py-1 px-2">Active</span></td>
                                                        <td class="action-item">
                                                            <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                                                <i class="ti ti-dots-vertical"></i>
                                                            </a>
                                                            <ul class="dropdown-menu p-2">
                                                                <li>
                                                                    <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_tax_rate">Edit</a>
                                                                </li>
                                                                <li>
                                                                    <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_tax_rate">Delete</a>
                                                                </li>
                                                            </ul>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>SGST</td>
                                                        <td>10%</td>
                                                        <td>05 Mar 2025</td>
                                                        <td><span class="badge bg-soft-success fs-13 fw-medium text-success border border-success py-1 px-2">Active</span></td>
                                                        <td class="action-item">
                                                            <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                                                <i class="ti ti-dots-vertical"></i>
                                                            </a>
                                                            <ul class="dropdown-menu p-2">
                                                                <li>
                                                                    <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_tax_rate">Edit</a>
                                                                </li>
                                                                <li>
                                                                    <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_tax_rate">Delete</a>
                                                                </li>
                                                            </ul>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <!-- /Table List -->
                                    </div><!-- end card body -->
                                </div><!-- end card -->
                            </div><!-- end col -->
                            <div class="col-lg-12">
                                <div class="card flex-fill mb-0 border-0 bg-light-500 shadow-none">
                                    <div class="card-header border-bottom px-0 mx-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h5 class="fw-bold">Tax Group</h5>
                                            <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_tax_group"><i class="ti ti-plus me-1"></i>New Tax Group</a>
                                        </div>
                                    </div>
                                    <div class="card-body px-0 mx-3">
                                        <!-- Table List -->
                                        <div class="table-responsive border">
                                            <table class="table table-nowrap">
                                                <thead class="tablehead-light">
                                                    <tr>
                                                        <th>Tax Name</th>
                                                        <th>Tax Rate</th>
                                                        <th>Created On</th>
                                                        <th>Status</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>GST</td>
                                                        <td>18%</td>
                                                        <td>20 Mar 2025</td>
                                                        <td><span class="badge bg-soft-success fs-13 fw-medium text-success border border-success py-1 px-2">Active</span></td>
                                                        <td class="action-item">
                                                            <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                                                <i class="ti ti-dots-vertical"></i>
                                                            </a>
                                                            <ul class="dropdown-menu p-2">
                                                                <li>
                                                                    <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_tax_group">Edit</a>
                                                                </li>
                                                                <li>
                                                                    <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_tax_group">Delete</a>
                                                                </li>
                                                            </ul>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <!-- /Table List -->
                                    </div><!-- end card body -->
                                </div><!-- end card -->
                            </div><!-- end col -->
                        </div>
                        <!-- end row -->

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