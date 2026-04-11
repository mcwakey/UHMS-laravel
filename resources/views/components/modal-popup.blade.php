@if (Route::is(['doctor-dashboard']))
    <!-- Start Add New Appointment -->
    <div class="offcanvas offcanvas-offset offcanvas-end" tabindex="-1" id="new_appointment">
        <div class="offcanvas-header d-block pb-0 px-0">
            <div class="border-bottom d-flex align-items-center justify-content-between pb-3 px-3">
                <h5 class="offcanvas-title fs-18 fw-bold">New Appointment</h5>
                <button type="button" class="btn-close custom-btn-close opacity-100" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
        </div>
        <div class="offcanvas-body pt-3">
            <form action="#">
                <!-- start row-->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Appointment ID <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control rounded bg-light" value="AP234354">
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Patient<span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Select
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Search">
                                        </div>
                                    </div>
                                    <ul class="mb-3 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Emily Clark
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>John Carter
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-16.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Sophia White
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-15.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Michael Johnson
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-14.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Olivia Harris
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>David Anderson
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Appointment Type <span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Select
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Select">
                                        </div>
                                    </div>
                                    <ul class="mb-3 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                In Person
                                            </label>
                                        </li>
                                        <li class="list-none">
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Online
                                            </label>
                                        </li>
                                        
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium"> Date of Appointment <span class="text-danger">*</span></label>
                            <div class="input-icon-end position-relative">  
                                <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                <span class="input-icon-addon">
                                    <i class="ti ti-calendar"></i>
                                </span>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium"> Time <span class="text-danger">*</span></label>
                            <div class="input-icon-end position-relative">  
                                <input type="text" class="form-control timepicker" placeholder="-- : --">
                                <span class="input-icon-addon">
                                    <i class="ti ti-clock"></i>
                                </span>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <div>
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Appointment Reason</label>
                                <textarea rows="4" class="form-control rounded"> </textarea>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Status<span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Select
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Select">
                                        </div>
                                    </div>
                                    <ul class="mb-3 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Checked Out
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Checked In
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Cancelled
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Scheduled
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col-->
                </div>
                <!-- end row-->
            </form>
        </div>
        <div class="offcanvas-footer mb-1 mt-3 p-3 border-1 border-top">
            <div class=" d-flex justify-content-end gap-2">
                <a href="javascript:void(0);" class="btn btn-light btm-md">Cancel</a>
                <button data-bs-dismiss="offcanvas" class="btn btn-primary btm-md" id="filter-submit">Create Create Appointment</button>
            </div>
        </div>
    </div>
    <!-- End Add New Appointment-->

    <!-- Start Edit New Appointment -->
    <div class="offcanvas offcanvas-offset offcanvas-end" tabindex="-1" id="edit_appointment">
        <div class="offcanvas-header d-block pb-0 px-0">
            <div class="border-bottom d-flex align-items-center justify-content-between pb-3 px-3">
                <h5 class="offcanvas-title fs-18 fw-bold"> Edit Appointment</h5>
                <button type="button" class="btn-close custom-btn-close opacity-100" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
        </div>
        <div class="offcanvas-body pt-3">
            <form action="#">
                <!-- start row-->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Appointment ID <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control rounded bg-light" value="AP234354">
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Patient<span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Emily Clark
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Search">
                                        </div>
                                    </div>
                                    <ul class="mb-3 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Emily Clark
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>John Carter
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-16.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Sophia White
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-15.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Michael Johnson
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-14.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Olivia Harris
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>David Anderson
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Appointment Type <span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    In Person
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Select">
                                        </div>
                                    </div>
                                    <ul class="mb-0 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                In Person
                                            </label>
                                        </li>
                                        <li class="list-none">
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Online
                                            </label>
                                        </li>
                                        
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium"> Date of Appointment <span class="text-danger">*</span></label>
                            <div class="input-icon-end position-relative">  
                                <input type="text" class="form-control datetimepicker" placeholder="20/08/2025">
                                <span class="input-icon-addon">
                                    <i class="ti ti-calendar"></i>
                                </span>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium"> Time <span class="text-danger">*</span></label>
                            <div class="input-icon-end position-relative">  
                                <input type="text" class="form-control timepicker" placeholder="01 : 20 : PM">
                                <span class="input-icon-addon">
                                    <i class="ti ti-clock"></i>
                                </span>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <div>
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Appointment Reason</label>
                                <textarea rows="4" class="form-control rounded"> An account of the present illness, which includes the circumstances surrounding the onset of recent health changes and the Purpose. </textarea>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Status<span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Checked Out
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Select">
                                        </div>
                                    </div>
                                    <ul class="mb-3 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Checked Out
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Checked In
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Cancelled
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Scheduled
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col-->
                </div>
                <!-- end row-->
            </form>
        </div>
        <div class="offcanvas-footer mb-1 mt-3 p-3 border-1 border-top">
            <div class=" d-flex justify-content-end gap-2">
                <a href="javascript:void(0);" class="btn btn-light btm-md">Cancel</a>
                <button data-bs-dismiss="offcanvas" class="btn btn-primary btm-md" id="filter-submit2">Create Create Appointment</button>
            </div>
        </div>
    </div>
    <!-- End Edit New Appointment-->

    <!-- Start View Details -->
    <div class="offcanvas offcanvas-offset offcanvas-end" tabindex="-1" id="view_details">
        <div class="offcanvas-header d-block pb-0 px-0">
            <div class="border-bottom d-flex align-items-center justify-content-between pb-3 px-3">
                <h5 class="offcanvas-title fs-18 fw-bold">Appointment Details <span class="badge badge-soft-primary border pt-1 px-2 border-primary fw-medium ms-2">#AP544658</span></h5>
                <button type="button" class="btn-close custom-btn-close opacity-100" data-bs-dismiss="offcanvas" aria-label="Close"></button></div>
        </div>
        <div class="offcanvas-body pt-0 px-0">
            <h6 class="bg-light py-2 px-3 text-dark fw-bold"> When & Where </h6>
            <div class="px-3 my-4">
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Appointment On <span class="text-body fw-normal"> Saturday, 25 Apr 2025  </span> </p>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Time <span class="text-body fw-normal"> 09:00 AM - 11:00 AM  </span> </p>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Location <span class="text-body fw-normal">Newyork , USA   </span> </p>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Appointment Type <span class="text-body fw-normal"> Online Consultation </span> </p>
                <div class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Patient Details 
                    <div class="text-body fw-normal d-flex align-items-center"> 
                        <span class="avatar avatar-sm">
                            <img src="{{URL::asset('build/img/users/avatar-2.jpg')}}" alt="" class="rounded-circle me-1">
                        </span>
                        James Adrian 
                    </div> 
                </div>
            </div>
            <h6 class="bg-light py-2 px-3 text-dark fw-bold"> Appointment Details  </h6>
            <div class="px-3 my-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center"> 
                        Telehealth 
                        <label class="d-flex align-items-center form-switch ps-1">
                            <input class="form-check-input m-0 me-2" type="checkbox" checked>
                        </label>
                    </div>
                    <div>  <a href="{{url('online-consultations')}}" class="btn-primary btn btn-sm rounded d-flex align-items-center"> <i class="ti ti-video me-1"></i> Start </a></div>
                </div>
                <div class="row align-items-center">
                    <div class="col-lg-6 col-md-6">
                        <p class="text-dark"> Status </p>
                    </div>
                    
                    <div class="col-lg-6 col-md-6"> 
                        <div class="mb-3">
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Pending
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Select">
                                        </div>
                                    </div>
                                    <ul class="mb-0 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Checked Out
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Checked In
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Cancelled
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Scheduled
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <!-- End Add New Appointment-->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['patient-dashboard']))
    <!-- Start Add New Appointment -->
    <div class="offcanvas offcanvas-offset offcanvas-end" tabindex="-1" id="new_appointment">
        <div class="offcanvas-header d-block pb-0 px-0">
            <div class="border-bottom d-flex align-items-center justify-content-between pb-3 px-3">
                <h5 class="offcanvas-title fs-18 fw-bold">New Appointment</h5>
                <button type="button" class="btn-close custom-btn-close opacity-100" data-bs-dismiss="offcanvas" aria-label="Close"></button></div>
        </div>
        <div class="offcanvas-body pt-3">
            <form action="#">

                <!-- start row-->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Appointment ID <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control rounded bg-light" value="AP234354">
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Patient<span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Select
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Search">
                                        </div>
                                    </div>
                                    <ul class="mb-3 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Emily Clark
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>John Carter
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-16.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Sophia White
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-15.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Michael Johnson
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-14.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Olivia Harris
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>David Anderson
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Appointment Type <span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Select
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Select">
                                        </div>
                                    </div>
                                    <ul class="mb-3 list-style-none">
                                        <li class="list-none">
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Online
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Member
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium"> Date of Appointment <span class="text-danger">*</span></label>
                            <div class="input-icon-end position-relative">  
                                <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                <span class="input-icon-addon">
                                    <i class="ti ti-calendar"></i>
                                </span>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium"> Time <span class="text-danger">*</span></label>
                            <div class="input-icon-end position-relative">  
                                <input type="text" class="form-control timepicker" placeholder="-- : --">
                                <span class="input-icon-addon">
                                    <i class="ti ti-clock"></i>
                                </span>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <div>
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Appointment Reason</label>
                                <textarea rows="4" class="form-control rounded"> </textarea>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Status<span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Select
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Select">
                                        </div>
                                    </div>
                                    <ul class="mb-3 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Checked Out
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Checked In
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Cancelled
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Scheduled
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col-->
                </div>
                <!-- end row-->

                
            </form>
        </div>
        <div class="offcanvas-footer mb-1 mt-3 p-3 border-1 border-top">
            <div class=" d-flex justify-content-end gap-2">
                <a href="javascript:void(0);" class="btn btn-light btm-md">Cancel</a>
                <button data-bs-dismiss="offcanvas" class="btn btn-primary btm-md">Create Create Appointment</button>
            </div>
        </div>
    </div>
    <!-- End Add New Appointment-->

    <!-- Start View Details -->
    <div class="offcanvas offcanvas-offset offcanvas-end" tabindex="-1" id="view_details">
        <div class="offcanvas-header d-block pb-0 px-0">
            <div class="border-bottom d-flex align-items-center justify-content-between pb-3 px-3">
                <h5 class="offcanvas-title fs-18 fw-bold">Appointment Details <span class="badge badge-soft-primary border pt-1 px-2 border-primary fw-medium ms-2">#AP544658</span></h5>
                <button type="button" class="btn-close custom-btn-close opacity-100" data-bs-dismiss="offcanvas" aria-label="Close"></button>            </div>
        </div>
        <div class="offcanvas-body pt-0 px-0">
            <h6 class="bg-light py-2 px-3 text-dark fw-bold"> When & Where </h6>

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 bg-light mx-3 p-3 rounded my-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-lg">
                        <img src="{{URL::asset('build/img/doctors/doctor-01.jpg')}}" alt="doctor-01" class="img-fluid img1 rounded-circle">
                    </div>
                    <h6 class="fs-14 fw-semibold m-0"> Dr. Emily Carter <span class="d-block fs-13 fw-normal text-body pt-1"> Pediatrician </span></h6>
                </div>
                <div class="gap-1 d-flex">
                    <a href="" class="bg-white d-flex align-items-center justify-content-center p-1 border rounded"> <i class="ti ti-brand-hipchat fs-13"></i></a>
                    <a href="" class="bg-white d-flex align-items-center justify-content-center p-1 border rounded"> <i class="ti ti-video fs-13"></i></a>
                </div>
            </div>

            <div class="px-3 my-4">
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Appointment On <span class="text-body fw-normal"> Saturday, 25 Apr 2025  </span> </p>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Time <span class="text-body fw-normal"> 09:00 AM - 11:00 AM  </span> </p>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Location <span class="text-body fw-normal">Newyork , USA   </span> </p>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Appointment Type <span class="text-body fw-normal"> Online Consultation </span> </p>
            </div>
            <h6 class="bg-light py-2 px-3 text-dark fw-bold"> Appointment Details  </h6>
            <div class="px-3 my-3">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center"> 
                        Telehealth 
                        <label class="d-flex align-items-center form-switch ps-1">
                            <input class="form-check-input m-0 me-2" type="checkbox" checked>
                        </label>
                    </div>
                    <div>  <a href="{{url('online-consultations')}}" class="btn-primary btn btn-sm rounded d-flex align-items-center"> <i class="ti ti-video me-1 fs-13"></i> Start </a></div>
                </div>
                <div class="row align-items-center">
                    <div class="col-lg-6 col-md-6">
                        <p class="text-dark"> Status </p>
                    </div>
                    
                    <div class="col-lg-6 col-md-6"> 
                        <div class="mb-0">
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Pending
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Select">
                                        </div>
                                    </div>
                                    <ul class="mb-0 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Checked Out
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Checked In
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Cancelled
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Scheduled
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <!-- End Add New Appointment-->

    <!-- Start Edit New Appointment -->
    <div class="offcanvas offcanvas-offset offcanvas-end" tabindex="-1" id="edit_appointment">
        <div class="offcanvas-header d-block pb-0 px-0">
            <div class="border-bottom d-flex align-items-center justify-content-between pb-3 px-3">
                <h5 class="offcanvas-title fs-18 fw-bold"> Edit Appointment</h5>
                <button type="button" class="btn-close custom-btn-close opacity-100" data-bs-dismiss="offcanvas" aria-label="Close"></button></div>
        </div>
        <div class="offcanvas-body pt-3">
            <form action="#">
                <!-- start row-->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Appointment ID <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control rounded bg-light" value="AP234354">
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Patient<span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Emily Clark
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Search">
                                        </div>
                                    </div>
                                    <ul class="mb-3 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Emily Clark
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>John Carter
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-16.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Sophia White
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-15.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Michael Johnson
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-14.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Olivia Harris
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>David Anderson
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Appointment Type <span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    In Person
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Select">
                                        </div>
                                    </div>
                                    <ul class="mb-0 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                In Person
                                            </label>
                                        </li>
                                        <li class="list-none">
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Online
                                            </label>
                                        </li>
                                        
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium"> Date of Appointment <span class="text-danger">*</span></label>
                            <div class="input-icon-end position-relative">  
                                <input type="text" class="form-control datetimepicker" placeholder="20/08/2025">
                                <span class="input-icon-addon">
                                    <i class="ti ti-calendar"></i>
                                </span>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium"> Time <span class="text-danger">*</span></label>
                            <div class="input-icon-end position-relative">  
                                <input type="text" class="form-control timepicker" placeholder="01 : 20 : PM">
                                <span class="input-icon-addon">
                                    <i class="ti ti-clock"></i>
                                </span>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <div>
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Appointment Reason</label>
                                <textarea rows="4" class="form-control rounded"> An account of the present illness, which includes the circumstances surrounding the onset of recent health changes and the Purpose. </textarea>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Status<span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Checked Out
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Select">
                                        </div>
                                    </div>
                                    <ul class="mb-3 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Checked Out
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Checked In
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Cancelled
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Scheduled
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col-->
                </div>
                <!-- end row-->
            </form>
        </div>
        <div class="offcanvas-footer mb-1 mt-3 p-3 border-1 border-top">
            <div class=" d-flex justify-content-end gap-2">
                <a href="javascript:void(0);" class="btn btn-light btm-md">Cancel</a>
                <button data-bs-dismiss="offcanvas" class="btn btn-primary btm-md">Create Create Appointment</button>
            </div>
        </div>
    </div>
    <!-- End Edit New Appointment-->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['call-history']))
    <!-- Edit Employee Start -->
    <div class="modal fade" id="call_history">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="d-flex align-items-center">
                        <h4 class="modal-title">Caller Details</h4>
                    </div>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form action="{{url('call-history')}}">
                    <div class="modal-body">
                        <div class="card shadow-none">
                            <div class="card-body">
                                <div class="text-center">
                                    <div class="avatar avatar-xxxl mb-3">
                                        <img src="{{URL::asset('build/img/users/user-01.jpg')}}" alt="img" class="rounded-circle">
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-center">
                                    <a href="{{url('video-call')}}" class="btn btn-light btn-icon rounded-circle p-0 d-flex align-items-center justify-content-center me-3"><i class="ti ti-video fs-13"></i></a>
                                    <a href="{{url('chat')}}" class="btn btn-light btn-icon rounded-circle p-0 d-flex align-items-center justify-content-center me-3"><i class="ti ti-message fs-13"></i></a>
                                    <a href="{{url('voice-call')}}" class="btn btn-light btn-icon rounded-circle p-0 d-flex align-items-center justify-content-center"><i class="ti ti-phone fs-13"></i></a>
                                </div>
                            </div>
                        </div>
                        <!-- start row -->
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div>
                                    <p class="mb-1 fs-13">Name</p>
                                    <h6 class="fw-medium fs-14">Anthony Lewis</h6>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div>
                                    <p class="mb-1 fs-13">Total Calls</p>
                                    <h6 class="fw-medium fs-14">20</h6>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div>
                                    <p class="mb-1 fs-13">Phone</p>
                                    <h6 class="fw-medium fs-14">(123) 4567 890</h6>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div>
                                    <p class="mb-1 fs-13">Average Call Timing</p>
                                    <h6 class="fw-medium fs-14">00:30</h6>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div>
                                    <p class="mb-1 fs-13">Email</p>
                                    <h6 class="fw-medium fs-14">anthony@example.com</h6>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div>
                                    <p class="mb-1 fs-13">Average Waiting Time</p>
                                    <h6 class="fw-medium fs-14">00:05</h6>
                                </div>
                            </div>
                        </div>
                        <!-- end row -->
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Edit Employee End -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['calendar']))
    <!-- Add New Event Start -->
    <div class="modal fade" id="add_event">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Event</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('calendar')}}">
                    <div class="modal-body">

                        <!-- start row -->
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Event Name</label>
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Event Date</label>
                                    <div class="input-icon-end position-relative">
                                        <input type="text" class="form-control datetimepicker">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-calendar text-gray-7"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Start Time</label>
                                    <div class="input-icon-end position-relative">
                                        <input type="text" class="form-control timepicker">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-clock text-gray-7"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">End Time</label>
                                    <div class="input-icon-end position-relative">
                                        <input type="text" class="form-control timepicker">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-clock text-gray-7"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Event Location</label>
                                    <input type="text" class="form-control">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Descriptions</label>
                                    <textarea class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        <!-- end row -->
                            
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-md btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-md btn-primary">Add Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Add New Event End -->

    <!-- Start Event -->
    <div class="modal fade" id="event_modal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark modal-bg">
                    <div class="modal-title text-white"><span id="eventTitle"></span></div>
                    <button type="button" class="btn-close btn-close-modal btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="d-flex align-items-center fw-medium text-black mb-3"><i class="ti ti-calendar-check text-default me-2"></i>26 Jul,2024 to 31 Jul,2024</p>
                    <p class="d-flex align-items-center fw-medium text-black mb-3"><i class="ti ti-calendar-check text-default me-2"></i>11:00 AM to 12:15 PM</p>
                    <p class="d-flex align-items-center fw-medium text-black mb-3"><i class="ti ti-map-pin-bolt text-default me-2"></i>Las Vegas, US</p>
                    <p class="d-flex align-items-center fw-medium text-black mb-0"><i class="ti ti-calendar-check text-default me-2"></i>A recurring or repeating event is simply any event that you will occur more than once on your calendar.</p>
                </div>
            </div>
        </div>
    </div>
    <!-- End Event -->
@endif

@if (Route::is(['contacts']))
    <!-- Start Add Customer -->
    <div class="modal fade" id="add-contact">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="page-title">
                        <h5 class="mb-0">Add Contact</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>

                </div>
                <form action="{{url('contacts')}}">
                    <div class="modal-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar avatar-xxl border border-dashed bg-light me-3 flex-shrink-0">
                                <i class="ti ti-photo text-primary"></i>
                            </div>
                            <div class="d-inline-flex flex-column align-items-start">
                                <div class="drag-upload-btn btn btn-sm btn-primary position-relative mb-2">
                                    <i class="ti ti-photo me-1"></i>Upload Image
                                    <input type="file" class="form-control image-sign" multiple="">
                                </div>
                                <span class="text-gray-9 fs-12">JPG or PNG format, not exceeding 5MB.</span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">First Name<span class="text-danger ms-1">*</span></label>
                                <input type="text" class="form-control">
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Last Name<span class="text-danger ms-1">*</span></label>
                                <input type="text" class="form-control">
                            </div>
                            <div class="col-lg-12 mb-3">
                                <label class="form-label">Email<span class="text-danger ms-1">*</span></label>
                                <input type="email" class="form-control">
                            </div>
                            <div class="col-lg-12 mb-3">
                                <label class="form-label">Phone<span class="text-danger ms-1">*</span></label>
                                <input type="tel" class="form-control">
                            </div>
                            <div class="col-lg-12">
                                <div class="mb-0">
                                    <label class="form-label">Contact Type <span class="text-danger">*</span></label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Admin</option>
                                        <option>Salesman</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn me-2 btn-light fs-13 fw-medium shadow-none" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fs-13 fw-medium">Add Contact</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Customer -->

    <!-- Start Edit Customer -->
    <div class="modal fade" id="edit-contact">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="page-title">
                        <h5 class="mb-0">Edit Contact</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('contacts')}}">
                    <div class="modal-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar avatar-xxl border border-dashed bg-light me-3 flex-shrink-0">
                                <div class="position-relative d-flex align-items-center">
                                    <img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="avatar avatar-xl " alt="User Img">
                                    <a href="javascript:void(0);" class="rounded-trash trash-top d-flex align-items-center justify-content-center"><i class="ti ti-trash"></i></a>
                                </div>
                            </div>
                            <div class="d-inline-flex flex-column align-items-start">
                                <div class="drag-upload-btn btn btn-sm btn-primary position-relative mb-2">
                                    <i class="ti ti-photo me-1"></i>Upload Image
                                    <input type="file" class="form-control image-sign" multiple="">
                                </div>
                                <span class="text-gray-9 fs-12">JPG or PNG format, not exceeding 5MB.</span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">First Name<span class="text-danger ms-1">*</span></label>
                                <input type="text" class="form-control" value="Carl">
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Last Name<span class="text-danger ms-1">*</span></label>
                                <input type="text" class="form-control" value="Evans">
                            </div>
                            <div class="col-lg-12 mb-3">
                                <label class="form-label">Email<span class="text-danger ms-1">*</span></label>
                                <input type="email" class="form-control" value="carlevans@example.com">
                            </div>
                            <div class="col-lg-12 mb-3">
                                <label class="form-label">Phone<span class="text-danger ms-1">*</span></label>
                                <input type="tel" class="form-control" value="+12163547758 ">
                            </div>
                            <div class="col-lg-12">
                                <div class="mb-0">
                                    <label class="form-label">Contact Type <span class="text-danger">*</span></label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option selected>Admin</option>
                                        <option>Salesman</option>
                                    </select>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn me-2 btn-light fs-13 fw-medium shadow-none" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fs-13 fw-medium">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Edit Customer -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['email']))
    <div id="compose-view" class="position-fixed top-0 start-0 w-100 h-100 align-items-end justify-content-end">
        <div class="bg-white border-0 rounded compose-view position-relative">

            <div class="compose-header d-flex align-items-center justify-content-between bg-dark p-3">
                <h5 class="text-white mb-0">Compose New Email</h5>
                <div class="d-flex align-items-center">
                    <a href="javascript:void(0);" class="d-inline-flex me-2 text-white fs-16"><i class="ti ti-minus"></i></a>
                    <a href="javascript:void(0);" class="d-inline-flex me-2 fs-16 text-white"><i class="ti ti-maximize"></i></a>
                    <button  class="bg-transparent border-0 fs-16 text-white position-static p-0" id="compose-close">
                        <i class="ti ti-x text-white"></i>
                    </button>
                </div>
            </div>

            <form action="">
                <div class="p-3 position-relative pb-2 border-bottom">
                    <div class="tag-with-img d-flex align-items-center">
                        <label class="form-label me-2 mb-0">To</label>
                        <input class="input-tags form-control border-0 h-100" data-choices data-choices-limit="3" data-choices-removeItem type="text" value="Angela Thomas">
                    </div>
                    <div class="d-flex align-items-center position-absolute end-0 pe-3 top-50 translate-middle-y">
                        <a href="javascript:void(0);" class="d-inline-flex me-2">Cc</a>
                        <a href="javascript:void(0);" class="d-inline-flex">Bcc</a>
                    </div>
                </div>
                <div class="p-3 border-bottom">
                    <div class="mb-3">
                        <input type="text" class="form-control" placeholder="Subject">
                    </div>
                    <div class="mb-0">
                        <textarea rows="7" class="form-control" placeholder="Compose Email"></textarea>
                    </div>
                </div>
                <div class="p-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <a href="javascript:void(0);" class="btn btn-icon btn-sm rounded-circle"><i class="ti ti-paperclip"></i></a>
                        <a href="javascript:void(0);" class="btn btn-icon btn-sm rounded-circle"><i class="ti ti-photo"></i></a>
                        <a href="javascript:void(0);" class="btn btn-icon btn-sm rounded-circle"><i class="ti ti-link"></i></a>
                        <a href="javascript:void(0);" class="btn btn-icon btn-sm rounded-circle"><i class="ti ti-pencil"></i></a>
                        <a href="javascript:void(0);" class="btn btn-icon btn-sm rounded-circle"><i class="ti ti-mood-smile"></i></a>
                    </div>
                    <div class="d-flex align-items-center compose-footer">
                        <a href="javascript:void(0);" class="btn btn-icon btn-sm rounded-circle"><i class="ti ti-calendar-repeat"></i></a>
                        <a href="javascript:void(0);" class="btn btn-icon btn-sm rounded-circle"><i class="ti ti-trash"></i></a>
                        <button type="submit" class="btn btn-primary d-inline-flex align-items-center ms-2">Send<i class="ti ti-arrow-right ms-2"></i></button>
                    </div>
                </div>
            </form> <!-- end form -->   

        </div>
    </div> <!-- end compose mail -->    
@endif

