<?php $page = 'add-blog'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->

    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content">

            <!-- start row -->
            <div class="row">

                <div class="col-lg-10 mx-auto">
                    <div class="mb-3">
                        <h6 class="fw-semibold"><a href="{{url('blogs')}}"><i class="ti ti-chevron-left me-1"></i>Blogs</a></h6>
                    </div>
                    <div class="card">
                        <div class="card-body">

                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" class="form-control">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>General Medicine</option>
                                    <option>Disease & Conditions</option>
                                    <option>Mental Health</option>
                                    <option>Patient Education </option>
                                    <option>Nutrition & Lifestyle Medicine</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Content</label>
                                <div class="editor"></div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tag</label>
                                <input class="input-tags form-control" id="inputBox" type="text" data-role="tagsinput"  name="specialist">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Feature Image</label>
                                <input class="form-control" type="file">
                            </div>

                            <div class="d-flex align-items-center justify-content-end">
                                <a href="javascript:void(0);" class="btn btn-light me-2">Cancel</a>
                                <a href="javascript:void(0);" class="btn btn-primary">Add Blog</a>
                            </div>


                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->
                
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