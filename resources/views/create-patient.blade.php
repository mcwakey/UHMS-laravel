<?php $page = 'create-patient'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->

    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content">

            <!-- row start -->
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <!-- page header start -->
                    <div class="mb-4">
                        <h6 class="fw-bold mb-0 d-flex align-items-center"> <a href="{{url('patients')}}" class="text-dark"> <i class="ti ti-chevron-left me-1"></i>Patients</a></h6>
                    </div>
                    <!-- page header end -->

                    <!-- card start -->
                    <div class="card">
                        <div class="card-body pb-0">
                            <div class="form">
                                <h6 class="fw-bold mb-3">Patient Information</h6>
                                <div class="row">

                                    <div class="col-lg-12">
                                        <div class="mb-3 d-flex align-items-center">
                                            <label class="form-label mb-0">Profile Image</label>
                                            <div class="drag-upload-btn avatar avatar-xxl rounded-circle bg-light text-muted position-relative overflow-hidden z-1 mb-2 ms-4 p-0">
                                                <i class="ti ti-user-plus fs-16"></i>
                                                <input type="file" class="form-control image-sign" multiple="">
                                                <div class="position-absolute bottom-0 end-0 star-0 w-100 h-25 bg-dark d-flex align-items-center justify-content-center z-n1">
                                                    <a href="javascript:void(0);" class="text-white d-flex align-items-center justify-content-center">
                                                        <i class="ti ti-photo fs-14"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label mb-1 fw-medium">First Name<span class="text-danger ms-1">*</span></label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label mb-1 fw-medium">Last Name<span class="text-danger ms-1">*</span></label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label mb-1 fw-medium">Phone Number<span class="text-danger ms-1">*</span></label>
                                            <input type="tel" class="form-control" id="phone" name="phone">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label mb-1 fw-medium">Email Address<span class="text-danger ms-1">*</span></label>
                                            <input type="email" class="form-control">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label mb-1 fw-medium">Primary Doctor<span class="text-danger ms-1">*</span></label>
                                            <select class="select">
                                                <option value="m-1">Select</option>
                                                <option value="m-2">Dr. Mick Thompson</option>
                                                <option value="m-3">Dr. Sarah Johnson</option>
                                                <option value="m-4">Dr. Emily Carter</option>
                                                <option value="m-5">Dr. David Lee</option>
                                                <option value="m-6">Dr. Anna Kim</option>
                                                <option value="m-7">Dr. John Smith</option>
                                                <option value="m-8">Dr. Lisa White</option>
                                                <option value="m-9">Dr. Patrica Brown</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label mb-1 fw-medium">DOB<span class="text-danger ms-1">*</span></label>
                                            <div class="input-icon-end position-relative">  
                                                <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                                <span class="input-icon-addon">
                                                    <i class="ti ti-calendar"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label mb-1 fw-medium">Gender<span class="text-danger ms-1">*</span></label>
                                            <select class="select">
                                                <option value="m-1">Select</option>
                                                <option value="m-2">Male</option>
                                                <option value="m-3">Female</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label mb-1 fw-medium">Blood Group<span class="text-danger ms-1">*</span></label>
                                            <select class="select">
                                                <option value="m-1">Select</option>
                                                <option value="m-2">O+</option>
                                                <option value="m-3">O-</option>
                                                <option value="m-3">A+</option>
                                                <option value="m-3">A-</option>
                                                <option value="m-3">B+</option>
                                                <option value="m-3">B-</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label mb-1 fw-medium">Status<span class="text-danger ms-1">*</span></label>
                                            <select class="select">
                                                <option value="m-1">Select</option>
                                                <option value="m-2">Available</option>
                                                <option value="m-3">Unavailable</option>
                                            </select>
                                        </div>
                                    </div>

                                </div>

                                <h6 class="fw-bold mb-3 border-top pt-3">Address Information</h6> 

                                <div class="row">

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label mb-1 fw-medium">Address 1<span class="text-danger ms-1">*</span></label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label mb-1 fw-medium">Address 2<span class="text-danger ms-1">*</span></label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label mb-1">Country<span class="text-danger ms-1">*</span></label>
                                            <select class="select"> 
                                                <option>Select</option>
                                                <option>United States</option>
                                                <option>Canada</option>
                                                <option>UK</option>
                                                <option>Germany</option>
                                                <option>France</option>
                                            </select>
                                        </div>
                                    </div>                        
            
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label mb-1">State<span class="text-danger ms-1">*</span></label>
                                            <select class="select"> 
                                                <option>Select</option>
                                                <option>California</option>
                                                <option>Ontario</option>
                                                <option>Bavaria</option>
                                                <option>Wellington</option>
                                                <option>Le-de-France</option>
                                            </select>
                                        </div>
                                    </div>
            
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label mb-1">City<span class="text-danger ms-1">*</span></label>
                                            <select class="select"> 
                                                <option>Select</option>
                                                <option>Los Angeles</option>
                                                <option>Toronto</option>
                                                <option>London</option>
                                                <option>Munich</option>
                                                <option>Sydney</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label mb-1">Pincode<span class="text-danger ms-1">*</span></label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>

                                </div>                                   
                            </div>
                        </div>
                    </div>
                    <!-- card end -->

                    <div class="d-flex align-items-center justify-content-end">
                        <a href="javascript:void(0);" class="btn btn-light me-2">Cancel</a>
                        <a href="javascript:void(0);" class="btn btn-primary">Add New Patient</a>
                    </div>

                </div>
            </div>
            <!-- row end -->               
            
        </div>
        <!-- End Content -->

        @component('components.footer')
        @endcomponent

    </div>

    <!-- ========================
        End Page Content
    ========================= -->

@endsection        