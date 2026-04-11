<?php $page = 'calendar'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->

    <div class="page-wrapper">
        
        <!-- Start Content -->
        <div class="content content-two">

            <!-- Page Header -->
            <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3">
                <div class="flex-grow-1">
                    <h4 class="fs-18 fw-semibold mb-0">Calendar</h4>
                </div>
                <div class="text-end">
                    <ol class="breadcrumb m-0 py-0">
                        <li class="breadcrumb-item"><a href="{{url('index')}}">Home</a></li>                              
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Applications</a></li>                            
                        <li class="breadcrumb-item active">Calendar</li>
                    </ol>
                </div>
            </div>
            <!-- End Page Header -->

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <div class="dropdown">
                    <a href="javascript:void(0);" class="dropdown-toggle btn btn-outline-light btn-sm bg-white text-dark d-inline-flex align-items-center drop-arrow-none" data-bs-toggle="dropdown">
                        <i class="ti ti-file-export me-1"></i>Export<i class="ti ti-chevron-down align-middle ms-1"></i>
                    </a>
                    <ul class="dropdown-menu  dropdown-menu-start">
                        <li>
                            <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                        </li>
                        <li>
                            <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
                        </li>
                    </ul>
                </div>
                <div>
                    <a href="#" data-bs-toggle="modal" data-bs-target="#add_event" class="btn btn-sm btn-primary">
                        <i class="ti ti-plus me-1"></i>Create
                    </a>
                </div>
            </div>

            <!-- start Card -->
            <div class="card mb-0">
                <div class="card-body">
                    <div id="calendar"></div>
                </div>
            </div>
            <!-- end card -->

        </div>
        <!-- End Content -->

        @component('components.footer')
        @endcomponent

    </div>

    <!-- ========================
        End Page Content
    ========================= -->

@endsection    