@if (Route::is(['email-reply']))
    <div id="compose-view" class="position-fixed top-0 start-0 w-100 h-100 align-items-end justify-content-end">
        <div class="bg-white border-0 rounded compose-view position-relative">

            <div class="compose-header d-flex align-items-center justify-content-between bg-dark p-3">
                <h5 class="text-white mb-0">Compose New Email</h5>
                <div class="d-flex align-items-center">
                    <a href="javascript:void(0);" class="d-inline-flex me-2 text-white fs-16"><i class="ti ti-minus"></i></a>
                    <a href="javascript:void(0);" class="d-inline-flex me-2 fs-16 text-white"><i class="ti ti-maximize"></i></a>
                    <button  class="bg-transparent border-0 fs-16 text-white position-static p-0" id="compose-close">
                        <i class="ti ti-x text-white"></i>
                    </button>
                </div>
            </div>

            <form action="{{url('email')}}">
                <div class="p-3 position-relative pb-2 border-bottom">
                    <div class="tag-with-img d-flex align-items-center">
                        <label class="form-label me-2 mb-0">To</label>
                        <input class="input-tags form-control border-0 h-100" data-choices data-choices-limit="3" data-choices-removeItem type="text" value="Angela Thomas">
                    </div>
                    <div class="d-flex align-items-center position-absolute end-0 pe-3 top-50 translate-middle-y">
                        <a href="javascript:void(0);" class="d-inline-flex me-2">Cc</a>
                        <a href="javascript:void(0);" class="d-inline-flex">Bcc</a>
                    </div>
                </div>
                <div class="p-3 border-bottom">
                    <div class="mb-3">
                        <input type="text" class="form-control" placeholder="Subject">
                    </div>
                    <div class="mb-0">
                        <textarea rows="7" class="form-control" placeholder="Compose Email"></textarea>
                    </div>
                </div>
                <div class="p-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <a href="javascript:void(0);" class="btn btn-icon btn-sm rounded-circle"><i class="ti ti-paperclip"></i></a>
                        <a href="javascript:void(0);" class="btn btn-icon btn-sm rounded-circle"><i class="ti ti-photo"></i></a>
                        <a href="javascript:void(0);" class="btn btn-icon btn-sm rounded-circle"><i class="ti ti-link"></i></a>
                        <a href="javascript:void(0);" class="btn btn-icon btn-sm rounded-circle"><i class="ti ti-pencil"></i></a>
                        <a href="javascript:void(0);" class="btn btn-icon btn-sm rounded-circle"><i class="ti ti-mood-smile"></i></a>
                    </div>
                    <div class="d-flex align-items-center compose-footer">
                        <a href="javascript:void(0);" class="btn btn-icon btn-sm rounded-circle"><i class="ti ti-calendar-repeat"></i></a>
                        <a href="javascript:void(0);" class="btn btn-icon btn-sm rounded-circle"><i class="ti ti-trash"></i></a>
                        <button type="submit" class="btn btn-primary d-inline-flex align-items-center ms-2">Send<i class="ti ti-arrow-right ms-2"></i></button>
                    </div>
                </div>
            </form> <!-- end form -->   

        </div>
    </div> <!-- end compose mail --> 
@endif

@if (Route::is(['invoice']))
    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['todo-list']))
    <!-- Add Todo -->
    <div class="modal fade" id="add_todo">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add New Todo</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{url('todo-list')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Todo Title</label>
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Tag</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Internal</option>
                                        <option>Projects</option>
                                        <option>Meetings</option>
                                        <option>Reminder</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Priority</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Medium</option>
                                        <option>High</option>
                                        <option>Low</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="mb-3">
                                    <label class="form-label">Descriptions</label>
                                    <div class="snow-editor"></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Add Assignee</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Sophie</option>
                                        <option>Cameron</option>
                                        <option>Doris</option>
                                        <option>Rufana</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-0">
                                    <label class="form-label">Status</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Completed</option>
                                        <option>Pending</option>
                                        <option>Onhold</option>
                                        <option>Inprogress</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add New Todo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Add Todo end -->

    <!-- Edit Todo -->
    <div class="modal fade" id="edit_todo">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit Todo</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="{{url('todo-list')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Todo Title</label>
                                    <input type="text" class="form-control" value="Update calendar and schedule">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Tag</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option selected>Internal</option>
                                        <option>Projects</option>
                                        <option>Meetings</option>
                                        <option>Reminder</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Priority</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>High</option>
                                        <option selected>Medium</option>
                                        <option>Low</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="mb-3">
                                    <label class="form-label">Descriptions</label>
                                    <div class="snow-editor"></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Add Assignee</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option selected>Sophie</option>
                                        <option>Cameron</option>
                                        <option>Doris</option>
                                        <option>Rufana</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-0">
                                    <label class="form-label">Status</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option selected>Completed</option>
                                        <option>Pending</option>
                                        <option>Onhold</option>
                                        <option>Inprogress</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Edit Todo end -->

    <!-- Todo Details -->
    <div class="modal fade" id="view_todo">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark">
                    <h4 class="modal-title text-white me-1">Respond to any pending messages</h4>
                    <span class="badge bg-danger d-inline-flex align-items-center me-1"><i class="ti ti-square me-1"></i>Urgent</span>
                    <span><i class="ti ti-star-filled text-warning me-1"></i></span>
                    <a href="#"><i class="ti ti-trash text-white"></i></a>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close bg-transparent fs-16 text-white position-static" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <h5 class="mb-2">Task Details</h5>
                    <div class="border rounded mb-3 p-2">
                        <div class="row row-gap-3">
                            <div class="col-md-4">
                                <div class="text-center">
                                    <span class="d-block mb-1">Created On</span>
                                    <p class="text-dark">22 July 2025</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <span class="d-block mb-1">Due Date</span>
                                    <p class="text-dark">22 July 2025</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <span class="d-block mb-1">Status</span>
                                    <span class="badge badge-soft-success d-inline-flex align-items-center">
                                        <i class="fas fa-circle fs-6 me-1"></i>Completed
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <h5 class="mb-2">Description</h5>
                        <p>Hiking is a long, vigorous walk, usually on trails or footpaths in the countryside. Walking for pleasure developed in Europe during the eighteenth century. Religious pilgrimages have existed much longer but they involve walking
                            long distances for a spiritual purpose associated with specific religions and also we achieve inner peace while we hike at a local park.
                        </p>
                    </div>
                    <div class="mb-3">
                        <h5 class="mb-2">Tags</h5>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-danger me-2">Internal</span>
                            <span class="badge bg-success me-2">Projects</span>
                            <span class="badge bg-secondary">Reminder</span>
                        </div>
                    </div>
                    <div>
                        <h5 class="mb-2">Assignee</h5>
                        <div class="avatar-list-stacked avatar-group-sm">
                            <span class="avatar avatar-rounded">
                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" alt="img">
                            </span>
                            <span class="avatar avatar-rounded">
                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="img">
                            </span>
                            <span class="avatar avatar-rounded">
                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" alt="img">
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Todo Details end -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['todo']))
    <!-- Add Todo -->
    <div class="modal fade" id="add_todo">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add New Todo</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{url('todo')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Todo Title</label>
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Tag</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Internal</option>
                                        <option>Projects</option>
                                        <option>Meetings</option>
                                        <option>Reminder</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Priority</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Medium</option>
                                        <option>High</option>
                                        <option>Low</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="mb-3">
                                    <label class="form-label">Descriptions</label>
                                    <div class="snow-editor"></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Add Assignee</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Sophie</option>
                                        <option>Cameron</option>
                                        <option>Doris</option>
                                        <option>Rufana</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-0">
                                    <label class="form-label">Status</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Completed</option>
                                        <option>Pending</option>
                                        <option>Onhold</option>
                                        <option>Inprogress</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add New Todo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Add Todo end -->

    <!-- Edit Todo -->
    <div class="modal fade" id="edit_todo">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit Todo</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="{{url('todo')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Todo Title</label>
                                    <input type="text" class="form-control" value="Update calendar and schedule">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Tag</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option selected>Internal</option>
                                        <option>Projects</option>
                                        <option>Meetings</option>
                                        <option>Reminder</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Priority</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>High</option>
                                        <option selected>Medium</option>
                                        <option>Low</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="mb-3">
                                    <label class="form-label">Descriptions</label>
                                    <div class="snow-editor"></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Add Assignee</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option selected>Sophie</option>
                                        <option>Cameron</option>
                                        <option>Doris</option>
                                        <option>Rufana</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-0">
                                    <label class="form-label">Status</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option selected>Completed</option>
                                        <option>Pending</option>
                                        <option>Onhold</option>
                                        <option>Inprogress</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Edit Todo end -->

    <!-- Todo Details -->
    <div class="modal fade" id="view_todo">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title me-1">Plan tasks for the next day</h4>
                    <span class="badge bg-danger d-inline-flex align-items-center p-1 me-1"><i class="ti ti-square me-1"></i>Urgent</span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h5 class="mb-2">Task Details</h5>
                    <div class="border rounded mb-3 p-2">
                        <div class="row row-gap-3">
                            <div class="col-md-4">
                                <div class="text-center">
                                    <span class="d-block mb-1">Created On</span>
                                    <p class="text-dark mb-0">22 July 2025</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <span class="d-block mb-1">Due Date</span>
                                    <p class="text-dark mb-0">22 July 2025</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <span class="d-block mb-1">Status</span>
                                    <span class="badge badge-soft-success d-inline-flex align-items-center">
                                        <i class="fas fa-circle fs-6 me-1"></i>Completed
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <h5 class="mb-2">Description</h5>
                        <p>Hiking is a long, vigorous walk, usually on trails or footpaths in the countryside. Walking for pleasure developed in Europe during the eighteenth century. Religious pilgrimages have existed much longer but they involve walking
                            long distances for a spiritual purpose associated with specific religions and also we achieve inner peace while we hike at a local park.
                        </p>
                    </div>
                    <div class="mb-3">
                        <h5 class="mb-2">Tags</h5>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-danger me-2">Internal</span>
                            <span class="badge bg-success me-2">Projects</span>
                            <span class="badge bg-secondary">Reminder</span>
                        </div>
                    </div>
                    <div>
                        <h5 class="mb-2">Assignee</h5>
                        <div class="avatar-list-stacked avatar-group-sm">
                            <span class="avatar avatar-rounded">
                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" alt="img">
                            </span>
                            <span class="avatar avatar-rounded">
                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="img">
                            </span>
                            <span class="avatar avatar-rounded">
                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" alt="img">
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Todo Details end -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['notes']))
    <!-- Start Add Note -->
    <div class="modal fade" id="add_note">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add Notes</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{url('notes')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Note Title</label>
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Assignee</label>
                                    <select class="select">
                                        <option>Choose</option>
                                        <option>Kathleen</option>
                                        <option>Gifford</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Tag</label>
                                    <input class="form-control" placeholder="Add new" type="text" data-choices data-choices-limit="3" data-choices-removeItem name="Label">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Priority</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Medium</option>
                                        <option>High</option>
                                        <option>Low</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="input-blocks todo-calendar">
                                    <label class="form-label">Due Date</label>
                                    <div class="input-group input-group-flat">
                                        <input type="text" class="form-control form-control" data-provider="flatpickr" data-date-format="d-m-Y">
                                        <span class="input-group-text">
                                            <i class="ti ti-calendar"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Active</option>
                                        <option>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="mb-0 summer-description-box">
                                    <label class="form-label">Descriptions</label>
                                    <div class="snow-editor mb-2"></div>
                                    <small>Maximum 60 Characters</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Note -->

    <!-- Start Edit Note -->
    <div class="modal fade" id="edit-note-units">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit Notes</h4>
                    <button type="button" class="btn-close btn-close-modal" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{url('notes')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Note Title</label>
                                    <input type="text" class="form-control" value="Team meet at Starbucks">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Assignee</label>
                                    <select class="select">
                                        <option>Choose</option>
                                        <option selected>Kathleen</option>
                                        <option>Gifford</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Tag</label>
                                    <input class="input-tags form-control" placeholder="Add new" type="text" data-choices data-choices-limit="3" data-choices-removeItem name="Label">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Priority</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option selected>Medium</option>
                                        <option>High</option>
                                        <option>Low</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="input-blocks todo-calendar">
                                    <label class="form-label">Due Date</label>                                        
                                    <div class="input-group input-group-flat">
                                        <input type="text" class="form-control form-control" data-provider="flatpickr" data-date-format="d-m-Y">
                                        <span class="input-group-text">
                                            <i class="ti ti-calendar"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option selected>Active</option>
                                        <option>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="mb-0 summer-description-box">
                                    <label class="form-label">Descriptions</label>
                                    <div class="snow-editor mb-2"></div>
                                    <small>Maximum 60 Characters</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Edit Note -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->

    <!-- Start View Note -->
    <div class="modal fade" id="view-note-units">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header">
                            <div class="d-flex align-items-center">
                                <h4 class="modal-title mb-0">Notes</h4>
                            </div>
                            <button type="button" class="btn-close btn-close-modal" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-12">
                                    <div>
                                        <h4 class="mb-2">Meet Lisa to discuss project details</h4>
                                        <p>Hiking is a long, vigorous walk, usually on trails or footpaths in the countryside. Walking for pleasure developed in Europe during the eighteenth century. Religious pilgrimages have existed much longer but they involve walking long distances for a spiritual purpose associated with specific religions and also we achieve inner peace while we hike at a local park.</p>
                                        <p class="badge badge-outline-danger d-inline-flex align-items-center mb-0"><i class="ti ti-circle-filled fs-7 me-1"></i> High</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <a href="#" class="btn btn-primary" data-bs-dismiss="modal">Close</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End View Note -->
@endif

@if (Route::is(['kanban-view']))
    <!-- Add Task -->
    <div id="add-task" class="modal fade">
        <div class="modal-dialog  modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{url('kanban-view')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Task Name <span class="text-danger">*</span></label>
                                    <input class="form-control" type="text" placeholder="Enter Task Name">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Select Employee <span class="text-danger">*</span></label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Shaun Farley</option>
                                        <option>Jenny Ellis</option>
                                        <option>Leon Baxter</option>
                                        <option>Karen Flores</option>
                                        <option>Charles Cline</option>
                                        <option>Aliza Duncan</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Status <span class="text-danger">*</span></label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Active</option>
                                        <option>Onhold</option>
                                        <option>Archieved</option>
                                        <option>Completed</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Priority <span class="text-danger">*</span></label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Low</option>
                                        <option>High</option>
                                        <option>Medium</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Start Date <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-flat">
                                        <input type="text" class="form-control form-control" data-provider="flatpickr" data-date-format="d-m-Y">
                                        <span class="input-group-text">
                                            <i class="ti ti-calendar"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Due Date <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-flat">
                                        <input type="text" class="form-control form-control" data-provider="flatpickr" data-date-format="d-m-Y">
                                        <span class="input-group-text">
                                            <i class="ti ti-calendar"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-0">
                                    <label class="form-label">Description <span class="text-danger">*</span></label>
                                    <textarea class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">Add Project</button>
                    </div>
                </form>
            </div>
        </div>
    </div>  <!-- end modal -->  

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  --> 
@endif

@if (Route::is(['file-manager']))
    <div class="modal fade" id="add_folder">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mb-0">Create Folder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{url('file-manager')}}">
                    <div class="modal-body">
                        <div class="mb-0">
                            <label class="form-label">Folder Name</label>
                            <input type="text" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add New Folder</button>
                    </div>
                </form>
            </div>
        </div>
    </div> <!-- end modal -->

    <div class="modal fade" id="add_member">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Members</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pb-2">
                    <div class="position-relative mb-3">
                        <input type="text" class="form-control" placeholder="Search Email">
                    </div>
                    <div class="form-check ps-0">
                        <label class="form-check-label member-check-list activate d-flex align-items-center justify-content-between p-2 rounded mb-1">
                            <span class="d-flex align-items-center text-dark">
                                <span>
                                    <img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="avatar avatar-md avatar-rounded me-2" alt="Img">
                                </span> Sophie
                            </span>
                            <input type="checkbox" class="form-check-input" checked>
                        </label>
                        <label class="form-check-label member-check-list d-flex align-items-center justify-content-between p-2 rounded mb-1">
                            <span class="d-flex align-items-center text-dark">
                                <span>
                                    <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" class="avatar avatar-md avatar-rounded me-2" alt="Img">
                                </span> Cameron
                            </span>
                            <input type="checkbox" class="form-check-input">
                        </label>
                        <label class="form-check-label member-check-list d-flex align-items-center justify-content-between p-2 rounded mb-1">
                            <span class="d-flex align-items-center text-dark">
                                <span>
                                    <img src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" class="avatar avatar-md avatar-rounded me-2" alt="Img">
                                </span> Doris
                            </span>
                            <input type="checkbox" class="form-check-input">
                        </label>
                        <label class="form-check-label member-check-list d-flex align-items-center justify-content-between p-2 rounded mb-1">
                            <span class="d-flex align-items-center text-dark">
                                <span>
                                    <img src="{{URL::asset('build/img/profiles/avatar-04.jpg')}}" class="avatar avatar-md avatar-rounded me-2" alt="Img">
                                </span> Rufana
                            </span>
                            <input type="checkbox" class="form-check-input">
                        </label>
                        <label class="form-check-label member-check-list d-flex align-items-center justify-content-between p-2 rounded mb-1">
                            <span class="d-flex align-items-center text-dark">
                                <span>
                                    <img src="{{URL::asset('build/img/profiles/avatar-04.jpg')}}" class="avatar avatar-md avatar-rounded me-2" alt="Img">
                                </span> Michael
                            </span>
                            <input type="checkbox" class="form-check-input">
                        </label>
                    </div>
                </div>

            </div>
        </div>
    </div> <!-- end modal -->

    <div class="sidebar-themesettings offcanvas offcanvas-end" id="preview">
        <div class="offcanvas-header d-flex align-items-center justify-content-between bg-dark">
            <div>
                <h4 class="mb-1 text-white">Preview</h4>
            </div>
            <div class="d-flex align-items-center">
                <a href="#" class="d-flex align-items-center justify-content-center me-3"><i class="ti ti-star-filled filled text-warning"></i></a>
                <a href="#" class="d-flex align-items-center justify-content-center text-white me-3"><i class="ti ti-trash"></i></a>
                <a href="#" class="custom-btn-close d-flex align-items-center justify-content-center text-white" data-bs-dismiss="offcanvas"><i class="ti ti-x"></i></a>
            </div>
        </div>
        <div class="offcanvas-body p-0">
            <div class="bg-light p-3 text-center">
                <div class="mb-2">
                    <img src="{{URL::asset('build/img/icons/pdf-icon.svg')}}" alt="icon">
                </div>
                <h6 class="mb-1">Document Final Proof Read<span class="badge bg-secondary fw-normal fs-10 ms-2">2.4 GB</span></h6>
                <p class="mb-0">Last Accessed on 15 Mar 2025, 08:15:23 PM</p>
            </div>
            <div class="p-3">
                <h6 class="mb-2">File Info</h6>
                <div class="border rounded d-flex align-items-center justify-content-between p-2 pb-0 gx-2 mb-3">
                    <div class="text-center mb-2 border-end pe-2">
                        <p class="fs-12 mb-0">File Type</p>
                        <p class="text-dark mb-0">PDF</p>
                    </div>
                    <div class="text-center mb-2 border-end pe-2">
                        <p class="fs-12 mb-0">Created on</p>
                        <p class="text-dark table-nowrap mb-0">22 Feb 2025, 08:30 PM</p>
                    </div>
                    <div class="text-center mb-2 border-0">
                        <p class="fs-12 mb-0">Location</p>
                        <p class="text-dark mb-0">Drive</p>
                    </div>
                </div>
                <div class="mb-3">
                    <h6 class="mb-2">Description</h6>
                    <div class="snow-editor"></div>
                </div>
                <h6 class="mb-2">Recent Activity</h6>
                <div class="card shadow-none">
                    <div class="card-body p-3 pb-0">
                        <h6 class="mb-3">Today</h6>
                        <ul class="recent-activity mb-3">
                            <li class="d-flex pb-2">
                                <span class="avatar avatar-md">
                                    <img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="rounded-circle" alt="img">
                                </span>
                                <div class="ms-2 flex-grow-1">
                                    <p class="mb-0"><span class="text-dark">Mercy</span> Added New File in <span class="text-dark">Drive</span></p>
                                    <p class="mb-0">05:22 PM</p>
                                    <div class="bg-light rounded p-2 d-flex align-items-center justify-content-between mt-1">
                                        <div class="d-flex align-items-center">
                                            <i class="ti ti-video text-dark fs-16"></i>
                                            <p class="ms-2 mb-0">All_files.mp4</p>
                                        </div>
                                        <span class="fs-12">8.2 MB</span>
                                    </div>
                                </div>
                            </li>
                            <li class="d-flex">
                                <span class="avatar avatar-md">
                                    <img src="{{URL::asset('build/img/profiles/avatar-15.jpg')}}" class="rounded-circle" alt="img">
                                </span>
                                <div class="ms-2 flex-grow-1">
                                    <p class="mb-0"><span class="text-dark">Druman</span> Added New File in <span class="text-dark">ROOT FOLDER</span></p>
                                    <p class="mb-0">05:23 PM</p>
                                    <div class="bg-light rounded p-2 d-flex align-items-center justify-content-between mt-1">
                                        <div class="d-flex align-items-center">
                                            <i class="ti ti-photo text-dark fs-16"></i>
                                            <p class="ms-2 mb-0">Screen.png</p>
                                        </div>
                                        <span class="fs-12">3.2 MB</span>
                                    </div>
                                    <div class="bg-light rounded p-2 d-flex align-items-center justify-content-between mt-1">
                                        <div class="d-flex align-items-center">
                                            <i class="ti ti-file-zip text-dark fs-16"></i>
                                            <p class="ms-2 mb-0">Finaldraft.zip</p>
                                        </div>
                                        <span class="fs-12">4 MB</span>
                                    </div>
                                    <div class="bg-light rounded p-2 d-flex align-items-center justify-content-between mt-1">
                                        <div class="d-flex align-items-center">
                                            <i class="ti ti-photo text-dark fs-16"></i>
                                            <p class="ms-2 mb-0">Photo.jpg</p>
                                        </div>
                                        <span class="fs-12">6.5 MB</span>
                                    </div>
                                </div>
                            </li>
                        </ul>
                        <h6 class="mb-2">28 Jan 2025</h6>
                        <ul class="recent-activity mb-3">
                            <li class="d-flex pb-2">
                                <span class="avatar avatar-md">
                                    <img src="{{URL::asset('build/img/profiles/avatar-05.jpg')}}" class="rounded-circle" alt="img">
                                </span>
                                <div class="ms-2 flex-grow-1">
                                    <p class="mb-0"><span class="text-dark">Mercy</span> Added New File in <span class="text-dark">Personal Assets</span></p>
                                    <p class="mb-0">05:22 PM</p>
                                    <div class="bg-light rounded p-2 d-flex align-items-center justify-content-between mt-1">
                                        <div class="d-flex align-items-center">
                                            <i class="ti ti-photo text-dark fs-16"></i>
                                            <p class="ms-2 mb-0">Photo_12.jpg</p>
                                        </div>
                                        <span class="fs-12">6.2 MB</span>
                                    </div>
                                </div>
                            </li>
                            <li class="d-flex">
                                <span class="avatar avatar-md">
                                    <img src="{{URL::asset('build/img/profiles/avatar-06.jpg')}}" class="rounded-circle" alt="img">
                                </span>
                                <div class="ms-2 flex-grow-1">
                                    <p class="mb-0"><span class="text-dark">Jackson</span> Added New File in <span class="text-dark">Drive</span></p>
                                    <p class="mb-0">05:23 PM</p>
                                    <div class="bg-light rounded p-2 d-flex align-items-center justify-content-between mt-1">
                                        <div class="d-flex align-items-center">
                                            <i class="ti ti-photo text-dark fs-16"></i>
                                            <p class="ms-2 mb-0">Photo.jpg</p>
                                        </div>
                                        <span class="fs-12">15.5 MB</span>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <h6 class="mb-2">Members</h6>
                    <a href="javascript:void(0);" class="fs-12 link-primary mb-2" data-bs-toggle="modal" data-bs-target="#add_member">Add Members</a>
                </div>
                <div class="card shadow-none mb-0">
                    <div class="card-body p-3 pb-0">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center mb-2">
                                <span class="avatar avatar-md">
                                    <img src="{{URL::asset('build/img/profiles/avatar-07.jpg')}}" class="rounded-circle" alt="img">
                                </span>
                                <div class="ms-2">
                                    <h6 class="fw-medium mb-1">Anthony Lewis</h6>
                                    <p class="fs-12 mb-0">Finance</p>
                                </div>
                            </div>
                            <a href="javascript:void(0);" class="user-icon mb-2">
                                <i class="ti ti-user-x fs-16"></i>
                            </a>
                        </div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center mb-2">
                                <span class="avatar avatar-md">
                                    <img src="{{URL::asset('build/img/profiles/avatar-06.jpg')}}" class="rounded-circle" alt="img">
                                </span>
                                <div class="ms-2">
                                    <h6 class="fw-medium mb-1">Harvey Smith</h6>
                                    <p class="fs-12 mb-0">Developer</p>
                                </div>
                            </div>
                            <a href="javascript:void(0);" class="user-icon mb-2">
                                <i class="ti ti-user-x fs-16"></i>
                            </a>
                        </div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center mb-2">
                                <span class="avatar avatar-md">
                                    <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" class="rounded-circle" alt="img">
                                </span>
                                <div class="ms-2">
                                    <h6 class="fw-medium mb-1">Stephan Peralt</h6>
                                    <p class="fs-12 mb-0">Executive Officer</p>
                                </div>
                            </div>
                            <a href="javascript:void(0);" class="user-icon mb-2">
                                <i class="ti ti-user-x fs-16"></i>
                            </a>
                        </div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center mb-2">
                                <span class="avatar avatar-md">
                                    <img src="{{URL::asset('build/img/profiles/avatar-08.jpg')}}" class="rounded-circle" alt="img">
                                </span>
                                <div class="ms-2">
                                    <h6 class="fw-medium mb-1">Doglas Martini</h6>
                                    <p class="fs-12 mb-0">Manager</p>
                                </div>
                            </div>
                            <a href="javascript:void(0);" class="user-icon mb-2">
                                <i class="ti ti-user-x fs-16"></i>
                            </a>
                        </div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center mb-2">
                                <span class="avatar avatar-md">
                                    <img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="rounded-circle" alt="img">
                                </span>
                                <div class="ms-2">
                                    <h6 class="fw-medium mb-1">Linda Ray</h6>
                                    <p class="fs-12 mb-0">Finance</p>
                                </div>
                            </div>
                            <a href="javascript:void(0);" class="user-icon mb-2">
                                <i class="ti ti-user-x fs-16"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- end offcanvas -->

    <div class="modal fade" id="delete-modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <span class="d-inline-flex align-items-center justify-content-center p-2 rounded-circle bg-danger mb-2"><i class="ti ti-trash fs-24 text-white"></i></span>
                    <h5 class="fw-medium mb-3">Are you sure to delete file?</h5>
                    <div class="d-flex">
                        <button type="button" class="btn btn-sm btn-light me-2 w-100" data-bs-dismiss="modal">Cancel</button>
                        <a href="{{url('file-manager')}}" class="btn btn-sm btn-primary w-100">Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- end modal -->
@endif

@if (Route::is(['doctors']))
    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative z-1">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-n1">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-n1">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('doctors')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->

    <!-- Start Success Modal  -->
    <div class="modal fade" id="success_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative z-1">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-success text-white"><i class="ti ti-check fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Added Successfully</h5>
                    <p class="mb-3">Doctor has been added to your List</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('doctor-details')}}" class="btn btn-primary position-relative z-1">View Details</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Success Modal  -->
