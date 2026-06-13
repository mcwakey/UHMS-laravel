(function(){var g=$(".main-wrapper");$(".page-wrapper"),$("body").append('<div class="sidebar-overlay"></div>'),$(document).on("click","#mobile_btn",function(){return g.toggleClass("slide-nav"),$(".sidebar-overlay").toggleClass("opened"),$("html").addClass("menu-opened"),!1}),$(".sidebar-close").on("click",function(){g.removeClass("slide-nav"),$(".sidebar-overlay").removeClass("opened"),$("html").removeClass("menu-opened")}),$(".sidebar-overlay").on("click",function(){$("html").removeClass("menu-opened"),$(this).removeClass("opened"),g.removeClass("slide-nav"),$(".sidebar-overlay").removeClass("opened")});function D(){$(".sidebar-menu a").on("click",function(t){$(this).parent().hasClass("submenu")&&t.preventDefault(),$(this).hasClass("subdrop")?$(this).hasClass("subdrop")&&($(this).removeClass("subdrop"),$(this).next("ul").slideUp(350)):($("ul",$(this).parents("ul:first")).slideUp(250),$("a",$(this).parents("ul:first")).removeClass("subdrop"),$(this).next("ul").slideDown(350),$(this).addClass("subdrop"))}),$(".sidebar-menu ul li.submenu a.active").parents("li:last").children("a:first").addClass("active").trigger("click")}$(".trial-item").length>0&&$(".trial-item .close-icon").on("click",function(){$(this).closest(".trial-item").hide()}),D(),$(document).on("mouseover",function(t){if(t.stopPropagation(),$("body").hasClass("mini-sidebar")&&$("#toggle_btn").is(":visible")){var e=$(t.target).closest(".sidebar, .header-left").length;return e?($("body").addClass("expand-menu"),$(".subdrop + ul").slideDown()):($("body").removeClass("expand-menu"),$(".subdrop + ul").slideUp()),!1}});var C="#select-all",b=".form-check.form-check-md :checkbox";if($(C).on("click",function(){this.checked?$(b).each(function(){this.checked=!0}):$(b).each(function(){this.checked=!1})}),$(document).on("click","#toggle_btn",function(){const t=$("body"),e=$("html"),i=t.hasClass("mini-sidebar"),n=e.attr("data-layout")==="full-width",o=e.attr("data-layout")==="hidden";return i?(t.removeClass("mini-sidebar"),$(this).addClass("active"),localStorage.setItem("screenModeNightTokenState","night"),setTimeout(function(){$(".header-left").addClass("active")},100)):(t.addClass("mini-sidebar"),$(this).removeClass("active"),localStorage.removeItem("screenModeNightTokenState"),setTimeout(function(){$(".header-left").removeClass("active")},100)),n?(t.addClass("full-width").removeClass("mini-sidebar"),$(".sidebar-overlay").addClass("opened"),$(document).on("click",".sidebar-close",function(){$("body").removeClass("full-width")})):t.removeClass("full-width"),o&&(t.toggleClass("hidden-layout"),t.removeClass("mini-sidebar"),$(document).on("click",".sidebar-close",function(){$("body").removeClass("full-width")})),!1}),$(document).on("click","#toggle_btn2",function(){const t=$("body"),e=$("html"),i=t.hasClass("mini-sidebar"),n=e.attr("data-layout")==="full-width",o=e.attr("data-layout")==="hidden";return i?(t.removeClass("mini-sidebar"),$(this).addClass("active"),localStorage.setItem("screenModeNightTokenState","night"),setTimeout(function(){$(".header-left").addClass("active")},100)):(t.addClass("mini-sidebar"),$(this).removeClass("active"),localStorage.removeItem("screenModeNightTokenState"),setTimeout(function(){$(".header-left").removeClass("active")},100)),n?(t.addClass("full-width").removeClass("mini-sidebar"),$(".sidebar-overlay").addClass("opened"),$(document).on("click",".sidebar-close",function(){$("body").removeClass("full-width")})):t.removeClass("full-width"),o&&(t.toggleClass("hidden-layout"),t.removeClass("mini-sidebar"),$(document).on("click",".sidebar-close",function(){$("body").removeClass("full-width")})),!1}),$(".select2").length>0&&$(".select2").select2(),$(".select").length>0&&$(".select").select2({minimumResultsForSearch:-1,width:"100%"}),document.addEventListener("DOMContentLoaded",function(){if(document.querySelector(".filter-dropdown")){const t=document.getElementById("close-filter"),e=document.getElementById("filter-dropdown");t&&e&&t.addEventListener("click",function(){e.classList.remove("show")})}}),$(".editor").length>0&&document.querySelectorAll(".editor").forEach(t=>{new Quill(t,{theme:"snow"})}),$(".toggle-password").length>0&&$(document).on("click",".toggle-password",function(){$(this).toggleClass("ti-eye-off ti-eye-slash");var t=$(".pass-input");t.attr("type")=="password"?t.attr("type","text"):t.attr("type","password")}),$(".toggle-passwords").length>0&&$(document).on("click",".toggle-passwords",function(){$(this).toggleClass("ti-eye-off ti-eye-slash");var t=$(".pass-inputs");t.attr("type")=="password"?t.attr("type","text"):t.attr("type","password")}),$(".toggle-passworda").length>0&&$(document).on("click",".toggle-passworda",function(){$(this).toggleClass("ti-eye-off ti-eye-slash");var t=$(".pass-inputa");t.attr("type")=="password"?t.attr("type","text"):t.attr("type","password")}),$(".toggle-passwordb").length>0&&$(document).on("click",".toggle-passwordb",function(){$(this).toggleClass("ti-eye-off ti-eye-slash");var t=$(".pass-inputb");t.attr("type")=="password"?t.attr("type","text"):t.attr("type","password")}),$(".toggle-passwordc").length>0&&$(document).on("click",".toggle-passwordc",function(){$(this).toggleClass("ti-eye-off ti-eye-slash");var t=$(".pass-inputc");t.attr("type")=="password"?t.attr("type","text"):t.attr("type","password")}),document.addEventListener("DOMContentLoaded",function(){document.addEventListener("click",function(t){if(t.target.classList.contains("close-filter")){const e=t.target.closest(".dropdown-info");e&&(e.classList.remove("show"),console.log("Dropdown closed:",e))}})}),document.addEventListener("DOMContentLoaded",function(){if(document.querySelector(".filter-dropdown")){const t=document.getElementById("close-filter"),e=document.getElementById("filter-dropdown");t&&e&&t.addEventListener("click",function(){e.classList.remove("show")})}}),$("#phone").length>0){var h=document.querySelector("#phone");window.intlTelInput(h,{utilsScript:"build/plugins/intltelinput/js/utils.js"})}if($("#phone2").length>0){var h=document.querySelector("#phone2");window.intlTelInput(h,{utilsScript:"build/plugins/intltelinput/js/utils.js"})}if($("#phone3").length>0){var h=document.querySelector("#phone3");window.intlTelInput(h,{utilsScript:"build/plugins/intltelinput/js/utils.js"})}document.addEventListener("DOMContentLoaded",function(){if(document.getElementById("profilePage")){const e=document.getElementById("uploadTrigger"),i=document.getElementById("profileUpload");e&&i&&e.addEventListener("click",function(){i.click()})}}),document.addEventListener("DOMContentLoaded",function(){if(document.getElementById("profilePage")){const e=document.getElementById("uploadTrigger1"),i=document.getElementById("profileUpload1");e&&i&&e.addEventListener("click",function(){i.click()})}}),document.addEventListener("DOMContentLoaded",function(){if(document.getElementById("profilePage")){const e=document.getElementById("uploadTrigger2"),i=document.getElementById("profileUpload2");e&&i&&e.addEventListener("click",function(){i.click()})}}),document.addEventListener("DOMContentLoaded",function(){if(document.getElementById("profilePage")){const e=document.getElementById("uploadTrigger3"),i=document.getElementById("profileUpload3");e&&i&&e.addEventListener("click",function(){i.click()})}}),document.addEventListener("DOMContentLoaded",function(){if(document.getElementById("profilePage")){const e=document.getElementById("uploadTrigger4"),i=document.getElementById("profileUpload4");e&&i&&e.addEventListener("click",function(){i.click()})}}),document.addEventListener("DOMContentLoaded",function(){if(document.getElementById("profilePage")){const e=document.getElementById("uploadTrigger5"),i=document.getElementById("profileUpload5");e&&i&&e.addEventListener("click",function(){i.click()})}}),$(".datepic").length>0&&$(".datepic").datetimepicker({format:"DD-MM-YYYY",keepOpen:!0,inline:!0,icons:{up:"fas fa-angle-up",down:"fas fa-angle-down",next:"fas fa-angle-right",previous:"fas fa-angle-left"}});var y=window.UHMS_I18N||{};if($(".datatable").length>0&&$(".datatable").DataTable({bFilter:!0,sDom:"fBtlpi",ordering:!1,language:{search:" ",sLengthMenu:"_MENU_",searchPlaceholder:y.dt_search_placeholder||"Search",info:y.dt_info||"_START_ - _END_ of _TOTAL_ items",paginate:{next:'<i class="ti ti-arrow-right"></i>',previous:'<i class="ti ti-arrow-left text-body"></i> '}},responsive:!0,autoWidth:!1,initComplete:(t,e)=>{$(".dataTables_filter").appendTo("#tableSearch"),$(".dataTables_filter").appendTo(".search-input")}}),$(".datetimepicker").length>0&&$(".datetimepicker").datetimepicker({format:"DD-MM-YYYY",icons:{up:"fas fa-angle-up",down:"fas fa-angle-down",next:"fas fa-angle-right",previous:"fas fa-angle-left"}}),$(".timepicker").length>0&&$(".timepicker").datetimepicker({format:"HH:mm A",icons:{up:"fas fa-angle-up",down:"fas fa-angle-down",next:"fas fa-angle-right",previous:"fas fa-angle-left"}}),$("#reportrange").length>0){let t=function(e,i){$("#reportrange span").html(e.format("D MMM YY")+" - "+i.format("D MMM YY"))};var w=t,s=moment().subtract(29,"days"),a=moment(),u=window.UHMS_I18N||{},l={};l[u.today||"Today"]=[moment(),moment()],l[u.yesterday||"Yesterday"]=[moment().subtract(1,"days"),moment().subtract(1,"days")],l[u.last_7_days||"Last 7 Days"]=[moment().subtract(6,"days"),moment()],l[u.last_30_days||"Last 30 Days"]=[moment().subtract(29,"days"),moment()],l[u.this_month||"This Month"]=[moment().startOf("month"),moment().endOf("month")],l[u.last_month||"Last Month"]=[moment().subtract(1,"month").startOf("month"),moment().subtract(1,"month").endOf("month")],$("#reportrange").daterangepicker({startDate:s,endDate:a,ranges:l},t),t(a,a)}if($(".reportrange").length>0){let i=function(o,v){$(".reportrange span").html(o.format("D MMM YY")+" - "+v.format("D MMM YY"))};var w=i,s=moment().subtract(29,"days"),a=moment(),f=window.UHMS_I18N||{},d={};d[f.today||"Today"]=[moment(),moment()],d[f.yesterday||"Yesterday"]=[moment().subtract(1,"days"),moment().subtract(1,"days")],d[f.last_7_days||"Last 7 Days"]=[moment().subtract(6,"days"),moment()],d[f.last_30_days||"Last 30 Days"]=[moment().subtract(29,"days"),moment()],d[f.this_month||"This Month"]=[moment().startOf("month"),moment().endOf("month")],d[f.last_month||"Last Month"]=[moment().subtract(1,"month").startOf("month"),moment().subtract(1,"month").endOf("month")],$(".reportrange").daterangepicker({startDate:s,endDate:a,ranges:d},i),i(a,a)}if($(".bookingrange").length>0){let i=function(n,o){$(".bookingrange span").html(n.format("D MMM YY")+" - "+o.format("D MMM YY"))};var L=i,s=moment().subtract(6,"days"),a=moment(),p=window.UHMS_I18N||{},r={};r[p.today||"Today"]=[moment(),moment()],r[p.yesterday||"Yesterday"]=[moment().subtract(1,"days"),moment().subtract(1,"days")],r[p.last_7_days||"Last 7 Days"]=[moment().subtract(6,"days"),moment()],r[p.last_30_days||"Last 30 Days"]=[moment().subtract(29,"days"),moment()],r[p.this_year||"This Year"]=[moment().startOf("year"),moment().endOf("year")],r[p.last_year||"Last Year"]=[moment().subtract(1,"year").startOf("year"),moment().subtract(1,"year").endOf("year")],$(".bookingrange").daterangepicker({startDate:s,endDate:a,ranges:r},i),i(s,a)}if($(".daterange").length>0){var c=window.UHMS_I18N||{},m={};m[c.today||"Today"]=[moment(),moment()],m[c.yesterday||"Yesterday"]=[moment().subtract(1,"days"),moment().subtract(1,"days")],m[c.last_7_days||"Last 7 Days"]=[moment().subtract(6,"days"),moment()],m[c.last_30_days||"Last 30 Days"]=[moment().subtract(29,"days"),moment()],m[c.this_year||"This Year"]=[moment().startOf("year"),moment().endOf("year")],m[c.next_year||"Next Year"]=[moment().add(1,"year").startOf("year"),moment().add(1,"year").endOf("year")],$(".daterange").daterangepicker({autoUpdateInput:!1,ranges:m,locale:{cancelLabel:c.clear||"Clear"}}),$("#daterange").on("input",function(){var t=$(this).val().length;$(this).css("width",t+10+"px")}),$(".daterange").on("apply.daterangepicker",function(t,e){$(this).val(e.startDate.format("MM/DD/YYYY")+" - "+e.endDate.format("MM/DD/YYYY"))}),$(".daterange").on("cancel.daterangepicker",function(t,e){$(this).val("")})}if($(document).on("click",".add-complaint",function(t){t.preventDefault(),$(this).closest(".complaint-list-item").before(`
		<div class="mb-3 complaint-list-item">
			<div class="input-group">
			<input type="text" class="form-control rounded" />
			<a href="#" class="remove-complaint ms-3 p-2 bg-light text-danger rounded d-flex align-items-center justify-content-center">
				<i class="ti ti-trash fs-16"></i>
			</a>
			</div>
		</div>
		`)}),$(document).on("click",".remove-complaint",function(t){t.preventDefault(),$(this).closest(".complaint-list-item").remove()}),$(document).on("click",".add-advices",function(t){t.preventDefault(),$(this).closest(".advices-list-item").before(`
			<div class="mb-3 advices-list-item">
				<label class="form-label mb-1 text-dark fs-14 fw-medium">Advice</label>
				<div class="input-group">
					<input type="text" class="form-control rounded" />
					<a href="#" class="remove-advices ms-3 p-2 bg-light text-danger rounded d-flex align-items-center justify-content-center"><i class="ti ti-trash fs-16"></i></a>
				</div>
			</div>
		`)}),$(document).on("click",".remove-advices",function(t){t.preventDefault(),$(this).closest(".advices-list-item").remove()}),$(document).on("click",".add-invest",function(t){t.preventDefault(),$(this).closest(".invest-list-item").before(`
			<div class="mb-3 invest-list-item">
				<label class="form-label mb-1 text-dark fs-14 fw-medium">Investigation & Procedure</label>
				<div class="input-group">
					<input type="text" class="form-control rounded" />
					<a href="#" class="remove-invest ms-3 p-2 bg-light text-danger rounded d-flex align-items-center justify-content-center"><i class="ti ti-trash fs-16"></i></a>
				</div>
			</div>
		`)}),$(document).on("click",".remove-invest",function(t){t.preventDefault(),$(this).closest(".invest-list-item").remove()}),$(document).ready(function(){$(document).off("click",".add-diagnosis").on("click",".add-diagnosis",function(t){t.preventDefault();const e=`
				<div class="row diagnosis-list-item">
					<div class="col-lg-6">
						<div class="mb-3">
							<select class="select form-control rounded">
								<option>Select</option>
								<option>Fever</option>
								<option>Headache</option>
								<option>Joint Pain</option>
								<option>Skin Rash</option>
								<option>Back Pain</option>
							</select>
						</div>
					</div> 
	
					<div class="col-lg-6">
						<div class="mb-3">
							<div class="input-group">
								<input type="text" class="form-control rounded" />
								<a href="#" class="remove-diagnosis ms-3 p-2 bg-light text-danger rounded d-flex align-items-center justify-content-center">
									<i class="ti ti-trash fs-16"></i>
								</a>
							</div>
						</div>
					</div> 
				</div>
			`;setTimeout(function(){$(".select"),setTimeout(function(){$(".select").select2({minimumResultsForSearch:-1,width:"100%"})},100)},100),$(".diagnosis-list").append(e)}),$(document).off("click",".remove-diagnosis").on("click",".remove-diagnosis",function(t){t.preventDefault(),$(this).closest(".diagnosis-list-item").remove()})}),$(document).ready(function(){$(document).off("click",".add-reminder").on("click",".add-reminder",function(t){t.preventDefault();const e=`
					<div class="row d-flex align-items-center mb-3 reminder-list-item">
						<div class="col-md-2">
							<h6 class="fs-14 fw-medium mb-0">Reminder </h6>
						</div>
						<div class="col-md-10 flex-grow-1">
							<div class="d-flex align-items-center justify-content-end">
								<div class="me-2">
									<select class="select me-2">
										<option selected>Email</option>
                                        <option>SMS</option>
									</select>
								</div>
								<div class="me-2">
									<select class="select me-2">
										<option>Select</option>
										<option>Welcome Email</option>
										<option selected>Appointment Reminder</option>
										<option>Appointment Confirmation</option>
										<option>Appointment Rescheduled</option>
										<option>Appointment Cancelled</option>
										<option>Test Result Notification</option>
									</select>
								</div>
								<div class="me-2">
									<select class="select me-2">
										<option>01</option>
										<option>02</option>
										<option>03</option>
										<option>05</option>
										<option>10</option>
									</select>
								</div>
								<span class="me-2">
									Days Before
								</span>
								<div class="d-flex align-items-center">
									<a href="javascript:void(0);" class="btn btn-white p-2 border rounded-2 me-2"><i class="ti ti-edit"></i></a>
									<a href="javascript:void(0);" class="btn btn-white p-2 border rounded-2 remove-reminder"><i class="ti ti-trash"></i></a>
								</div>
							</div>
						</div>
					</div>
			`;setTimeout(function(){$(".select"),setTimeout(function(){$(".select").select2({minimumResultsForSearch:-1,width:"100%"})},100)},100),$(".reminder-list").append(e)}),$(document).off("click",".remove-reminder").on("click",".remove-reminder",function(t){t.preventDefault(),$(this).closest(".reminder-list-item").remove()})}),$(document).on("click",".add-invoice",function(t){t.preventDefault();const e=`
			<div class="row invoice-list-item">
				<div class="col-lg-8">
					<div class="mb-3">
						<select class="select form-control rounded">
							<option>Select</option>
							<option>General Consultation</option>
							<option>Dental Cleaning</option>
							<option>Eye Checkup</option>
							<option>Blood Test</option>
							<option>Skin Allergy Test</option>
						</select>
					</div>
				</div> <!-- end col -->

				<div class="col-lg-4">
					<div class="mb-3">
						<div class="input-group">
							<input type="text" class="form-control rounded" />
							<a href="#" class="remove-invoice ms-3 p-2 bg-light text-danger rounded d-flex align-items-center justify-content-center"><i class="ti ti-trash fs-16"></i></a>
						</div>
					</div>
				</div> <!-- start row -->
			</div>
			<!-- end row -->
		`;setTimeout(function(){$(".select"),setTimeout(function(){$(".select").select2({minimumResultsForSearch:-1,width:"100%"})},100)},100),$(this).closest(".invoice-list-item").before(e)}),$(document).on("click",".remove-invoice",function(t){t.preventDefault(),$(this).closest(".invoice-list-item").remove()}),$(document).on("click",".add-medication",function(t){t.preventDefault(),$(this).closest(".medication-list-item").before(`
			<!-- start row -->
			<div class="row medication-list-item">
				<div class="col-lg-11">
					<!-- start row-->
					<div class="row">
						<div class="col-lg-2">
							<div class="mb-3">
								<label class="form-label mb-1 text-dark fs-14 fw-medium">Medicine Name</label>
								<input type="text" class="form-control">
							</div>
						</div> <!-- end col -->

						<div class="col-lg-2">
							<div class="mb-3">
								<label class="form-label mb-1 text-dark fs-14 fw-medium">Dosage</label>
								<div class="input-group">
									<input type="text" class="form-control">
									<span class="input-group-text bg-transparent text-dark fs-14" id="inputGroupPrepend">mg</span>
								</div>
							</div>
						</div> <!-- end col -->

						<div class="col-lg-2">
							<div class="mb-3">
								<label class="form-label mb-1 text-dark fs-14 fw-medium">Dosage</label>
								<div class="input-group">
									<input type="text" class="form-control">
									<span class="input-group-text bg-transparent text-dark fs-14" id="inputGroupPrepend">m</span>
								</div>
							</div>
						</div> <!-- end col -->

						<div class="col-lg-2">
							<div class="mb-3">
								<label class="form-label mb-1 text-dark fs-14 fw-medium">Frequency</label>
								<select class="select form-control rounded">
									<option>Select</option>
									<option>0-0-1</option>
									<option>1-0-0</option>
									<option>0-1-0</option>
								</select>
							</div>
						</div> <!-- end col -->

						<div class="col-lg-2">
							<div class="mb-3">
								<label class="form-label mb-1 text-dark fs-14 fw-medium">Timing</label>
								<select class="select form-control rounded">
									<option>Select</option>
									<option>Morning</option>
									<option>Afternoon</option> 
								</select>
							</div>
						</div> <!-- end col -->

						<div class="col-lg-2">
							<div class="mb-3">
								<label class="form-label mb-1 text-dark fs-14 fw-medium">Instruction</label>
								<div class="input-group">
									<input type="text" class="form-control">
								</div>
							</div>
						</div> <!-- end col -->
					</div>
				</div>
				<div class="col-lg-1 px-xxl-3">
					<label class="form-label mb-1 text-dark fs-14 fw-medium"></label>
					<a href="#" class="remove-medication ms-3 p-2 bg-light text-danger rounded d-flex align-items-center justify-content-center"><i class="ti ti-trash fs-16"></i></a>
				</div>
			</div>
			<!-- end row -->
		`)}),$(document).on("click",".remove-medication",function(t){t.preventDefault(),$(this).closest(".medication-list-item").remove()}),$(document).on("click",".add-invoices",function(t){t.preventDefault(),$(".invoices-list tr:last").before(`
			<tr class="invoices-list-item">
				<td><input type="text" class="form-control" /></td>
				<td><input type="text" class="form-control" /></td>
				<td><input type="number" class="form-control" /></td>
				<td><input type="number" class="form-control" /></td>
				<td><input type="text" class="form-control" readonly /></td>
				<td><button class="btn remove-invoices btn-sm border shadow-sm p-2 d-flex align-items-center justify-content-center rounded fs-14">
					<i class="ti ti-trash"></i>
				</button></td>
			</tr>
		`)}),$(document).on("click",".remove-invoices",function(t){t.preventDefault(),$(this).closest(".invoices-list-item").remove()}),document.querySelectorAll(".toggle-star").forEach(function(t){t.addEventListener("click",function(){this.classList.toggle("active")})}),$('[data-bs-toggle="tooltip"]').length>0){var x=[].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));x.map(function(t){return new bootstrap.Tooltip(t)})}$(".invoice-template").on("click",function(){$(".invoice-template").removeClass("active"),$(this).addClass("active")}),document.addEventListener("DOMContentLoaded",function(){const t=document.getElementById("break-hours-section");if(!t)return;const e=t.querySelector(".add-break"),i=t.querySelector(".break1");if(!e||!i)return;e.addEventListener("click",function(){const o=i.cloneNode(!0);o.querySelectorAll("input").forEach(M=>M.value="");const v=o.querySelector("p");v&&(v.textContent="New Break");const k=o.querySelector(".ti-trash");k&&k.addEventListener("click",function(){o.remove()}),i.parentNode.insertBefore(o,null)});const n=i.querySelector(".ti-trash");n&&n.addEventListener("click",function(){i.remove()})}),[...document.querySelectorAll('[data-bs-toggle="popover"]')].map(t=>new bootstrap.Popover(t));function S(){document.querySelectorAll("[data-choices]").forEach(t=>{const e={allowHTML:!0},i=t.attributes;i["data-choices-groups"]&&(e.placeholderValue="This is a placeholder set in the config"),i["data-choices-search-false"]&&(e.searchEnabled=!1),i["data-choices-search-true"]&&(e.searchEnabled=!0),i["data-choices-removeItem"]&&(e.removeItemButton=!0),i["data-choices-sorting-false"]&&(e.shouldSort=!1),i["data-choices-sorting-true"]&&(e.shouldSort=!0),i["data-choices-multiple-remove"]&&(e.removeItemButton=!0),i["data-choices-limit"]&&(e.maxItemCount=parseInt(i["data-choices-limit"].value)),i["data-choices-editItem-true"]&&(e.editItems=!0),i["data-choices-editItem-false"]&&(e.editItems=!1),i["data-choices-text-unique-true"]&&(e.duplicateItemsAllowed=!1),i["data-choices-text-disabled-true"]&&(e.addItems=!1);const n=new Choices(t,e);i["data-choices-text-disabled-true"]&&n.disable()})}if(document.addEventListener("DOMContentLoaded",S),document.querySelectorAll('[data-provider="flatpickr"]').forEach(t=>{const e={disableMobile:!0};if(t.hasAttribute("data-date-format")&&(e.dateFormat=t.getAttribute("data-date-format")),t.hasAttribute("data-enable-time")&&(e.enableTime=!0,e.dateFormat=e.dateFormat?`${e.dateFormat} H:i`:"Y-m-d H:i"),t.hasAttribute("data-altFormat")&&(e.altInput=!0,e.altFormat=t.getAttribute("data-altFormat")),t.hasAttribute("data-minDate")&&(e.minDate=t.getAttribute("data-minDate")),t.hasAttribute("data-maxDate")&&(e.maxDate=t.getAttribute("data-maxDate")),t.hasAttribute("data-default-date")){const i=t.getAttribute("data-default-date");!["true","false","",null].includes(i)&&!isNaN(Date.parse(i))&&(e.defaultDate=i)}if(t.hasAttribute("data-multiple-date")&&(e.mode="multiple"),t.hasAttribute("data-range-date")&&(e.mode="range"),t.hasAttribute("data-inline-date")){e.inline=!0;const i=t.getAttribute("data-inline-date");!["true","false","",null].includes(i)&&!isNaN(Date.parse(i))&&(e.defaultDate=i)}t.hasAttribute("data-disable-date")&&(e.disable=t.getAttribute("data-disable-date").split(",")),t.hasAttribute("data-week-number")&&(e.weekNumbers=!0),flatpickr(t,e)}),document.querySelectorAll('[data-provider="timepickr"]').forEach(t=>{const e=t.attributes,i={enableTime:!0,noCalendar:!0,dateFormat:"H:i"};e["data-time-hrs"]&&(i.time_24hr=!0),e["data-min-time"]&&(i.minTime=e["data-min-time"].value),e["data-max-time"]&&(i.maxTime=e["data-max-time"].value),e["data-default-time"]&&(i.defaultDate=e["data-default-time"].value),e["data-time-inline"]&&(i.inline=!0,i.defaultDate=e["data-time-inline"].value),flatpickr(t,i)}),jQuery().select2&&$('[data-toggle="select2"]').each(function(){const t=$(this),e={};t.attr("data-placeholder")&&(e.placeholder=t.attr("data-placeholder")),t.attr("data-allow-clear")==="true"&&(e.allowClear=!0),t.attr("data-tags")==="true"&&(e.tags=!0),t.attr("data-max-selections")&&(e.maximumSelectionLength=parseInt(t.attr("data-max-selections"))),t.attr("data-ajax--url")&&(e.ajax={url:t.attr("data-ajax--url"),dataType:"json",delay:250,data:function(i){return{q:i.term,page:i.page||1}},processResults:function(i,n){return n.page=n.page||1,{results:i.items||[],pagination:{more:i.more}}},cache:!0}),t.select2(e)}),$(".select").length>0&&$(".select").select2({minimumResultsForSearch:-1,width:"100%"}),$(window).width()>767&&$(".theiaStickySidebar").length>0&&$(".theiaStickySidebar").theiaStickySidebar({additionalMarginTop:30}),$(".daterangepick").length>0){let i=function(o,v){$(".daterangepick span").html(o.format("D MMM YY")+" - "+v.format("D MMM YY"))};var w=i,s=moment().subtract(29,"days"),a=moment();$(".daterangepick").daterangepicker({startDate:s,endDate:a,ranges:{Today:[moment(),moment()],Yesterday:[moment().subtract(1,"days"),moment().subtract(1,"days")],"Last 7 Days":[moment().subtract(6,"days"),moment()],"Last 30 Days":[moment().subtract(29,"days"),moment()],"This Month":[moment().startOf("month"),moment().endOf("month")],"Last Month":[moment().subtract(1,"month").startOf("month"),moment().subtract(1,"month").endOf("month")]}},i),i(a,a)}})();
