<?php $page = 'add-page'; ?>
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
                        <h6 class="fs-14 fw-semibold mb-0 d-flex align-items-center"> <a href="{{url('pages')}}" class="text-dark"> <i class="ti ti-chevron-left me-1"></i>Pages</a></h6>
                    </div>
                    <!-- page header end -->

                    <!-- card start -->
                    <div class="card">
                        <div class="card-body">
                            <form action="{{url('pages')}}">
                                    <div class="row">

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label mb-1 fw-medium">Title<span class="text-danger ms-1">*</span></label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label mb-1 fw-medium text-truncate">Slug<span class="fs-12 fw-normal text-body ms-1">(If you leave it empty, it will be generated automatically)</span><span class="text-danger ms-1">*</span></label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label mb-1 fw-medium text-truncate">Description<span class="fs-12 fw-normal text-body ms-1">(Meta Tag)</span><span class="text-danger ms-1">*</span></label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label mb-1 fw-medium text-truncate">Keywords<span class="fs-12 fw-normal text-body ms-1">(Meta Tag)</span><span class="text-danger ms-1">*</span></label>
                                            <input class="input-tags form-control" id="inputBox" type="text" data-role="tagsinput"  name="specialist">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label mb-1 fw-medium text-truncate">Location<span class="text-danger ms-1">*</span></label>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="form-check mb-1">
                                                    <input type="radio" id="customRadio1" name="customRadio" class="form-check-input">
                                                    <label class="form-check-label fw-normal" for="customRadio1">Top Menu</label>
                                                </div>
                                                <div class="form-check">
                                                    <input type="radio" id="customRadio2" name="customRadio" class="form-check-input">
                                                    <label class="form-check-label fw-normal" for="customRadio2">Quick Links</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label mb-1 fw-medium">Language<span class="text-danger ms-1">*</span></label>
                                            <select class="select">
                                                <option value="m-1">Select</option>
                                                <option value="m-2">English</option>
                                                <option value="m-3">German</option>
                                                <option value="m-4">Arabic</option>
                                                <option value="m-5">French</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="mb-0">
                                            <label class="form-label">Description</label>
                                            <div class="editor pages-editor"></div>                                                
                                        </div>
                                    </div>

                                    </div>
                            </form>
                        </div>
                    </div>
                    <!-- card end -->

                    <div class="d-flex align-items-center justify-content-end">
                        <a href="javascript:void(0);" class="btn btn-light me-2">Cancel</a>
                        <a href="javascript:void(0);" class="btn btn-primary">Add Page</a>
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