@endif

@if (Route::is(['doctor-schedule']))
    <!-- Start Edit Modal -->
    <div id="doctor_detil" class="modal fade">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title">Schedule Details</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('doctor-schedule')}}">
                    <div class="modal-body pb-0">
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="d-flex align-items-center flex-wrap row-gap-3">
                                    <span class="avatar avatar-xxl me-3"><img src="{{URL::asset('build/img/doctors/doctor-01.jpg')}}" alt=""></span>
                                    <div>
                                        <span class="text-primary d-block">#DT2002</span>
                                        <div class="d-flex align-items-center mb-1">
                                            <h5 class="fw-bold mb-0">Dr. John Smith</h5>
                                            <span class="badge badge-soft-primary border border-primary ms-2">Cardiology</span>
                                        </div>
                                        <p>MBBS, M.D, Cardiology</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <div class="border-bottom mb-3">
                                <h6 class="fw-bold mb-3">Schedule Detail</h6>
                                <div class="row">
                                    <div class="col-lg-3 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Location</label>
                                            <select class="select">
                                                <option>Select</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">From</label>
                                            <div class="input-icon-end position-relative">  
                                                <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                                <span class="input-icon-addon">
                                                    <i class="ti ti-calendar"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">To</label>
                                            <div class="input-icon-end position-relative">  
                                                <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                                <span class="input-icon-addon">
                                                    <i class="ti ti-calendar"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Recures Every</label>
                                            <select class="select">
                                                <option>Select</option>
                                                <option>1 Week</option>
                                                <option>1 Month</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-3">Schedules</h6>
                                <ul class="nav nav-pills schedule-tab mb-3" id="pills-tab2" role="tablist">
                                    <li class="nav-item me-1" role="presentation">
                                        <button class="nav-link btn btn-sm btn-icon p-2 d-flex align-items-center justify-content-center w-auto active" data-bs-toggle="pill" data-bs-target="#schedules-8" type="button" role="tab" aria-selected="true">Monday</button>
                                    </li>
                                    <li class="nav-item me-1" role="presentation">
                                        <button class="nav-link btn btn-sm btn-icon p-2 d-flex align-items-center justify-content-center w-auto" data-bs-toggle="pill" data-bs-target="#schedules-9" type="button" role="tab" aria-selected="false" tabindex="-1">Tuesday</button>
                                    </li>
                                    <li class="nav-item me-1" role="presentation">
                                        <button class="nav-link btn btn-sm btn-icon p-2 d-flex align-items-center justify-content-center w-auto" data-bs-toggle="pill" data-bs-target="#schedules-10" type="button" role="tab" aria-selected="false" tabindex="-1">Wednesday</button>
                                    </li>
                                    <li class="nav-item me-1" role="presentation">
                                        <button class="nav-link btn btn-sm btn-icon p-2 d-flex align-items-center justify-content-center w-auto" data-bs-toggle="pill" data-bs-target="#schedules-11" type="button" role="tab" aria-selected="false" tabindex="-1">Thursday</button>
                                    </li>
                                    <li class="nav-item me-1" role="presentation">
                                        <button class="nav-link btn btn-sm btn-icon p-2 d-flex align-items-center justify-content-center w-auto" data-bs-toggle="pill" data-bs-target="#schedules-12" type="button" role="tab" aria-selected="false" tabindex="-1">Friday</button>
                                    </li>
                                    <li class="nav-item me-1" role="presentation">
                                        <button class="nav-link btn btn-sm btn-icon p-2 d-flex align-items-center justify-content-center w-auto" data-bs-toggle="pill" data-bs-target="#schedules-13" type="button" role="tab" aria-selected="false" tabindex="-1">Saturday</button>
                                    </li>
                                    <li class="nav-item me-1" role="presentation">
                                        <button class="nav-link btn btn-sm btn-icon p-2 d-flex align-items-center justify-content-center w-auto" data-bs-toggle="pill" data-bs-target="#schedules-14" type="button" role="tab" aria-selected="false" tabindex="-1">Sunday</button>
                                    </li>
                                </ul>
                                <div class="tab-content" id="pills-tabContent2">
                                    <div class="tab-pane fade active show" id="schedules-8" role="tabpanel">
                                        <div class="add-schedule-list">
                                            <div class="row gx-3">
                                                <div class="col-lg-5">
                                                    <div class="mb-3">
                                                        <label class="form-label">Session</label>
                                                        <select class="select">
                                                            <option>Select</option>
                                                            <option selected>Morning</option>
                                                            <option>Noon</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-lg-7">
                                                    <div class="row align-items-end gx-3">
                                                        <div class="col-lg-9">
                                                            <div class="row gx-3">
                                                                <div class="col-lg-6">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">From</label>
                                                                        <div class="input-icon-end position-relative">  
                                                                            <input type="text" class="form-control timepicker" placeholder="03 : 05  AM">
                                                                            <span class="input-icon-addon">
                                                                                <i class="ti ti-clock-hour-10"></i>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-6">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">To</label>
                                                                        <div class="input-icon-end position-relative">  
                                                                            <input type="text" class="form-control timepicker" placeholder="03 : 05  AM">
                                                                            <span class="input-icon-addon">
                                                                                <i class="ti ti-clock-hour-10"></i>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-3">
                                                            <div class="mb-3">
                                                                <a href="#" class="add-schedule-btn p-2 bg-light btn-icon text-dark rounded d-flex align-items-center justify-content-center">
                                                                    <i class="ti ti-plus fs-16"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="schedules-9" role="tabpanel">
                                        <div class="add-schedule-list">
                                            <div class="row gx-3">
                                                <div class="col-lg-5">
                                                    <div class="mb-3">
                                                        <label class="form-label">Session</label>
                                                        <select class="select">
                                                            <option>Select</option>
                                                            <option>Morning</option>
                                                            <option>Noon</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-lg-7">
                                                    <div class="row align-items-end gx-3">
                                                        <div class="col-lg-9">
                                                            <div class="row gx-3">
                                                                <div class="col-lg-6">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">From</label>
                                                                        <div class="input-icon-end position-relative">  
                                                                            <input type="text" class="form-control timepicker" placeholder="03 : 05  AM">
                                                                            <span class="input-icon-addon">
                                                                                <i class="ti ti-clock-hour-10"></i>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-6">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">To</label>
                                                                        <div class="input-icon-end position-relative">  
                                                                            <input type="text" class="form-control timepicker" placeholder="03 : 05  AM">
                                                                            <span class="input-icon-addon">
                                                                                <i class="ti ti-clock-hour-10"></i>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-3">
                                                            <div class="mb-3">
                                                                <a href="#" class="add-schedule-btn p-2 bg-light btn-icon text-dark rounded d-flex align-items-center justify-content-center">
                                                                    <i class="ti ti-plus fs-16"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="schedules-10" role="tabpanel">
                                        <div class="add-schedule-list">
                                            <div class="row gx-3">
                                                <div class="col-lg-5">
                                                    <div class="mb-3">
                                                        <label class="form-label">Session</label>
                                                        <select class="select">
                                                            <option>Select</option>
                                                            <option>Morning</option>
                                                            <option>Noon</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-lg-7">
                                                    <div class="row align-items-end gx-3">
                                                        <div class="col-lg-9">
                                                            <div class="row gx-3">
                                                                <div class="col-lg-6">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">From</label>
                                                                        <div class="input-icon-end position-relative">  
                                                                            <input type="text" class="form-control timepicker" placeholder="03 : 05  AM">
                                                                            <span class="input-icon-addon">
                                                                                <i class="ti ti-clock-hour-10"></i>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-6">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">To</label>
                                                                        <div class="input-icon-end position-relative">  
                                                                            <input type="text" class="form-control timepicker" placeholder="03 : 05  AM">
                                                                            <span class="input-icon-addon">
                                                                                <i class="ti ti-clock-hour-10"></i>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-3">
                                                            <div class="mb-3">
                                                                <a href="#" class="add-schedule-btn p-2 bg-light btn-icon text-dark rounded d-flex align-items-center justify-content-center">
                                                                    <i class="ti ti-plus fs-16"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="schedules-11" role="tabpanel">
                                        <div class="add-schedule-list">
                                            <div class="row gx-3">
                                                <div class="col-lg-5">
                                                    <div class="mb-3">
                                                        <label class="form-label">Session</label>
                                                        <select class="select">
                                                            <option>Select</option>
                                                            <option>Morning</option>
                                                            <option>Noon</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-lg-7">
                                                    <div class="row align-items-end gx-3">
                                                        <div class="col-lg-9">
                                                            <div class="row gx-3">
                                                                <div class="col-lg-6">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">From</label>
                                                                        <div class="input-icon-end position-relative">  
                                                                            <input type="text" class="form-control timepicker" placeholder="03 : 05  AM">
                                                                            <span class="input-icon-addon">
                                                                                <i class="ti ti-clock-hour-10"></i>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-6">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">To</label>
                                                                        <div class="input-icon-end position-relative">  
                                                                            <input type="text" class="form-control timepicker" placeholder="03 : 05  AM">
                                                                            <span class="input-icon-addon">
                                                                                <i class="ti ti-clock-hour-10"></i>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-3">
                                                            <div class="mb-3">
                                                                <a href="#" class="add-schedule-btn p-2 bg-light btn-icon text-dark rounded d-flex align-items-center justify-content-center">
                                                                    <i class="ti ti-plus fs-16"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="schedules-12" role="tabpanel">
                                        <div class="add-schedule-list">
                                            <div class="row gx-3">
                                                <div class="col-lg-5">
                                                    <div class="mb-3">
                                                        <label class="form-label">Session</label>
                                                        <select class="select">
                                                            <option>Select</option>
                                                            <option>Morning</option>
                                                            <option>Noon</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-lg-7">
                                                    <div class="row align-items-end gx-3">
                                                        <div class="col-lg-9">
                                                            <div class="row gx-3">
                                                                <div class="col-lg-6">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">From</label>
                                                                        <div class="input-icon-end position-relative">  
                                                                            <input type="text" class="form-control timepicker" placeholder="03 : 05  AM">
                                                                            <span class="input-icon-addon">
                                                                                <i class="ti ti-clock-hour-10"></i>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-6">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">To</label>
                                                                        <div class="input-icon-end position-relative">  
                                                                            <input type="text" class="form-control timepicker" placeholder="03 : 05  AM">
                                                                            <span class="input-icon-addon">
                                                                                <i class="ti ti-clock-hour-10"></i>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-3">
                                                            <div class="mb-3">
                                                                <a href="#" class="add-schedule-btn p-2 bg-light btn-icon text-dark rounded d-flex align-items-center justify-content-center">
                                                                    <i class="ti ti-plus fs-16"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="schedules-13" role="tabpanel">
                                        <div class="add-schedule-list">
                                            <div class="row gx-3">
                                                <div class="col-lg-5">
                                                    <div class="mb-3">
                                                        <label class="form-label">Session</label>
                                                        <select class="select">
                                                            <option>Select</option>
                                                            <option>Morning</option>
                                                            <option>Noon</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-lg-7">
                                                    <div class="row align-items-end gx-3">
                                                        <div class="col-lg-9">
                                                            <div class="row gx-3">
                                                                <div class="col-lg-6">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">From</label>
                                                                        <div class="input-icon-end position-relative">  
                                                                            <input type="text" class="form-control timepicker" placeholder="03 : 05  AM">
                                                                            <span class="input-icon-addon">
                                                                                <i class="ti ti-clock-hour-10"></i>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-6">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">To</label>
                                                                        <div class="input-icon-end position-relative">  
                                                                            <input type="text" class="form-control timepicker" placeholder="03 : 05  AM">
                                                                            <span class="input-icon-addon">
                                                                                <i class="ti ti-clock-hour-10"></i>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-3">
                                                            <div class="mb-3">
                                                                <a href="#" class="add-schedule-btn p-2 bg-light btn-icon text-dark rounded d-flex align-items-center justify-content-center">
                                                                    <i class="ti ti-plus fs-16"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="schedules-14" role="tabpanel">
                                        <div class="add-schedule-list">
                                            <div class="row gx-3">
                                                <div class="col-lg-5">
                                                    <div class="mb-3">
                                                        <label class="form-label">Session</label>
                                                        <select class="select">
                                                            <option>Select</option>
                                                            <option>Morning</option>
                                                            <option>Noon</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-lg-7">
                                                    <div class="row align-items-end gx-3">
                                                        <div class="col-lg-9">
                                                            <div class="row gx-3">
                                                                <div class="col-lg-6">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">From</label>
                                                                        <div class="input-icon-end position-relative">  
                                                                            <input type="text" class="form-control timepicker" placeholder="03 : 05  AM">
                                                                            <span class="input-icon-addon">
                                                                                <i class="ti ti-clock-hour-10"></i>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-6">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">To</label>
                                                                        <div class="input-icon-end position-relative">  
                                                                            <input type="text" class="form-control timepicker" placeholder="03 : 05  AM">
                                                                            <span class="input-icon-addon">
                                                                                <i class="ti ti-clock-hour-10"></i>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-3">
                                                            <div class="mb-3">
                                                                <a href="#" class="add-schedule-btn p-2 bg-light btn-icon text-dark rounded d-flex align-items-center justify-content-center">
                                                                    <i class="ti ti-plus fs-16"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Edit Modal -->
@endif

@if (Route::is(['patients']))
    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['patients-grid']))
    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['patient-details']))
    <!-- Start View Details -->
    <div class="offcanvas offcanvas-offset offcanvas-end" tabindex="-1" id="view_details">
        <div class="offcanvas-header d-block pb-0 px-0">
            <div class="border-bottom d-flex align-items-center justify-content-between pb-3 px-3">
                <h5 class="offcanvas-title fs-18 fw-bold">Appointment Details <span class="badge badge-soft-primary border pt-1 px-2 border-primary fw-medium ms-2">#AP544658</span></h5>
                <button type="button" class="btn-close custom-btn-close opacity-100" data-bs-dismiss="offcanvas" aria-label="Close"></button>            </div>
        </div>
        <div class="offcanvas-body pt-0 px-0">
            <h6 class="bg-light py-2 px-3 fw-bold"> When & Where </h6>
            <div class="px-3 my-4">
                <div class="bg-light p-3 mb-3 border rounded-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <a href="javascript:void(0);" class="avatar avatar-md me-2">
                            <img src="{{URL::asset('build/img/doctors/doctor-03.jpg')}}" alt="product" class="rounded-circle">
                        </a>
                        <a href="javascript:void(0);" class="text-dark fw-semibold">Dr. Emily Carter <span class="text-body fs-13 fw-normal d-block">Pediatrician </span>  </a>
                    </div>
                    <div class="flex-shrink-0">
                        <a href="javascript:void(0);" class="btn btn-outline-white bg-white fs-14 d-inline-flex border rounded-2 p-1 me-1">
                            <i class="ti ti-brand-hipchat"></i>
                        </a>
                        <a href="javascript:void(0);" class="btn btn-outline-white bg-white shadow-sm fs-14 d-inline-flex border rounded-2 p-1 me-1">
                            <i class="ti ti-video"></i>
                        </a>
                    </div>
                </div>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Appointment On <span class="text-body fw-normal"> Saturday, 25 Apr 2025  </span> </p>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Time <span class="text-body fw-normal"> 09:00 AM - 11:00 AM  </span> </p>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Location <span class="text-body fw-normal">Newyork , USA   </span> </p>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Appointment Type <span class="text-body fw-normal"> Online Consultation </span> </p>
                <div class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Patient Details 
                    <div class="text-body fw-normal d-flex align-items-center"> 
                        <div class="avatar avatar-xs flex-shrink-0">
                            <img src="{{URL::asset('build/img/users/avatar-2.jpg')}}" alt="" class="rounded-circle me-1 flex-shrink-0">
                        </div>
                            James Adrian 
                    </div> 
                </div>
            </div>
            <h6 class="bg-light py-2 px-3 text-dark fw-bold"> Appointment Details  </h6>
            <div class="px-3 my-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center"> 
                        Telehealth 
                        <label class="d-flex align-items-center form-switch ps-1">
                            <input class="form-check-input m-0 me-2" type="checkbox" checked>
                        </label>
                    </div>
                    <div>  <a href="{{url('appointment-consultations')}}" class="btn-primary btn btn-sm rounded d-flex align-items-center"> <i class="ti ti-video me-1"></i> Start </a></div>
                </div>
                <div class="row align-items-center">
                    <div class="col-lg-6 col-md-6">
                        <p class="text-dark"> Status </p>
                    </div>
                    
                    <div class="col-lg-6 col-md-6"> 
                        <div class="mb-3">
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Pending
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Select">
                                        </div>
                                    </div>
                                    <ul class="mb-0 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Checked Out
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Checked In
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Cancelled
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Scheduled
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <!-- End Add New Appointment-->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['appointments']))
    <!-- Start View Details -->
    <div class="offcanvas offcanvas-offset offcanvas-end" tabindex="-1" id="view_details">
        <div class="offcanvas-header d-block pb-0 px-0">
            <div class="border-bottom d-flex align-items-center justify-content-between pb-3 px-3">
                <h5 class="offcanvas-title fs-18 fw-bold">Appointment Details <span class="badge badge-soft-primary border pt-1 px-2 border-primary fw-medium ms-2">#AP544658</span></h5>
                <button type="button" class="btn-close custom-btn-close opacity-100" data-bs-dismiss="offcanvas" aria-label="Close"></button></div>
        </div>
        <div class="offcanvas-body pt-0 px-0">
            <h6 class="bg-light py-2 px-3 fw-bold"> When & Where </h6>
            <div class="px-3 my-4">
                <div class="bg-light p-3 mb-3 border rounded-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <a href="javascript:void(0);" class="avatar avatar-md me-2">
                            <img src="{{URL::asset('build/img/doctors/doctor-03.jpg')}}" alt="product" class="rounded-circle">
                        </a>
                        <a href="javascript:void(0);" class="text-dark fw-semibold">Dr. Emily Carter <span class="text-body fs-13 fw-normal d-block">Pediatrician </span>  </a>
                    </div>
                    <div class="flex-shrink-0">
                        <a href="javascript:void(0);" class="btn btn-outline-white bg-white fs-14 d-inline-flex border rounded-2 p-1 me-1">
                            <i class="ti ti-brand-hipchat"></i>
                        </a>
                        <a href="javascript:void(0);" class="btn btn-outline-white bg-white shadow-sm fs-14 d-inline-flex border rounded-2 p-1 me-1">
                            <i class="ti ti-video"></i>
                        </a>
                    </div>
                </div>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Appointment On <span class="text-body fw-normal"> Saturday, 25 Apr 2025  </span> </p>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Time <span class="text-body fw-normal"> 09:00 AM - 11:00 AM  </span> </p>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Location <span class="text-body fw-normal">Newyork , USA   </span> </p>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Appointment Type <span class="text-body fw-normal"> Online Consultation </span> </p>
                <div class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Patient Details 
                    <div class="text-body fw-normal d-flex align-items-center"> 
                        <div class="avatar avatar-xs flex-shrink-0">
                            <img src="{{URL::asset('build/img/users/avatar-2.jpg')}}" alt="" class="rounded-circle me-1 flex-shrink-0">
                        </div>
                            James Adrian 
                    </div> 
                </div>
            </div>
            <h6 class="bg-light py-2 px-3 text-dark fw-bold"> Appointment Details  </h6>
            <div class="px-3 my-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center"> 
                        Telehealth 
                        <label class="d-flex align-items-center form-switch ps-1">
                            <input class="form-check-input m-0 me-2" type="checkbox" checked>
                        </label>
                    </div>
                    <div>  <a href="{{url('appointment-consultations')}}" class="btn-primary btn btn-sm rounded d-flex align-items-center"> <i class="ti ti-video me-1"></i> Start </a></div>
                </div>
                <div class="row align-items-center">
                    <div class="col-lg-6 col-md-6">
                        <p class="text-dark"> Status </p>
                    </div>
                    
                    <div class="col-lg-6 col-md-6"> 
                        <div class="mb-3">
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Pending
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Select">
                                        </div>
                                    </div>
                                    <ul class="mb-0 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Checked Out
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Checked In
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Cancelled
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Scheduled
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <!-- End Add New Appointment-->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['new-appointment']))
    <!-- Start Add modal -->
    <div class="modal fade" id="add_modal">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark fw-bold">Add New Patient</h5>
                    <button type="button" class="btn-close custom-btn-close opacity-100" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="modal-body pb-0">
                    <!-- form start -->
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
                    <!-- form end -->
                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm me-2 fs-13 fw-medium" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fs-13 fw-medium">Add New Patient</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Add modal  -->
@endif

@if (Route::is(['appointment-calendar']))
    <!-- Start Event -->
    <div class="modal fade" id="event_modal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark modal-bg">
                    <div class="modal-title text-white"><span id="eventTitle"></span></div>
                    <button type="button" class="btn-close btn-close-modal btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="d-flex align-items-center fw-medium text-black mb-3"><i class="ti ti-calendar-check text-default me-2"></i>26 Jul,2024 to 31 Jul,2024</p>
                    <p class="d-flex align-items-center fw-medium text-black mb-3"><i class="ti ti-calendar-check text-default me-2"></i>11:00 AM to 12:15 PM</p>
                    <p class="d-flex align-items-center fw-medium text-black mb-3"><i class="ti ti-map-pin-bolt text-default me-2"></i>Las Vegas, US</p>
                    <p class="d-flex align-items-center fw-medium text-black mb-0"><i class="ti ti-calendar-check text-default me-2"></i>A recurring or repeating event is simply any event that you will occur more than once on your calendar.</p>
                </div>
            </div>
        </div>
    </div>
    <!-- End Event -->
@endif

