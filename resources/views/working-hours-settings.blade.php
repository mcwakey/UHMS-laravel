<?php $page = 'working-hours-settings'; ?>
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

                        <div class="card flex-fill mb-0 border-0 bg-light-500 shadow-none">
                            <div class="card-header border-bottom px-0 mx-3">
                                <div class="d-flex align-items-center">
                                    <h5 class="fw-bold">Working Hours</h5>
                                </div>
                            </div>
                            <div class="card-body px-0 mx-3 break-hours-section" id="break-hours-section">
                                <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
                                    <h6 class="fs-14 fw-medium">Expected Productive Time<span class="text-danger ms-1">*</span></h6>
                                    <div class="d-flex align-items-center">
                                        <div class="input-icon-end position-relative me-2">
                                            <input type="text" class="form-control timepicker">
                                            <span class="input-icon-addon">
                                                <i class="ti ti-clock text-gray-7"></i>
                                            </span>
                                        </div>
                                        <span class="flex-shrink-0 align-items-center">Hours / Day</span>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-3">Working Days</h5>

                                    <!-- start row -->
                                        <div class="row align-items-center row-gap-2 pb-3 mb-3 border-bottom">
                                        <div class="col-lg-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input me-1" type="checkbox" checked="" id="check1">
                                                <label for="check1" class="fw-normal">Monday</label>
                                            </div>
                                        </div><!-- end col -->
                                        <div class="col-lg-6">
                                            <div class="d-flex align-items-center">
                                                <div class="input-icon-end position-relative me-2">
                                                    <input type="text" value="9:30 AM" class="form-control timepicker">
                                                    <span class="input-icon-addon">
                                                        <i class="ti ti-clock text-gray-7"></i>
                                                    </span>
                                                </div>
                                                <span class="text-dark me-2">to</span>
                                                <div class="input-icon-end position-relative">
                                                    <input type="text" value="9:30 AM" class="form-control timepicker">
                                                    <span class="input-icon-addon">
                                                        <i class="ti ti-clock text-gray-7"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div><!-- end col -->
                                        <div class="col-lg-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input me-1" type="checkbox" checked="" id="check2">
                                                <label for="check2" class="fw-normal">Tuesday</label>
                                            </div>
                                        </div><!-- end col -->
                                        <div class="col-lg-6">
                                            <div class="d-flex align-items-center">
                                                <div class="input-icon-end position-relative me-2">
                                                    <input type="text" value="9:30 AM" class="form-control timepicker">
                                                    <span class="input-icon-addon">
                                                        <i class="ti ti-clock text-gray-7"></i>
                                                    </span>
                                                </div>
                                                <span class="text-dark me-2">to</span>
                                                <div class="input-icon-end position-relative">
                                                    <input type="text" value="9:30 AM" class="form-control timepicker">
                                                    <span class="input-icon-addon">
                                                        <i class="ti ti-clock text-gray-7"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div><!-- end col -->
                                        <div class="col-lg-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input me-1" type="checkbox" checked="" id="check3">
                                                <label for="check3" class="fw-normal">Wednesday</label>
                                            </div>
                                        </div><!-- end col -->
                                        <div class="col-lg-6">
                                            <div class="d-flex align-items-center">
                                                <div class="input-icon-end position-relative me-2">
                                                    <input type="text" value="9:30 AM" class="form-control timepicker">
                                                    <span class="input-icon-addon">
                                                        <i class="ti ti-clock text-gray-7"></i>
                                                    </span>
                                                </div>
                                                <span class="text-dark me-2">to</span>
                                                <div class="input-icon-end position-relative">
                                                    <input type="text" value="9:30 AM" class="form-control timepicker">
                                                    <span class="input-icon-addon">
                                                        <i class="ti ti-clock text-gray-7"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div><!-- end col -->
                                        <div class="col-lg-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input me-1" type="checkbox" checked="" id="check4">
                                                <label for="check4" class="fw-normal">Thursday</label>
                                            </div>
                                        </div><!-- end col -->
                                        <div class="col-lg-6">
                                            <div class="d-flex align-items-center">
                                                <div class="input-icon-end position-relative me-2">
                                                    <input type="text" value="9:30 AM" class="form-control timepicker">
                                                    <span class="input-icon-addon">
                                                        <i class="ti ti-clock text-gray-7"></i>
                                                    </span>
                                                </div>
                                                <span class="text-dark me-2">to</span>
                                                <div class="input-icon-end position-relative">
                                                    <input type="text" value="9:30 AM" class="form-control timepicker">
                                                    <span class="input-icon-addon">
                                                        <i class="ti ti-clock text-gray-7"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div><!-- end col -->
                                        <div class="col-lg-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input me-1" type="checkbox" checked="" id="check5">
                                                <label for="check5" class="fw-normal">Friday</label>
                                            </div>
                                        </div><!-- end col -->
                                        <div class="col-lg-6">
                                            <div class="d-flex align-items-center">
                                                <div class="input-icon-end position-relative me-2">
                                                    <input type="text" value="9:30 AM" class="form-control timepicker">
                                                    <span class="input-icon-addon">
                                                        <i class="ti ti-clock text-gray-7"></i>
                                                    </span>
                                                </div>
                                                <span class="text-dark me-2">to</span>
                                                <div class="input-icon-end position-relative">
                                                    <input type="text" value="9:30 AM" class="form-control timepicker">
                                                    <span class="input-icon-addon">
                                                        <i class="ti ti-clock text-gray-7"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div><!-- end col -->
                                        <div class="col-lg-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input me-1" type="checkbox" id="check6">
                                                <label for="check6" class="fw-normal">Saturday</label>
                                            </div>
                                        </div><!-- end col -->
                                        <div class="col-lg-6">
                                            <div class="d-flex align-items-center">
                                                <div class="input-icon-end position-relative me-2">
                                                    <input type="text" disabled class="form-control timepicker">
                                                    <span class="input-icon-addon">
                                                        <i class="ti ti-clock text-gray-7"></i>
                                                    </span>
                                                </div>
                                                <span class="text-dark me-2">to</span>
                                                <div class="input-icon-end position-relative">
                                                    <input type="text" disabled class="form-control timepicker">
                                                    <span class="input-icon-addon">
                                                        <i class="ti ti-clock text-gray-7"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div><!-- end col -->
                                        <div class="col-lg-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input me-1" type="checkbox" id="check7">
                                                <label for="check7" class="fw-normal">Sunday</label>
                                            </div>
                                        </div><!-- end col -->
                                        <div class="col-lg-6">
                                            <div class="d-flex align-items-center">
                                                <div class="input-icon-end position-relative me-2">
                                                    <input type="text" disabled class="form-control timepicker">
                                                    <span class="input-icon-addon">
                                                        <i class="ti ti-clock text-gray-7"></i>
                                                    </span>
                                                </div>
                                                <span class="text-dark me-2">to</span>
                                                <div class="input-icon-end position-relative">
                                                    <input type="text" disabled class="form-control timepicker">
                                                    <span class="input-icon-addon">
                                                        <i class="ti ti-clock text-gray-7"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div><!-- end col -->
                                        </div>
                                        <!-- end row -->

                                        <div class=" pb-3 mb-3 border-bottom">
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <h5 class="fw-bold">Break Hours</h5>
                                            <a href="javascript:void(0);" class="add-break"><i class="ti ti-plus me-1"></i>Add New</a>
                                        </div>

                                        <!-- start row -->
                                            <div class="row align-items-center row-gap-2 mb-3 break1">
                                            <div class="col-lg-6">
                                                <p class="text-dark fw-medium mb-0">Morning Break</p>
                                            </div><!-- end col -->
                                            <div class="col-lg-6">
                                                <div class="d-flex align-items-center">
                                                    <div class="input-icon-end position-relative me-2">
                                                        <input type="text" value="9:30 AM" class="form-control timepicker">
                                                        <span class="input-icon-addon">
                                                            <i class="ti ti-clock text-gray-7"></i>
                                                        </span>
                                                    </div>
                                                    <span class="text-dark me-2">to</span>
                                                    <div class="input-icon-end position-relative me-2">
                                                        <input type="text" value="9:30 AM" class="form-control timepicker">
                                                        <span class="input-icon-addon">
                                                            <i class="ti ti-clock text-gray-7"></i>
                                                        </span>
                                                    </div>
                                                    <a href="javascript:void(0);" class="btn btn-white p-2 border rounded-2 me-2"><i class="ti ti-edit"></i></a>
                                                    <a href="javascript:void(0);" class="btn btn-white p-2 border rounded-2"><i class="ti ti-trash"></i></a>
                                                </div>
                                            </div><!-- end col -->
                                            </div>
                                            <!-- end row -->
                                        </div>

                                        <div class=" pb-3 mb-3 border-bottom">
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <h5 class="fw-bold">Lunch Hours</h5>
                                        </div>

                                        <!-- start row -->
                                            <div class="row align-items-center row-gap-2">
                                            <div class="col-lg-6">
                                                <p class="text-dark fw-medium mb-0">Lunch Break</p>
                                            </div><!-- end col -->
                                            <div class="col-lg-6">
                                                <div class="d-flex align-items-center">
                                                    <select class="select">
                                                        <option>Select</option>
                                                        <option>15 Mins</option>
                                                        <option>30 Mins</option>
                                                        <option selected>45 Mins</option>
                                                        <option>60 Mins</option>
                                                    </select>
                                                    <span class="text-dark flex-shrink-0 mx-2">Lunch at</span>
                                                    <select class="select">
                                                        <option>Select</option>
                                                        <option>11 AM</option>
                                                        <option selected>01:00 PM</option>
                                                        <option>05:00 PM</option>
                                                    </select>
                                                </div>
                                            </div><!-- end col -->
                                            </div>
                                            <!-- end row -->

                                        </div>
                                        
                                        <div class="d-flex align-items-center justify-content-end">
                                        <a href="javascript:void(0);" class="btn btn-light me-2">Cancel</a>
                                        <a href="javascript:void(0);" class="btn btn-primary">Save Changes</a>
                                        </div>

                                </div>
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