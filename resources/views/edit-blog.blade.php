<?php $page = 'edit-blog'; ?>
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
                                <input type="text" class="form-control" value="Health First: Your Guide to Better Living">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option selected>General Medicine</option>
                                    <option>Disease & Conditions</option>
                                    <option>Mental Health</option>
                                    <option>Patient Education </option>
                                    <option>Nutrition & Lifestyle Medicine</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Content</label>
                                <div class="editor">
                                    Preventive care refers to medical services that help you avoid illness, detect health issues early, and maintain overall wellness. Exercise isn't just about weight loss—it's about boosting your mood, energy, and heart health. Even 30 minutes a day of walking, stretching, or dancing can make a difference. Sleep is when your body and brain recharge. Poor sleep can affect everything from your immune system to your mental health. Your mental health is just as important as your physical health. Practice mindfulness, gratitude, and self-compassion. Therapy and journaling can also help you stay emotionally grounded. Water is life—literally. Staying hydrated improves your skin, digestion, and energy levels. Don’t wait for a crisis. Regular checkups, screenings, and vaccinations keep you one step ahead of potential problems.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tag</label>
                                <input class="input-tags form-control" id="inputBox" type="text" data-role="tagsinput"  name="specialist" value="Preventive Care">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Feature Image</label>
                                <input class="form-control" type="file">
                            </div>

                            <div class="mb-3">
                                <div class="selected-img">
                                    <img src="{{URL::asset('build/img/blogs/blog-img-01.jpg')}}" alt="img" class="avatar avatar-xxl img-fluid">
                                    <a href="javascript:void(0);" class="close-img">
                                        <i class="ti ti-circle-x-filled"></i>
                                    </a>
                                    </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-end">
                                <a href="javascript:void(0);" class="btn btn-light me-2">Cancel</a>
                                <a href="javascript:void(0);" class="btn btn-primary">Save Changes</a>
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