@if (Route::is(['appointment-consultations']))
    <!-- Start View Details -->
    <div class="offcanvas offcanvas-offset offcanvas-end" tabindex="-1"  id="view_details">
        <div class="offcanvas-header d-block pb-0 px-0">
            <div class="border-bottom d-flex align-items-center justify-content-between pb-3 px-3">
                <h5 class="offcanvas-title fs-18 fw-bold">Appointment Details <span class="badge badge-soft-primary border pt-1 px-2 border-primary fw-medium ms-2">#AP544658</span></h5>
                <button type="button" class="btn-close custom-btn-close opacity-100" data-bs-dismiss="offcanvas" aria-label="Close"></button></div>
        </div>
        <div class="offcanvas-body pt-0 px-0">
            <h6 class="bg-light py-2 px-3 fw-bold"> When & Where </h6>
            <div class="px-3 my-4">
                <div class="bg-light p-3 mb-3 border rounded-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <a href="javascript:void(0);" class="avatar avatar-md me-2">
                            <img src="{{URL::asset('build/img/doctors/doctor-03.jpg')}}" alt="product" class="rounded-circle">
                        </a>
                        <a href="javascript:void(0);" class="text-dark fw-semibold">Dr. Emily Carter <span class="text-body fs-13 fw-normal d-block">Pediatrician </span>  </a>
                    </div>
                    <div class="flex-shrink-0">
                        <a href="javascript:void(0);" class="btn btn-outline-white bg-white fs-14 d-inline-flex border rounded-2 p-1 me-1">
                            <i class="ti ti-brand-hipchat"></i>
                        </a>
                        <a href="javascript:void(0);" class="btn btn-outline-white bg-white shadow-sm fs-14 d-inline-flex border rounded-2 p-1 me-1">
                            <i class="ti ti-video"></i>
                        </a>
                    </div>
                </div>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Appointment On <span class="text-body fw-normal"> Saturday, 25 Apr 2025  </span> </p>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Time <span class="text-body fw-normal"> 09:00 AM - 11:00 AM  </span> </p>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Location <span class="text-body fw-normal">Newyork , USA   </span> </p>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Appointment Type <span class="text-body fw-normal"> Online Consultation </span> </p>
                <div class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Patient Details 
                    <div class="text-body fw-normal d-flex align-items-center"> 
                    <div class="avatar avatar-xs">
                        <img src="{{URL::asset('build/img/users/avatar-2.jpg')}}" alt="" class="rounded-circle me-1">
                    </div>
                        James Adrian 
                    </div>
                </div>
            </div>
            <h6 class="bg-light py-2 px-3 text-dark fw-bold"> Appointment Details  </h6>
            <div class="px-3 my-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center"> 
                        Telehealth 
                        <label class="d-flex align-items-center form-switch ps-1">
                            <input class="form-check-input m-0 me-2" type="checkbox" checked>
                        </label>
                    </div>
                    <div>  <a href="{{url('appointment-consultations')}}" class="btn-primary btn btn-sm rounded d-flex align-items-center"> <i class="ti ti-video me-1"></i> Start </a></div>
                </div>
                <div class="row align-items-center">
                    <div class="col-lg-6 col-md-6">
                        <p class="text-dark"> Status </p>
                    </div>
                    
                    <div class="col-lg-6 col-md-6"> 
                        <div class="mb-3">
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Pending
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Select">
                                        </div>
                                    </div>
                                    <ul class="mb-0 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Checked Out
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Checked In
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Cancelled
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Scheduled
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <!-- End Add New Appointment-->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['locations']))
    <!-- Start Add modal -->
    <div class="modal fade" id="add_modal">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark fw-bold">New Location</h5>
                    <button type="button" class="btn-close custom-btn-close opacity-100" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="modal-body pb-0">

                    <!-- start row -->
                    <div class="row">
                    
                        <div class="col-lg-12">
                            <div class="mb-3 d-flex align-items-center">
                                <label class="form-label mb-0">Image</label>
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

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1">Name<span class="text-danger ms-1">*</span></label>
                                <input type="text" class="form-control">
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1">Location Type<span class="text-danger ms-1">*</span></label>
                                <select class="select"> 
                                    <option>Select</option>
                                    <option>In Person</option>
                                    <option>Video Consultation</option>
                                    <option>Both</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1">Email Address<span class="text-danger ms-1">*</span></label>
                                <input type="email" class="form-control">
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1">Phone Number<span class="text-danger ms-1">*</span></label>
                                <input type="tel" class="form-control">
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1">Address 1<span class="text-danger ms-1">*</span></label>
                                <input type="text" class="form-control">
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1">Address 2<span class="text-danger ms-1">*</span></label>
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
                    <!-- end row -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm me-2 fs-13 fw-medium" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fs-13 fw-medium">Add New Location</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Add modal  -->

    <!-- Start edit modal -->
    <div class="modal fade" id="edit_modal">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark fw-bold">Edit Location</h5>
                    <button type="button" class="btn-close custom-btn-close opacity-100" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="modal-body pb-0">

                    <!-- start row -->
                    <div class="row">
                    
                        <div class="col-lg-12">
                            <div class="mb-3 d-flex align-items-center">
                                <label class="form-label mb-0">Image</label>
                                <div class="drag-upload-btn avatar avatar-xxl rounded-circle bg-light text-muted position-relative overflow-hidden z-1 mb-2 ms-4 p-0">
                                    <img src="{{URL::asset('build/img/icons/clinic-08.svg')}}" class="position-relative z-n1" alt="img">
                                    <input type="file" class="form-control image-sign" multiple="">
                                    <div class="position-absolute bottom-0 end-0 star-0 w-100 h-25 bg-dark d-flex align-items-center justify-content-center z-n1">
                                        <a href="javascript:void(0);" class="text-white d-flex align-items-center justify-content-center">
                                            <i class="ti ti-photo fs-14"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1">Name<span class="text-danger ms-1">*</span></label>
                                <input type="text" class="form-control" value="James Carter">
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1">Location Type<span class="text-danger ms-1">*</span></label>
                                <select class="select"> 
                                    <option>In Person</option>
                                    <option>Video Consultation</option>
                                    <option>Both</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1">Email Address<span class="text-danger ms-1">*</span></label>
                                <input type="email" class="form-control" value="james@example.com">
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1">Phone Number<span class="text-danger ms-1">*</span></label>
                                <input type="tel" class="form-control" value="+1 43554 54584">
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1">Address 1<span class="text-danger ms-1">*</span></label>
                                <input type="text" class="form-control" value="123 Main Street">
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1">Address 2<span class="text-danger ms-1">*</span></label>
                                <input type="text" class="form-control" value=" Apartment 4B">
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1">Country<span class="text-danger ms-1">*</span></label>
                                <select class="select">
                                    <option>USA</option>
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
                                <input type="text" class="form-control" value="90001">
                            </div>
                        </div>
                         
                    </div>
                    <!-- end row -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm me-2 fs-13 fw-medium" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fs-13 fw-medium">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End edit modal  -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['services']))
    <!-- Start Add Modal -->
    <div id="add_service" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="text-dark modal-title fw-bold">New Service</h4>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('services')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Service Name<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option>General Medicine</option>
                                <option>Dentistry</option>
                                <option>Ophthalmology</option>
                                <option>Radiology</option>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Price<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add New Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Modal -->

    <!-- Start Add Modal -->
    <div id="edit_service" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="text-dark modal-title fw-bold">Edit Service</h4>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('services')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Service Name<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" value="General Consultation">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option>General Medicine</option>
                                <option>Dentistry</option>
                                <option>Ophthalmology</option>
                                <option>Radiology</option>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Price<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" value="$200">
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Modal -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_service">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative z-1">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-n1">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-n1">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('services')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['specializations']))
    <!-- Start Add Modal -->
    <div id="add_specialization" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="text-dark modal-title fw-bold">Add New Specialization</h4>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('specializations')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Specialization<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Specialization</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Modal -->

    <!-- Start Add Modal -->
    <div id="edit_specialization" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="text-dark modal-title fw-bold">Edit Specialization</h4>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('specializations')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Specialization<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" value="Cardiologist">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="3">Focuses on heart conditions in children.</textarea>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Modal -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_specialization">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative z-1">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-n1">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-n1">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('specializations')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['assets']))
    <!-- Start Add Asset -->
    <div class="modal fade" id="add_asset">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark fw-bold">Add Asset</h5>
                    <button type="button" class="btn-close custom-btn-close opacity-100" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <!-- start row -->
                    <div class="row">

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Asset Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Asset User <span class="text-danger">*</span></label>
                                <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Select
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Select">
                                        </div>
                                    </div>
                                    <ul class="mb-3 list-style-none">
                                        <li class="list-none">
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Emily Clark
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>John Carter
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-16.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Sophia White
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-15.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Michael Johnson
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-14.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Olivia Harris
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>David Anderson
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Purchase Date <span class="text-danger">*</span></label>
                                <div class="input-icon-end position-relative">  
                                    <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-calendar"></i>
                                    </span>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Purchase From <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium"> Manufacturer <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium"> Model <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium"> Serial Number <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium"> Supplier<span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select class="select form-control rounded w-100"> 
                                        <option>New one</option>
                                        <option>Old one</option>
                                        <option>Recent one</option>
                                    </select>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium"> Condition  <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select class="select form-control rounded w-100"> 
                                        <option>Select</option>
                                        <option>Good one</option>
                                        <option>Old one</option>
                                        <option>Recent one</option>
                                    </select>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">  warranty <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Value <span class="text-danger">* </span> </label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent text-dark fs-14">$</span>
                                <input type="text" class="form-control" placeholder="0">
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium"> Status <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select class="select form-control rounded w-100"> 
                                        <option>Select</option>
                                        <option>Approved</option>
                                        <option>Pending</option>
                                    </select>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <div>
                                    <label class="form-label mb-1 text-dark fs-14 fw-medium">Description <span class="text-danger">*</span> </label>
                                    <textarea rows="4" class="form-control rounded" placeholder="Description "> </textarea>
                                </div>
                            </div>
                        </div> <!-- end col -->
                    </div>
                    <!-- end row -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm me-2 fs-13 fw-medium" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fs-13 fw-medium">Add Asset</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Add Asset  -->

    <!-- Start Edit Asset -->
    <div class="modal fade" id="edit_asset">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark fw-bold">Edit Asset</h5>
                    <button type="button" class="btn-close custom-btn-close opacity-100" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <!-- start row -->
                    <div class="row">

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Asset Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="VitalScan Monitor">
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Asset User <span class="text-danger">*</span></label>
                                <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Select
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Emily clerk">
                                        </div>
                                    </div>
                                    <ul class="mb-3 list-style-none">
                                        <li class="list-none">
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Emily Clark
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>John Carter
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-16.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Sophia White
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-15.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Michael Johnson
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-14.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Olivia Harris
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>David Anderson
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Purchase Date <span class="text-danger">*</span></label>
                                <div class="input-icon-end position-relative">  
                                    <input type="text" class="form-control datetimepicker" placeholder="10/04/2025">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-calendar"></i>
                                    </span>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Purchase From <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="2547">
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium"> Manufacturer <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Endosys">
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium"> Model <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="CareKIT Pro">
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium"> Serial Number <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="ENW12547E789">
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium"> Supplier<span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select class="select form-control rounded w-100"> 
                                        <option>Endosys Customization</option>
                                        <option>New one</option>
                                        <option>Old one</option>
                                        <option>Recent one</option>
                                    </select>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium"> Condition  <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select class="select form-control rounded w-100"> 
                                        <option>Good one</option>
                                        <option>Old one</option>
                                        <option>Recent one</option>
                                    </select>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">  warranty <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="2 years">
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Value <span class="text-danger">* </span> </label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent text-dark fs-14">$</span>
                                <input type="text" class="form-control" placeholder="0" value="100">
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium"> Status <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select class="select form-control rounded w-100"> 
                                        <option>Approved</option>
                                        <option>Pending</option>
                                    </select>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <div>
                                    <label class="form-label mb-1 text-dark fs-14 fw-medium">Description <span class="text-danger">*</span> </label>
                                    <textarea rows="4" class="form-control rounded" placeholder=""> Evaluates the Autonomic Nervous System (ANS) to help identify disorders such as sudden death risk, silent heart attacks, hypertension, and syncope.​</textarea>
                                </div>
                            </div>
                        </div> <!-- end col -->
                    </div>
                    <!-- end row -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm me-2 fs-13 fw-medium" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fs-13 fw-medium">Add Asset</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Edit Asset  -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['doctors-appointments']))
    <!-- Start Add New Appointment -->
    <div class="offcanvas offcanvas-offset offcanvas-end" tabindex="-1"  id="new_appointment">
        <div class="offcanvas-header d-block pb-0 px-0">
            <div class="border-bottom d-flex align-items-center justify-content-between pb-3 px-3">
                <h5 class="offcanvas-title fs-18 fw-bold">New Appointment</h5>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="offcanvas" aria-label="Close"></button></div>
        </div>
        <div class="offcanvas-body pt-3">
            <form action="#">
                <!-- start row-->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Appointment ID <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control rounded bg-light" value="AP234354">
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Patient<span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Select
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Search">
                                        </div>
                                    </div>
                                    <ul class="mb-3 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Emily Clark
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>John Carter
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-16.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Sophia White
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-15.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Michael Johnson
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-14.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Olivia Harris
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>David Anderson
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Appointment Type <span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Select
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Select">
                                        </div>
                                    </div>
                                    <ul class="mb-3 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                In Person
                                            </label>
                                        </li>
                                        <li class="list-none">
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Online
                                            </label>
                                        </li>
                                        
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium"> Date of Appointment <span class="text-danger">*</span></label>
                            <div class="input-icon-end position-relative">  
                                <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                <span class="input-icon-addon">
                                    <i class="ti ti-calendar"></i>
                                </span>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium"> Time <span class="text-danger">*</span></label>
                            <div class="input-icon-end position-relative">  
                                <input type="text" class="form-control timepicker" placeholder="-- : --">
                                <span class="input-icon-addon">
                                    <i class="ti ti-clock"></i>
                                </span>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <div>
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Appointment Reason</label>
                                <textarea rows="4" class="form-control rounded"> </textarea>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Status<span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Select
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Select">
                                        </div>
                                    </div>
                                    <ul class="mb-3 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Checked Out
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Checked In
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Cancelled
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Scheduled
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col-->
                </div>
                <!-- end row-->
            </form>
        </div>
        <div class="offcanvas-footer mb-1 mt-3 p-3 border-1 border-top">
            <div class=" d-flex justify-content-end gap-2">
                <a href="javascript:void(0);" class="btn btn-light btm-md">Cancel</a>
                <button data-bs-dismiss="offcanvas" class="btn btn-primary btm-md" id="filter-submit">Create Create Appointment</button>
            </div>
        </div>
    </div>
    <!-- End Add New Appointment-->

    <!-- Start Edit New Appointment -->
    <div class="offcanvas offcanvas-offset offcanvas-end" tabindex="-1"  id="edit_appointment">
        <div class="offcanvas-header d-block pb-0 px-0">
            <div class="border-bottom d-flex align-items-center justify-content-between pb-3 px-3">
                <h5 class="offcanvas-title fs-18 fw-bold"> Edit Appointment</h5>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="offcanvas" aria-label="Close"></button></div>
        </div>
        <div class="offcanvas-body pt-3">
            <form action="#">
                <!-- start row-->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Appointment ID <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control rounded bg-light" value="AP234354">
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Patient<span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Emily Clark
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Search">
                                        </div>
                                    </div>
                                    <ul class="mb-3 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Emily Clark
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>John Carter
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-16.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Sophia White
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-15.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Michael Johnson
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-14.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Olivia Harris
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>David Anderson
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Appointment Type <span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    In Person
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Select">
                                        </div>
                                    </div>
                                    <ul class="mb-0 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                In Person
                                            </label>
                                        </li>
                                        <li class="list-none">
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Online
                                            </label>
                                        </li>
                                        
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium"> Date of Appointment <span class="text-danger">*</span></label>
                            <div class="input-icon-end position-relative">  
                                <input type="text" class="form-control datetimepicker" placeholder="20/08/2025">
                                <span class="input-icon-addon">
                                    <i class="ti ti-calendar"></i>
                                </span>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium"> Time <span class="text-danger">*</span></label>
                            <div class="input-icon-end position-relative">  
                                <input type="text" class="form-control timepicker" placeholder="01 : 20 : PM">
                                <span class="input-icon-addon">
                                    <i class="ti ti-clock"></i>
                                </span>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <div>
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Appointment Reason</label>
                                <textarea rows="4" class="form-control rounded"> An account of the present illness, which includes the circumstances surrounding the onset of recent health changes and the Purpose. </textarea>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Status<span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Checked Out
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Select">
                                        </div>
                                    </div>
                                    <ul class="mb-3 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Checked Out
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Checked In
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Cancelled
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Scheduled
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col-->
                </div>
                <!-- end row-->
            </form>
        </div>
        <div class="offcanvas-footer mb-1 mt-3 p-3 border-1 border-top">
            <div class=" d-flex justify-content-end gap-2">
                <a href="javascript:void(0);" class="btn btn-light btm-md">Cancel</a>
                <button data-bs-dismiss="offcanvas" class="btn btn-primary btm-md" id="filter-submit2">Create Create Appointment</button>
            </div>
        </div>
    </div>
    <!-- End Edit New Appointment-->

    <!-- Start View Details -->
    <div class="offcanvas offcanvas-offset offcanvas-end" tabindex="-1"  id="view_details">
        <div class="offcanvas-header d-block pb-0 px-0">
            <div class="border-bottom d-flex align-items-center justify-content-between pb-3 px-3">
                <h5 class="offcanvas-title fs-18 fw-bold">Appointment Details <span class="badge badge-soft-primary border pt-1 px-2 border-primary fw-medium ms-2">#AP544658</span></h5>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="offcanvas" aria-label="Close"></button>            </div>
        </div>
        <div class="offcanvas-body pt-0 px-0">
            <h6 class="bg-light py-2 px-3 text-dark fw-bold"> When & Where </h6>
            <div class="px-3 my-4">
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Appointment On <span class="text-body fw-normal"> Saturday, 25 Apr 2025  </span> </p>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Time <span class="text-body fw-normal"> 09:00 AM - 11:00 AM  </span> </p>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Location <span class="text-body fw-normal">Newyork , USA   </span> </p>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Appointment Type <span class="text-body fw-normal"> Online Consultation </span> </p>
                <div class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Patient Details 
                    <div class="text-body fw-normal d-flex align-items-center"> 
                        <span class="avatar avatar-sm">
                            <img src="{{URL::asset('build/img/users/avatar-2.jpg')}}" alt="" class="rounded-circle me-1">
                        </span>
                        James Adrian 
                    </div> 
                </div>
            </div>
            <h6 class="bg-light py-2 px-3 text-dark fw-bold"> Appointment Details  </h6>
            <div class="px-3 my-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center"> 
                        Telehealth 
                        <label class="d-flex align-items-center form-switch ps-1">
                            <input class="form-check-input m-0 me-2" type="checkbox" checked>
                        </label>
                    </div>
                    <div>  <a href="{{url('online-consultations')}}" class="btn-primary btn btn-sm rounded d-flex align-items-center"> <i class="ti ti-video me-1"></i> Start </a></div>
                </div>
                <div class="row align-items-center">
                    <div class="col-lg-6 col-md-6">
                        <p class="text-dark"> Status </p>
                    </div>
                    
                    <div class="col-lg-6 col-md-6"> 
                        <div class="mb-3">
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Pending
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Select">
                                        </div>
                                    </div>
                                    <ul class="mb-0 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Checked Out
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Checked In
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Cancelled
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Scheduled
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <!-- End Add New Appointment-->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['doctors-appointment-details']))
    <!-- Add New Event Start -->
    <div class="modal fade" id="add_event">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add New Event</h4>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('appointment-calendar')}}">
                    <div class="modal-body">
                        <!-- start row -->
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Event Name</label>
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Event Date</label>
                                    <div class="input-icon-end position-relative">
                                        <input type="text" class="form-control datetimepicker">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-calendar text-gray-7"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Start Time</label>
                                    <div class="input-icon-end position-relative">
                                        <input type="text" class="form-control timepicker">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-clock text-gray-7"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">End Time</label>
                                    <div class="input-icon-end position-relative">
                                        <input type="text" class="form-control timepicker">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-clock text-gray-7"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Event Location</label>
                                    <input type="text" class="form-control">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Descriptions</label>
                                    <textarea class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        <!-- end row -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-md btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-md btn-primary">Add Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Add New Event End -->

    <!-- Start Event -->
    <div class="modal fade" id="event_modal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark modal-bg">
                    <div class="modal-title text-white"><span id="eventTitle"></span></div>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <div class="modal-body">
                    <p class="d-flex align-items-center fw-medium text-black mb-3"><i class="ti ti-calendar-check text-default me-2"></i>26 Jul,2024 to 31 Jul,2024</p>
                    <p class="d-flex align-items-center fw-medium text-black mb-3"><i class="ti ti-calendar-check text-default me-2"></i>11:00 AM to 12:15 PM</p>
                    <p class="d-flex align-items-center fw-medium text-black mb-3"><i class="ti ti-map-pin-bolt text-default me-2"></i>Las Vegas, US</p>
                    <p class="d-flex align-items-center fw-medium text-black mb-0"><i class="ti ti-calendar-check text-default me-2"></i>A recurring or repeating event is simply any event that you will occur more than once on your calendar.</p>
                </div>
            </div>
        </div>
    </div>
    <!-- End Event -->

    <!-- Start Add New Appointment -->
    <div class="offcanvas offcanvas-offset offcanvas-end" tabindex="-1"  id="new_appointment">
        <div class="offcanvas-header d-block pb-0 px-0">
            <div class="border-bottom d-flex align-items-center justify-content-between pb-3 px-3">
                <h5 class="offcanvas-title fs-18 fw-bold">New Appointment</h5>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="offcanvas" aria-label="Close"></button></div>
        </div>
        <div class="offcanvas-body pt-3">
            <form action="#">

                <!-- start row-->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Appointment ID <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control rounded bg-light" value="AP234354">
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Patient<span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Select
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Search">
                                        </div>
                                    </div>
                                    <ul class="mb-3 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Emily Clark
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>John Carter
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-16.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Sophia White
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-15.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Michael Johnson
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-14.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Olivia Harris
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>David Anderson
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Appointment Type <span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Select
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Select">
                                        </div>
                                    </div>
                                    <ul class="mb-3 list-style-none">
                                        <li class="list-none">
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Online
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Member
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium"> Date of Appointment <span class="text-danger">*</span></label>
                            <div class="input-icon-end position-relative">  
                                <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                <span class="input-icon-addon">
                                    <i class="ti ti-calendar"></i>
                                </span>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium"> Time <span class="text-danger">*</span></label>
                            <div class="input-icon-end position-relative">  
                                <input type="text" class="form-control timepicker" placeholder="-- : --">
                                <span class="input-icon-addon">
                                    <i class="ti ti-clock"></i>
                                </span>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <div>
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Appointment Reason</label>
                                <textarea rows="4" class="form-control rounded"> </textarea>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Status<span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Select
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Select">
                                        </div>
                                    </div>
                                    <ul class="mb-3 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Checked Out
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Checked In
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Cancelled
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Scheduled
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col-->
                </div>
                <!-- end row-->

                
            </form>
        </div>
        <div class="offcanvas-footer mb-1 mt-3 p-3 border-1 border-top">
            <div class=" d-flex justify-content-end gap-2">
                <a href="javascript:void(0);" class="btn btn-light btm-md">Cancel</a>
                <button data-bs-dismiss="offcanvas" class="btn btn-primary btm-md" id="filter-submit">Create Create Appointment</button>
            </div>
        </div>
    </div>
    <!-- End Add New Appointment-->

    <!-- Start View Details -->
    <div class="offcanvas offcanvas-offset offcanvas-end" tabindex="-1" id="view_details">
        <div class="offcanvas-header d-block pb-0 px-0">
            <div class="border-bottom d-flex align-items-center justify-content-between pb-3 px-3">
                <h5 class="offcanvas-title fs-18 fw-bold">Appointment Details <span class="badge badge-soft-primary border pt-1 px-2 border-primary fw-medium ms-2">#AP544658</span></h5>
                <button type="button" class="btn-close custom-btn-close opacity-100" data-bs-dismiss="offcanvas" aria-label="Close"></button></div>
        </div>
        <div class="offcanvas-body pt-0 px-0">
            <h6 class="bg-light py-2 px-3 text-dark fw-bold"> When & Where </h6>
            <div class="px-3 my-4">
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Appointment On <span class="text-body fw-normal"> Saturday, 25 Apr 2025  </span> </p>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Time <span class="text-body fw-normal"> 09:00 AM - 11:00 AM  </span> </p>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Location <span class="text-body fw-normal">Newyork , USA   </span> </p>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Appointment Type <span class="text-body fw-normal"> Online Consultation </span> </p>
                <div class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Patient Details 
                    <div class="text-body fw-normal d-flex align-items-center"> 
                        <span class="avatar avatar-sm">
                            <img src="{{URL::asset('build/img/users/avatar-2.jpg')}}" alt="" class="rounded-circle me-1">
                        </span>
                        James Adrian 
                    </div> 
                </div>
            </div>
            <h6 class="bg-light py-2 px-3 text-dark fw-bold"> Appointment Details  </h6>
            <div class="px-3 my-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center"> 
                        Telehealth 
                        <label class="d-flex align-items-center form-switch ps-1">
                            <input class="form-check-input m-0 me-2" type="checkbox" checked>
                        </label>
                    </div>
                    <div>  <a href="" class="btn-primary btn btn-sm rounded d-flex align-items-center"> <i class="ti ti-video me-1"></i> Start </a></div>
                </div>
                <div class="row align-items-center">
                    <div class="col-lg-6 col-md-6">
                        <p class="text-dark"> Status </p>
                    </div>
                    
                    <div class="col-lg-6 col-md-6"> 
                        <div class="mb-0">
                            <select class="select form-control rounded w-100"> 
                                <option>Pending</option>
                                <option>Oldest</option>
                                <option>Recent</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <!-- End Add New Appointment-->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['online-consultations']))
    <!-- Start Phone Number-->
    <div class="modal fade" id="cancel-reason">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
					<h5 class="modal-title text-dark fw-bold">Cancel Reason</h5>
					<button type="button" class="btn-close custom-btn-close opacity-100" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
				<div class="modal-body">
					<div class="mb-3">
                        <label class="form-label mb-1 text-dark fs-14 fw-medium">Cancel Reason</label>
                        <select class="select form-control rounded">
                            <option>Select</option>
                            <option>Type-1</option>
                            <option>Type-2</option>
                            <option>Type-3</option>
                            <option>Type-4</option>
                        </select>
                    </div>
					<div class="mb-3">
						<div>
							<label class="form-label mb-1 text-dark fs-14 fw-medium">Comments</label>
							<textarea rows="4" class="form-control rounded"> </textarea>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light btn-sm me-2 fs-13 fw-medium" data-bs-dismiss="modal">Close</button>
					<button type="submit" class="btn btn-primary btn-sm fs-13 fw-medium">Cancel Appointment</button>
				</div>
			</div>
		</div>
    </div>
    <!-- End Phone Number -->
@endif

@if (Route::is(['doctors-prescriptions']))
    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['doctors-prescription-details']))
    <!-- Start Phone Number-->
    <div class="modal fade" id="cancel-leave">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark fw-bold">Cancel Reason</h5>
                    <button type="button" class="btn-close custom-btn-close opacity-100" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <div>
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Reason</label>
                            <textarea rows="4" class="form-control rounded" placeholder="Description"> </textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm me-2 fs-13 fw-medium" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fs-13 fw-medium">Cancel Leave</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Phone Number -->
@endif

