<?php $page = 'organization-settings'; ?>
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
                                <h5 class="fw-bold">Organization</h5>
                            </div>
                            <div class="card-body px-0 mx-3">
                                <form action="{{url('organization-settings')}}">

                                    <!-- start row -->
                                    <div class="row border-bottom mb-3">
                                        
                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                                <div class="col-lg-4">
                                                    <label class="form-label mb-0">Company Name<span class="text-danger ms-1">*</span></label>
                                                </div><!-- end col -->
                                                <div class="col-lg-8">
                                                    <input type="text" class="form-control">
                                                </div><!-- end col -->
                                            </div>
                                            <!-- end row -->

                                        </div><!-- end col -->
                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                            <div class="col-lg-4">
                                                <label class="form-label mb-0">Email Address<span class="text-danger ms-1">*</span></label>
                                            </div><!-- end col -->
                                            <div class="col-lg-8">
                                                <input type="text" class="form-control">
                                            </div><!-- end col -->
                                        </div>
                                        <!-- end row -->

                                        </div><!-- end col -->
                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                            <div class="col-lg-4">
                                                <label class="form-label mb-0">Phone Number<span class="text-danger ms-1">*</span></label>
                                            </div><!-- end col -->
                                            <div class="col-lg-8">
                                                <input type="text" class="form-control">
                                            </div><!-- end col -->
                                        </div>
                                        <!-- end row -->

                                        </div><!-- end col -->
                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                            <div class="col-lg-4">
                                                <label class="form-label mb-0">Fax</label>
                                            </div><!-- end col -->
                                            <div class="col-lg-8">
                                                <input type="text" class="form-control">
                                            </div><!-- end col -->
                                        </div>
                                        <!-- end row -->

                                        </div><!-- end col -->
                                    </div>
                                    <!-- end row -->

                                    <!-- start row -->
                                    <div class="row mb-3 border-bottom">

                                        <div class="mb-3">
                                            <h5 class="fw-bold mb-0">Company Images</h5>
                                        </div>

                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="me-3">
                                                    <div class="profile-container">
                                                        <img src="{{URL::asset('build/img/logo.svg')}}" alt="Profile" class="img-fluid object-fit-contain p-1">
                                                        <div class="overlay-btn">
                                                        <a href="javascript:void(0);" class="text-white" id="uploadTrigger">
                                                            <i class="ti ti-photo fs-10"></i>
                                                        </a>
                                                        </div>
                                                        <input type="file" id="profileUpload" style="display: none;">
                                                    </div>
                                                </div><!-- end col -->
                                                <div class="">
                                                    <p class="fw-medium text-dark mb-1">Logo</p>
                                                    <span>Recommended size is 250 px*100 px</span>
                                                </div><!-- end col -->
                                            </div>
                                            <!-- end row -->
                                        
                                        </div><!-- end col -->

                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="me-3">
                                                    <div class="profile-container">
                                                        <img src="{{URL::asset('build/img/logo-white.svg')}}" alt="Profile" class="img-fluid object-fit-contain bg-dark p-1">
                                                        <div class="overlay-btn">
                                                        <a href="javascript:void(0);" class="text-white" id="uploadTrigger1">
                                                            <i class="ti ti-photo fs-10"></i>
                                                        </a>
                                                        </div>
                                                        <input type="file" id="profileUpload1" style="display: none;">
                                                    </div>
                                                </div><!-- end col -->
                                                <div class="">
                                                    <p class="fw-medium text-dark mb-1">Dark Logo</p>
                                                    <span>Recommended size is 250 px*100 px</span>
                                                </div><!-- end col -->
                                            </div>
                                            <!-- end row -->
                                        
                                        </div><!-- end col -->

                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="me-3">
                                                    <div class="profile-container">
                                                        <img src="{{URL::asset('build/img/logo-small.svg')}}" alt="Profile" class="img-fluid object-fit-none">
                                                        <div class="overlay-btn">
                                                        <a href="javascript:void(0);" class="text-white" id="uploadTrigger2">
                                                            <i class="ti ti-photo fs-10"></i>
                                                        </a>
                                                        </div>
                                                        <input type="file" id="profileUpload2" style="display: none;">
                                                    </div>
                                                </div><!-- end col -->
                                                <div class="">
                                                    <p class="fw-medium text-dark mb-1">Mini Logo</p>
                                                    <span>Recommended size is 250 px*100 px</span>
                                                </div><!-- end col -->
                                            </div>
                                            <!-- end row -->
                                        
                                        </div><!-- end col -->

                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="me-3">
                                                    <div class="profile-container">
                                                        <img src="{{URL::asset('build/img/logo-small.svg')}}" alt="Profile" class="img-fluid object-fit-none bg-dark">
                                                        <div class="overlay-btn">
                                                        <a href="javascript:void(0);" class="text-white" id="uploadTrigger3">
                                                            <i class="ti ti-photo fs-10"></i>
                                                        </a>
                                                        </div>
                                                        <input type="file" id="profileUpload3" style="display: none;">
                                                    </div>
                                                </div><!-- end col -->
                                                <div class="">
                                                    <p class="fw-medium text-dark mb-1">Dark Mini Logo</p>
                                                    <span>Recommended size is 250 px*100 px</span>
                                                </div><!-- end col -->
                                            </div>
                                            <!-- end row -->
                                        
                                        </div><!-- end col -->

                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="me-3">
                                                    <div class="profile-container">
                                                        <img src="{{URL::asset('build/img/logo-small.svg')}}" alt="Profile" class="img-fluid object-fit-none">
                                                        <div class="overlay-btn">
                                                        <a href="javascript:void(0);" class="text-white" id="uploadTrigger4">
                                                            <i class="ti ti-photo fs-10"></i>
                                                        </a>
                                                        </div>
                                                        <input type="file" id="profileUpload4" style="display: none;">
                                                    </div>
                                                </div><!-- end col -->
                                                <div class="">
                                                    <p class="fw-medium text-dark mb-1">Favicon</p>
                                                    <span>Recommended size is 250 px*100 px</span>
                                                </div><!-- end col -->
                                            </div>
                                            <!-- end row -->
                                        
                                        </div><!-- end col -->

                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="me-3">
                                                    <div class="profile-container">
                                                        <img src="{{URL::asset('build/img/logo-small.svg')}}" alt="Profile" class="img-fluid object-fit-none">
                                                        <div class="overlay-btn">
                                                        <a href="javascript:void(0);" class="text-white" id="uploadTrigger5">
                                                            <i class="ti ti-photo fs-10"></i>
                                                        </a>
                                                        </div>
                                                        <input type="file" id="profileUpload5" style="display: none;">
                                                    </div>
                                                </div><!-- end col -->
                                                <div class="">
                                                    <p class="fw-medium text-dark mb-1">Apple Icon</p>
                                                    <span>Recommended size is 250 px*100 px</span>
                                                </div><!-- end col -->
                                            </div>
                                            <!-- end row -->
                                        
                                        </div><!-- end col -->
                                    </div>
                                    <!-- end row -->

                                    <!-- start row -->
                                    <div class="row border-bottom mb-3 pb-3">
                                        <div class="mb-3">
                                            <h5 class="fw-bold mb-0">Contact Information</h5>
                                        </div>
                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                                <div class="col-lg-4">
                                                    <label class="form-label mb-0">Address Line 1</label>
                                                </div><!-- end col -->
                                                <div class="col-lg-8">
                                                    <input type="text" class="form-control">
                                                </div><!-- end col -->
                                            </div>
                                            <!-- end row -->

                                        </div><!-- end col -->
                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                            <div class="col-lg-4">
                                                <label class="form-label mb-0">Address Line 2</label>
                                            </div><!-- end col -->
                                            <div class="col-lg-8">
                                                <input type="text" class="form-control">
                                            </div><!-- end col -->
                                        </div>
                                        <!-- end row -->

                                        </div><!-- end col -->
                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                            <div class="col-lg-4">
                                                <label class="form-label mb-0">Country</label>
                                            </div><!-- end col -->
                                            <div class="col-lg-8">
                                                    <select class="select">
                                                        <option>Select</option>
                                                        <option>USA</option>
                                                        <option>Canada</option>
                                                        <option>UK</option>
                                                        <option>Germany</option>
                                                    </select>
                                            </div><!-- end col -->
                                        </div>
                                        <!-- end row -->

                                        </div><!-- end col -->
                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                            <div class="col-lg-4">
                                                <label class="form-label mb-0">State</label>
                                            </div><!-- end col -->
                                            <div class="col-lg-8">
                                                <select class="select">
                                                        <option>Select</option>
                                                        <option>California</option>
                                                        <option>Ontario</option>
                                                        <option>England</option>
                                                        <option>Bavaria</option>
                                                </select>
                                            </div><!-- end col -->
                                        </div>
                                        <!-- end row -->

                                        </div><!-- end col -->
                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                            <div class="col-lg-4">
                                                <label class="form-label mb-0">City</label>
                                            </div><!-- end col -->
                                            <div class="col-lg-8">
                                                <select class="select">
                                                        <option>Select</option>
                                                        <option>Los Angeles</option>
                                                        <option>Toronto</option>
                                                        <option>London</option>
                                                        <option>Munich</option>
                                                </select>
                                            </div><!-- end col -->
                                        </div>
                                        <!-- end row -->

                                        </div><!-- end col -->
                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                            <div class="col-lg-4">
                                                <label class="form-label mb-0">Pincode</label>
                                            </div><!-- end col -->
                                            <div class="col-lg-8">
                                                <input type="text" class="form-control">
                                            </div><!-- end col -->
                                        </div>
                                        <!-- end row -->

                                        </div><!-- end col -->
                                    </div>
                                    <!-- end row -->

                                    <div class="d-flex align-items-center justify-content-end">
                                        <a href="javascript:void(0);" class="btn btn-light me-3">Cancel</a>
                                        <a href="javascript:void(0);" class="btn btn-primary">Save Changes</a>
                                    </div>

                                </form>
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