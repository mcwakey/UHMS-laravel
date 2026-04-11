<?php $page = 'newsletters'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->
        
    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content">

            <!-- Page Header -->
            <div class="mb-3 pb-3 border-bottom">
                <div class="d-flex align-items-center">
                    <h4 class="fw-bold mb-0 me-2">Newsletter</h4>
                    <span class="badge badge-soft-primary border pt-1 px-2 border-primary fw-medium">Total Newsletter : 565</span>
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

            <!-- Table List -->
            <div class="table-responsive">
                <table class="table table-nowrap datatable mb-0">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Created On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>ruth@gmail.com</td>
                            <td>30 Apr 2025</td>
                        </tr>
                        <tr>
                            <td>john@gmail.com</td>
                            <td>15 Apr 2025</td>
                        </tr>
                        <tr>
                            <td>mary@gmail.com</td>
                            <td>02 Apr 2025</td>
                        </tr>
                        <tr>
                            <td>emma@gmail.com</td>
                            <td>27 Mar 2025</td>
                        </tr>
                        <tr>
                            <td>kelly@gmail.com</td>
                            <td>30 Apr 2025</td>
                        </tr>
                        <tr>
                            <td>oscar@gmail.com</td>
                            <td>12 Mar 2025</td>
                        </tr>
                        <tr>
                            <td>daniel@gmail.com</td>
                            <td>05 Mar 2025</td>
                        </tr>
                        <tr>
                            <td>chris@gmail.com</td>
                            <td>24 Feb 2025</td>
                        </tr>
                        <tr>
                            <td>steve@gmail.com</td>
                            <td>16 Feb 2025</td>
                        </tr>
                        <tr>
                            <td>charles@gmail.com</td>
                            <td>01 Feb 2025</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- /Table List -->
                            
        </div>
        <!-- End Content -->

        @component('components.footer')
        @endcomponent

    </div>

    <!-- ========================
        End Page Content
    ========================= -->

@endsection    