@if (Route::is(['doctors-leaves']))
    <!-- Start Add leave -->
    <div class="modal fade" id="add-leave">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark fw-bold">Add New Leave</h5>
                    <button type="button" class="btn-close btn-close-modal" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <div class="modal-body">

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Leave Type<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        Select
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-lg p-2 dropdown-employee w-100">
                                        <li>
                                            <div class="mb-2">
                                                <input type="text" class="form-control form-control" placeholder="Search">
                                            </div>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Casual Leave
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                    Sick Leave
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Maternity Leave
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Paternity Leave
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Compensatory Leave
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Emergency Leave
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Bereavement Leave
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Study/Exam Leave
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Paid Leave
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Unpaid Leave
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">From Date<span class="text-danger">*</span></label>
                                <div class="input-icon-end position-relative">  
                                    <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-calendar"></i>
                                    </span>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">To Date<span class="text-danger">*</span></label>
                                <div class="input-icon-end position-relative">  
                                    <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-calendar"></i>
                                    </span>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">No of Days<span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <div class="row p-2 bg-light rounded align-items-center flex-wrap">
                                    <div class="col-lg-6">
                                        <label class="form-label mb-1 text-body fs-14 fw-medium">dd/mm/yyyy</label>
                                    </div>
                                    <div class="col-lg-6">
                                        <label class="form-label mb-1 text-dark fs-14 fw-medium">Leave Type<span class="text-danger">*</span></label>
                                            <div class="dropdown">
                                                <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                                    Select
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-lg p-2 dropdown-employee w-100">
                                                    <li>
                                                        <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            Applied
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                            <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                                Approved
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                            <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                                Rejected
                                                        </label>
                                                    </li>
                                                </ul>
                                            </div>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <div>
                                    <label class="form-label mb-1 text-dark fs-14 fw-medium">Reason</label>
                                    <textarea rows="4" class="form-control rounded" placeholder="Description"> </textarea>
                                </div>
                            </div>
                        </div> <!-- end col -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm me-2 fs-13 fw-medium" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fs-13 fw-medium">Add New Leave</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Add New leave  -->

    <!-- Start Cancel Modal -->
    <div class="modal fade" id="cancel-leave">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark fw-bold">Cancel Reason</h5>
                    <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <div>
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Reason</label>
                            <textarea rows="4" class="form-control rounded" placeholder="Description"> </textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm me-2 fs-13 fw-medium" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fs-13 fw-medium">Cancel Leave</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Cancel Modal  -->

    <!-- Start Add leave -->
    <div class="modal fade" id="edit-leave">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark fw-bold">Edit New Leave</h5>
                    <button type="button" class="btn-close btn-close-modal" data-bs-dismiss="modal" aria-label="Close"> <i class="ti ti-x"></i></button>
                </div>
                <div class="modal-body">

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Leave Type<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        Casual Leave
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-lg p-2 dropdown-employee w-100">
                                        <li>
                                            <div class="mb-2">
                                                <input type="text" class="form-control form-control" placeholder="Search">
                                            </div>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Casual Leave
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                    Sick Leave
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Maternity Leave
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Paternity Leave
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Compensatory Leave
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Emergency Leave
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Bereavement Leave
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Study/Exam Leave
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Paid Leave
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Unpaid Leave
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">From Date<span class="text-danger">*</span></label>
                                <div class="input-group position-relative">
                                    <input type="text" class="form-control datetimepicker" placeholder="20/09/2025">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-calendar text-body"></i>
                                    </span>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">To Date<span class="text-danger">*</span></label>
                                <div class="input-group position-relative">
                                    <input type="text" class="form-control datetimepicker" placeholder="20/10/2025">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-calendar text-body"></i>
                                    </span>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">No of Days<span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="30">
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <div class="row p-2 bg-light rounded align-items-center flex-wrap">
                                    <div class="col-lg-6">
                                        <label class="form-label mb-1 text-body fs-14 fw-medium">dd/mm/yyyy</label>
                                    </div>
                                    <div class="col-lg-6">
                                        <label class="form-label mb-1 text-dark fs-14 fw-medium">Leave Type<span class="text-danger">*</span></label>
                                            <div class="dropdown">
                                                <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                                    Applied
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-lg p-2 dropdown-employee w-100">
                                                    <li>
                                                        <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            Applied
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                            <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                                Approved
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                            <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                                Rejected
                                                        </label>
                                                    </li>
                                                </ul>
                                            </div>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <div>
                                    <label class="form-label mb-1 text-dark fs-14 fw-medium">Reason</label>
                                    <textarea rows="4" class="form-control rounded" placeholder="Description"> </textarea>
                                </div>
                            </div>
                        </div> <!-- end col -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm me-2 fs-13 fw-medium" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fs-13 fw-medium">Edit Leave</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Add New leave  -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['doctors-reviews']))
    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['doctors-notifications']))
    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_notification">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative z-1">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-n1">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-n1">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('doctors-notifications')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['patient-appointments']))
    <!-- Start Add New Appointment -->
    <div class="offcanvas offcanvas-offset offcanvas-end" tabindex="-1" id="new_appointment">
        <div class="offcanvas-header d-block pb-0 px-0">
            <div class="border-bottom d-flex align-items-center justify-content-between pb-3 px-3">
                <h5 class="offcanvas-title fs-18 fw-bold">New Appointment</h5>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="offcanvas" aria-label="Close"></button></div>
        </div>
        <div class="offcanvas-body pt-3">
            <form action="#">

                <!-- start row-->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Appointment ID <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control rounded bg-light" value="AP234354">
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Patient<span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Select
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Search">
                                        </div>
                                    </div>
                                    <ul class="mb-3 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Emily Clark
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>John Carter
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-16.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Sophia White
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-15.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Michael Johnson
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-14.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Olivia Harris
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>David Anderson
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Appointment Type <span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Select
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Select">
                                        </div>
                                    </div>
                                    <ul class="mb-3 list-style-none">
                                        <li class="list-none">
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Online
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Member
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium"> Date of Appointment <span class="text-danger">*</span></label>
                            <div class="input-icon-end position-relative">  
                                <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                <span class="input-icon-addon">
                                    <i class="ti ti-calendar"></i>
                                </span>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium"> Time <span class="text-danger">*</span></label>
                            <div class="input-icon-end position-relative">  
                                <input type="text" class="form-control timepicker" placeholder="-- : --">
                                <span class="input-icon-addon">
                                    <i class="ti ti-clock"></i>
                                </span>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <div>
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Appointment Reason</label>
                                <textarea rows="4" class="form-control rounded"> </textarea>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Status<span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Select
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Select">
                                        </div>
                                    </div>
                                    <ul class="mb-3 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Checked Out
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Checked In
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Cancelled
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Scheduled
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col-->
                </div>
                <!-- end row-->

                
            </form>
        </div>
        <div class="offcanvas-footer mb-1 mt-3 p-3 border-1 border-top">
            <div class=" d-flex justify-content-end gap-2">
                <a href="javascript:void(0);" class="btn btn-light btm-md">Cancel</a>
                <button data-bs-dismiss="offcanvas" class="btn btn-primary btm-md">Create Create Appointment</button>
            </div>
        </div>
    </div>
    <!-- End Add New Appointment-->

    <!-- Start View Details -->
    <div class="offcanvas offcanvas-offset offcanvas-end" tabindex="-1"  id="view_details">
        <div class="offcanvas-header d-block pb-0 px-0">
            <div class="border-bottom d-flex align-items-center justify-content-between pb-3 px-3">
                <h5 class="offcanvas-title fs-18 fw-bold">Appointment Details <span class="badge badge-soft-primary border pt-1 px-2 border-primary fw-medium ms-2">#AP544658</span></h5>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="offcanvas" aria-label="Close"></button></div>
        </div>
        <div class="offcanvas-body pt-0 px-0">
            <h6 class="bg-light py-2 px-3 text-dark fw-bold"> When & Where </h6>

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 bg-light mx-3 p-3 rounded my-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-lg">
                        <img src="{{URL::asset('build/img/doctors/doctor-01.jpg')}}" alt="doctor-01" class="img-fluid img1 rounded-circle">
                    </div>
                    <h6 class="fs-14 fw-semibold m-0"> Dr. Emily Carter <span class="d-block fs-13 fw-normal text-body pt-1"> Pediatrician </span></h6>
                </div>
                <div class="gap-1 d-flex">
                    <a href="" class="bg-white d-flex align-items-center justify-content-center p-1 border rounded"> <i class="ti ti-brand-hipchat fs-13"></i></a>
                    <a href="" class="bg-white d-flex align-items-center justify-content-center p-1 border rounded"> <i class="ti ti-video fs-13"></i></a>
                </div>
            </div>

            <div class="px-3 my-4">
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Appointment On <span class="text-body fw-normal"> Saturday, 25 Apr 2025  </span> </p>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Time <span class="text-body fw-normal"> 09:00 AM - 11:00 AM  </span> </p>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Location <span class="text-body fw-normal">Newyork , USA   </span> </p>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Appointment Type <span class="text-body fw-normal"> Online Consultation </span> </p>
            </div>
            <h6 class="bg-light py-2 px-3 text-dark fw-bold"> Appointment Details  </h6>
            <div class="px-3 my-3">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center"> 
                        Telehealth 
                        <label class="d-flex align-items-center form-switch ps-1">
                            <input class="form-check-input m-0 me-2" type="checkbox" checked>
                        </label>
                    </div>
                    <div>  <a href="{{url('online-consultations')}}" class="btn-primary btn btn-sm rounded d-flex align-items-center"> <i class="ti ti-video me-1 fs-13"></i> Start </a></div>
                </div>
                <div class="row align-items-center">
                    <div class="col-lg-6 col-md-6">
                        <p class="text-dark"> Status </p>
                    </div>
                    
                    <div class="col-lg-6 col-md-6"> 
                        <div class="mb-0">
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Pending
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Select">
                                        </div>
                                    </div>
                                    <ul class="mb-0 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Checked Out
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Checked In
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Cancelled
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Scheduled
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <!-- End Add New Appointment-->

    <!-- Start Edit New Appointment -->
    <div class="offcanvas offcanvas-offset offcanvas-end" tabindex="-1" id="edit_appointment">
        <div class="offcanvas-header d-block pb-0 px-0">
            <div class="border-bottom d-flex align-items-center justify-content-between pb-3 px-3">
                <h5 class="offcanvas-title fs-18 fw-bold"> Edit Appointment</h5>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="offcanvas" aria-label="Close"></button></div>
        </div>
        <div class="offcanvas-body pt-3">
            <form action="#">
                <!-- start row-->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Appointment ID <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control rounded bg-light" value="AP234354">
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Patient<span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Emily Clark
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Search">
                                        </div>
                                    </div>
                                    <ul class="mb-3 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Emily Clark
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>John Carter
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-16.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Sophia White
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-15.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Michael Johnson
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-14.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Olivia Harris
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>David Anderson
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Appointment Type <span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    In Person
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Select">
                                        </div>
                                    </div>
                                    <ul class="mb-0 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                In Person
                                            </label>
                                        </li>
                                        <li class="list-none">
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Online
                                            </label>
                                        </li>
                                        
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium"> Date of Appointment <span class="text-danger">*</span></label>
                            <div class="input-icon-end position-relative">  
                                <input type="text" class="form-control datetimepicker" placeholder="20/08/2025">
                                <span class="input-icon-addon">
                                    <i class="ti ti-calendar"></i>
                                </span>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium"> Time <span class="text-danger">*</span></label>
                            <div class="input-icon-end position-relative">  
                                <input type="text" class="form-control timepicker" placeholder="01 : 20 : PM">
                                <span class="input-icon-addon">
                                    <i class="ti ti-clock"></i>
                                </span>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <div>
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Appointment Reason</label>
                                <textarea rows="4" class="form-control rounded"> An account of the present illness, which includes the circumstances surrounding the onset of recent health changes and the Purpose. </textarea>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Status<span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Checked Out
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Select">
                                        </div>
                                    </div>
                                    <ul class="mb-3 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Checked Out
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Checked In
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Cancelled
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Scheduled
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col-->
                </div>
                <!-- end row-->
            </form>
        </div>
        <div class="offcanvas-footer mb-1 mt-3 p-3 border-1 border-top">
            <div class=" d-flex justify-content-end gap-2">
                <a href="javascript:void(0);" class="btn btn-light btm-md">Cancel</a>
                <button data-bs-dismiss="offcanvas" class="btn btn-primary btm-md">Create Create Appointment</button>
            </div>
        </div>
    </div>
    <!-- End Edit New Appointment-->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['patient-appointment-details']))
    <!-- Add New Event Start -->
    <div class="modal fade" id="add_event">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add New Event</h4>
                    <button type="button" class="btn-close btn-close-modal " data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{url('patient-appointment-details')}}">
                    <div class="modal-body">
                        <!-- start row -->
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Event Name</label>
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Event Date</label>
                                    <div class="input-icon-end position-relative">
                                        <input type="text" class="form-control datetimepicker">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-calendar text-gray-7"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Start Time</label>
                                    <div class="input-icon-end position-relative">
                                        <input type="text" class="form-control timepicker">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-clock text-gray-7"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">End Time</label>
                                    <div class="input-icon-end position-relative">
                                        <input type="text" class="form-control timepicker">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-clock text-gray-7"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Event Location</label>
                                    <input type="text" class="form-control">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Descriptions</label>
                                    <textarea class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        <!-- end row -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-md btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-md btn-primary">Add Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Add New Event End -->

    <!-- Start Event -->
    <div class="modal fade" id="event_modal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark modal-bg">
                    <div class="modal-title text-white"><span id="eventTitle"></span></div>
                    <button type="button" class="btn-close btn-close-modal text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="d-flex align-items-center fw-medium text-black mb-3"><i class="ti ti-calendar-check text-default me-2"></i>26 Jul,2024 to 31 Jul,2024</p>
                    <p class="d-flex align-items-center fw-medium text-black mb-3"><i class="ti ti-calendar-check text-default me-2"></i>11:00 AM to 12:15 PM</p>
                    <p class="d-flex align-items-center fw-medium text-black mb-3"><i class="ti ti-map-pin-bolt text-default me-2"></i>Las Vegas, US</p>
                    <p class="d-flex align-items-center fw-medium text-black mb-0"><i class="ti ti-calendar-check text-default me-2"></i>A recurring or repeating event is simply any event that you will occur more than once on your calendar.</p>
                </div>
            </div>
        </div>
    </div>
    <!-- End Event -->

    <!-- Start Add New Appointment -->
    <div class="offcanvas offcanvas-offset offcanvas-end" tabindex="-1" id="new_appointment">
        <div class="offcanvas-header d-block pb-0 px-0">
            <div class="border-bottom d-flex align-items-center justify-content-between pb-3 px-3">
                <h5 class="offcanvas-title fs-18 fw-bold">New Appointment</h5>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="offcanvas" aria-label="Close"></button></div>
        </div>
        <div class="offcanvas-body pt-3">
            <form action="#">

                <!-- start row-->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Appointment ID <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control rounded bg-light" value="AP234354">
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Patient<span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Select
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Search">
                                        </div>
                                    </div>
                                    <ul class="mb-3 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Emily Clark
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>John Carter
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-16.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Sophia White
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-15.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Michael Johnson
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-14.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Olivia Harris
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>David Anderson
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Appointment Type <span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Select
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Select">
                                        </div>
                                    </div>
                                    <ul class="mb-3 list-style-none">
                                        <li class="list-none">
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Online
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Member
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium"> Date of Appointment <span class="text-danger">*</span></label>
                            <div class="input-icon-end position-relative">  
                                <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                <span class="input-icon-addon">
                                    <i class="ti ti-calendar"></i>
                                </span>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium"> Time <span class="text-danger">*</span></label>
                            <div class="input-icon-end position-relative">  
                                <input type="text" class="form-control timepicker" placeholder="-- : --">
                                <span class="input-icon-addon">
                                    <i class="ti ti-clock"></i>
                                </span>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <div>
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Appointment Reason</label>
                                <textarea rows="4" class="form-control rounded"> </textarea>
                            </div>
                        </div>
                    </div> <!-- end col-->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Status<span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Select
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Select">
                                        </div>
                                    </div>
                                    <ul class="mb-3 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Checked Out
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Checked In
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Cancelled
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Scheduled
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col-->
                </div>
                <!-- end row-->

                
            </form>
        </div>
        <div class="offcanvas-footer mb-1 mt-3 p-3 border-1 border-top">
            <div class=" d-flex justify-content-end gap-2">
                <a href="javascript:void(0);" class="btn btn-light btm-md">Cancel</a>
                <button data-bs-dismiss="offcanvas" class="btn btn-primary btm-md" id="filter-submit">Create Create Appointment</button>
            </div>
        </div>
    </div>
    <!-- End Add New Appointment-->    
@endif

