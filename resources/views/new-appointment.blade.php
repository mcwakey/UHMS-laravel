<?php $page = 'new-appointment'; ?>
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
                        <h6 class="fw-bold mb-0 d-flex align-items-center"> <a href="{{url('appointments')}}" class="text-dark"> <i class="ti ti-chevron-left me-1"></i>Appointments</a></h6>
                    </div>
                    <!-- page header end -->

                    <!-- card start -->
                    <div class="card">
                        <div class="card-body">
                            <div class="form">
                                <div class="mb-3">
                                    <label class="form-label mb-1 fw-medium">Appointment ID<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" value="AP234354" disabled>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                <label class="form-label mb-0 fw-medium">Patient<span class="text-danger ms-1">*</span></label>
                                                <a href="javascript:void(0);" class="link-primary" data-bs-toggle="modal" data-bs-target="#add_modal"><i class="ti ti-circle-plus me-1"></i>Add New</a>
                                            </div>
                                            <select class="select">
                                                <option value="m-1">Select</option>
                                                <option value="m-2">Alberto Ripley</option>
                                                <option value="m-3">Susan Babin</option>
                                                <option value="m-4">Martin Lisa</option>
                                                <option value="m-5">Stella Mary</option>
                                                <option value="m-6">Carol Lam</option>
                                                <option value="m-7">Jesus Adams</option>
                                                <option value="m-8">Ezra Belcher</option>
                                                <option value="m-9">Unit Manager</option>
                                                <option value="m-10">Bernard Griffith</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label mb-1 fw-medium">Department<span class="text-danger ms-1">*</span></label>
                                            <select class="select">
                                                <option value="m-1">Select</option>
                                                <option value="m-2">General Medicine</option>
                                                <option value="m-3">Pediatrics</option>
                                                <option value="m-4">Gynecology</option>
                                                <option value="m-5">Cardiology</option>
                                                <option value="m-6">Orthopedics</option>
                                                <option value="m-7">Dermatology</option>
                                                <option value="m-8">ENT</option>
                                                <option value="m-9">Neurology</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label mb-1 fw-medium">Doctor<span class="text-danger ms-1">*</span></label>
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
                                            <label class="form-label mb-1 fw-medium">Appointment Type<span class="text-danger ms-1">*</span></label>
                                            <select class="select">
                                                <option value="m-1">Select</option>
                                                <option value="m-2">In Person</option>
                                                <option value="m-3">Online</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label mb-1 fw-medium">Date of Appointment<span class="text-danger ms-1">*</span></label>
                                            <div class="input-icon-end position-relative">  
                                                <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy" value="dd/mm/yyyy">
                                                <span class="input-icon-addon">
                                                    <i class="ti ti-calendar"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label mb-1 fw-medium">Time<span class="text-danger ms-1">*</span></label>
                                            <div class="input-icon-end position-relative">
                                                <input type="text" class="form-control timepicker">
                                                <span class="input-icon-addon">
                                                    <i class="ti ti-clock text-gray-7"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label mb-1 fw-medium">Appointment Reason<span class="text-danger ms-1">*</span></label>
                                    <textarea class="form-control" rows="3"></textarea>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label mb-1 fw-medium">Status<span class="text-danger ms-1">*</span></label>
                                    <select class="select">
                                        <option value="m-1">Select</option>
                                        <option value="m-2">Checked Out</option>
                                        <option value="m-3">Checked In</option>
                                        <option value="m-4">Cancelled</option>
                                        <option value="m-5">Schedule</option>
                                        <option value="m-6">Confirmed</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- card end -->

                    <div class="d-flex align-items-center justify-content-end">
                        <a href="javascript:void(0);" class="btn btn-light me-2">Cancel</a>
                        <a href="javascript:void(0);" class="btn btn-primary">Create Appointment</a>
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