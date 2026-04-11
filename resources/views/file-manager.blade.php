<?php $page = 'file-manager'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->

    <div class="page-wrapper">
        
        <!-- Start Content -->
        <div class="content">
            
            <!-- Page Header -->
            <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3">
                <div class="flex-grow-1">
                    <h4 class="fs-18 fw-semibold mb-0">File Manager</h4>
                </div>
                <div class="text-end">
                    <ol class="breadcrumb m-0 py-0">
                        <li class="breadcrumb-item"><a href="{{url('index')}}">Home</a></li>                              
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Applications</a></li>                              
                        <li class="breadcrumb-item active" aria-current="page">File Manager</li>
                    </ol>
                </div>
            </div>
            <!-- End Page Header -->

            <div class="d-flex align-items-center justify-content-between flex-wrap mb-2">
                <div class="mb-2">
                    <div class="dropdown">
                        <a href="javascript:void(0);" class="dropdown-toggle btn btn-sm btn-outline-light bg-white text-dark d-inline-flex align-items-center drop-arrow-none" data-bs-toggle="dropdown">
                            All Files<i class="ti ti-chevron-down align-middle ms-1"></i>
                        </a>
                        <ul class="dropdown-menu  dropdown-menu-start">
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">All Files</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">Music</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">Video</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">Documents</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">Photos</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="mb-2">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#add_folder" class="btn btn-sm btn-primary d-flex align-items-center"><i class="ti ti-circle-plus me-1"></i>Create Folder</a>
                </div>
            </div>

            <!-- start row -->
            <div class="row">

                <div class="col-lg-3 col-md-6 d-flex">
                    <div class="card flex-fill">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center">
                                    <img src="{{URL::asset('build/img/icons/dropbox.svg')}}" alt="img">
                                    <h5 class="fs-16 ms-2 mb-0">Dropbox</h5>
                                </div>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots"></i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-folder-open me-2"></i>Open</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-trash me-1"></i>Delete All</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-status-change me-1"></i>Reset</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="progress progress-xs flex-grow-1 mb-2">
                                <div class="progress-bar bg-pink rounded" role="progressbar" style="width: 20%;" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <p class="mb-0">200 Files</p>
                                <p class="text-dark mb-0">28GB</p>
                            </div>
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div> <!-- end col -->

                <div class="col-lg-3 col-md-6 d-flex">
                    <div class="card flex-fill">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center">
                                    <img src="{{URL::asset('build/img/icons/drive.svg')}}" alt="img">
                                    <h5 class="fs-16 ms-2 mb-0">Google Drive</h5>
                                </div>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots"></i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-folder-open me-2"></i>Open</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-trash me-1"></i>Delete All</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-status-change me-1"></i>Reset</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="progress progress-xs flex-grow-1 mb-2">
                                <div class="progress-bar bg-pink rounded" role="progressbar" style="width: 80%;" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <p class="mb-0">144 Files</p>
                                <p class="text-dark mb-0">54GB</p>
                            </div>
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div> <!-- end col -->

                <div class="col-lg-3 col-md-6 d-flex">
                    <div class="card flex-fill">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center">
                                    <img src="{{URL::asset('build/img/icons/cloud.svg')}}" alt="img">
                                    <h5 class="fs-16 ms-2 mb-0">Cloud Storage</h5>
                                </div>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots"></i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-folder-open me-2"></i>Open</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-trash me-1"></i>Delete All</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-status-change me-1"></i>Reset</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="progress progress-xs flex-grow-1 mb-2">
                                <div class="progress-bar bg-purple rounded" role="progressbar" style="width: 50%;" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <p class="mb-0">144 Files</p>
                                <p class="text-dark mb-0">54GB</p>
                            </div>
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div> <!-- end col -->

                <div class="col-lg-3 col-md-6 d-flex">
                    <div class="card flex-fill">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center">
                                    <img src="{{URL::asset('build/img/icons/storage.svg')}}" alt="img">
                                    <h5 class="fs-16 ms-2 mb-0">Internal Storage</h5>
                                </div>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots"></i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-folder-open me-2"></i>Open</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-trash me-1"></i>Delete All</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-status-change me-1"></i>Reset</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="progress progress-xs flex-grow-1 mb-2">
                                <div class="progress-bar bg-purple rounded" role="progressbar" style="width: 20%;" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <p class="mb-0">144 Files</p>
                                <p class="text-dark mb-0">54GB</p>
                            </div>
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div> <!-- end col -->

            </div>
            <!-- end row -->

            <!-- start row -->
            <div class="row">

                <!-- Start Sidebar -->
                <div class="col-xl-3 theiaStickySidebar">
                    <div class="card">
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center overflow-hidden">
                                        <span class="avatar flex-shrink-0">
                                            <img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" alt="img" class="rounded-circle">
                                        </span>
                                        <div class="overflow-hidden ms-2">
                                            <h5 class="fs-16 text-truncate mb-1">James Hong</h5>
                                            <p class="fs-13 text-truncate mb-0">jameshong@example.com</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="border rounded position-relative p-3 mb-3 text-center">
                                <span class="avatar avatar-sm bg-primary text-white mb-2">
                                    <i class="ti ti-upload fs-16"></i>
                                </span>
                                <h6 class="mb-2">Drop files here</h6>
                                <p class="fs-13 mb-0">Select files to upload</p>
                                <input type="file" class="position-absolute top-0 start-0 opacity-0 w-100 h-100">
                            </div>
                            <div class="files-list nav d-block">
                                <a href="javscript:void(0);" class="d-flex align-items-center fw-medium p-2 active"><i class="ti ti-folder-up me-2"></i>All Folder / Files</a>
                                <a href="javscript:void(0);" class="d-flex align-items-center fw-medium p-2"><i class="ti ti-star me-2"></i>Drive</a>
                                <a href="javscript:void(0);" class="d-flex align-items-center fw-medium p-2"><i class="ti ti-octahedron me-2"></i>Dropbox</a>
                                <a href="javscript:void(0);" class="d-flex align-items-center fw-medium p-2"><i class="ti ti-share-2 me-2"></i>Shared with Me</a>
                                <a href="javscript:void(0);" class="d-flex align-items-center fw-medium p-2"><i class="ti ti-file me-2"></i>Document</a>
                                <a href="javscript:void(0);" class="d-flex align-items-center fw-medium p-2"><i class="ti ti-clock-hour-11 me-2"></i>Recent File</a>
                                <a href="javscript:void(0);" class="d-flex align-items-center fw-medium p-2"><i class="ti ti-star me-2"></i>Important</a>
                                <a href="javscript:void(0);" class="d-flex align-items-center fw-medium p-2"><i class="ti ti-music me-2"></i>Media</a>
                            </div>
                        </div><!-- end card body -->
                    </div><!-- end card -->

                    <div class="card mb-3 mb-xl-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-1 mb-2">
                                <h6 class="mb-2">Storage Details</h6>
                                <span class="badge bg-success mb-2">Used 77%</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center overflow-hidden">
                                    <span class="avatar avatar-md bg-info-subtle">
                                        <i class="ti ti-music fs-20 text-info"></i>
                                    </span>
                                    <div class="overflow-hidden ms-2">
                                        <h6 class="text-truncate fs-14">Music</h6>
                                        <p class="fs-13 text-truncate mb-0">35 Files</p>
                                    </div>
                                </div>
                                <p class="text-dark mb-0">8.5 GB</p>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center overflow-hidden">
                                    <span class="avatar avatar-md bg-warning-subtle">
                                        <i class="ti ti-video fs-20 text-warning"></i>
                                    </span>
                                    <div class="overflow-hidden ms-2">
                                        <h6 class="text-truncate fs-14">Video</h6>
                                        <p class="fs-13 text-truncate mb-0">145 Files</p>
                                    </div>
                                </div>
                                <p class="text-dark mb-0">2 GB</p>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center overflow-hidden">
                                    <span class="avatar avatar-md bg-secondary-subtle">
                                        <i class="ti ti-file-description fs-20 text-secondary"></i>
                                    </span>
                                    <div class="overflow-hidden ms-2">
                                        <h6 class="text-truncate fs-14">Documents</h6>
                                        <p class="fs-13 text-truncate mb-0">487 Files</p>
                                    </div>
                                </div>
                                <p class="text-dark mb-0">24.5 GB</p>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center overflow-hidden">
                                    <span class="avatar avatar-md bg-primary-subtle">
                                        <i class="ti ti-photo fs-20 text-primary"></i>
                                    </span>
                                    <div class="overflow-hidden ms-2">
                                        <h6 class="text-truncate fs-14">Photos</h6>
                                        <p class="fs-13 text-truncate mb-0">35 Files</p>
                                    </div>
                                </div>
                                <p class="text-dark mb-0">8.5 GB</p>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-0">
                                <div class="d-flex align-items-center overflow-hidden">
                                    <span class="avatar avatar-md bg-danger-subtle">
                                        <i class="ti ti-file-type-doc fs-20 text-danger"></i>
                                    </span>
                                    <div class="overflow-hidden ms-2">
                                        <h6 class="text-truncate fs-14">Other</h6>
                                        <p class="fs-13 text-truncate mb-0">487 Files</p>
                                    </div>
                                </div>
                                <p class="text-dark mb-0">16.2 GB</p>
                            </div>
                        </div><!-- end card body -->
                    </div><!-- end card -->

                </div> <!-- end col -->
                <!-- End Sidebar -->

                <div class="col-xl-9">

                    <!-- Start Quick Access -->
                    <div class="border-bottom mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="mb-2">Quick Access</h6>
                            <div>
                                <a href="javascript:void(0);" class="mb-2 fw-medium link-default">View All</a>
                            </div>
                        </div>

                        <!-- start row -->
                        <div class="row row-cols-xxl-5 row-cols-xl-3 row-cols-sm-3 row-cols-1 justify-content-center">

                            <div class="col d-flex">
                                <div class="card position-relative flex-fill">
                                    <div class="card-body text-center">
                                        <img src="{{URL::asset('build/img/icons/file.svg')}}" alt="img" class="mb-3">
                                        <h6 class="mb-2 fw-medium"><a href="javascript:void(0);" data-bs-toggle="offcanvas" data-bs-target="#preview">Final.doc</a></h6>
                                        <span class="badge badge-soft-primary">2.4 GB</span>
                                    </div><!-- end card body -->
                                    <span class="position-absolute end-0 top-0 p-2"><i class="ti ti-star-filled filled text-warning"></i></span>
                                </div><!-- end card -->
                            </div> <!-- end col -->

                            <div class="col d-flex">
                                <div class="card position-relative flex-fill">
                                    <div class="card-body text-center">
                                        <img src="{{URL::asset('build/img/icons/pdf-icon.svg')}}" alt="img" class="mb-3">
                                        <h6 class="mb-2 fw-medium"><a href="javascript:void(0);" data-bs-toggle="offcanvas" data-bs-target="#preview">Marklist.pdf</a></h6>
                                        <span class="badge badge-soft-primary">2.4 GB</span>
                                    </div><!-- end card body -->
                                    <span class="position-absolute end-0 top-0 p-2"><i class="ti ti-star"></i></span>
                                </div><!-- end card -->
                            </div> <!-- end col -->

                            <div class="col d-flex">
                                <div class="card position-relative flex-fill">
                                    <div class="card-body text-center">
                                        <img src="{{URL::asset('build/img/icons/image.svg')}}" alt="img" class="mb-3">
                                        <h6 class="mb-2 fw-medium"><a href="javascript:void(0);" data-bs-toggle="offcanvas" data-bs-target="#preview">Nature.png</a></h6>
                                        <span class="badge badge-soft-primary">2.4 GB</span>
                                    </div><!-- end card body -->
                                    <span class="position-absolute end-0 top-0 p-2"><i class="ti ti-star-filled filled text-warning"></i></span>
                                </div><!-- end card -->
                            </div> <!-- end col -->

                            <div class="col d-flex">
                                <div class="card position-relative flex-fill">
                                    <div class="card-body text-center">
                                        <img src="{{URL::asset('build/img/icons/xls-icon.svg')}}" alt="img" class="mb-3">
                                        <h6 class="mb-2 fw-medium"><a href="javascript:void(0);" data-bs-toggle="offcanvas" data-bs-target="#preview">List.xlsx</a></h6>
                                        <span class="badge badge-soft-primary">2.4 GB</span>
                                    </div><!-- end card body -->
                                    <span class="position-absolute end-0 top-0 p-2"><i class="ti ti-star"></i></span>
                                </div><!-- end card -->
                            </div> <!-- end col -->

                            <div class="col d-flex">
                                <div class="card position-relative flex-fill">
                                    <div class="card-body text-center">
                                        <img src="{{URL::asset('build/img/icons/folder-icon.svg')}}" alt="img" class="mb-3">
                                        <h6 class="mb-2 fw-medium"><a href="javascript:void(0);" data-bs-toggle="offcanvas" data-bs-target="#preview">Group Photos</a></h6>
                                        <span class="badge badge-soft-primary">2.4 GB</span>
                                    </div><!-- end card body -->
                                    <span class="position-absolute end-0 top-0 p-2"><i class="ti ti-star"></i></span>
                                </div><!-- end card -->
                            </div> <!-- end col -->

                            <div class="col d-flex">
                                <div class="card position-relative flex-fill">
                                    <div class="card-body text-center">
                                        <img src="{{URL::asset('build/img/icons/file.svg')}}" alt="img" class="mb-3">
                                        <h6 class="mb-2 fw-medium"><a href="javascript:void(0);" data-bs-toggle="offcanvas" data-bs-target="#preview">Final.doc</a></h6>
                                        <span class="badge badge-soft-primary">2.4 GB</span>
                                    </div><!-- end card body -->
                                    <span class="position-absolute end-0 top-0 p-2"><i class="ti ti-star-filled filled text-warning"></i></span>
                                </div><!-- end card -->
                            </div> <!-- end col -->

                        </div>
                        <!-- end row -->

                    </div>
                    <!-- End Quick Access -->

                    <!-- Start Recent Folders -->
                    <div class="border-bottom mb-3">

                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="mb-2">Recent Folders</h6>
                            <div class="dropdown mb-2">
                                <a href="javascript:void(0);" class="dropdown-toggle btn btn-sm btn-outline-light bg-white text-dark d-inline-flex align-items-center drop-arrow-none" data-bs-toggle="dropdown">
                                    Last 7 Days<i class="ti ti-chevron-down align-middle ms-1"></i>
                                </a>
                                <ul class="dropdown-menu  dropdown-menu-end">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">Last 7 Days</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">Last Month</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">Last Year</a>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- start row -->
                        <div class="row">

                            <div class="col-lg-4 col-md-6 d-flex">
                                <div class="bg-white d-flex align-items-center justify-content-between border p-2 rounded mb-3 flex-fill">
                                    <div class="d-flex align-items-center">
                                        <span class="text-warning fs-24">
                                            <i class="ti ti-folder-filled"></i>
                                        </span>
                                        <div class="ms-2">
                                            <h6 class="mb-1"><a href="#" data-bs-toggle="offcanvas" data-bs-target="#preview">Assets</a></h6>
                                            <div class="d-flex align-items-center">
                                                <p class="fs-12 mb-0 me-1">2.4 GB</p>
                                                <p class="fs-12 mb-0 d-flex align-items-center"><i class="ti ti-circle-filled fs-7 me-1 text-dark"></i>35 files</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-list-stacked avatar-group-sm">
                                            <span class="avatar avatar-rounded">
                                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-07.jpg')}}" alt="img">
                                            </span>
                                            <span class="avatar avatar-rounded">
                                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="img">
                                            </span>
                                        </div>
                                        <div class="dropdown ms-2">
                                            <a href="javascript:void(0);" class="d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                                <i class="ti ti-dots"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a href="javascript:void(0);" data-bs-toggle="offcanvas" data-bs-target="#preview" class="dropdown-item rounded-1"><i class="ti ti-folder-open me-2"></i>Preview</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-copy me-2"></i>Duplicate</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-arrow-left-right me-2"></i>Move</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-user-plus me-2"></i>Invite</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-share-3 me-2"></i>Share Link</a>
                                                </li>
                                                <li>
                                                    <hr class="dropdown-divider my-2">
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-eye me-2"></i>View Details</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-download me-2"></i>Download</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete-modal" class="dropdown-item rounded-1"><i class="ti ti-trash-x me-2"></i>Delete</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div> <!-- end col -->

                            <div class="col-lg-4 col-md-6 d-flex">
                                <div class="bg-white d-flex align-items-center justify-content-between border p-2 rounded mb-3 flex-fill">
                                    <div class="d-flex align-items-center">
                                        <span class="text-warning fs-24">
                                            <i class="ti ti-folder-filled"></i>
                                        </span>
                                        <div class="ms-2">
                                            <h6 class="mb-1"><a href="#" data-bs-toggle="offcanvas" data-bs-target="#preview">Document</a></h6>
                                            <div class="d-flex align-items-center">
                                                <p class="fs-12 mb-0 me-1">4 GB</p>
                                                <p class="fs-12 mb-0 d-flex align-items-center"><i class="ti ti-circle-filled fs-7 me-1 text-dark"></i>15 files</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-list-stacked avatar-group-sm">
                                            <span class="avatar avatar-rounded">
                                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-05.jpg')}}" alt="img">
                                            </span>
                                            <span class="avatar avatar-rounded">
                                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="img">
                                            </span>
                                        </div>
                                        <div class="dropdown ms-2">
                                            <a href="javascript:void(0);" class="d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                                <i class="ti ti-dots"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a href="javascript:void(0);" data-bs-toggle="offcanvas" data-bs-target="#preview" class="dropdown-item rounded-1"><i class="ti ti-folder-open me-2"></i>Preview</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-copy me-2"></i>Duplicate</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-arrow-left-right me-2"></i>Move</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-user-plus me-2"></i>Invite</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-share-3 me-2"></i>Share Link</a>
                                                </li>
                                                <li>
                                                    <hr class="dropdown-divider my-2">
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-eye me-2"></i>View Details</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-download me-2"></i>Download</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete-modal" class="dropdown-item rounded-1"><i class="ti ti-trash-x me-2"></i>Delete</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div> <!-- end col -->

                            <div class="col-lg-4 col-md-6 d-flex">
                                <div class="bg-white d-flex align-items-center justify-content-between border p-2 rounded mb-3 flex-fill">
                                    <div class="d-flex align-items-center">
                                        <span class="text-warning fs-24">
                                            <i class="ti ti-folder-filled"></i>
                                        </span>
                                        <div class="ms-2">
                                            <h6 class="mb-1"><a href="#" data-bs-toggle="offcanvas" data-bs-target="#preview">Handyimages</a></h6>
                                            <div class="d-flex align-items-center">
                                                <p class="fs-12 mb-0 me-1">1.4 GB</p>
                                                <p class="fs-12 mb-0 d-flex align-items-center"><i class="ti ti-circle-filled fs-7 me-1 text-dark"></i>115 files</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-list-stacked avatar-group-sm">
                                            <span class="avatar avatar-rounded">
                                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-05.jpg')}}" alt="img">
                                            </span>
                                            <span class="avatar avatar-rounded">
                                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="img">
                                            </span>
                                        </div>
                                        <div class="dropdown ms-2">
                                            <a href="javascript:void(0);" class="d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                                <i class="ti ti-dots"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a href="javascript:void(0);" data-bs-toggle="offcanvas" data-bs-target="#preview" class="dropdown-item rounded-1"><i class="ti ti-folder-open me-2"></i>Preview</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-copy me-2"></i>Duplicate</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-arrow-left-right me-2"></i>Move</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-user-plus me-2"></i>Invite</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-share-3 me-2"></i>Share Link</a>
                                                </li>
                                                <li>
                                                    <hr class="dropdown-divider my-2">
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-eye me-2"></i>View Details</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-download me-2"></i>Download</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete-modal" class="dropdown-item rounded-1"><i class="ti ti-trash-x me-2"></i>Delete</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div> <!-- end col -->

                        </div>
                        <!-- end row -->

                    </div>
                    <!-- End Recent Folders -->

                    <!-- Start Recent Files -->
                    <div class="border-bottom mb-3">

                        <div class="d-flex align-items-center justify-content-between mb-2 table-header">
                            <h6 class="mb-2"><a href="#" data-bs-toggle="offcanvas" data-bs-target="#preview">Recent Files</a></h6>
                            <div class="dropdown mb-2">
                                <a href="javascript:void(0);" class="dropdown-toggle btn btn-sm bg-white text-dark btn-outline-light drop-arrow-none" data-bs-toggle="dropdown">
                                    Last Modified<i class="ti ti-chevron-down align-middle ms-1"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">Newest to Oldest</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">Last Modified</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">Oldest to Newest</a>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- start row -->
                        <div class="row">

                            <div class="col-lg-4 col-md-6">
                                <div class="rounded border mb-3">
                                    <div class="bg-transparent-dark p-5 d-flex align-items-center justify-content-center rounded-top">
                                        <i class="ti ti-file-description fs-24 text-dark"></i>
                                    </div>
                                    <div class="bg-white d-flex align-items-center justify-content-between p-3 rounded-bottom">
                                        <h6 class="fw-medium mb-0"><a href="#" data-bs-toggle="offcanvas" data-bs-target="#preview">customer_data.txt</a></h6>
                                        <div class="dropdown ms-2">
                                            <a href="javascript:void(0);" class="d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                                <i class="ti ti-dots"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a href="javascript:void(0);" data-bs-toggle="offcanvas" data-bs-target="#preview" class="dropdown-item rounded-1"><i class="ti ti-folder-open me-2"></i>Preview</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-copy me-2"></i>Duplicate</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-arrow-left-right me-2"></i>Move</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-user-plus me-2"></i>Invite</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-share-3 me-2"></i>Share Link</a>
                                                </li>
                                                <li>
                                                    <hr class="dropdown-divider my-2">
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-eye me-2"></i>View Details</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-download me-2"></i>Download</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete-modal" class="dropdown-item rounded-1"><i class="ti ti-trash-x me-2"></i>Delete</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div> <!-- end col -->

                            <div class="col-lg-4 col-md-6">
                                <div class="rounded border mb-3">
                                    <div class="bg-transparent-dark p-5 d-flex align-items-center justify-content-center rounded-top">
                                        <i class="ti ti-file-type-pdf fs-24 text-dark"></i>
                                    </div>
                                    <div class="bg-white d-flex align-items-center justify-content-between p-3 rounded-bottom">
                                        <h6 class="fw-medium text-truncate mb-0"><a href="#" data-bs-toggle="offcanvas" data-bs-target="#preview">video_player_installer_setup.rar</a></h6>
                                        <div class="dropdown ms-2">
                                            <a href="javascript:void(0);" class="d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                                <i class="ti ti-dots"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a href="javascript:void(0);" data-bs-toggle="offcanvas" data-bs-target="#preview" class="dropdown-item rounded-1"><i class="ti ti-folder-open me-2"></i>Preview</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-copy me-2"></i>Duplicate</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-arrow-left-right me-2"></i>Move</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-user-plus me-2"></i>Invite</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-share-3 me-2"></i>Share Link</a>
                                                </li>
                                                <li>
                                                    <hr class="dropdown-divider my-2">
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-eye me-2"></i>View Details</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-download me-2"></i>Download</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete-modal" class="dropdown-item rounded-1"><i class="ti ti-trash-x me-2"></i>Delete</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div> <!-- end col -->

                            <div class="col-lg-4 col-md-6">
                                <div class="rounded border mb-3">
                                    <div class="bg-transparent-dark p-5 d-flex align-items-center justify-content-center rounded-top">
                                        <i class="ti ti-headphones fs-24 text-dark"></i>
                                    </div>
                                    <div class="bg-white d-flex align-items-center justify-content-between p-3 rounded-bottom">
                                        <h6 class="fw-medium text-truncate mb-0"><a href="#" data-bs-toggle="offcanvas" data-bs-target="#preview">recording.mp3</a></h6>
                                        <div class="dropdown ms-2">
                                            <a href="javascript:void(0);" class="d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                                <i class="ti ti-dots"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a href="javascript:void(0);" data-bs-toggle="offcanvas" data-bs-target="#preview" class="dropdown-item rounded-1"><i class="ti ti-folder-open me-2"></i>Preview</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-copy me-2"></i>Duplicate</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-arrow-left-right me-2"></i>Move</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-user-plus me-2"></i>Invite</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-share-3 me-2"></i>Share Link</a>
                                                </li>
                                                <li>
                                                    <hr class="dropdown-divider my-2">
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-eye me-2"></i>View Details</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-download me-2"></i>Download</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete-modal" class="dropdown-item rounded-1"><i class="ti ti-trash-x me-2"></i>Delete</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div> <!-- end col -->

                        </div>
                        <!-- end row -->

                    </div>
                    <!-- End Recent Files -->

                    <!-- Start table list -->
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        <h6 class="mb-0">Files</h6>
                        <div class="d-flex align-items-center">
                            <div class="dropdown me-2">
                                <a href="javascript:void(0);" class="dropdown-toggle btn bg-white text-dark btn-sm btn-outline-light drop-arrow-none" data-bs-toggle="dropdown">
                                    Sort By : Docs Type<i class="ti ti-chevron-down align-middle ms-1"></i>
                                </a>
                                <ul class="dropdown-menu  dropdown-menu-end">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">Docs</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">Pdf</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">Image</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">Folder</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">Xml</a>
                                    </li>
                                </ul>
                            </div>
                            <a href="javascript:void(0);" class="link-primary fw-medium">View All</a>
                        </div>
                    </div>

                    <div class="table-responsive table-nowrap">

                        <!-- Start Table List-->
                        <table class="table mb-0 border">
                            <thead class="table-light">
                                <tr>
                                    <th>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox" id="select-all">
                                        </div>
                                    </th>
                                    <th class="fs-14 fw-medium">Name</th>
                                    <th class="fs-14 fw-medium">Size</th>
                                    <th class="fs-14 fw-medium">Type</th>
                                    <th class="fs-14 fw-medium">Modified</th>
                                    <th class="fs-14 fw-medium">Share</th>
                                    <th class="fs-14 fw-medium"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="#" class="avatar avatar-md bg-light" data-bs-toggle="offcanvas" data-bs-target="#preview">
                                                <img src="{{URL::asset('build/img/icons/file-01.svg')}}" class="img-fluid w-auto h-auto" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark fw-medium  mb-0"><a href="#" data-bs-toggle="offcanvas" data-bs-target="#preview">Secret</a></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>7.6 MB</td>
                                    <td>Doc</td>
                                    <td>
                                        <p class="text-dark mb-0">Mar 15, 2025</p>
                                        <span>05:00:14 PM</span>
                                    </td>
                                    <td>
                                        <div class="avatar-list-stacked avatar-group-sm">
                                            <span class="avatar avatar-rounded">
                                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" alt="img">
                                            </span>
                                            <span class="avatar avatar-rounded">
                                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-04.jpg')}}" alt="img">
                                            </span>
                                            <span class="avatar avatar-rounded">
                                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-12.jpg')}}" alt="img">
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rating-select me-2">
                                                <a href="javascript:void(0);"><i class="ti ti-star"></i></a>
                                            </div>
                                            <div class="dropdown">
                                                <a href="#" class="d-flex align-items-center justify-content-center" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ti ti-dots fs-14"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-right">
                                                    <li>
                                                        <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#delete-modal">
                                                            <i class="ti ti-trash me-2"></i>Permanent Delete
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item rounded-1" href="#">
                                                            <i class="ti ti-edit-circle me-2"></i>Restore File
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="#" class="avatar avatar-md bg-light" data-bs-toggle="offcanvas" data-bs-target="#preview">
                                                <img src="{{URL::asset('build/img/icons/file-02.svg')}}" class="img-fluid w-auto h-auto" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark fw-medium  mb-0"><a href="#" data-bs-toggle="offcanvas" data-bs-target="#preview">Sophie Headrick</a></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>7.4 MB</td>
                                    <td>PDF</td>
                                    <td>
                                        <p class="text-dark mb-0">Jan 8, 2025</p>
                                        <span>08:20:13 PM</span>
                                    </td>
                                    <td>
                                        <div class="avatar-list-stacked avatar-group-sm">
                                            <span class="avatar avatar-rounded">
                                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-15.jpg')}}" alt="img">
                                            </span>
                                            <span class="avatar avatar-rounded">
                                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-16.jpg')}}" alt="img">
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rating-select me-2">
                                                <a href="javascript:void(0);"><i class="ti ti-star"></i></a>
                                            </div>
                                            <div class="dropdown">
                                                <a href="#" class="d-flex align-items-center justify-content-center" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ti ti-dots fs-14"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-right">
                                                    <li>
                                                        <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#delete-modal">
                                                            <i class="ti ti-trash me-2"></i>Permanent Delete
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item rounded-1" href="#">
                                                            <i class="ti ti-edit-circle me-2"></i>Restore File
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="#" class="avatar avatar-md bg-light" data-bs-toggle="offcanvas" data-bs-target="#preview">
                                                <img src="{{URL::asset('build/img/icons/file-03.svg')}}" class="img-fluid w-auto h-auto" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark fw-medium  mb-0"><a href="#" data-bs-toggle="offcanvas" data-bs-target="#preview">Gallery</a></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>6.1 MB</td>
                                    <td>Image</td>
                                    <td>
                                        <p class="text-dark mb-0">Aug 6, 2025</p>
                                        <span>04:10:12 PM</span>
                                    </td>
                                    <td>
                                        <div class="avatar-list-stacked avatar-group-sm">
                                            <span class="avatar avatar-rounded">
                                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="img">
                                            </span>
                                            <span class="avatar avatar-rounded">
                                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" alt="img">
                                            </span>
                                            <span class="avatar avatar-rounded">
                                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-05.jpg')}}" alt="img">
                                            </span>
                                            <span class="avatar avatar-rounded">
                                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-06.jpg')}}" alt="img">
                                            </span>
                                            <a class="avatar bg-primary avatar-rounded text-fixed-white" href="javascript:void(0);">
                                                +1
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rating-select me-2">
                                                <a href="javascript:void(0);"><i class="ti ti-star"></i></a>
                                            </div>
                                            <div class="dropdown">
                                                <a href="#" class="d-flex align-items-center justify-content-center" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ti ti-dots fs-14"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-right">
                                                    <li>
                                                        <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#delete-modal">
                                                            <i class="ti ti-trash me-2"></i>Permanent Delete
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item rounded-1" href="#">
                                                            <i class="ti ti-edit-circle me-2"></i>Restore File
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="#" class="avatar avatar-md bg-light" data-bs-toggle="offcanvas" data-bs-target="#preview">
                                                <img src="{{URL::asset('build/img/icons/file-04.svg')}}" class="img-fluid w-auto h-auto" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark fw-medium  mb-0"><a href="#" data-bs-toggle="offcanvas" data-bs-target="#preview">Doris Crowley</a></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>5.2 MB</td>
                                    <td>Folder</td>
                                    <td>
                                        <p class="text-dark mb-0">Jan 6, 2025</p>
                                        <span>03:40:14 PM</span>
                                    </td>
                                    <td>
                                        <div class="avatar-list-stacked avatar-group-sm">
                                            <span class="avatar avatar-rounded">
                                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-06.jpg')}}" alt="img">
                                            </span>
                                            <span class="avatar avatar-rounded">
                                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-10.jpg')}}" alt="img">
                                            </span>
                                            <span class="avatar avatar-rounded">
                                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-15.jpg')}}" alt="img">
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rating-select me-2">
                                                <a href="javascript:void(0);"><i class="ti ti-star"></i></a>
                                            </div>
                                            <div class="dropdown">
                                                <a href="#" class="d-flex align-items-center justify-content-center" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ti ti-dots fs-14"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-right">
                                                    <li>
                                                        <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#delete-modal">
                                                            <i class="ti ti-trash me-2"></i>Permanent Delete
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item rounded-1" href="#">
                                                            <i class="ti ti-edit-circle me-2"></i>Restore File
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="#" class="avatar avatar-md bg-light" data-bs-toggle="offcanvas" data-bs-target="#preview">
                                                <img src="{{URL::asset('build/img/icons/file-05.svg')}}" class="img-fluid w-auto h-auto" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark fw-medium  mb-0"><a href="#" data-bs-toggle="offcanvas" data-bs-target="#preview">Cheat_codez</a></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>8 MB</td>
                                    <td>Xml</td>
                                    <td>
                                        <p class="text-dark mb-0">Oct 12, 2025</p>
                                        <span>05:00:14 PM</span>
                                    </td>
                                    <td>
                                        <div class="avatar-list-stacked avatar-group-sm">
                                            <span class="avatar avatar-rounded">
                                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-04.jpg')}}" alt="img">
                                            </span>
                                            <span class="avatar avatar-rounded">
                                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-05.jpg')}}" alt="img">
                                            </span>
                                            <span class="avatar avatar-rounded">
                                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-12.jpg')}}" alt="img">
                                            </span>
                                            <span class="avatar avatar-rounded">
                                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-11.jpg')}}" alt="img">
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rating-select me-2">
                                                <a href="javascript:void(0);"><i class="ti ti-star"></i></a>
                                            </div>
                                            <div class="dropdown">
                                                <a href="#" class="d-flex align-items-center justify-content-center" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ti ti-dots fs-14"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-right">
                                                    <li>
                                                        <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#delete-modal">
                                                            <i class="ti ti-trash me-2"></i>Permanent Delete
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item rounded-1" href="#">
                                                            <i class="ti ti-edit-circle me-2"></i>Restore File
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- End Table List -->

                </div> <!-- end col -->

            </div>
            <!-- end row -->

        </div>
        <!-- End Content -->

        @component('components.footer')
        @endcomponent

    </div>

    <!-- ========================
        End Page Content
    ========================= -->

@endsection    