@if (Route::is(['patient-doctors']))
    <!-- Start Edit Modal -->
    <div id="edit_doctors" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title">Edit Date</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('patient-doctors')}}">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Date<span class="text-danger ms-1">*</span></label>
                            <div class="input-group position-relative">
                                <input type="text" class="form-control datetimepicker rounded-end h-auto py-2 bg-white" placeholder="30 Apr 2025">
                                <span class="input-icon-addon fs-16 text-dark">
                                    <i class="ti ti-calendar "></i>
                                </span>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Status<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option selected>Active</option>
                                <option>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Edit Modal -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['patients-doctor-details']))
    <!-- Start View Details -->
    <div class="offcanvas offcanvas-offset offcanvas-end" tabindex="-1" id="view_details">
        <div class="offcanvas-header d-block pb-0 px-0">
            <div class="border-bottom d-flex align-items-center justify-content-between pb-3 px-3">
                <h5 class="offcanvas-title fs-18 fw-bold">Appointment Details <span class="badge badge-soft-primary border pt-1 px-2 border-primary fw-medium ms-2">#AP544658</span></h5>
                <button type="button" class="btn-close custom-btn-close opacity-100" data-bs-dismiss="offcanvas" aria-label="Close"></button></div>
        </div>
        <div class="offcanvas-body pt-0 px-0">
            <h6 class="bg-light py-2 px-3 fw-bold"> When & Where </h6>
            <div class="px-3 my-4">
                <div class="bg-light p-3 mb-3 border rounded-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <a href="javascript:void(0);" class="avatar avatar-md me-2">
                            <img src="{{URL::asset('build/img/doctors/doctor-03.jpg')}}" alt="product" class="rounded-circle">
                        </a>
                        <a href="javascript:void(0);" class="text-dark fw-semibold">Dr. Emily Carter <span class="text-body fs-13 fw-normal d-block">Pediatrician </span>  </a>
                    </div>
                    <div class="flex-shrink-0">
                        <a href="javascript:void(0);" class="btn btn-outline-white bg-white fs-14 d-inline-flex border rounded-2 p-1 me-1">
                            <i class="ti ti-brand-hipchat"></i>
                        </a>
                        <a href="javascript:void(0);" class="btn btn-outline-white bg-white shadow-sm fs-14 d-inline-flex border rounded-2 p-1 me-1">
                            <i class="ti ti-video"></i>
                        </a>
                    </div>
                </div>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Appointment On <span class="text-body fw-normal"> Saturday, 25 Apr 2025  </span> </p>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Time <span class="text-body fw-normal"> 09:00 AM - 11:00 AM  </span> </p>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Location <span class="text-body fw-normal">Newyork , USA   </span> </p>
                <p class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Appointment Type <span class="text-body fw-normal"> Online Consultation </span> </p>
                <div class="text-dark mb-3 fw-semibold d-flex align-items-center justify-content-between"> Patient Details 
                    <div class="text-body fw-normal d-flex align-items-center"> 
                        <div class="avatar avatar-xs flex-shrink-0">
                            <img src="{{URL::asset('build/img/users/avatar-2.jpg')}}" alt="" class="rounded-circle me-1 flex-shrink-0">
                        </div>
                            James Adrian 
                    </div> 
                </div>
            </div>
            <h6 class="bg-light py-2 px-3 text-dark fw-bold"> Appointment Details  </h6>
            <div class="px-3 my-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center"> 
                        Telehealth 
                        <label class="d-flex align-items-center form-switch ps-1">
                            <input class="form-check-input m-0 me-2" type="checkbox" checked>
                        </label>
                    </div>
                    <div>  <a href="{{url('online-consultations')}}" class="btn-primary btn btn-sm rounded d-flex align-items-center"> <i class="ti ti-video me-1"></i> Start </a></div>
                </div>
                <div class="row align-items-center">
                    <div class="col-lg-6 col-md-6">
                        <p class="text-dark"> Status </p>
                    </div>
                    
                    <div class="col-lg-6 col-md-6"> 
                        <div class="mb-3">
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Pending
                                </a>
                                <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                    <div class="mb-3">
                                        <div class="input-icon-start position-relative">
                                            <span class="input-icon-addon fs-12">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="text" class="form-control form-control-sm" placeholder="Select">
                                        </div>
                                    </div>
                                    <ul class="mb-0 list-style-none">
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Checked Out
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Checked In
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Cancelled
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Scheduled
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <!-- End Add New Appointment-->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['patient-prescriptions']))
    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['patient-invoices']))
    <!-- Start Edit Modal -->
    <div id="edit_invoices" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title">Edit Invoices</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('patient-invoices')}}">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Edit Invoices<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control rounded-end h-auto py-2 bg-white">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Date<span class="text-danger ms-1">*</span></label>
                            <div class="input-group position-relative">
                                <input type="text" class="form-control date-range bookingrange rounded-end h-auto py-2 bg-white">
                                <span class="input-icon-addon fs-16 text-gray-9">
                                    <i class="ti ti-calendar"></i>
                                </span>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Status<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option selected>Active</option>
                                <option>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Edit Modal -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['patient-notifications']))
    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_notification">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative z-1">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-n1">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-n1">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('patient-notifications')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['staffs']))
    <!-- Start Add Modal -->
    <div id="view_staff" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="fw-bold modal-title">Staff Details</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <div class="modal-body">
                    <div class="card bg-light">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="me-2">
                                    <img src="{{URL::asset('build/img/users/user-08.jpg')}}" alt="img" class="img-fluid avatar avatar-xxl rounded">
                                </div>
                                <div>
                                    <span class="text-primary mb-1">#STF020</span>
                                    <div class="d-flex align-items-center mb-1">
                                        <h5 class="fw-bold mb-0 me-2">James Allaire</h5>
                                        <span class="badge badge-soft-success border border-success fw-medium fs-13">Available</span>
                                    </div>
                                    <p>Front Office Executive</p>
                                </div>
                            </div>
                        </div>
                    </div><!-- end card -->

                    <ul class="nav nav-tabs nav-bordered mb-3">
                        <li class="nav-item"><a class="nav-link active" href="javascript:void(0);" data-bs-toggle="tab" data-bs-target="#tab1">Basic Info</a></li>
                        <li class="nav-item"><a class="nav-link" href="javascript:void(0);" data-bs-toggle="tab" data-bs-target="#tab2">Salary Info</a></li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane active" id="tab1" role="tabpanel" tabindex="0">

                            <!-- start row -->
                            <div class="row row-gap-2">
                                <div class="col-md-4">
                                    <p class="text-dark fs-13 fw-medium mb-0">Gender</p>
                                    <p class="fs-13">Male</p>
                                </div><!-- end col -->
                                <div class="col-md-4">
                                    <p class="text-dark fs-13 fw-medium mb-0">Phone Number</p>
                                    <p class="fs-13">+1 54546 45648</p>
                                </div><!-- end col -->
                                <div class="col-md-4">
                                    <p class="text-dark fs-13 fw-medium mb-0">Email</p>
                                    <p class="fs-13">james@example.com</p>
                                </div><!-- end col -->
                                <div class="col-md-4">
                                    <p class="text-dark fs-13 fw-medium mb-0">Date of Joining</p>
                                    <p class="fs-13">12 Dec 2024</p>
                                </div><!-- end col -->
                                <div class="col-md-4">
                                    <p class="text-dark fs-13 fw-medium mb-0">Email</p>
                                    <p class="fs-13">james@example.com</p>
                                </div><!-- end col -->
                                <div class="col-md-4">
                                    <p class="text-dark fs-13 fw-medium mb-0">Staff Type </p>
                                    <p class="fs-13">Permanent</p>
                                </div><!-- end col -->
                                <div class="col-md-12">
                                    <p class="text-dark fs-13 fw-medium mb-0">Addresss</p>
                                    <p class="fs-13">10 Elizabethtown Plaza, Downers Grove, Elizabeth UK07202 </p>
                                </div><!-- end col -->
                            </div>
                            <!-- end row -->

                        </div>
                        <div class="tab-pane" id="tab2" role="tabpanel" tabindex="0">
                            <!-- Table List -->
                            <div class="table-responsive border bg-white">
                                <table class="table table-nowrap">
                                    <thead>
                                        <tr>
                                            <th>Credit Date</th>
                                            <th>Amount</th>
                                            <th>Salary for</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>13 Jul 2025</td>
                                            <td>$4800</td>
                                            <td>Jun 2025</td>
                                            <td>
                                                <div class="action-item">
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <ul class="dropdown-menu p-2">
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit">Edit</a>
                                                        </li>
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete">Delete</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>12 Jun 2025</td>
                                            <td>$4800</td>
                                            <td>May 2025</td>
                                            <td>
                                                <div class="action-item">
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <ul class="dropdown-menu p-2">
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit">Edit</a>
                                                        </li>
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete">Delete</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>15 May 2025</td>
                                            <td>$4800</td>
                                            <td>Apr 2025</td>
                                            <td>
                                                <div class="action-item">
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <ul class="dropdown-menu p-2">
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit">Edit</a>
                                                        </li>
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete">Delete</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>14 Apr 2025</td>
                                            <td>$4800</td>
                                            <td>Mar 2025</td>
                                            <td>
                                                <div class="action-item">
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <ul class="dropdown-menu p-2">
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit">Edit</a>
                                                        </li>
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete">Delete</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>13 Mar 2025</td>
                                            <td>$4800</td>
                                            <td>Feb 2025</td>
                                            <td>
                                                <div class="action-item">
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <ul class="dropdown-menu p-2">
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit">Edit</a>
                                                        </li>
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete">Delete</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>14 Feb 2025</td>
                                            <td>$4800</td>
                                            <td>Jan 2025</td>
                                            <td>
                                                <div class="action-item">
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <ul class="dropdown-menu p-2">
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit">Edit</a>
                                                        </li>
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete">Delete</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>12 Jan 2025</td>
                                            <td>$4800</td>
                                            <td>Dec 2024</td>
                                            <td>
                                                <div class="action-item">
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <ul class="dropdown-menu p-2">
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit">Edit</a>
                                                        </li>
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete">Delete</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <!-- /Table List -->
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <!-- End Add Modal -->

    <!-- Start Add Modal -->
    <div id="add_staff" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="fw-bold modal-title">New Staff</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('staffs')}}">
                    <div class="modal-body">
                        <h6 class="fw-bold mb-3">Staff Information</h6>
                        <div class="mb-3 d-flex align-items-center">
                            <label class="form-label">Profile Image</label>
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
                        <div class="mb-3">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control">
                        </div>

                        <!-- start row -->
                        <div class="row mb-3 border-bottom">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Role<span class="text-danger ms-1">*</span></label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Admin</option>
                                        <option>Reception</option>
                                        <option>Nurse</option>
                                        <option>Nurse Practitioner</option>
                                    </select>
                                </div>
                            </div><!-- end col -->
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Designation<span class="text-danger ms-1">*</span></label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Admin Officer</option>
                                        <option>Front Officer </option>
                                        <option>Medical Recorder</option>
                                        <option>Billing Executive</option>
                                    </select>
                                </div>
                            </div><!-- end col -->
                        </div>
                        <!-- end row -->

                        <h6 class="fw-bold mb-3">Contact Information</h6>

                        <!-- start row -->
                            <div class="row row-gap-2">
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">Phone Number<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control">
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">Email<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control">
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">DOB<span class="text-danger ms-1">*</span></label>
                                    <div class="input-icon-end position-relative">  
                                        <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-calendar"></i>
                                        </span>
                                    </div>
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">Gender<span class="text-danger ms-1">*</span></label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Male</option>
                                        <option>Female</option>
                                    </select>
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-12">
                                <div class="mb-0">
                                    <label class="form-label">Blood Group<span class="text-danger ms-1">*</span></label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>O+</option>
                                        <option>O-</option>
                                        <option>A+</option>
                                        <option>A-</option>
                                        <option>B+</option>
                                        <option>B-</option>
                                    </select>
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">Address 1</label>
                                    <input type="text" class="form-control">
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">Address 2</label>
                                    <input type="text" class="form-control">
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">Country</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>USA</option>
                                        <option>Canada</option>
                                        <option>UK</option>
                                        <option>Germany</option>
                                    </select>
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">State</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>California</option>
                                        <option>Ontario</option>
                                        <option>England</option>
                                        <option>Bavaria</option>
                                    </select>
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">City</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Los Angeles</option>
                                        <option>Toronto</option>
                                        <option>London</option>
                                        <option>Munich</option>
                                    </select>
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">Pincode</label>
                                    <input type="text" class="form-control">
                                </div>
                            </div><!-- end col -->
                            </div>
                            <!-- end row -->

                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Staff</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Modal -->

    <!-- Start Edit Modal -->
    <div id="edit_staff" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="fw-bold modal-title">Edit Staff</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('staffs')}}">
                    <div class="modal-body">
                        <h6 class="fw-bold mb-3">Staff Information</h6>
                        <div class="mb-3 d-flex align-items-center">
                            <label class="form-label me-3">Profile Image</label>
                            <div class="profile-container">
                                <img src="{{URL::asset('build/img/users/user-08.jpg')}}" alt="Profile">
                                <div class="overlay-btn">
                                    <a href="javascript:void(0);" class="text-white" id="uploadTrigger">
                                    <i class="ti ti-photo fs-10"></i>
                                    </a>
                                </div>
                                <input type="file" id="profileUpload" style="display: none;">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" value="James Adair">
                        </div>

                        <!-- start row -->
                        <div class="row mb-3 border-bottom">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Role<span class="text-danger ms-1">*</span></label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option selected>Admin</option>
                                        <option>Reception</option>
                                        <option>Nurse</option>
                                        <option>Nurse Practitioner</option>
                                    </select>
                                </div>
                            </div><!-- end col -->
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Designation<span class="text-danger ms-1">*</span></label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option selected>Admin Officer</option>
                                        <option>Front Officer </option>
                                        <option>Medical Recorder</option>
                                        <option>Billing Executive</option>
                                    </select>
                                </div>
                            </div><!-- end col -->
                        </div>
                        <!-- end row -->

                        <h6 class="fw-bold mb-3">Contact Information</h6>

                        <!-- start row -->
                            <div class="row row-gap-2">
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">Phone Number<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" value="+1 5258 25874">
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">Email<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" value="james@gmail.com">
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">DOB<span class="text-danger ms-1">*</span></label>
                                    <div class="input-icon-end position-relative">  
                                        <input type="text" class="form-control datetimepicker" placeholder="03/02/1999">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-calendar"></i>
                                        </span>
                                    </div>
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">Gender<span class="text-danger ms-1">*</span></label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option selected>Male</option>
                                        <option>Female</option>
                                    </select>
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-12">
                                <div class="mb-0">
                                    <label class="form-label">Blood Group<span class="text-danger ms-1">*</span></label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option selected>O+</option>
                                        <option>O-</option>
                                        <option>A+</option>
                                        <option>A-</option>
                                        <option>B+</option>
                                        <option>B-</option>
                                    </select>
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">Address 1</label>
                                    <input type="text" class="form-control" value="3-174,">
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">Address 2</label>
                                    <input type="text" class="form-control" value="3-/174,">
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">Country</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option selected>USA</option>
                                        <option>Canada</option>
                                        <option>UK</option>
                                        <option>Germany</option>
                                    </select>
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">State</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option selected>California</option>
                                        <option>Ontario</option>
                                        <option>England</option>
                                        <option>Bavaria</option>
                                    </select>
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">City</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option selected>Los Angeles</option>
                                        <option>Toronto</option>
                                        <option>London</option>
                                        <option>Munich</option>
                                    </select>
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">Pincode</label>
                                    <input type="text" class="form-control" value="IN 46625">
                                </div>
                            </div><!-- end col -->
                            </div>
                            <!-- end row -->

                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Edit Modal -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_staff">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative z-1">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-n1">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-n1">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('staffs')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['hrm-departments']))
    <!-- Start Add Modal -->
    <div id="add_department" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="text-dark modal-title fw-bold">Add New Department</h4>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('hrm-departments')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Department Name<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Department</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Modal -->

    <!-- Start Edit Modal -->
    <div id="edit_deparment" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="text-dark modal-title fw-bold">Edit Department</h4>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('hrm-departments')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Designation Name<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" value="Nursing">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="3">Nursing is caring for and supporting patients.</textarea>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Edit Modal -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_designation">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative z-1">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-n1">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-n1">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('hrm-departments')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['designation']))
    <!-- Start Add Modal -->
    <div id="add_designation" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="text-dark modal-title fw-bold">Add New Designation</h4>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('designation')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <div class="d-flex align-items-center">
                                <div class="form-check d-flex align-items-center me-3">
                                    <input class="form-check-input me-2" type="radio" name="Radio-2" id="Radio-sm-1">
                                    <label class="form-check-label fs-13" for="Radio-sm-1">Staff</label>
                                </div>
                                <div class="form-check d-flex align-items-center">
                                    <input class="form-check-input me-2" type="radio" name="Radio-2" id="Radio-sm-2">
                                    <label class="form-check-label fs-13" for="Radio-sm-2">Doctor</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Designation Name<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option>Nurse</option>
                                <option>Pharmacist</option>
                                <option>Receptionist</option>
                                <option>Technician</option>
                                <option>Medical Assistant</option>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Designation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Modal -->

    <!-- Start Edit Modal -->
    <div id="edit_designation" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="text-dark modal-title fw-bold">Edit Designation</h4>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('designation')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <div class="d-flex align-items-center">
                                <div class="form-check d-flex align-items-center me-3">
                                    <input class="form-check-input me-2" type="radio" name="Radio-2" id="Radio-sm-3" checked>
                                    <label class="form-check-label fs-13" for="Radio-sm-3">Staff</label>
                                </div>
                                <div class="form-check d-flex align-items-center">
                                    <input class="form-check-input me-2" type="radio" name="Radio-2" id="Radio-sm-4">
                                    <label class="form-check-label fs-13" for="Radio-sm-4">Doctor</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Designation Name<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" value="Nurse">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option selected>Nurse</option>
                                <option>Pharmacist</option>
                                <option>Receptionist</option>
                                <option>Technician</option>
                                <option>Medical Assistant</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="3">A nurse provides patient care and supports medical treatments.</textarea>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Status</label>
                            <select class="select">
                                <option>Select</option>
                                <option selected>Active</option>
                                <option>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Edit Modal -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_designation">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative z-1">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-n1">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-n1">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('designation')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['leaves']))
    <!-- Start Add Modal -->
    <div id="add_leave" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="text-dark modal-title fw-bold">Add New Leave</h4>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('leaves')}}">
                    <div class="modal-body">
                        <!-- start row -->
                            <div class="row row-gap-2">
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">Employee<span class="text-danger ms-1">*</span></label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>James Adair</option>
                                        <option>Adam Milne</option>
                                        <option>Richard Clark</option>
                                        <option>Robert Reid</option>
                                    </select>
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">Leave Type<span class="text-danger ms-1">*</span></label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Sick Leave</option>
                                        <option>Casual Leave</option>
                                        <option>Emergency</option>
                                        <option>Vacation</option>
                                    </select>
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">From Date<span class="text-danger ms-1">*</span></label>
                                    <div class="input-icon-end position-relative">  
                                        <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-calendar"></i>
                                        </span>
                                    </div>
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">To Date<span class="text-danger ms-1">*</span></label>
                                    <div class="input-icon-end position-relative">  
                                        <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-calendar"></i>
                                        </span>
                                    </div>
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-12">
                                <div class="mb-0">
                                    <label class="form-label">No of Days<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control">
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-12">
                                <div class="p-2 bg-light rounded">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <span>dd/mm/yyyy</span>
                                        </div>
                                        <div class="col-md-6">
                                            <select class="select">
                                                <option>Select</option>
                                                <option>Full Day</option>
                                                <option>Half Day</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-12">
                                <div class="mb-0">
                                    <label class="form-label">No of Days<span class="text-danger ms-1">*</span></label>
                                    <textarea class="form-control" rows="3"></textarea>
                                </div>
                            </div><!-- end col -->
                            </div>
                            <!-- end row -->
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add New Leave</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Modal -->

    <!-- Start Add Modal -->
    <div id="edit_leave" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="text-dark modal-title fw-bold">Edit Leave</h4>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('leaves')}}">
                    <div class="modal-body">
                        <!-- start row -->
                            <div class="row row-gap-2">
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">Employee<span class="text-danger ms-1">*</span></label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option selected>James Adair</option>
                                        <option>Adam Milne</option>
                                        <option>Richard Clark</option>
                                        <option>Robert Reid</option>
                                    </select>
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">Leave Type<span class="text-danger ms-1">*</span></label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option selected>Sick Leave</option>
                                        <option>Casual Leave</option>
                                        <option>Emergency</option>
                                        <option>Vacation</option>
                                    </select>
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">From Date<span class="text-danger ms-1">*</span></label>
                                    <div class="input-icon-end position-relative">  
                                        <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-calendar"></i>
                                        </span>
                                    </div>
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">To Date<span class="text-danger ms-1">*</span></label>
                                    <div class="input-icon-end position-relative">  
                                        <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-calendar"></i>
                                        </span>
                                    </div>
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-12">
                                <div class="mb-0">
                                    <label class="form-label">No of Days<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" value="2">
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-12">
                                <div class="p-2 bg-light rounded">
                                    <div class="row align-items-center mb-2">
                                        <div class="col-md-6">
                                            <span>18 Jan 2025</span>
                                        </div>
                                        <div class="col-md-6">
                                            <select class="select">
                                                <option>Select</option>
                                                <option selected>Full Day</option>
                                                <option>Half Day</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <span>18 Jan 2025</span>
                                        </div>
                                        <div class="col-md-6">
                                            <select class="select">
                                                <option>Select</option>
                                                <option selected>Full Day</option>
                                                <option>Half Day</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-12">
                                <div class="mb-0">
                                    <label class="form-label">No of Days<span class="text-danger ms-1">*</span></label>
                                    <textarea class="form-control" rows="3">Not feeling well due to cold and fatigue, taking rest as advised by family doctor.</textarea>
                                </div>
                            </div><!-- end col -->
                            </div>
                            <!-- end row -->
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Modal -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_leave">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative z-1">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-n1">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-n1">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('leaves')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['leave-type']))
    <!-- Start Add Modal -->
    <div id="add_leave_type" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Add Leave Type</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('leave-type')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Leave Type<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Leave Quota<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add New Leave Type</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Modal -->

    <!-- Start Edit Modal -->
    <div id="edit_leave_type" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Edit Leave Type</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('leave-type')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Leave Type<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" value="Sick Leave">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Leave Quota<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" value="12">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Status</label>
                            <select class="select">
                                <option>Select</option>
                                <option selected>Active</option>
                                <option>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Edit Modal -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_leave_type">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('leave-type')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['holidays']))
    <!-- Start Add Modal -->
    <div id="add_holiday" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Add Holiday</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('holidays')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Title<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Date<span class="text-danger ms-1">*</span></label>
                            <div class="input-icon-end position-relative">  
                                <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                <span class="input-icon-addon">
                                    <i class="ti ti-calendar"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Holiday</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Modal -->

    <!-- Start Edit Modal -->
    <div id="edit_holiday" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Edit Holiday</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('holidays')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Title<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" value=" Mosquito Awareness Week ">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="3">Encourage communities to eliminate mosquito breeding sites, especially standing water.</textarea>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Date<span class="text-danger ms-1">*</span></label>
                            <div class="input-icon-end position-relative">  
                                <input type="text" class="form-control datetimepicker" placeholder="06/05/2025">
                                <span class="input-icon-addon">
                                    <i class="ti ti-calendar"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Edit Modal -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_holiday">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('holidays')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['payroll']))
    <!-- Start Add Modal -->
    <div id="add_payroll" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="text-dark modal-title fw-bold">Add Employee Salary</h4>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('payroll')}}">
                    <div class="modal-body">
                        <!-- start row -->
                        <div class="row row-gap-2 mb-3">
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">Select Staff</label>
                                    <select class="select">
                                        <option>Select staff</option>
                                    </select>
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">Net Salary</label>
                                    <input type="text" class="form-control" placeholder="0">
                                </div>
                            </div><!-- end col -->
                        </div>
                        <!-- end row -->
                        <!-- start row -->
                        <div class="row row-gap-2">
                            <div class="col-md-6">
                                <h6 class="mb-3">Earnings ($)</h6>
                                <div class="mb-3">
                                    <label class="form-label">Basic Salary<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" value="0">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">DA (40%)<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" value="0">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">HRA (15%)<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" value="0">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Conveyance<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" value="0">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Medical Allowance<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" value="0">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Others<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" value="0">
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-6">
                                <h6 class="mb-3">Deductions ($)</h6>
                                <div class="mb-3">
                                    <label class="form-label">TDS<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" placeholder="0">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">ESI</label>
                                    <input type="text" class="form-control" placeholder="0">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">PF<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" placeholder="0">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Prof Tax<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" placeholder="0">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Labour Welfare<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" placeholder="0">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Others<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" placeholder="0">
                                </div>
                            </div><!-- end col -->
                        </div>
                        <!-- end row -->
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Payslip</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Modal -->

    <!-- Start Add Modal -->
    <div id="edit_payroll" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="text-dark modal-title fw-bold">Edit Employee Salary</h4>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('payroll')}}">
                    <div class="modal-body">
                        <!-- start row -->
                        <div class="row row-gap-2 mb-3">
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">Select Staff</label>
                                    <select class="select">
                                        <option>Select staff</option>
                                    </select>
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">Net Salary</label>
                                    <input type="text" class="form-control" value="1000">
                                </div>
                            </div><!-- end col -->
                        </div>
                        <!-- end row -->
                        <!-- start row -->
                        <div class="row row-gap-2">
                            <div class="col-md-6">
                                <h6 class="mb-3">Earnings ($)</h6>
                                <div class="mb-3">
                                    <label class="form-label">Basic Salary<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" value="1000">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">DA (40%)<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" value="800">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">HRA (15%)<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" value="600">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Conveyance<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" value="50">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Medical Allowance<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" value="40">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Others<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" value="20">
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-6">
                                <h6 class="mb-3">Deductions ($)</h6>
                                <div class="mb-3">
                                    <label class="form-label">TDS<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" value="$600">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">ESI</label>
                                    <input type="text" class="form-control" value="500">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">PF<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" value="60">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Prof Tax<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" value="40">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Labour Welfare<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" value="30">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Others<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" value="10">
                                </div>
                            </div><!-- end col -->
                        </div>
                        <!-- end row -->
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Modal -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_payroll">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative z-1">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-n1">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-n1">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('payroll')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['expenses']))
    <!-- Start Add Expense -->
    <div class="modal fade" id="add_new_expense">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark fw-bold">New Expense</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <div class="modal-body">

                    <!-- start row -->
                    <div class="row">

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Expense Name<span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Category<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        Select
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-lg p-2 dropdown-employee w-100">
                                        <li>
                                            <div class="mb-2">
                                                <input type="text" class="form-control form-control" placeholder="Search">
                                            </div>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Medical Supplies
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Options Enhanced
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Cleaning Services
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Medical Equipment Maintenance
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Staff Salaries & Wages
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Utilities
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Software & Licensing
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Facility Rent
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Waste Disposal Services
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Insurance Premiums
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Amount<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        Select
                                    </a>
                                    <div class="dropdown-menu shadow-lg w-100 dropdown-info p-2">
                                        <div class="filter-range">
                                            <input type="text" id="range_01">
                                            <p class="mt-2 fs-13">Range : <span class="text-dark">$200 - $5695</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Date<span class="text-danger">*</span></label>
                                <div class="input-group position-relative">
                                    <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-calendar text-body"></i>
                                    </span>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Purchased By<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        Select
                                    </a>
                                    <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                        <div class="mb-3">
                                            <div class="input-icon-start position-relative">
                                                <span class="input-icon-addon fs-12">
                                                    <i class="ti ti-search"></i>
                                                </span>
                                                <input type="text" class="form-control form-control-sm" placeholder="Search">
                                            </div>
                                        </div>
                                        <ul class="mb-0 list-style-none">
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>James Allaire
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Esther Schmidt
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-03.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Judi Lenahan
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-04.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Robert Reid
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dottie Sellers
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Cheryl Bilodeau
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-03.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Diane Nash
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-04.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Sally Cavazos
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-06.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Forest Heath
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Payment Method<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        Select
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-lg p-2 dropdown-employee w-100">
                                        <li>
                                            <div class="mb-2">
                                                <input type="text" class="form-control form-control" placeholder="Search">
                                            </div>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                PayPal
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Options Enhanced
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Cheque
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Status<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        Select
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-lg p-2 dropdown-employee w-100">
                                        <li>
                                            <div class="mb-2">
                                                <input type="text" class="form-control form-control" placeholder="Search">
                                            </div>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Approved
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Options Enhanced
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Pending
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                New
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div> <!-- end col -->

                    </div>
                    <!-- end row -->

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm me-2 fs-13 fw-medium" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fs-13 fw-medium">Add New Expense</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Add Expense  -->

    <!-- Start Edit Expense -->
    <div class="modal fade" id="edit_new_expense">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark fw-bold">Edit Expense</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <div class="modal-body">

                    <!-- start row -->
                    <div class="row">

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Expense Name<span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="Gloves & Masks">
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Category<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        Medical Supplies
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-lg p-2 dropdown-employee w-100">
                                        <li>
                                            <div class="mb-2">
                                                <input type="text" class="form-control form-control" placeholder="Search">
                                            </div>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Medical Supplies
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Options Enhanced
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Cleaning Services
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Medical Equipment Maintenance
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Staff Salaries & Wages
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Utilities
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Software & Licensing
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Facility Rent
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Waste Disposal Services
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Insurance Premiums
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Amount<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        $500
                                    </a>
                                    <div class="dropdown-menu shadow-lg w-100 dropdown-info p-2">
                                        <div class="filter-range">
                                            <input type="text" id="range_02" value="$220 - $500">
                                            <p class="mt-2 fs-13">Range : <span class="text-dark">$200 - $5695</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Date<span class="text-danger">*</span></label>
                                <div class="input-group position-relative">
                                    <input type="text" class="form-control datetimepicker" placeholder="30/April/2025">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-calendar text-body"></i>
                                    </span>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Purchased By<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        James Allaire
                                    </a>
                                    <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                        <div class="mb-3">
                                            <div class="input-icon-start position-relative">
                                                <span class="input-icon-addon fs-12">
                                                    <i class="ti ti-search"></i>
                                                </span>
                                                <input type="text" class="form-control form-control-sm" placeholder="Search">
                                            </div>
                                        </div>
                                        <ul class="mb-0 list-style-none">
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>James Allaire
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Esther Schmidt
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-03.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Judi Lenahan
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-04.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Robert Reid
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dottie Sellers
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Cheryl Bilodeau
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-03.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Diane Nash
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-04.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Sally Cavazos
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-06.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Forest Heath
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Payment Method<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        PayPal
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-lg p-2 dropdown-employee w-100">
                                        <li>
                                            <div class="mb-2">
                                                <input type="text" class="form-control form-control" placeholder="Search">
                                            </div>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                PayPal
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Options Enhanced
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Cheque
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Status<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        Approved
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-lg p-2 dropdown-employee w-100">
                                        <li>
                                            <div class="mb-2">
                                                <input type="text" class="form-control form-control" placeholder="Search">
                                            </div>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Approved
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Options Enhanced
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Pending
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                New
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div> <!-- end col -->

                    </div>
                    <!-- end row -->

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm me-2 fs-13 fw-medium" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fs-13 fw-medium">Add New Expense</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Edit Expense  -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['expense-category']))
    <!-- Start Add Category -->
    <div class="modal fade" id="add_new_category">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark fw-bold">New Category</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label mb-1 text-dark fs-14 fw-medium">Category Name<span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm me-2 fs-13 fw-medium" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fs-13 fw-medium">Add New Category</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Add Category  -->

    <!-- Start Edit Category -->
    <div class="modal fade" id="edit_new_category">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark fw-bold">Edit Category</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <div class="modal-body">

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Category Name<span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="Medical Supplies">
                            </div>
                        </div>
                    </div> <!-- end col -->

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label mb-1 text-dark fs-14 fw-medium">Status<span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                    Active
                                </a>
                                <ul class="dropdown-menu dropdown-menu-lg p-2 dropdown-employee w-100">
                                    <li>
                                        <div class="mb-2">
                                            <input type="text" class="form-control form-control" placeholder="Search">
                                        </div>
                                    </li>
                                    <li>
                                        <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                            <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                            Approved
                                        </label>
                                    </li>
                                    <li>
                                        <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                            Options Enhanced
                                        </label>
                                    </li>
                                    <li>
                                        <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                            <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                            Pending
                                        </label>
                                    </li>
                                    <li>
                                        <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                            New
                                        </label>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div> <!-- end col -->

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm me-2 fs-13 fw-medium" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fs-13 fw-medium">Add New Category</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Edit Category  -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['income']))
    <!-- Start Add Income -->
    <div class="modal fade" id="add_new_income">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark fw-bold">New Income</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <div class="modal-body">

                    <!-- start row -->
                    <div class="row">

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Income Name<span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Amount<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        Select
                                    </a>
                                    <div class="dropdown-menu shadow-lg w-100 dropdown-info p-2">
                                        <div class="filter-range">
                                            <input type="text" id="range_01">
                                            <p class="mt-2 fs-13">Range : <span class="text-dark">$200 - $5695</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Date<span class="text-danger">*</span></label>
                                <div class="input-group position-relative">
                                    <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-calendar text-body"></i>
                                    </span>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Received From<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        Select
                                    </a>
                                    <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                        <div class="mb-3">
                                            <div class="input-icon-start position-relative">
                                                <span class="input-icon-addon fs-12">
                                                    <i class="ti ti-search"></i>
                                                </span>
                                                <input type="text" class="form-control form-control-sm" placeholder="Search">
                                            </div>
                                        </div>
                                        <ul class="mb-0 list-style-none">
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Alberto Ripley
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Susan Babin
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-03.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Martin Lisa
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-04.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Stella Mary
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Carol Lam
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Jesus Adams
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-03.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Ezra Belcher
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-04.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Glen Lentz
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-06.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Bernard Griffith
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Payment Method<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        Select
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-lg p-2 dropdown-employee w-100">
                                        <li>
                                            <div class="mb-2">
                                                <input type="text" class="form-control form-control" placeholder="Search">
                                            </div>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                PayPal
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Options Enhanced
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Cheque
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Status<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        Select
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-lg p-2 dropdown-employee w-100">
                                        <li>
                                            <div class="mb-2">
                                                <input type="text" class="form-control form-control" placeholder="Search">
                                            </div>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Approved
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Options Enhanced
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Pending
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                New
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div> <!-- end col -->

                    </div>
                    <!-- end row -->

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm me-2 fs-13 fw-medium" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fs-13 fw-medium">Add New Expense</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Add Income  -->

    <!-- Start Edit Income -->
    <div class="modal fade" id="edit_new_income">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark fw-bold">Edit Income</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <div class="modal-body">

                    <!-- start row -->
                    <div class="row">

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Income Name<span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="General Consultation">
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Amount<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        $220
                                    </a>
                                    <div class="dropdown-menu shadow-lg w-100 dropdown-info p-2">
                                        <div class="filter-range">
                                            <input type="text" id="range_02">
                                            <p class="mt-2 fs-13">Range : <span class="text-dark">$200 - $5695</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Date<span class="text-danger">*</span></label>
                                <div class="input-group position-relative">
                                    <input type="text" class="form-control datetimepicker" placeholder="30/April/2025">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-calendar text-body"></i>
                                    </span>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Received From<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        Alberto Ripley
                                    </a>
                                    <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                        <div class="mb-3">
                                            <div class="input-icon-start position-relative">
                                                <span class="input-icon-addon fs-12">
                                                    <i class="ti ti-search"></i>
                                                </span>
                                                <input type="text" class="form-control form-control-sm" placeholder="Search">
                                            </div>
                                        </div>
                                        <ul class="mb-0 list-style-none">
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Alberto Ripley
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Susan Babin
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-03.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Martin Lisa
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-04.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Stella Mary
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Carol Lam
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Jesus Adams
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-03.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Ezra Belcher
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-04.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Glen Lentz
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-06.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Bernard Griffith
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Payment Method<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        PayPal
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-lg p-2 dropdown-employee w-100">
                                        <li>
                                            <div class="mb-2">
                                                <input type="text" class="form-control form-control" placeholder="Search">
                                            </div>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                PayPal
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Options Enhanced
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Cheque
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Status<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        Approved
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-lg p-2 dropdown-employee w-100">
                                        <li>
                                            <div class="mb-2">
                                                <input type="text" class="form-control form-control" placeholder="Search">
                                            </div>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Approved
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Options Enhanced
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Pending
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                New
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div> <!-- end col -->

                    </div>
                    <!-- end row -->

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm me-2 fs-13 fw-medium" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fs-13 fw-medium">Add New Expense</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Edit Income  -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['invoices']))
    <!-- Start Add Expense -->
    <div class="modal fade" id="add_new_payment">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark fw-bold">New Payment</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <div class="modal-body">

                    <!-- start row -->
                    <div class="row">

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Invoice ID <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Paid Date<span class="text-danger">*</span></label>
                                <div class="input-group position-relative">
                                    <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-calendar text-body"></i>
                                    </span>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Patient Name<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        Select
                                    </a>
                                    <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                        <div class="mb-3">
                                            <div class="input-icon-start position-relative">
                                                <span class="input-icon-addon fs-12">
                                                    <i class="ti ti-search"></i>
                                                </span>
                                                <input type="text" class="form-control form-control-sm" placeholder="Search">
                                            </div>
                                        </div>
                                        <ul class="mb-0 list-style-none">
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Alberto Ripley
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Susan Babin
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-03.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Martin Lisa
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-04.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Stella Mary
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-05.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Carol Lam
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-06.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Jesus Adams
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-07.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Ezra Belcher
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-08.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Glen Lentz
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-09.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Bernard Griffith
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-10.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>John Elsass
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Doctor Name<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        Select
                                    </a>
                                    <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                        <div class="mb-3">
                                            <div class="input-icon-start position-relative">
                                                <span class="input-icon-addon fs-12">
                                                    <i class="ti ti-search"></i>
                                                </span>
                                                <input type="text" class="form-control form-control-sm" placeholder="Search">
                                            </div>
                                        </div>
                                        <ul class="mb-0 list-style-none">
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Mick Thompson
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Sarah Johnson
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-03.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Emily Carter
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-04.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.David Lee
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-05.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Anna Kim
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-06.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.John Smith
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-07.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Lisa White
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-08.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Patrica Brown
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-09.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Rachel Green
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-10.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Michael Smith
                                                </label>
                                            </li>
                                            
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Total Amount<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        Select
                                    </a>
                                    <div class="dropdown-menu shadow-lg w-100 dropdown-info p-2">
                                        <div class="filter-range">
                                            <input type="text" id="range_01">
                                            <p class="mt-2 fs-13">Range : <span class="text-dark">$200 - $5695</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Payment Method<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        Select
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-lg p-2 dropdown-employee w-100">
                                        <li>
                                            <div class="mb-2">
                                                <input type="text" class="form-control form-control" placeholder="Search">
                                            </div>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                PayPal
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Options Enhanced
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Cheque
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Payment Status<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        Select
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-lg p-2 dropdown-employee w-100">
                                        <li>
                                            <div class="mb-2">
                                                <input type="text" class="form-control form-control" placeholder="Search">
                                            </div>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Approved
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Options Enhanced
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Pending
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                New
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Other Information <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <textarea class="form-control" rows="4"></textarea>
                                </div>
                            </div>
                        </div> <!-- end col -->

                    </div>
                    <!-- end row -->

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm me-2 fs-13 fw-medium" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fs-13 fw-medium">Add New Payment</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Add Expense  -->

    <!-- Start Edit Expense -->
    <div class="modal fade" id="edit_new_payment">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark fw-bold">Edit Payment</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <div class="modal-body">

                    <!-- start row -->
                    <div class="row">

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Invoice ID <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="#INV0025">
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Paid Date<span class="text-danger">*</span></label>
                                <div class="input-group position-relative">
                                    <input type="text" class="form-control datetimepicker" placeholder="30/April/2025">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-calendar text-body"></i>
                                    </span>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Patient Name<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        James carter
                                    </a>
                                    <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                        <div class="mb-3">
                                            <div class="input-icon-start position-relative">
                                                <span class="input-icon-addon fs-12">
                                                    <i class="ti ti-search"></i>
                                                </span>
                                                <input type="text" class="form-control form-control-sm" placeholder="Search">
                                            </div>
                                        </div>
                                        <ul class="mb-0 list-style-none">
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>James Allaire
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Esther Schmidt
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-03.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Judi Lenahan
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-04.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Robert Reid
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dottie Sellers
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Cheryl Bilodeau
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-03.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Diane Nash
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-04.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Sally Cavazos
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-06.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Forest Heath
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Doctor Name<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        Dr.Mick Thompson
                                    </a>
                                    <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                        <div class="mb-3">
                                            <div class="input-icon-start position-relative">
                                                <span class="input-icon-addon fs-12">
                                                    <i class="ti ti-search"></i>
                                                </span>
                                                <input type="text" class="form-control form-control-sm" placeholder="Search">
                                            </div>
                                        </div>
                                        <ul class="mb-0 list-style-none">
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Mick Thompson
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Sarah Johnson
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-03.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Emily Carter
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-04.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.David Lee
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-05.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Anna Kim
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-06.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.John Smith
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-07.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Lisa White
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-08.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Patrica Brown
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-09.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Rachel Green
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-10.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Michael Smith
                                                </label>
                                            </li>
                                            
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Total Amount<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        $800
                                    </a>
                                    <div class="dropdown-menu shadow-lg w-100 dropdown-info p-2">
                                        <div class="filter-range">
                                            <input type="text" id="range_02">
                                            <p class="mt-2 fs-13">Range : <span class="text-dark">$200 - $5695</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Payment Method<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        PayPal
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-lg p-2 dropdown-employee w-100">
                                        <li>
                                            <div class="mb-2">
                                                <input type="text" class="form-control form-control" placeholder="Search">
                                            </div>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                PayPal
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Options Enhanced
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Cheque
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Payment Status<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        Paid
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-lg p-2 dropdown-employee w-100">
                                        <li>
                                            <div class="mb-2">
                                                <input type="text" class="form-control form-control" placeholder="Search">
                                            </div>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Approved
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Options Enhanced
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Pending
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                New
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium">Other Information <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <textarea class="form-control" rows="4"> </textarea>
                                </div>
                            </div>
                        </div> <!-- end col -->

                    </div>
                    <!-- end row -->

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm me-2 fs-13 fw-medium" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fs-13 fw-medium">Add New Expense</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Edit Expense  -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['payments']))
    <!-- Start Add Expense -->
    <div class="modal fade" id="add_new_payment">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark fw-bold">New Payment</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <div class="modal-body">

                    <!-- start row -->
                    <div class="row">

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium" >Invoice ID <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium" >Paid Date<span class="text-danger">*</span></label>
                                <div class="input-group position-relative">
                                    <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-calendar text-body"></i>
                                    </span>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium" >Patient Name<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        Select
                                    </a>
                                    <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                        <div class="mb-3">
                                            <div class="input-icon-start position-relative">
                                                <span class="input-icon-addon fs-12">
                                                    <i class="ti ti-search"></i>
                                                </span>
                                                <input type="text" class="form-control form-control-sm" placeholder="Search">
                                            </div>
                                        </div>
                                        <ul class="mb-0 list-style-none">
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Alberto Ripley
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Susan Babin
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-03.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Martin Lisa
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-04.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Stella Mary
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-05.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Carol Lam
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-06.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Jesus Adams
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-07.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Ezra Belcher
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-08.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Glen Lentz
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-09.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Bernard Griffith
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-10.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>John Elsass
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium" >Doctor Name<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        Select
                                    </a>
                                    <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                        <div class="mb-3">
                                            <div class="input-icon-start position-relative">
                                                <span class="input-icon-addon fs-12">
                                                    <i class="ti ti-search"></i>
                                                </span>
                                                <input type="text" class="form-control form-control-sm" placeholder="Search">
                                            </div>
                                        </div>
                                        <ul class="mb-0 list-style-none">
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Mick Thompson
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Sarah Johnson
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-03.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Emily Carter
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-04.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.David Lee
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-05.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Anna Kim
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-06.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.John Smith
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-07.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Lisa White
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-08.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Patrica Brown
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-09.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Rachel Green
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-10.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Michael Smith
                                                </label>
                                            </li>
                                            
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium" >Total Amount<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        Select
                                    </a>
                                    <div class="dropdown-menu shadow-lg w-100 dropdown-info p-2">
                                        <div class="filter-range">
                                            <input type="text" id="range_01">
                                            <p class="mt-2 fs-13">Range : <span class="text-dark">$200 - $5695</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium" >Payment Method<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        Select
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-lg p-2 dropdown-employee w-100">
                                        <li>
                                            <div class="mb-2">
                                                <input type="text" class="form-control form-control" placeholder="Search">
                                            </div>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                PayPal
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Options Enhanced
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Cheque
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium" >Payment Status<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        Select
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-lg p-2 dropdown-employee w-100">
                                        <li>
                                            <div class="mb-2">
                                                <input type="text" class="form-control form-control" placeholder="Search">
                                            </div>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Approved
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Options Enhanced
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Pending
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                New
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium" >Other Information <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <textarea class="form-control" rows="4"></textarea>
                                </div>
                            </div>
                        </div> <!-- end col -->

                    </div>
                    <!-- end row -->

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm me-2 fs-13 fw-medium" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fs-13 fw-medium">Add New Payment</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Add Expense  -->

    <!-- Start Edit Expense -->
    <div class="modal fade" id="edit_new_payment">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark fw-bold">Edit Payment</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <div class="modal-body">

                    <!-- start row -->
                    <div class="row">

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium" >Invoice ID <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="#INV0025">
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium" >Paid Date<span class="text-danger">*</span></label>
                                <div class="input-group position-relative">
                                    <input type="text" class="form-control datetimepicker" placeholder="30/April/2025">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-calendar text-body"></i>
                                    </span>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium" >Patient Name<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        James carter
                                    </a>
                                    <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                        <div class="mb-3">
                                            <div class="input-icon-start position-relative">
                                                <span class="input-icon-addon fs-12">
                                                    <i class="ti ti-search"></i>
                                                </span>
                                                <input type="text" class="form-control form-control-sm" placeholder="Search">
                                            </div>
                                        </div>
                                        <ul class="mb-0 list-style-none">
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>James Allaire
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Esther Schmidt
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-03.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Judi Lenahan
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-04.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Robert Reid
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dottie Sellers
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Cheryl Bilodeau
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-03.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Diane Nash
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-04.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Sally Cavazos
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-06.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Forest Heath
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium" >Doctor Name<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control rounded d-flex align-items-center justify-content-between border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        Dr.Mick Thompson
                                    </a>
                                    <div class="dropdown-menu shadow-lg w-100 dropdown-info">
                                        <div class="mb-3">
                                            <div class="input-icon-start position-relative">
                                                <span class="input-icon-addon fs-12">
                                                    <i class="ti ti-search"></i>
                                                </span>
                                                <input type="text" class="form-control form-control-sm" placeholder="Search">
                                            </div>
                                        </div>
                                        <ul class="mb-0 list-style-none">
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Mick Thompson
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Sarah Johnson
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-03.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Emily Carter
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-04.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.David Lee
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-05.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Anna Kim
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-06.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.John Smith
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-07.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Lisa White
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-08.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Patrica Brown
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-09.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Rachel Green
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                    <input class="form-check-input m-0 me-2" type="checkbox">
                                                    <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-10.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr.Michael Smith
                                                </label>
                                            </li>
                                            
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium" >Total Amount<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        $800
                                    </a>
                                    <div class="dropdown-menu shadow-lg w-100 dropdown-info p-2">
                                        <div class="filter-range">
                                            <input type="text" id="range_02">
                                            <p class="mt-2 fs-13">Range : <span class="text-dark">$200 - $5695</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium" >Payment Method<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        PayPal
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-lg p-2 dropdown-employee w-100">
                                        <li>
                                            <div class="mb-2">
                                                <input type="text" class="form-control form-control" placeholder="Search">
                                            </div>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                PayPal
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Options Enhanced
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Cheque
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium" >Payment Status<span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle form-control w-100 d-flex align-items-center justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                        Paid
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-lg p-2 dropdown-employee w-100">
                                        <li>
                                            <div class="mb-2">
                                                <input type="text" class="form-control form-control" placeholder="Search">
                                            </div>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                Approved
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Options Enhanced
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                Pending
                                            </label>
                                        </li>
                                        <li>
                                            <label class="dropdown-item px-2 d-flex align-items-center rounded-1">
                                                <input class="form-check-input m-0 me-2" type="checkbox">
                                                New
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label mb-1 text-dark fs-14 fw-medium" >Other Information <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <textarea class="form-control" rows="4"> </textarea>
                                </div>
                            </div>
                        </div> <!-- end col -->

                    </div>
                    <!-- end row -->

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm me-2 fs-13 fw-medium" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fs-13 fw-medium">Add New Expense</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Edit Expense  -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['transactions']))
    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['roles-and-permissions']))
    <!-- Start Add Modal -->
    <div id="add_role" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="text-dark modal-title fw-bold">New Role</h4>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('roles-and-permissions')}}">
                    <div class="modal-body">
                        <div class="mb-0">
                            <label class="form-label">Role<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add New Role</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Modal -->

    <!-- Start Add Modal -->
    <div id="edit_role" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="text-dark modal-title fw-bold">Edit Role</h4>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('roles-and-permissions')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Role<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" value="Doctor">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Status<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option selected>Active</option>
                                <option>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Modal -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_role">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative z-1">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-n1">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-n1">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('roles-and-permissions')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['permissions']))
    <!-- Start Add Modal -->
    <div id="add_role" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="text-dark modal-title fw-bold">New Role</h4>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('permissions')}}">
                    <div class="modal-body">
                        <div class="mb-0">
                            <label class="form-label">Role<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add New Role</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Modal -->

    <!-- Start Add Modal -->
    <div id="edit_role" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="text-dark modal-title fw-bold">Edit Role</h4>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('permissions')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Role<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" value="Doctor">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Status<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option selected>Active</option>
                                <option>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Modal -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_role">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative z-1">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-n1">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-n1">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('permissions')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['delete-account-request']))
    <!-- Start Delete Modal  -->
    <div class="modal fade" id="cancel_request">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative z-1">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-n1">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-n1">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-x fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Cancel Confirmation</h5>
                    <p class="mb-3">Are you sure want to cancel?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('delete-account-request')}}" class="btn btn-danger position-relative z-1">Yes, Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_request">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative z-1">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-n1">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-n1">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('delete-account-request')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['doctors-list']))
    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative z-1">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-n1">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-n1">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('doctors-list')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['pages']))
    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['testimonials']))
    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_categories">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['faq']))
    <!-- Start Add Categories -->
    <div id="add_faq" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark fw-bold modal-title">Add FAQ</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('faq')}}">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Category</label>
                            <input type="text" class="form-control" placeholder="Placeholder">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Question</label>
                            <input type="text" class="form-control" placeholder="Placeholder">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Answer</label>
                            <textarea class="form-control" placeholder="Description"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add FAQ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Categories -->

    <!-- Start Edit Categories -->
    <div id="edit_faq" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title">Edit FAQ</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('faq')}}">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Category</label>
                            <input type="text" class="form-control" value="General">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Question</label>
                            <input type="text" class="form-control" value="Do you accept my health insurance?">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Answer</label>
                            <textarea class="form-control">We accep  t most major insurance plans. You can check the list of accepted insurers on our website or call us to confirm coverage.</textarea>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Edit Categories -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_faq">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('faq')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['blog-comments']))
    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_categories">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('blog-comments')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['blog-categories']))
    <!-- Start Add Categories -->
    <div id="add_categories" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title">Add New Category</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('blog-categories')}}">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Category Name<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option>General Medicine</option>
                                <option>Disease & Conditions</option>
                                <option>Mental Health</option>
                                <option>Patient Education </option>
                                <option>Nutrition & Lifestyle Medicine</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Date<span class="text-danger ms-1">*</span></label>
                            <div class="input-group position-relative">
                                <input type="text" class="form-control date-range bookingrange rounded-end h-auto py-2 bg-white">
                                <span class="input-icon-addon fs-16 text-gray-9">
                                    <i class="ti ti-calendar"></i>
                                </span>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Status<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option>Active</option>
                                <option>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Categories -->

    <!-- Start Edit Categories -->
    <div id="edit_categories" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title">Edit Category</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('blog-categories')}}">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Category Name<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option selected>General Medicine</option>
                                <option>Disease & Conditions</option>
                                <option>Mental Health</option>
                                <option>Patient Education </option>
                                <option>Nutrition & Lifestyle Medicine</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Date<span class="text-danger ms-1">*</span></label>
                            <div class="input-group position-relative">
                                <input type="text" class="form-control date-range bookingrange rounded-end h-auto py-2 bg-white">
                                <span class="input-icon-addon fs-16 text-gray-9">
                                    <i class="ti ti-calendar"></i>
                                </span>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Status<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option selected>Active</option>
                                <option>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Edit Categories -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_categories">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure, you want to delete Blog Category?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('blog-categories')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['blogs']))
    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_categories">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('blogs')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['cities']))
    <!-- Start Add Categories -->
    <div id="add_countries" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title">Add New City</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('cities')}}">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Country Name<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option>USA</option>
                                <option>Canada</option>
                                <option>UK</option>
                                <option>Germany</option>
                                <option>France</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">State<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option>California</option>
                                <option>Ontario</option>
                                <option>England</option>
                                <option>Bavaria</option>
                                <option>Le-de-France</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">City<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option>Los Angeles</option>
                                <option>Toronto</option>
                                <option>London</option>
                                <option>Munich</option>
                                <option>Paris</option>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Status<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option>Active</option>
                                <option>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add City</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Categories -->

    <!-- Start Edit Categories -->
    <div id="edit_countries" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title">Edit City</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('cities')}}">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Country Name<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option selected>USA</option>
                                <option>Canada</option>
                                <option>UK</option>
                                <option>Germany</option>
                                <option>France</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">State<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option selected>California</option>
                                <option>Ontario</option>
                                <option>England</option>
                                <option>Bavaria</option>
                                <option>Le-de-France</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">City<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option selected>Los Angeles</option>
                                <option>Toronto</option>
                                <option>London</option>
                                <option>Munich</option>
                                <option>Paris</option>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Status<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option selected>Active</option>
                                <option>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Edit Categories -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_countries">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('cities')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['countries']))
    <!-- Start Add Categories -->
    <div id="add_countries" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title">Add New Country</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('countries')}}">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Country Code<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option>US</option>
                                <option>CA</option>
                                <option>UK</option>
                                <option>DE</option>
                                <option>FR</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Country Name<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option>USA</option>
                                <option>Canada</option>
                                <option>UK</option>
                                <option>Germany</option>
                                <option>France</option>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Status<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option>Active</option>
                                <option>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Country</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Categories -->

    <!-- Start Edit Categories -->
    <div id="edit_countries" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title">Edit Country</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('countries')}}">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Country Code<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option selected>US</option>
                                <option>CA</option>
                                <option>UK</option>
                                <option>DE</option>
                                <option>FR</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Country Name<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option selected>USA</option>
                                <option>Canada</option>
                                <option>UK</option>
                                <option>Germany</option>
                                <option>France</option>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Status<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option selected>Active</option>
                                <option>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Edit Categories -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_countries">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('countries')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['states']))
    <!-- Start Add Categories -->
    <div id="add_countries" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title">Add New State</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('states')}}">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Country Code<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option>US</option>
                                <option>CA</option>
                                <option>UK</option>
                                <option>DE</option>
                                <option>FR</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Country Name<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option>USA</option>
                                <option>Canada</option>
                                <option>UK</option>
                                <option>Germany</option>
                                <option>France</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">State<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option>California</option>
                                <option>Ontario</option>
                                <option>England</option>
                                <option>Bavaria</option>
                                <option>Le-de-France</option>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Status<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option>Active</option>
                                <option>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add State</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Categories -->

    <!-- Start Edit Categories -->
    <div id="edit_countries" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title">Edit State</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('states')}}">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Country Code<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option selected>US</option>
                                <option>CA</option>
                                <option>UK</option>
                                <option>DE</option>
                                <option>FR</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Country Name<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option selected>USA</option>
                                <option>Canada</option>
                                <option>UK</option>
                                <option>Germany</option>
                                <option>France</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">State<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option selected>California</option>
                                <option>Ontario</option>
                                <option>England</option>
                                <option>Bavaria</option>
                                <option>Le-de-France</option>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Status<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option selected>Active</option>
                                <option>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Edit Categories -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_countries">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('states')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['contact-messages']))
    <!-- Start Edit Contact -->
    <div id="edit_contact" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title">Edit New Country</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('contact-messages')}}">
                    <div class="modal-body">
                        <!-- start row -->
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="mb-2">
                                    <label class="form-label">Name<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" value="John Carter">
                                </div>
                            </div><!-- end col -->
                            <div class="col-lg-6">
                                <div class="mb-2">
                                    <label class="form-label">Email Address<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" value="john@example.com">
                                </div>
                            </div><!-- end col -->
                            <div class="col-lg-6">
                                <div class="mb-2">
                                    <label class="form-label">Phone Number<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" id="phone">
                                </div>
                            </div><!-- end col -->
                            <div class="col-lg-12">
                                <div class="mb-2">
                                    <label class="form-label">Message</label>
                                    <textarea class="form-control" rows="3">Unable to log into my account. Can you assist?</textarea>
                                </div>
                            </div><!-- end col -->
                        </div>
                        <!-- end row -->
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Edit Contact -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_contact">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('contact-messages')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['tickets']))
    <!-- Start Add Contact -->
    <div id="add_tickets" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Add New Ticket</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('tickets')}}">
                    <div class="modal-body">
                        <!-- start row -->
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-2">
                                    <label class="form-label">Ticket ID<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" placeholder="#TKT0020" disabled>
                                </div>
                            </div><!-- end col -->
                            <div class="col-lg-6">
                                <div class="mb-2">
                                    <label class="form-label">Created By<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control">
                                </div>
                            </div><!-- end col -->
                            <div class="col-lg-6">
                                <div class="mb-2">
                                    <label class="form-label">Phone Number<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" id="phone">
                                </div>
                            </div><!-- end col -->
                            <div class="col-lg-6">
                                <div class="mb-2">
                                    <label class="form-label">Email Address<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control">
                                </div>
                            </div><!-- end col -->
                            <div class="col-lg-6">
                                <div class="mb-2">
                                    <label class="form-label">Subject<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control">
                                </div>
                            </div><!-- end col -->
                            <div class="col-lg-6">
                                <div class="mb-2">
                                    <label class="form-label">Category<span class="text-danger ms-1">*</span></label>
                                    <select class="select">
                                        <option selected>Technical Issues</option>
                                        <option>Missing Invoices</option>
                                        <option>Payment Gateway Errors</option>
                                        <option>User Role Issues</option>
                                        <option>Client Queries</option>
                                    </select>
                                </div>
                            </div><!-- end col -->
                            <div class="col-lg-6">
                                <div class="mb-2">
                                    <label class="form-label">Priority<span class="text-danger ms-1">*</span></label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>High</option>
                                        <option>Medium</option>
                                        <option>Low</option>
                                    </select>
                                </div>
                            </div><!-- end col -->
                            <div class="col-lg-6">
                                <div class="mb-2">
                                    <label class="form-label">Created Date<span class="text-danger ms-1">*</span></label>
                                    <div class="input-group position-relative">
                                        <input type="text" class="form-control date-range bookingrange rounded-end h-auto py-2 bg-white">
                                        <span class="input-icon-addon fs-16 text-gray-9">
                                            <i class="ti ti-calendar"></i>
                                        </span>
                                    </div>
                                </div>
                            </div><!-- end col -->
                            <div class="col-lg-12">
                                <div class="mb-2">
                                    <label class="form-label">Content<span class="text-danger ms-1">*</span></label>
                                    <textarea class="form-control" rows="3"></textarea>
                                </div>
                            </div><!-- end col -->
                            <div class="col-lg-12">
                                <div class="mb-2">
                                    <label class="form-label">File</label>
                                    <div class="file-upload drag-file bg-light w-100 d-flex align-items-center justify-content-center flex-column">
                                        <span class="upload-img d-block mb-2"><i class="ti ti-folder-open text-primary"></i></span>
                                        <p class="mb-0 text-dark">Drop Your Files or <a href="javascript:void(0);" class="text-primary text-decoration-underline">
                                                browse</a></p>
                                        <input type="file" accept="video/image">
                                        <p class="fs-13">Maximum size : 50 MB</p>
                                    </div>
                                </div>
                            </div><!-- end col -->
                        </div>
                        <!-- end row -->
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Ticket</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Contact -->

    <!-- Start Edit Contact -->
    <div id="edit_tickets" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Edit Ticket</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('tickets')}}">
                    <div class="modal-body">
                        <!-- start row -->
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-2">
                                    <label class="form-label">Ticket ID<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" placeholder="#TKT0020" disabled>
                                </div>
                            </div><!-- end col -->
                            <div class="col-lg-6">
                                <div class="mb-2">
                                    <label class="form-label">Created By<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" value="Andrew Simmons">
                                </div>
                            </div><!-- end col -->
                            <div class="col-lg-6">
                                <div class="mb-2">
                                    <label class="form-label">Phone Number<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" id="phone2" value="000-000-0000">
                                </div>
                            </div><!-- end col -->
                            <div class="col-lg-6">
                                <div class="mb-2">
                                    <label class="form-label">Email Address<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" value="andrew@example.com">
                                </div>
                            </div><!-- end col -->
                            <div class="col-lg-6">
                                <div class="mb-2">
                                    <label class="form-label">Subject<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" value="Auto Logout Complaint">
                                </div>
                            </div><!-- end col -->
                            <div class="col-lg-6">
                                <div class="mb-2">
                                    <label class="form-label">Category<span class="text-danger ms-1">*</span></label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option selected>Technical Issues</option>
                                        <option>Missing Invoices</option>
                                        <option>Payment Gateway Errors</option>
                                        <option>User Role Issues</option>
                                        <option>Client Queries</option>
                                    </select>
                                </div>
                            </div><!-- end col -->
                            <div class="col-lg-6">
                                <div class="mb-2">
                                    <label class="form-label">Priority<span class="text-danger ms-1">*</span></label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>High</option>
                                        <option selected>Medium</option>
                                        <option>Low</option>
                                    </select>
                                </div>
                            </div><!-- end col -->
                            <div class="col-lg-6">
                                <div class="mb-2">
                                    <label class="form-label">Created Date<span class="text-danger ms-1">*</span></label>
                                    <div class="input-group position-relative">
                                        <input type="text" class="form-control date-range bookingrange rounded-end h-auto py-2 bg-white">
                                        <span class="input-icon-addon fs-16 text-gray-9">
                                            <i class="ti ti-calendar"></i>
                                        </span>
                                    </div>
                                </div>
                            </div><!-- end col -->
                            <div class="col-lg-12">
                                <div class="mb-2">
                                    <label class="form-label">Content<span class="text-danger ms-1">*</span></label>
                                    <textarea class="form-control" rows="3">Keep getting logged out while working, even without being idle. It’s frustrating and interrupts my documentation process.</textarea>
                                </div>
                            </div><!-- end col -->
                            <div class="col-lg-12">
                                <div class="mb-2">
                                    <label class="form-label">File</label>
                                    <div class="file-upload drag-file bg-light w-100 d-flex align-items-center justify-content-center flex-column">
                                        <span class="upload-img d-block mb-2"><i class="ti ti-folder-open text-primary"></i></span>
                                        <p class="mb-0 text-dark">Drop Your Files or <a href="javascript:void(0);" class="text-primary text-decoration-underline">
                                                browse</a></p>
                                        <input type="file" accept="video/image">
                                        <p class="fs-13">Maximum size : 50 MB</p>
                                    </div>
                                </div>
                            </div><!-- end col -->
                            <div class="col-lg-12">
                                <div class="mb-2">
                                    <label class="form-label">Status<span class="text-danger ms-1">*</span></label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Inprogress</option>
                                        <option selected>Resolved</option>
                                        <option>Closed</option>
                                        <option>open</option>
                                    </select>
                                </div>
                            </div><!-- end col -->
                        </div>
                        <!-- end row -->
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Edit Contact -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_tickets">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('tickets')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['announcements']))
    <!-- Start Add Categories -->
    <div id="add_announcement" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Add New Announcements</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('announcements')}}">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Announcement Title<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Announcement Type<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Content</label>
                            <textarea class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Announcement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Categories -->

    <!-- Start Edit Categories -->
    <div id="edit_announcement" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Edit Announcement</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('announcements')}}">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Announcement Title<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" value="System Maintenance Scheduled">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Announcement Type<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" value="System Update">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Content</label>
                            <textarea class="form-control" rows="3">Platform will be under maintenance on 07 Apr from 1 AM to 3 AM.</textarea>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Status<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option>Inactive</option>
                                <option selected>Active</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Edit Categories -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_announcement">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('announcements')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['newsletters']))
    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_categories">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['notifications']))
    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_notification">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  --> 
