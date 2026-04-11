<?php $page = 'notes'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->

    <div class="page-wrapper">
        
        <!-- Start Content -->
        <div class="content pb-0">

            <!-- Page Header -->
            <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3">
                <div class="flex-grow-1">
                    <h4 class="fs-18 fw-semibold mb-0">Notes</h4>
                </div>
                <div class="text-end">
                    <ol class="breadcrumb m-0 py-0">
                        <li class="breadcrumb-item"><a href="{{url('index')}}">Home</a></li>                              
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Applications</a></li>                              
                        <li class="breadcrumb-item active" aria-current="page">Notes</li>
                    </ol>
                </div>
            </div>
            <!-- End Page Header -->

            <!-- start row -->
            <div class="row">

                <div class="col-xl-3 col-md-12 theiaStickySidebar">
                    <div class="card">
                        <div class="card-body">
                            <div class="mb-3 pb-3 border-bottom">
                                <h6 class="fs-16 d-flex align-items-center mb-0"><i class="ti ti-file-text me-2"></i>Notes List</h6>
                            </div>

                            <div class="border-bottom pb-3">
                                <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                                    <button class="d-flex text-start align-items-center fw-medium fs-15 nav-link active mb-1" id="v-pills-profile-tab" data-bs-toggle="pill" data-bs-target="#v-pills-profile" type="button" role="tab" aria-controls="v-pills-profile" aria-selected="true"><i class="ti ti-inbox me-2"></i>All Notes</button>
                                    <button class="d-flex text-start align-items-center fw-medium fs-15 nav-link mb-1" id="v-pills-messages-tab" data-bs-toggle="pill" data-bs-target="#v-pills-messages" type="button" role="tab" aria-controls="v-pills-messages" aria-selected="false"><i class="ti ti-star me-2"></i>Important</button>
                                    <button class="d-flex text-start align-items-center fw-medium fs-15 nav-link mb-0" id="v-pills-settings-tab" data-bs-toggle="pill" data-bs-target="#v-pills-settings" type="button" role="tab" aria-controls="v-pills-settings" aria-selected="false"><i class="ti ti-trash me-2"></i>Trash</button>
                                </div>
                            </div>

                            <div class="mt-3">
                                <div class="border-bottom px-2 pb-3 mb-3">
                                    <h6 class="fs-16 mb-2">Tags</h6>
                                    <div class="d-flex flex-column mt-2">
                                        <a href="javascript:void(0);" class="text-info d-flex align-items-center mb-2"><i class="ti ti-square-filled square-rotate fs-10 me-2"></i>Pending</a>
                                        <a href="javascript:void(0);" class="text-danger d-flex align-items-center mb-2"><i class="ti ti-square-filled square-rotate fs-10 me-2"></i>Onhold</a>
                                        <a href="javascript:void(0);" class="text-warning d-flex align-items-center mb-2"><i class="ti ti-square-filled square-rotate fs-10 me-2"></i>Inprogress</a>
                                        <a href="javascript:void(0);" class="text-success d-flex align-items-center"><i class="ti ti-square-filled square-rotate fs-10 me-2"></i>Done</a>
                                    </div>
                                </div>
                                <div class="px-2">
                                    <h6 class="fs-16 mb-2">Priority</h6>
                                    <div class="d-flex flex-column mt-2">
                                        <a href="javascript:void(0);" class="text-warning d-flex align-items-center mb-2"><i class="ti ti-square-filled square-rotate fs-10 me-2"></i>Medium</a>
                                        <a href="javascript:void(0);" class="text-success d-flex align-items-center mb-2"><i class="ti ti-square-filled square-rotate fs-10 me-2"></i>High</a>
                                        <a href="javascript:void(0);" class="text-danger d-flex align-items-center"><i class="ti ti-square-filled square-rotate fs-10 me-2"></i>Low</a>
                                    </div>
                                </div>
                            </div>

                        </div><!-- end card body -->
                    </div><!-- end card -->                        
                </div> <!-- end col -->

                <div class="col-xl-9">
                    <div class="card">
                        <div class="card-body">

                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div>
                                    <select class="form-select">
                                        <option>Bulk Actions</option>
                                        <option>Delete Marked</option>
                                        <option>Unmark All</option>
                                        <option>Mark All</option>
                                    </select>
                                </div>
                                <div class="d-flex align-items-center flex-wrap gap-2">
                                    <div class="dropdown">
                                        <a href="javascript:void(0);" class="dropdown-toggle btn border bg-white rounded text-dark d-inline-flex align-items-center drop-arrow-none" data-bs-toggle="dropdown">
                                            <i class="ti ti-file-export me-1"></i>Export<i class="ti ti-chevron-down align-middle ms-1"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
                                            </li>
                                        </ul>
                                    </div>
                                    <a href="#" class="btn btn-primary btn-md d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#add_note"><i class="ti ti-circle-plus me-1"></i>Add Notes</a>
                                </div>
                            </div>

                        </div><!-- end card body -->
                    </div><!-- end card -->

                    <div class="tab-content" id="v-pills-tabContent2">

                        <!-- Items -->
                        <div class="tab-pane fade active show" id="v-pills-profile" role="tabpanel" aria-labelledby="v-pills-profile-tab">
                            <div>

                                <!-- start row -->
                                <div class="row">

                                    <div class="col-md-12">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-3 mb-3">
                                            <div class="d-flex align-items-center">
                                                <h4 class="mb-0">Important Notes </h4>
                                            </div>
                                            <div>
                                                <a href="javascript:void(0);" class="text-danger fs-15"><i class="ti ti-times me-1"></i> Close </a>
                                            </div>
                                        </div>
                                    </div> <!-- end col -->
                                    
                                </div>
                                <!-- end row -->

                                <!-- start row -->
                                <div class="row">

                                    <div class="col-md-4 d-flex">
                                        <div class="card flex-fill">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <span class="badge badge-outline-warning d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-7 me-1"></i>Medium</span>
                                                    <div>
                                                        <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false"><i class="ti ti-dots-vertical"></i></a>
                                                        <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                            <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i class="ti ti-edit me-1"></i></span>Edit</a>
                                                            <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><span><i class="ti ti-trash me-1"></i></span>Delete</a>
                                                            <a href="javascript:void(0);" class="dropdown-item"><span><i class="ti ti-star me-1"></i></span>Not Important</a>
                                                            <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i class="ti ti-eye me-1"></i></span>View</a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="my-3">
                                                    <h6 class="fs-16 text-truncate mb-1"><a href="javascript:void(0);">Plan a trip to another country</a></h6>
                                                    <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>20 Jan 2024</p>
                                                    <p class="text-truncate line-clamb-2 text-wrap">Space, the final frontier. These are the voyages of the Starship Enterprise.</p>
                                                </div>
                                                <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                    <div class="d-flex align-items-center">
                                                        <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                            <img src="{{URL::asset('./build/img/profiles/avatar-01.jpg')}}" alt="Profile" class="img-fluid rounded-circle">
                                                        </a>
                                                        <span class="text-info d-flex align-items-center"><i class="ti ti-square-filled square-rotate fs-10 me-1"></i>Personal</span>
                                                    </div>
                                                    <div class="d-flex align-items-center">
                                                        <a href="javascript:void(0);" class="me-2">
                                                            <span><i class="ti ti-star text-warning"></i></span>
                                                        </a>
                                                        <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_modal">
                                                            <span><i class="ti ti-trash text-danger"></i></span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div><!-- end card body -->
                                        </div><!-- end card -->
                                    </div> <!-- end col -->

                                    <div class="col-md-4 d-flex">
                                        <div class="card flex-fill">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <span class="badge badge-outline-danger d-inline-flex align-items-center"><i
                                                        class="ti ti-circle-filled fs-7 me-1"></i>Low</span>
                                                    <div>
                                                        <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="ti ti-dots-vertical"></i>
                                                        </a>
                                                        <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                            <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i class="ti ti-edit me-1"></i></span>Edit</a>
                                                            <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><span><i class="ti ti-trash me-1"></i></span>Delete</a>
                                                            <a href="javascript:void(0);" class="dropdown-item"><span><i class="ti ti-star me-1"></i></span>Not Important</a>
                                                            <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i class="ti ti-eye me-1"></i></span>View</a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="my-3">
                                                    <h6 class="fs-16 text-truncate mb-1"><a
                                                        href="javascript:void(0);">Improve touch typing</a>
                                                </h6>
                                                    <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>22 Jan 2024</p>
                                                    <p class="text-truncate line-clamb-2 text-wrap">Well, the way they make shows is, they make one show.</p>
                                                </div>
                                                <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                    <div class="d-flex align-items-center">
                                                        <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                            <img src="{{URL::asset('./build/img/profiles/avatar-02.jpg')}}" alt="Profile" class="img-fluid rounded-circle">
                                                        </a>
                                                        <span class="text-success d-flex align-items-center"><i
                                                            class="ti ti-square-filled square-rotate fs-10 me-1"></i>Work</span>
                                                    </div>
                                                    <div class="d-flex align-items-center">
                                                        <a href="javascript:void(0);" class="me-2">
                                                            <span><i
                                                                class="ti ti-star text-warning"></i></span>
                                                        </a>
                                                        <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_modal">
                                                            <span><i class="ti ti-trash text-danger"></i></span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div><!-- end card body -->
                                        </div><!-- end card -->
                                    </div> <!-- end col -->

                                    <div class="col-md-4 d-flex">
                                        <div class="card flex-fill">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <span class="badge badge-outline-danger d-inline-flex align-items-center"><i
                                                        class="ti ti-circle-filled fs-7 me-1"></i>Low</span>
                                                    <div>
                                                        <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="ti ti-dots-vertical"></i>
                                                        </a>
                                                        <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                            <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i class="ti ti-edit me-1"></i></span>Edit</a>
                                                            <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><span><i class="ti ti-trash me-1"></i></span>Delete</a>
                                                            <a href="javascript:void(0);" class="dropdown-item"><span><i class="ti ti-star me-1"></i></span>Not Important</a>
                                                            <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i class="ti ti-eye me-1"></i></span>View</a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="my-3">
                                                    <h6 class="fs-16 text-truncate mb-1"><a
                                                        href="javascript:void(0);">Learn calligraphy</a>
                                                </h6>
                                                    <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>24 Jan 2024</p>
                                                    <p class="text-truncate line-clamb-2 text-wrap">Calligraphy, the art of beautiful handwriting. It derive from Greek.</p>
                                                </div>
                                                <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                    <div class="d-flex align-items-center">
                                                        <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                            <img src="{{URL::asset('./build/img/profiles/avatar-03.jpg')}}" alt="Profile" class="img-fluid rounded-circle">
                                                        </a>
                                                        <span class="text-info d-flex align-items-center"><i
                                                            class="ti ti-square-filled square-rotate fs-10 me-1"></i>Social</span>
                                                    </div>
                                                    <div class="d-flex align-items-center">
                                                        <a href="javascript:void(0);" class="me-2">
                                                            <span><i
                                                                class="ti ti-star text-warning"></i></span>
                                                        </a>
                                                        <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_modal">
                                                            <span><i class="ti ti-trash text-danger"></i></span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div><!-- end card body -->
                                        </div><!-- end card -->
                                    </div> <!-- end col -->

                                </div>

                            </div>
                            <!-- end row -->

                            <!-- start row -->
                            <div class="row">

                                <div class="col-md-4 d-flex">
                                    <div class="card flex-fill">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="badge badge-outline-success d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-7 me-1"></i>High</span>
                                                <div>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i class="ti ti-edit me-1"></i></span>Edit</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><span><i class="ti ti-trash me-1"></i></span>Delete</a>
                                                        <a href="javascript:void(0);" class="dropdown-item"><span><i class="ti ti-star me-1"></i></span>Not Important</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i class="ti ti-eye me-1"></i></span>View</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="my-3">
                                                <h6 class="fs-16 text-truncate mb-1"><a href="javascript:void(0);">Backup Files EOD</a></h6>
                                                <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>20 Jan 2024</p>
                                                <p class="text-truncate line-clamb-2 text-wrap">Project files should be took backup before end of the day.</p>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                        <img src="{{URL::asset('./build/img/profiles/avatar-05.jpg')}}" alt="Profile" class="img-fluid rounded-circle">
                                                    </a>
                                                    <span class="text-info d-flex align-items-center"><i class="ti ti-square-filled square-rotate fs-10 me-1"></i>Personal</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-2">
                                                        <span><i class="ti ti-star text-warning"></i></span>
                                                    </a>
                                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_modal">
                                                        <span><i class="ti ti-trash text-danger"></i></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div><!-- end card body -->
                                    </div><!-- end card -->
                                </div> <!-- end col -->

                                <div class="col-md-4 d-flex">
                                    <div class="card flex-fill">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="badge badge-outline-danger d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-7 me-1"></i>Low</span>
                                                <div>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i class="ti ti-edit me-1"></i></span>Edit</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><span><i class="ti ti-trash me-1"></i></span>Delete</a>
                                                        <a href="javascript:void(0);" class="dropdown-item"><span><i class="ti ti-star me-1"></i></span>Not Important</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i class="ti ti-eye me-1"></i></span>View</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="my-3">
                                                <h6 class="fs-16 text-truncate mb-1"><a href="javascript:void(0);">Download Server Logs</a></h6>
                                                <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>25 Jan 2024</p>
                                                <p class="text-truncate line-clamb-2 text-wrap">Server log is a text document that contains a record of all activity.</p>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                        <img src="{{URL::asset('./build/img/profiles/avatar-06.jpg')}}" alt="Profile" class="img-fluid rounded-circle">
                                                    </a>
                                                    <span class="text-success d-flex align-items-center"><i class="ti ti-square-filled square-rotate fs-10 me-1"></i>Work</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-2">
                                                        <span><i class="ti ti-star text-warning"></i></span>
                                                    </a>
                                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_modal">
                                                        <span><i class="ti ti-trash text-danger"></i></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div><!-- end card body -->
                                    </div><!-- end card -->
                                </div> <!-- end col -->


                                <div class="col-md-4 d-flex">
                                    <div class="card flex-fill">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="badge badge-outline-warning d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-7 me-1"></i>Medium</span>
                                                <div>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i class="ti ti-edit me-1"></i></span>Edit</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><span><i class="ti ti-trash me-1"></i></span>Delete</a>
                                                        <a href="javascript:void(0);" class="dropdown-item"><span><i class="ti ti-star me-1"></i></span>Not Important</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i class="ti ti-eye me-1"></i></span>View</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="my-3">
                                                <h6 class="fs-16 text-truncate mb-1"><a href="javascript:void(0);">Team meet at Starbucks</a></h6>
                                                <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>26 Jan 2024</p>
                                                <p class="text-truncate line-clamb-2 text-wrap">Meeting all teamets at Starbucks for identifying them all.</p>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                        <img src="{{URL::asset('./build/img/profiles/avatar-07.jpg')}}" alt="Profile" class="img-fluid rounded-circle">
                                                    </a>
                                                    <span class="text-warning d-flex align-items-center"><i class="ti ti-square-filled square-rotate fs-10 me-1"></i>Social</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-2">
                                                        <span><i class="ti ti-star text-warning"></i></span>
                                                    </a>
                                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_modal">
                                                        <span><i class="ti ti-trash text-danger"></i></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div> <!-- end card body -->                                            
                                    </div> <!-- end card -->                                        
                                </div> <!-- end col -->

                                <div class="col-md-4 d-flex">
                                    <div class="card flex-fill">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="badge badge-outline-success d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-7 me-1"></i>High</span>
                                                <div>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i class="ti ti-edit me-1"></i></span>Edit</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><span><i class="ti ti-trash me-1"></i></span>Delete</a>
                                                        <a href="javascript:void(0);" class="dropdown-item"><span><i class="ti ti-star me-1"></i></span>Not Important</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i class="ti ti-eye me-1"></i></span>View</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="my-3">
                                                <h6 class="fs-16 text-truncate mb-1"><a href="javascript:void(0);">Create a compost pile</a></h6>
                                                <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>27 Jan 2024</p>
                                                <p class="text-truncate line-clamb-2 text-wrap">Compost pile refers to fruit and vegetable scraps, used tea  etc..</p>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                        <img src="{{URL::asset('./build/img/profiles/avatar-08.jpg')}}" alt="Profile" class="img-fluid rounded-circle">
                                                    </a>
                                                    <span class="text-warning d-flex align-items-center"><i class="ti ti-square-filled square-rotate fs-10 me-1"></i>Social</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-2">
                                                        <span><i class="ti ti-star text-warning"></i></span>
                                                    </a>
                                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_modal">
                                                        <span><i class="ti ti-trash text-danger"></i></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div><!-- end card body -->
                                    </div><!-- end card -->
                                </div> <!-- end col -->

                                <div class="col-md-4 d-flex">
                                    <div class="card flex-fill">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="badge badge-outline-danger d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-7 me-1"></i>Low</span>
                                                <div>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i class="ti ti-edit me-1"></i></span>Edit</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><span><i class="ti ti-trash me-1"></i></span>Delete</a>
                                                        <a href="javascript:void(0);" class="dropdown-item"><span><i class="ti ti-star me-1"></i></span>Not Important</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i class="ti ti-eye me-1"></i></span>View</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="my-3">
                                                <h6 class="fs-16 text-truncate mb-1"><a href="javascript:void(0);">Take a hike at a local park</a></h6>
                                                <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>28 Jan 2024</p>
                                                <p class="text-truncate line-clamb-2 text-wrap">Hiking involves a long energetic walk in a natural environment.</p>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                        <img src="{{URL::asset('./build/img/profiles/avatar-09.jpg')}}" alt="Profile" class="img-fluid rounded-circle">
                                                    </a>
                                                    <span class="text-info d-flex align-items-center"><i class="ti ti-square-filled square-rotate fs-10 me-1"></i>Personal</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-2">
                                                        <span><i class="ti ti-star text-warning"></i></span>
                                                    </a>
                                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_modal">
                                                        <span><i class="ti ti-trash text-danger"></i></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>  <!-- end card body -->                                           
                                    </div>  <!-- end card -->                                       
                                </div> <!-- end col -->                                    

                                <div class="col-md-4 d-flex">
                                    <div class="card flex-fill">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="badge badge-outline-info d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-7 me-1"></i>medium</span>
                                                <div>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i class="ti ti-edit me-1"></i></span>Edit</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><span><i class="ti ti-trash me-1"></i></span>Delete</a>
                                                        <a href="javascript:void(0);" class="dropdown-item"><span><i class="ti ti-star me-1"></i></span>Not Important</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i class="ti ti-eye me-1"></i></span>View</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="my-3">
                                                <h6 class="fs-16 text-truncate mb-1"><a href="javascript:void(0);">Research a topic interested</a></h6>
                                                <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>28 Jan 2024</p>
                                                <p class="text-truncate line-clamb-2 text-wrap">Research a topic interested by listen actively and attentively.</p>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                        <img src="{{URL::asset('./build/img/profiles/avatar-10.jpg')}}" alt="Profile" class="img-fluid rounded-circle">
                                                    </a>
                                                    <span class="text-success d-flex align-items-center"><i
                                                        class="ti ti-square-filled square-rotate fs-10 me-1"></i>Work</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-2">
                                                        <span><i class="ti ti-star text-warning"></i></span>
                                                    </a>
                                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_modal">
                                                        <span><i class="ti ti-trash text-danger"></i></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div><!-- end card body -->
                                    </div><!-- end card -->
                                </div> <!-- end col -->

                            </div>
                            <!-- end row -->

                        </div>

                        <!-- Items -->
                        <div class="tab-pane fade" id="v-pills-messages" role="tabpanel" aria-labelledby="v-pills-messages-tab">

                            <!-- start row -->
                            <div class="row">

                                <div class="col-md-4 d-flex">
                                    <div class="card flex-fill">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="badge badge-outline-success d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-7 me-1"></i>High</span>
                                                <div>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i class="ti ti-edit me-1"></i></span>Edit</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><span><i class="ti ti-trash me-1"></i></span>Delete</a>
                                                        <a href="javascript:void(0);" class="dropdown-item"><span><i class="ti ti-star me-1"></i></span>Not Important</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i class="ti ti-eye me-1"></i></span>View</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="my-3">
                                                <h6 class="fs-16 text-truncate mb-1"><a href="javascript:void(0);">Backup Files EOD</a></h6>
                                                <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>20 Jan 2024</p>
                                                <p class="text-truncate line-clamb-2 text-wrap">Project files should be took backup before end of the day.</p>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                        <img src="{{URL::asset('./build/img/profiles/avatar-05.jpg')}}" alt="Profile" class="img-fluid rounded-circle">
                                                    </a>
                                                    <span class="text-info d-flex align-items-center"><i class="ti ti-square-filled square-rotate fs-10 me-1"></i>Personal</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-2">
                                                        <span><i class="ti ti-star text-warning"></i></span>
                                                    </a>
                                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_modal">
                                                        <span><i class="ti ti-trash text-danger"></i></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div><!-- end card body -->
                                    </div><!-- end card -->
                                </div> <!-- end col -->

                                <div class="col-md-4 d-flex">
                                    <div class="card flex-fill">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="badge badge-outline-danger d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-7 me-1"></i>Low</span>
                                                <div>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i class="ti ti-edit me-1"></i></span>Edit</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><span><i class="ti ti-trash me-1"></i></span>Delete</a>
                                                        <a href="javascript:void(0);" class="dropdown-item"><span><i class="ti ti-star me-1"></i></span>Not Important</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i class="ti ti-eye me-1"></i></span>View</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="my-3">
                                                <h6 class="fs-16 text-truncate mb-1"><a href="javascript:void(0);">Download Server Logs</a></h6>
                                                <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>25 Jan 2024</p>
                                                <p class="text-truncate line-clamb-2 text-wrap">Server log is a text document that contains a record of all activity.</p>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                        <img src="{{URL::asset('./build/img/profiles/avatar-06.jpg')}}" alt="Profile" class="img-fluid rounded-circle">
                                                    </a>
                                                    <span class="text-success d-flex align-items-center"><i class="ti ti-square-filled square-rotate fs-10 me-1"></i>Work</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-2">
                                                        <span><i class="ti ti-star text-warning"></i></span>
                                                    </a>
                                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_modal">
                                                        <span><i class="ti ti-trash text-danger"></i></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div><!-- end card body -->
                                    </div><!-- end card -->
                                </div> <!-- end col -->

                                <div class="col-md-4 d-flex">
                                    <div class="card flex-fill">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="badge badge-outline-warning d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-7 me-1"></i>Medium</span>
                                                <div>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i class="ti ti-edit me-1"></i></span>Edit</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><span><i class="ti ti-trash me-1"></i></span>Delete</a>
                                                        <a href="javascript:void(0);" class="dropdown-item"><span><i class="ti ti-star me-1"></i></span>Not Important</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i class="ti ti-eye me-1"></i></span>View</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="my-3">
                                                <h6 class="fs-16 text-truncate mb-1"><a href="javascript:void(0);">Team meet at Starbucks</a></h6>
                                                <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>26 Jan 2024</p>
                                                <p class="text-truncate line-clamb-2 text-wrap">Meeting all teamets at Starbucks for identifying them all.</p>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                        <img src="{{URL::asset('./build/img/profiles/avatar-07.jpg')}}" alt="Profile" class="img-fluid rounded-circle">
                                                    </a>
                                                    <span class="text-warning d-flex align-items-center"><i
                                                        class="ti ti-square-filled square-rotate fs-10 me-1"></i>Social</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-2">
                                                        <span><i class="ti ti-star text-warning"></i></span>
                                                    </a>
                                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_modal">
                                                        <span><i class="ti ti-trash text-danger"></i></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div><!-- end card body -->
                                    </div><!-- end card -->
                                </div> <!-- end col -->
                                
                                <div class="col-md-4 d-flex">
                                    <div class="card flex-fill">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="badge badge-outline-success d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-7 me-1"></i>High</span>
                                                <div>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i class="ti ti-edit me-1"></i></span>Edit</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><span><i class="ti ti-trash me-1"></i></span>Delete</a>
                                                        <a href="javascript:void(0);" class="dropdown-item"><span><i class="ti ti-star me-1"></i></span>Not Important</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i class="ti ti-eye me-1"></i></span>View</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="my-3">
                                                <h6 class="fs-16 text-truncate mb-1"><a href="javascript:void(0);">Create a compost pile</a></h6>
                                                <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>27 Jan 2024</p>
                                                <p class="text-truncate line-clamb-2 text-wrap">Compost pile refers to fruit and vegetable scraps, used tea etc..</p>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                        <img src="{{URL::asset('./build/img/profiles/avatar-08.jpg')}}" alt="Profile" class="img-fluid rounded-circle">
                                                    </a>
                                                    <span class="text-warning d-flex align-items-center"><i
                                                        class="ti ti-square-filled square-rotate fs-10 me-1"></i>Social</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-2">
                                                        <span><i class="ti ti-star text-warning"></i></span>
                                                    </a>
                                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_modal">
                                                        <span><i class="ti ti-trash text-danger"></i></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div><!-- end card body -->
                                    </div><!-- end card -->
                                </div> <!-- end col -->
                                
                                <div class="col-md-4 d-flex">
                                    <div class="card flex-fill">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="badge badge-outline-danger d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-7 me-1"></i>Low</span>
                                                <div>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i class="ti ti-edit me-1"></i></span>Edit</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><span><i class="ti ti-trash me-1"></i></span>Delete</a>
                                                        <a href="javascript:void(0);" class="dropdown-item"><span><i class="ti ti-star me-1"></i></span>Not Important</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i class="ti ti-eye me-1"></i></span>View</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="my-3">
                                                <h6 class="fs-16 text-truncate mb-1"><a href="javascript:void(0);">Take a hike at a local park</a></h6>
                                                <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>28 Jan 2024</p>
                                                <p class="text-truncate line-clamb-2 text-wrap">Hiking involves a long energetic walk in a natural environment.</p>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                        <img src="{{URL::asset('./build/img/profiles/avatar-09.jpg')}}" alt="Profile" class="img-fluid rounded-circle">
                                                    </a>
                                                    <span class="text-info d-flex align-items-center"><i
                                                        class="ti ti-square-filled square-rotate fs-10 me-1"></i>Personal</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-2">
                                                        <span><i class="ti ti-star text-warning"></i></span>
                                                    </a>
                                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_modal">
                                                        <span><i class="ti ti-trash text-danger"></i></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div><!-- end card body -->
                                    </div><!-- end card -->
                                </div> <!-- end col -->
                                
                                <div class="col-md-4 d-flex">
                                    <div class="card flex-fill">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="badge badge-outline-info d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-7 me-1"></i>medium</span>
                                                <div>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i class="ti ti-edit me-1"></i></span>Edit</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><span><i class="ti ti-trash me-1"></i></span>Delete</a>
                                                        <a href="javascript:void(0);" class="dropdown-item"><span><i class="ti ti-star me-1"></i></span>Not Important</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i class="ti ti-eye me-1"></i></span>View</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="my-3">
                                                <h6 class="fs-16 text-truncate mb-1"><a href="javascript:void(0);">Research a topic interested</a></h6>
                                                <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>28 Jan 2024</p>
                                                <p class="text-truncate line-clamb-2 text-wrap">Research a topic interested by listen actively and attentively.</p>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                        <img src="{{URL::asset('./build/img/profiles/avatar-10.jpg')}}" alt="Profile" class="img-fluid rounded-circle">
                                                    </a>
                                                    <span class="text-success d-flex align-items-center"><i class="ti ti-square-filled square-rotate fs-10 me-1"></i>Work</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-2">
                                                        <span><i class="ti ti-star text-warning"></i></span>
                                                    </a>
                                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_modal">
                                                        <span><i class="ti ti-trash text-danger"></i></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div><!-- end card body -->
                                    </div><!-- end card -->
                                </div> <!-- end col -->

                            </div>
                            <!-- end row -->

                        </div>

                        <!-- Items -->
                        <div class="tab-pane fade" id="v-pills-settings" role="tabpanel" aria-labelledby="v-pills-settings-tab">
                            <div class="row">
                                <div class="col-12 d-flex align-items-center justify-content-end">
                                    <a href="#" class="btn btn-danger mb-3">
                                        <span> <i class="ti ti-trash f-20 me-2"></i> </span> Restore all
                                    </a>
                                </div>
                            </div>

                            <!-- start row -->
                            <div class="row">
                                
                                <div class="col-md-4 d-flex">
                                    <div class="card flex-fill">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="badge badge-outline-success d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-7 me-1"></i>High</span>
                                                <div>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i class="ti ti-edit me-1"></i></span>Edit</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><span><i class="ti ti-trash me-1"></i></span>Delete</a>
                                                        <a href="javascript:void(0);" class="dropdown-item"><span><i class="ti ti-star me-1"></i></span>Not Important</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i class="ti ti-eye me-1"></i></span>View</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="my-3">
                                                <h6 class="fs-16 text-truncate mb-1"><a href="javascript:void(0);">Create a compost pile</a></h6>
                                                <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>27 Jan 2024</p>
                                                <p class="text-truncate line-clamb-2 text-wrap">Compost pile refers to fruit and vegetable scraps, used tea etc..</p>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                        <img src="{{URL::asset('./build/img/profiles/avatar-08.jpg')}}" alt="Profile" class="img-fluid rounded-circle">
                                                    </a>
                                                    <span class="text-warning d-flex align-items-center"><i class="ti ti-square-filled square-rotate fs-10 me-1"></i>Social</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-2">
                                                        <span><i class="ti ti-star text-warning"></i></span>
                                                    </a>
                                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_modal">
                                                        <span><i class="ti ti-trash text-danger"></i></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div><!-- end card body -->
                                    </div><!-- end card -->
                                </div> <!-- end col -->

                                <div class="col-md-4 d-flex">
                                    <div class="card flex-fill">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="badge badge-outline-danger d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-7 me-1"></i>Low</span>
                                                <div>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i class="ti ti-edit me-1"></i></span>Edit</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><span><i class="ti ti-trash me-1"></i></span>Delete</a>
                                                        <a href="javascript:void(0);" class="dropdown-item"><span><i class="ti ti-star me-1"></i></span>Not Important</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i class="ti ti-eye me-1"></i></span>View</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="my-3">
                                                <h6 class="fs-16 text-truncate mb-1"><a href="javascript:void(0);">Take a hike at a local park</a></h6>
                                                <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>28 Jan 2024</p>
                                                <p class="text-truncate line-clamb-2 text-wrap">Hiking involves a long energetic walk in a natural environment.</p>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                        <img src="{{URL::asset('./build/img/profiles/avatar-09.jpg')}}" alt="Profile" class="img-fluid rounded-circle">
                                                    </a>
                                                    <span class="text-info d-flex align-items-center"><i class="ti ti-square-filled square-rotate fs-10 me-1"></i>Personal</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-2">
                                                        <span><i class="ti ti-star text-warning"></i></span>
                                                    </a>
                                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_modal">
                                                        <span><i class="ti ti-trash text-danger"></i></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div><!-- end card body -->
                                    </div><!-- end card -->
                                </div> <!-- end col -->

                                <div class="col-md-4 d-flex">
                                    <div class="card flex-fill">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="badge badge-outline-info d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-7 me-1"></i>medium</span>
                                                <div>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i class="ti ti-edit me-1"></i></span>Edit</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><span><i class="ti ti-trash me-1"></i></span>Delete</a>
                                                        <a href="javascript:void(0);" class="dropdown-item"><span><i class="ti ti-star me-1"></i></span>Not Important</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i class="ti ti-eye me-1"></i></span>View</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="my-3">
                                                <h6 class="fs-16 text-truncate mb-1"><a href="javascript:void(0);">Research a topic interested</a></h6>
                                                <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>28 Jan 2024</p>
                                                <p class="text-truncate line-clamb-2 text-wrap">Research a topic interested by listen actively and attentively.</p>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                        <img src="{{URL::asset('./build/img/profiles/avatar-10.jpg')}}" alt="Profile" class="img-fluid rounded-circle">
                                                    </a>
                                                    <span class="text-success d-flex align-items-center"><i class="ti ti-square-filled square-rotate fs-10 me-1"></i>Work</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-2">
                                                        <span><i class="ti ti-star text-warning"></i></span>
                                                    </a>
                                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_modal">
                                                        <span><i class="ti ti-trash text-danger"></i></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div><!-- end card body -->
                                    </div><!-- end card -->
                                </div> <!-- end col -->

                            </div>
                            <!-- end row -->

                        </div><!-- end card body -->
                    </div><!-- end card -->
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