<?php $page = 'localization-settings'; ?>
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
                                <h5 class="fw-bold">Localization</h5>
                            </div>
                            <div class="card-body px-0 mx-3">
                                <form action="{{url('profile-settings')}}">

                                    <!-- start row -->
                                    <div class="row border-bottom mb-3">
                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                                <div class="col-lg-4">
                                                    <label class="form-label mb-0">Timezone </label>
                                                </div><!-- end col -->
                                                <div class="col-lg-8">
                                                    <select class="select">
                                                        <option>(+5:30) GMT</option>
                                                        <option>(GMT -10:00) Hawaii</option>
                                                        <option>(GMT -9:30) Taiohae</option>
                                                        <option>(GMT -9:00) Alaska </option>
                                                        <option>(GMT -8:00) Pacific Time, Canada</option>
                                                    </select>
                                                </div><!-- end col -->
                                            </div>
                                            <!-- end row -->

                                        </div><!-- end col -->
                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                            <div class="col-lg-4">
                                                <label class="form-label mb-0">Start Week On</label>
                                            </div><!-- end col -->
                                            <div class="col-lg-8">
                                                <select class="select">
                                                    <option>Monday</option>
                                                    <option>Tuesday</option>
                                                    <option>Wednesday</option>
                                                    <option>Thursday</option>
                                                    <option>Friday</option>
                                                    <option>Saturday</option>
                                                    <option>Sunday</option>
                                                </select>
                                            </div><!-- end col -->
                                        </div>
                                        <!-- end row -->

                                        </div><!-- end col -->
                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                            <div class="col-lg-4">
                                                <label class="form-label mb-0">Date Format</label>
                                            </div><!-- end col -->
                                            <div class="col-lg-8">
                                                <select class="select">
                                                    <option>18 Mar 2025</option>
                                                    <option>Mar 18 2025</option>
                                                    <option>2025 Mar 18</option>
                                                </select>
                                            </div><!-- end col -->
                                        </div>
                                        <!-- end row -->

                                        </div><!-- end col -->
                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                            <div class="col-lg-4">
                                                <label class="form-label mb-0">Time Format</label>
                                            </div><!-- end col -->
                                            <div class="col-lg-8">
                                                <select class="select">
                                                    <option>12 hrs</option>
                                                    <option>24hrs</option>
                                                </select>
                                            </div><!-- end col -->
                                        </div>
                                        <!-- end row -->

                                        </div><!-- end col -->
                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                            <div class="col-lg-4">
                                                <label class="form-label mb-0">Default Language</label>
                                            </div><!-- end col -->
                                            <div class="col-lg-8">
                                                <select class="select">
                                                    <option>English</option>
                                                    <option>German</option>
                                                    <option>Arabic</option>
                                                    <option>French</option>
                                                </select>
                                            </div><!-- end col -->
                                        </div>
                                        <!-- end row -->

                                        </div><!-- end col -->
                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                            <div class="col-lg-4">
                                                <label class="form-label mb-0">Language Switcher</label>
                                            </div><!-- end col -->
                                            <div class="col-lg-8">
                                                <div class="form-check form-switch ps-0">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </div>
                                            </div><!-- end col -->
                                        </div>
                                        <!-- end row -->

                                        </div><!-- end col -->
                                    </div>
                                    <!-- end row -->

                                    <!-- start row -->
                                    <div class="row border-bottom mb-3">
                                        <div class="mb-3">
                                            <h5 class="fw-bold mb-0">Currency Information</h5>
                                        </div>
                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                                <div class="col-lg-4">
                                                    <label class="form-label mb-0">Currency</label>
                                                </div><!-- end col -->
                                                <div class="col-lg-8">
                                                    <select class="select">
                                                        <option>USD</option>
                                                        <option>Dollar</option>
                                                        <option>Euro</option>
                                                        <option>Pound</option>
                                                        <option>Rupee</option>
                                                    </select>
                                                </div><!-- end col -->
                                            </div>
                                            <!-- end row -->

                                        </div><!-- end col -->
                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                            <div class="col-lg-4">
                                                <label class="form-label mb-0">Currency Symbol</label>
                                            </div><!-- end col -->
                                            <div class="col-lg-8">
                                                <select class="select">
                                                    <option>$</option>
                                                    <option>₹</option>
                                                    <option>£</option>
                                                    <option>€</option>
                                                </select>
                                            </div><!-- end col -->
                                        </div>
                                        <!-- end row -->

                                        </div><!-- end col -->
                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                            <div class="col-lg-4">
                                                <label class="form-label mb-0">Currency Position</label>
                                            </div><!-- end col -->
                                            <div class="col-lg-8">
                                                <select class="select">
                                                    <option>$100</option>
                                                    <option>100$</option>
                                                    <option>$ 100</option>
                                                    <option>100 $</option>
                                                </select>
                                            </div><!-- end col -->
                                        </div>
                                        <!-- end row -->

                                        </div><!-- end col -->
                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                            <div class="col-lg-4">
                                                <label class="form-label mb-0">Decimal Seperator</label>
                                            </div><!-- end col -->
                                            <div class="col-lg-8">
                                                <select class="select">
                                                    <option>.</option>
                                                    <option>,</option>
                                                </select>
                                            </div><!-- end col -->
                                        </div>
                                        <!-- end row -->

                                        </div><!-- end col -->
                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                                <div class="col-lg-4">
                                                    <label class="form-label mb-0">Thousand Seperator</label>
                                                </div><!-- end col -->
                                                <div class="col-lg-8">
                                                    <select class="select lh-2">
                                                        <option>.</option>
                                                        <option>,</option>
                                                        <option>'</option>
                                                    </select>
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