@endif

@if (Route::is(['widgets']))
    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['security-settings']))
    <!-- Start Change Password Modal -->
    <div id="change_password" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title fw-bold">Change Password</h4>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('security-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Current Password<span class="text-danger ms-1">*</span></label>
                            <div class="position-relative">
                                <div class="pass-group input-group position-relative border rounded">
                                    <span class="input-group-text bg-white border-0">
                                        <i class="ti ti-lock text-dark fs-14"></i>
                                    </span>
                                    <input type="password" class="pass-input form-control ps-0 border-0" placeholder="****************">
                                    <span class="input-group-text bg-white border-0">
                                        <i class="ti toggle-password ti-eye-off text-dark fs-14"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password<span class="text-danger ms-1">*</span></label>
                            <div class="position-relative">
                                <div class="pass-group input-group position-relative border rounded">
                                    <span class="input-group-text bg-white border-0">
                                        <i class="ti ti-lock text-dark fs-14"></i>
                                    </span>
                                    <input type="password" class="pass-inputs form-control ps-0 border-0" placeholder="****************">
                                    <span class="input-group-text bg-white border-0">
                                        <i class="ti toggle-passwords ti-eye-off text-dark fs-14"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="password-strength d-flex" id="passwordStrength">
                                <span id="poor"></span>
                                <span id="weak"></span>
                                <span id="strong"></span>
                                <span id="heavy"></span>
                            </div>
                            <div id="passwordInfo" class="mb-2"></div>
                            <p class="text-gray-5">Use 8 or more characters with a mix of letters, numbers & symbols.</p>
                        </div>
                        <div>
                            <label class="form-label">Confirm Password<span class="text-danger ms-1">*</span></label>
                            <div class="position-relative">
                                <div class="pass-group input-group position-relative border rounded">
                                    <span class="input-group-text bg-white border-0">
                                        <i class="ti ti-lock text-dark fs-14"></i>
                                    </span>
                                    <input type="password" class="pass-inputa form-control ps-0 border-0" placeholder="****************">
                                    <span class="input-group-text bg-white border-0">
                                        <i class="ti toggle-passworda ti-eye-off text-dark fs-14"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center justify-content-between gap-1">
                        <button type="button" class="btn btn-outline-white" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Change password Modal -->

    <!-- Start Phone Verification Modal -->
    <div id="phone_verification" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title fw-bold">Change Phone Number</h4>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('security-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Current Phone Number<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" id="phone">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Phone Number<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" id="phone2">
                            <p class="mt-2 d-inline-flex align-items-center"><i class="ti ti-info-circle me-1"></i>New phone number only updated once you verified </p>
                        </div>
                        <div>
                            <label class="form-label">Current Password<span class="text-danger ms-1">*</span></label>
                            <div class="position-relative">
                                <div class="pass-group input-group position-relative border rounded">
                                    <span class="input-group-text bg-white border-0">
                                        <i class="ti ti-lock text-dark fs-14"></i>
                                    </span>
                                    <input type="password" class="pass-inputb form-control ps-0 border-0" placeholder="****************">
                                    <span class="input-group-text bg-white border-0">
                                        <i class="ti toggle-passwordb ti-eye-off text-dark fs-14"></i>
                                    </span>
                                </div>
                            </div>
                        </div>								
                    </div>
                    <div class="modal-footer d-flex align-items-center justify-content-between gap-1">
                        <button type="button" class="btn btn-outline-white" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Phone Verification Modal -->

    <!-- Start Email verification Modal -->
    <div id="email_verification" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title fw-bold">Change Email Address</h4>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('security-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Current Email Address<span class="text-danger ms-1">*</span></label>
                            <input type="email" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Email Address<span class="text-danger ms-1">*</span></label>
                            <input type="email" class="form-control">
                            <p class="mt-2 d-inline-flex align-items-center"><i class="ti ti-info-circle me-1"></i>New email address only updated once you verified </p>
                        </div>
                        <div>
                            <label class="form-label">Current Password<span class="text-danger ms-1">*</span></label>
                            <div class="position-relative">
                                <div class="pass-group input-group position-relative border rounded">
                                    <span class="input-group-text bg-white border-0">
                                        <i class="ti ti-lock text-dark fs-14"></i>
                                    </span>
                                    <input type="password" class="pass-inputc form-control ps-0 border-0" placeholder="****************">
                                    <span class="input-group-text bg-white border-0">
                                        <i class="ti toggle-passwordc ti-eye-off text-dark fs-14"></i>
                                    </span>
                                </div>
                            </div>
                        </div>								
                    </div>
                    <div class="modal-footer d-flex align-items-center justify-content-end gap-1">
                        <button type="button" class="btn btn-outline-white" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Email verification modal -->

    <!-- Start Two Factor Modal -->
    <div id="two-factor" class="modal fade">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title fw-bold">SMS Two Factor Authentication</h4>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('security-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Phone Number<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" id="phone3">
                        </div>
                        <p class="fs-13 mb-0">By providing your phone number, you agree to receive text messages from Figma to enable two-factor authentication when you log in. </p>							
                    </div>
                    <div class="modal-footer d-flex align-items-center justify-content-between gap-1">
                        <button type="button" class="btn btn-outline-white" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Verify</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Two Factor Modal -->

    <!-- Start Delete Modal -->
    <div id="delete_modal" class="modal fade">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title fw-bold">Delete Account</h4>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('security-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <p class="text-dark fw-semibold mb-0">Why Are You Deleting Your Account?</p>
                            <p class="fs-13">We're sorry to see you go! To help us improve, please let us know your reason for deleting your account</p>
                        </div>
                        <div>
                            <div class="form-check mb-3 d-flex align-items-center">
                                <input class="form-check-input" type="radio" name="Radio-2" id="Radio-sm-1">
                                <div class="ms-2">
                                    <p class="text-dark fw-semibold mb-0">No longer using the service</p>
                                    <label class="form-check-label fs-13" for="Radio-sm-1">
                                        I no longer need this service and won’t be using it in the future.
                                    </label>
                                </div>
                            </div>
                            <div class="form-check mb-3 d-flex align-items-center">
                                <input class="form-check-input" type="radio" name="Radio-2" id="Radio-sm-2">
                                <div class="ms-2">
                                    <p class="text-dark fw-semibold mb-0">Privacy concerns</p>
                                    <label class="form-check-label fs-13" for="Radio-sm-2">
                                        I am concerned about how my data is handled and want to remove
                                    </label>
                                </div>
                            </div>
                            <div class="form-check mb-3 d-flex align-items-center">
                                <input class="form-check-input" type="radio" name="Radio-2" id="Radio-sm-3">
                                <div class="ms-2">
                                    <p class="text-dark fw-semibold mb-0">Too many notifications/emails</p>
                                    <label class="form-check-label fs-13" for="Radio-sm-3">
                                        I’m overwhelmed by the volume of notifications or emails
                                    </label>
                                </div>
                            </div>
                            <div class="form-check mb-3 d-flex align-items-center">
                                <input class="form-check-input" type="radio" name="Radio-2" id="Radio-sm-4">
                                <div class="ms-2">
                                    <p class="text-dark fw-semibold mb-0">Poor user experience</p>
                                    <label class="form-check-label fs-13" for="Radio-sm-4">
                                        I’ve had difficulty using the platform, and it didn’t meet my expectations
                                    </label>
                                </div>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="Radio-2" id="Radio-sm-5" checked>
                                <label class="form-check-label text-dark fw-semibold" for="Radio-sm-5">
                                    Other (Please specify)
                                </label>
                            </div>
                        </div>	
                        <div>
                            <label class="form-label">Reason<span class="text-danger ms-1">*</span></label>
                            <textarea class="form-control" rows="3"></textarea>
                        </div>					
                    </div>
                    <div class="modal-footer d-flex align-items-center justify-content-between gap-1">
                        <button type="button" class="btn btn-outline-white" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Confirm & Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Delete Modal -->
@endif

@if (Route::is(['integrations-settings']))
    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('integrations-settings')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->   
@endif

@if (Route::is(['login-and-register-settings']))
    <!-- Start Add Mail -->
    <div id="google" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Google Login Settings</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('login-and-register-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Client ID<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Client Secret Key<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Login Redirect URL<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Mail -->

    <!-- Start Add Mail -->
    <div id="facebook" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Facebook Login Settings</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('login-and-register-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">API ID<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">API Secret Key<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Login Redirect URL<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Mail -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('login-and-register-settings')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->     
@endif

@if (Route::is(['language-settings', 'language-settings2', 'language-settings3']))
    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  --> 
@endif

@if (Route::is(['cancellation-reason-settings']))
    <!-- Start Add Categories -->
    <div id="add_reason" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Add Cancellation Reason</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('cancellation-reason-settings')}}">
                    <div class="modal-body">
                        <div class="mb-0">
                            <label class="form-label">Reason<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Reason</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Categories -->

    <!-- Start Edit Categories -->
    <div id="edit_reason" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Edit Cancellation Reason</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('cancellation-reason-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Reason<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" value="Personal Emergency">
                        </div>
                        <div class="mb-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <label class="form-label">Required</label>
                                <div class="form-check form-switch ps-0">
                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Edit Categories -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_reason">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('cancellation-reason-settings')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['invoice-templates-settings']))
    <!-- Start Invoivce View -->
    <div class="modal fade addmodal" id="invoice_view_1">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="mb-0">General Invoice 1</h4>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close-modal" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa-solid fa-x"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center justify-content-center"><img class="w-100 invoice-template-img" src="{{URL::asset('build/img/invoice/invoice-template-img-01.jpg')}}" alt="User Img"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Invoivce View -->

    <!-- Start Invoivce View -->
    <div class="modal fade addmodal" id="invoice_view_2">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="mb-0">General Invoice 2</h4>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close-modal" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa-solid fa-x"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center justify-content-center"><img class="w-100 invoice-template-img" src="{{URL::asset('build/img/invoice/invoice-template-img-02.jpg')}}" alt="User Img"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Invoivce View -->

    <!-- Start Invoivce View -->
    <div class="modal fade addmodal" id="invoice_view_3">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="mb-0">General Invoice 3</h4>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close-modal" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa-solid fa-x"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center justify-content-center"><img class="w-100 invoice-template-img" src="{{URL::asset('build/img/invoice/invoice-template-img-03.jpg')}}" alt="User Img"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Invoivce View -->

    <!-- Start Invoivce View -->
    <div class="modal fade addmodal" id="invoice_view_4">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="mb-0">General Invoice 4</h4>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close-modal" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa-solid fa-x"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center justify-content-center"><img class="w-100 invoice-template-img" src="{{URL::asset('build/img/invoice/invoice-template-img-04.jpg')}}" alt="User Img"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Invoivce View -->
@endif

@if (Route::is(['signatures-settings']))
    <!-- Start Add Categories -->
    <div id="add_reason" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Add Signature</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('signatures-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Signature Name<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Signature Image<span class="text-danger ms-1">*</span></label>
                            <input type="file" class="form-control">
                        </div>
                        <div class="mb-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <label class="form-label">Make a default</label>
                                <div class="form-check form-switch ps-0">
                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Signature</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Categories -->

    <!-- Start Edit Categories -->
    <div id="edit_reason" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Edit Signature</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('signatures-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Signature Name<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" value="Samuel Donatte">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Signature Image<span class="text-danger ms-1">*</span></label>
                            <input type="file" class="form-control">
                        </div>
                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <label class="form-label">Make a default</label>
                                <div class="form-check form-switch ps-0">
                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                </div>
                            </div>
                        </div>
                        <div class="mb-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch ps-0">
                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Edit Categories -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_reason">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('signatures-settings')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['custom-fields-settings']))
    <!-- Start Add Categories -->
    <div id="add_reason" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Add Custom Field</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('custom-fields-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Module<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option>Patient</option>
                                <option>Staff</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Input Type<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option>Text</option>
                                <option>Number</option>
                                <option>Password</option>
                                <option>File</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Label<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Default Value<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <label class="form-label">Required</label>
                                <div class="form-check form-switch ps-0">
                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Custom Field</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Categories -->

    <!-- Start Edit Categories -->
    <div id="edit_reason" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Edit Custom Field</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('custom-fields-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Module<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option>Select</option>
                                <option selected>Patient</option>
                                <option>Staff</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Input Type<span class="text-danger ms-1">*</span></label>
                            <select class="select">
                                <option selected>Select</option>
                                <option>Text</option>
                                <option>Number</option>
                                <option>Password</option>
                                <option>File</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Label<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" value="Preferred Language">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Default Value<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" value="English">
                        </div>
                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <label class="form-label">Required</label>
                                <div class="form-check form-switch ps-0">
                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                </div>
                            </div>
                        </div>
                        <div class="mb-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch ps-0">
                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Edit Categories -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_reason">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('custom-fields-settings')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['email-settings']))
    <!-- Start Add Mail -->
    <div id="php_mailer" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">PHP Mailer</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('email-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">From Email Address<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Password<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">From Email Name<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Mail -->

    <!-- Start Add Mail -->
    <div id="php_smtp" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">SMTP</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('email-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">From Email Address<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Password<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Host<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Port<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Mail -->

    <!-- Start Add Mail -->
    <div id="test_mail" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Test Mail</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('email-settings')}}">
                    <div class="modal-body">
                        <div class="mb-0">
                            <label class="form-label">Email Address<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Mail -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('email-settings')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->    
@endif

@if (Route::is(['email-templates-settings']))
    <!-- Start Add Categories -->
    <div id="add_reason" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Add Template</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('email-templates-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Template Name<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Content<span class="text-danger ms-1">*</span></label>
                            <div class="editor three-line"></div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Reason</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Categories -->

    <!-- Start Edit Categories -->
    <div id="edit_reason" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Edit Template</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('email-templates-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Template Name<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" value="Welcome Email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Content<span class="text-danger ms-1">*</span></label>
                            <div class="editor three-line">
                                <p>Subject: Welcome to <span class="text-primary">{Company Name}</span> – Let’s Get Started!</p>

                                <p>Dear <span class="text-primary">{Customer Name}</span>,</p>

                                <p>Welcome to {Clinic Name}! Your account has been successfully created in our medical system. You now have secure access to schedule appointments, view your health records, check prescriptions, and see lab results all in one place.</p>

                                <p>You can log in anytime using your email {User Email} and the temporary password provided. For your safety, please make sure to update your password after your first login.</p>

                                <p>If you have any questions or need assistance, feel free to reach out to us at {Support Email} or call us at {Phone Number}.</p>

                                <p>We're excited to support you on your healthcare journey.</p>

                                <p>Best Regards,{Company Name} Team</p>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tags</label>
                            <input class="input-tags form-control" id="inputBox2" type="text" data-role="tagsinput"  name="specialist" value="{Company Name}, {Customer Name}, {Clinic Name}, {User Email}, {Invoice ID}, {Date}, {Support Email}, {Phone Number}, {Invoice Amount}, {Shipping Address}, {Billing Address}, {Doctor Name}, {Patient Name}">
                        </div>
                        <div class="mb-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <label class="form-label">Status<span class="text-danger ms-1">*</span></label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Edit Categories -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_reason">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('email-templates-settings')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['sms-gateways-settings']))
    <!-- Start Add Mail -->
    <div id="add_nexmo" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Nexmo</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('sms-gateways-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">API Key<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">API Secret Key<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Sender ID<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Mail -->

    <!-- Start Add Mail -->
    <div id="php_smtp" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">SMTP</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('sms-gateways-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">From Email Address<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Password<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Host<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Port<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Mail -->

    <!-- Start Add Mail -->
    <div id="test_mail" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Test Mail</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('sms-gateways-settings')}}">
                    <div class="modal-body">
                        <div class="mb-0">
                            <label class="form-label">Email Address<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Mail -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->    
@endif

@if (Route::is(['sms-templates-settings']))
    <!-- Start Add Categories -->
    <div id="add_template" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Add Template</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('sms-templates-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Template Name<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Content</label>
                            <div class="editor three-line"></div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Template</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Categories -->

    <!-- Start Edit Categories -->
    <div id="edit_reason" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Edit Template</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('sms-templates-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Template Name<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" value="Welcome Email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Content</label>
                            <div class="editor three-line">
                                <p>Hi {Patient Name}, your appointment with Dr.{Doctor Name} on {Date} at {Time} is confirmed. – {Clinic Name}</p>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tags</label>
                            <input class="input-tags form-control" id="inputBox2" type="text" data-role="tagsinput"  name="specialist" value="{Phone Number}, {Patient Name}, {Invoice ID}, {Date}, {Amount}, {Company Name}, {Clinic Name}, {Doctor Name}">
                        </div>
                        <div class="mb-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Edit Categories -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_reason">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('sms-templates-settings')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['payment-methods-settings']))
    <!-- Start Add Categories -->
    <div id="add_reason" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Add Payment Method</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('payment-methods-settings')}}">
                    <div class="modal-body">
                        <div class="mb-0">
                            <label class="form-label">Name<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Payment Method</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Categories -->

    <!-- Start Edit Categories -->
    <div id="edit_reason" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Edit Payment Method</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('payment-methods-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" value="Personal Emergency">
                        </div>
                        <div class="mb-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch ps-0">
                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Edit Categories -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_reason">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('payment-methods-settings')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['bank-accounts-settings']))
    <!-- Start Add Categories -->
    <div id="add_reason" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Add Bank Account</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('bank-accounts-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Bank Name<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Account Number<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Account Holder Name<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Branch<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">ABA Number<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Bank Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Categories -->

    <!-- Start Edit Categories -->
    <div id="edit_reason" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Edit Bank Account</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('bank-accounts-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Bank Name<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" value="JPM">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Account Number<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" value="9471 8424 1832">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Account Holder Name<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" value="Andrew Simons">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Branch<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" value="New York">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ABA Number<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" value="021000021">
                        </div>
                        <div class="mb-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch ps-0">
                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Edit Categories -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_reason">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('bank-accounts-settings')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['tax-rates-settings']))
    <!-- Start Add Categories -->
    <div id="add_tax_rate" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Add Tax Rate</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('tax-rates-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tax Name<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Tax Rate (%)<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Tax Rate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Categories -->

    <!-- Start Edit Categories -->
    <div id="edit_tax_rate" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Edit Tax Rate</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('tax-rates-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tax Name<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" value="VAT">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tax Rate (%)<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" value="10%">
                        </div>
                        <div class="mb-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch ps-0">
                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Edit Categories -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_tax_rate">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('tax-rates-settings')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->

    <!-- Start Add Categories -->
    <div id="add_tax_group" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Add Tax Group</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('tax-rates-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tax Name<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Sub Taxes<span class="text-danger ms-1">*</span></label>
                            <input class="input-tags form-control" id="inputBox2" type="text" data-role="tagsinput"  name="specialist">
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Tax Group</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Categories -->

    <!-- Start Edit Categories -->
    <div id="edit_tax_group" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Edit Tax Group</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('tax-rates-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tax Name<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" value="GST">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sub Taxes<span class="text-danger ms-1">*</span></label>
                            <input class="input-tags form-control" type="text" data-role="tagsinput"  name="specialist" value="CGST, SGST">
                        </div>
                        <div class="mb-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch ps-0">
                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Edit Categories -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_tax_group">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('tax-rates-settings')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['currencies-settings']))
    <!-- Start Add Categories -->
    <div id="add_tax_rate" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Add Currency</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('currencies-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Currency Name<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Exchange Rate<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <!-- start row -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">Code<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control">
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">Symbol<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control">
                                </div>
                            </div><!-- end col -->
                        </div>
                        <!-- end row -->
                        <div class="mb-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <label class="form-label">Make a default</label>
                                <div class="form-check form-switch ps-0">
                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Currency</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Categories -->

    <!-- Start Edit Categories -->
    <div id="edit_tax_rate" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Edit Currency</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('currencies-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Currency Name<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" value="Dollar">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Exchange Rate<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" value="01">
                        </div>
                        <!-- start row -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">Code<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" value="USD">
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">Symbol<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" value="$">
                                </div>
                            </div><!-- end col -->
                        </div>
                        <!-- end row -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <label class="form-label">Make a default</label>
                                <div class="form-check form-switch ps-0">
                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                </div>
                            </div>
                        </div>
                        <div class="mb-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch ps-0">
                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Edit Categories -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_tax_rate">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('currencies-settings')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['sitemap-settings']))
    <!-- Start Add Categories -->
    <div id="add_sitemap" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Generate Sitemap</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('sitemap-settings')}}">
                    <div class="modal-body">
                        <div class="mb-0">
                            <label class="form-label">Sitemap URL<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Categories -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_sitemap">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('sitemap-settings')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['storage-settings']))
    <!-- Start Add Categories -->
    <div id="add_storage" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">AWS</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('sitemap-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">AWS Access Key<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Secret Key<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bucket Name<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Region<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Base URL<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Categories -->
@endif

@if (Route::is(['ban-ip-address-settings']))
    <!-- Start Add Categories -->
    <div id="add_ip_address" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Add Ban IP Address</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('ban-ip-address-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">IP Address<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">IP Address<span class="text-danger ms-1">*</span></label>
                            <textarea class="form-control" rows="3" placeholder="Description"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Ban IP Address</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Categories -->

    <!-- Start Add Categories -->
    <div id="edit_ip_address" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="text-dark modal-title fw-bold">Edit Ban IP Address</h5>
                    <button type="button" class="btn-close btn-close-modal custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form action="{{url('ban-ip-address-settings')}}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">IP Address<span class="text-danger ms-1">*</span></label>
                            <input type="text" class="form-control" value="211.11.0.25">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">IP Address<span class="text-danger ms-1">*</span></label>
                            <textarea class="form-control" rows="3">You can get on-demand services in order to find a nearby service.</textarea>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Categories -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_ip_address">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('ban-ip-address-settings')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->
@endif

@if (Route::is(['system-backup-settings']))
    <!-- Start Generate Modal  -->
    <div class="modal fade" id="generate">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/generate-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/generate-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-info text-white"><i class="ti ti-folder fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Generate Backup</h5>
                    <p class="mb-3">Are you sure want to generate?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('database-backup-settings')}}" class="btn btn-primary position-relative z-1">Generate</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Generate Modal  -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0 z-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0 z-0">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1 position-relative z-1">Delete Confirmation</h5>
                    <p class="mb-3 position-relative z-1">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="" class="btn btn-danger position-relative z-1" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->       
@endif

@if (Route::is(['database-backup-settings']))
    <!-- Start Generate Modal  -->
    <div class="modal fade" id="generate">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/generate-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/generate-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-info text-white"><i class="ti ti-folder fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Generate Backup</h5>
                    <p class="mb-3">Are you sure want to generate?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('database-backup-settings')}}" class="btn btn-primary position-relative z-1">Generate</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Generate Modal  -->

    <!-- Start Delete Modal  -->
    <div class="modal fade" id="delete_backup">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center position-relative">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-01.png')}}" alt="" class="img-fluid position-absolute top-0 start-0">
                    <img src="{{URL::asset('build/img/bg/delete-modal-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                    <div class="mb-3">
                        <span class="avatar avatar-lg bg-danger text-white"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure want to delete?</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light position-relative z-1 me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="{{url('database-backup-settings')}}" class="btn btn-danger position-relative z-1">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete Modal  -->    
@endif