(function(){var d=$(".main-wrapper");$(".page-wrapper"),$("body").append('<div class="sidebar-overlay"></div>'),$(document).on("click","#mobile_btn",function(){return d.toggleClass("slide-nav"),$(".sidebar-overlay").toggleClass("opened"),$("html").addClass("menu-opened"),!1}),$(".sidebar-close").on("click",function(){d.removeClass("slide-nav"),$(".sidebar-overlay").removeClass("opened"),$("html").removeClass("menu-opened")}),$(".sidebar-overlay").on("click",function(){$("html").removeClass("menu-opened"),$(this).removeClass("opened"),d.removeClass("slide-nav"),$(".sidebar-overlay").removeClass("opened")});function f(){$(".sidebar-menu a").on("click",function(e){$(this).parent().hasClass("submenu")&&e.preventDefault(),$(this).hasClass("subdrop")?$(this).hasClass("subdrop")&&($(this).removeClass("subdrop"),$(this).next("ul").slideUp(350)):($("ul",$(this).parents("ul:first")).slideUp(250),$("a",$(this).parents("ul:first")).removeClass("subdrop"),$(this).next("ul").slideDown(350),$(this).addClass("subdrop"))}),$(".sidebar-menu ul li.submenu a.active").parents("li:last").children("a:first").addClass("active").trigger("click")}$(".trial-item").length>0&&$(".trial-item .close-icon").on("click",function(){$(this).closest(".trial-item").hide()}),f(),$(document).on("mouseover",function(e){if(e.stopPropagation(),$("body").hasClass("mini-sidebar")&&$("#toggle_btn").is(":visible")){var t=$(e.target).closest(".sidebar, .header-left").length;return t?($("body").addClass("expand-menu"),$(".subdrop + ul").slideDown()):($("body").removeClass("expand-menu"),$(".subdrop + ul").slideUp()),!1}});var p="#select-all",c=".form-check.form-check-md :checkbox";if($(p).on("click",function(){this.checked?$(c).each(function(){this.checked=!0}):$(c).each(function(){this.checked=!1})}),$(document).on("click","#toggle_btn",function(){const e=$("body"),t=$("html"),i=e.hasClass("mini-sidebar"),a=t.attr("data-layout")==="full-width",n=t.attr("data-layout")==="hidden";return i?(e.removeClass("mini-sidebar"),$(this).addClass("active"),localStorage.setItem("screenModeNightTokenState","night"),setTimeout(function(){$(".header-left").addClass("active")},100)):(e.addClass("mini-sidebar"),$(this).removeClass("active"),localStorage.removeItem("screenModeNightTokenState"),setTimeout(function(){$(".header-left").removeClass("active")},100)),a?(e.addClass("full-width").removeClass("mini-sidebar"),$(".sidebar-overlay").addClass("opened"),$(document).on("click",".sidebar-close",function(){$("body").removeClass("full-width")})):e.removeClass("full-width"),n&&(e.toggleClass("hidden-layout"),e.removeClass("mini-sidebar"),$(document).on("click",".sidebar-close",function(){$("body").removeClass("full-width")})),!1}),$(document).on("click","#toggle_btn2",function(){const e=$("body"),t=$("html"),i=e.hasClass("mini-sidebar"),a=t.attr("data-layout")==="full-width",n=t.attr("data-layout")==="hidden";return i?(e.removeClass("mini-sidebar"),$(this).addClass("active"),localStorage.setItem("screenModeNightTokenState","night"),setTimeout(function(){$(".header-left").addClass("active")},100)):(e.addClass("mini-sidebar"),$(this).removeClass("active"),localStorage.removeItem("screenModeNightTokenState"),setTimeout(function(){$(".header-left").removeClass("active")},100)),a?(e.addClass("full-width").removeClass("mini-sidebar"),$(".sidebar-overlay").addClass("opened"),$(document).on("click",".sidebar-close",function(){$("body").removeClass("full-width")})):e.removeClass("full-width"),n&&(e.toggleClass("hidden-layout"),e.removeClass("mini-sidebar"),$(document).on("click",".sidebar-close",function(){$("body").removeClass("full-width")})),!1}),$(".select2").length>0&&$(".select2").select2(),$(".select").length>0&&$(".select").select2({minimumResultsForSearch:-1,width:"100%"}),document.addEventListener("DOMContentLoaded",function(){if(document.querySelector(".filter-dropdown")){const e=document.getElementById("close-filter"),t=document.getElementById("filter-dropdown");e&&t&&e.addEventListener("click",function(){t.classList.remove("show")})}}),$(".editor").length>0&&document.querySelectorAll(".editor").forEach(e=>{new Quill(e,{theme:"snow"})}),$(".toggle-password").length>0&&$(document).on("click",".toggle-password",function(){$(this).toggleClass("ti-eye-off ti-eye-slash");var e=$(".pass-input");e.attr("type")=="password"?e.attr("type","text"):e.attr("type","password")}),$(".toggle-passwords").length>0&&$(document).on("click",".toggle-passwords",function(){$(this).toggleClass("ti-eye-off ti-eye-slash");var e=$(".pass-inputs");e.attr("type")=="password"?e.attr("type","text"):e.attr("type","password")}),$(".toggle-passworda").length>0&&$(document).on("click",".toggle-passworda",function(){$(this).toggleClass("ti-eye-off ti-eye-slash");var e=$(".pass-inputa");e.attr("type")=="password"?e.attr("type","text"):e.attr("type","password")}),$(".toggle-passwordb").length>0&&$(document).on("click",".toggle-passwordb",function(){$(this).toggleClass("ti-eye-off ti-eye-slash");var e=$(".pass-inputb");e.attr("type")=="password"?e.attr("type","text"):e.attr("type","password")}),$(".toggle-passwordc").length>0&&$(document).on("click",".toggle-passwordc",function(){$(this).toggleClass("ti-eye-off ti-eye-slash");var e=$(".pass-inputc");e.attr("type")=="password"?e.attr("type","text"):e.attr("type","password")}),document.addEventListener("DOMContentLoaded",function(){document.addEventListener("click",function(e){if(e.target.classList.contains("close-filter")){const t=e.target.closest(".dropdown-info");t&&(t.classList.remove("show"),console.log("Dropdown closed:",t))}})}),document.addEventListener("DOMContentLoaded",function(){if(document.querySelector(".filter-dropdown")){const e=document.getElementById("close-filter"),t=document.getElementById("filter-dropdown");e&&t&&e.addEventListener("click",function(){t.classList.remove("show")})}}),$("#phone").length>0){var r=document.querySelector("#phone");window.intlTelInput(r,{utilsScript:"build/plugins/intltelinput/js/utils.js"})}if($("#phone2").length>0){var r=document.querySelector("#phone2");window.intlTelInput(r,{utilsScript:"build/plugins/intltelinput/js/utils.js"})}if($("#phone3").length>0){var r=document.querySelector("#phone3");window.intlTelInput(r,{utilsScript:"build/plugins/intltelinput/js/utils.js"})}if(document.addEventListener("DOMContentLoaded",function(){if(document.getElementById("profilePage")){const t=document.getElementById("uploadTrigger"),i=document.getElementById("profileUpload");t&&i&&t.addEventListener("click",function(){i.click()})}}),document.addEventListener("DOMContentLoaded",function(){if(document.getElementById("profilePage")){const t=document.getElementById("uploadTrigger1"),i=document.getElementById("profileUpload1");t&&i&&t.addEventListener("click",function(){i.click()})}}),document.addEventListener("DOMContentLoaded",function(){if(document.getElementById("profilePage")){const t=document.getElementById("uploadTrigger2"),i=document.getElementById("profileUpload2");t&&i&&t.addEventListener("click",function(){i.click()})}}),document.addEventListener("DOMContentLoaded",function(){if(document.getElementById("profilePage")){const t=document.getElementById("uploadTrigger3"),i=document.getElementById("profileUpload3");t&&i&&t.addEventListener("click",function(){i.click()})}}),document.addEventListener("DOMContentLoaded",function(){if(document.getElementById("profilePage")){const t=document.getElementById("uploadTrigger4"),i=document.getElementById("profileUpload4");t&&i&&t.addEventListener("click",function(){i.click()})}}),document.addEventListener("DOMContentLoaded",function(){if(document.getElementById("profilePage")){const t=document.getElementById("uploadTrigger5"),i=document.getElementById("profileUpload5");t&&i&&t.addEventListener("click",function(){i.click()})}}),$(".datepic").length>0&&$(".datepic").datetimepicker({format:"DD-MM-YYYY",keepOpen:!0,inline:!0,icons:{up:"fas fa-angle-up",down:"fas fa-angle-down",next:"fas fa-angle-right",previous:"fas fa-angle-left"}}),$(".datatable").length>0&&$(".datatable").DataTable({bFilter:!0,sDom:"fBtlpi",ordering:!1,language:{search:" ",sLengthMenu:"_MENU_",searchPlaceholder:"Search",sLengthMenu:"Row Per Page _MENU_ Entries",info:"_START_ - _END_ of _TOTAL_ items",paginate:{next:'<i class="ti ti-arrow-right"></i>',previous:'<i class="ti ti-arrow-left text-body"></i> '}},responsive:!0,autoWidth:!1,initComplete:(e,t)=>{$(".dataTables_filter").appendTo("#tableSearch"),$(".dataTables_filter").appendTo(".search-input")}}),$(".datetimepicker").length>0&&$(".datetimepicker").datetimepicker({format:"DD-MM-YYYY",icons:{up:"fas fa-angle-up",down:"fas fa-angle-down",next:"fas fa-angle-right",previous:"fas fa-angle-left"}}),$(".timepicker").length>0&&$(".timepicker").datetimepicker({format:"HH:mm A",icons:{up:"fas fa-angle-up",down:"fas fa-angle-down",next:"fas fa-angle-right",previous:"fas fa-angle-left"}}),$("#reportrange").length>0){let e=function(t,i){$("#reportrange span").html(t.format("D MMM YY")+" - "+i.format("D MMM YY"))};var m=e,s=moment().subtract(29,"days"),o=moment();$("#reportrange").daterangepicker({startDate:s,endDate:o,ranges:{Today:[moment(),moment()],Yesterday:[moment().subtract(1,"days"),moment().subtract(1,"days")],"Last 7 Days":[moment().subtract(6,"days"),moment()],"Last 30 Days":[moment().subtract(29,"days"),moment()],"This Month":[moment().startOf("month"),moment().endOf("month")],"Last Month":[moment().subtract(1,"month").startOf("month"),moment().subtract(1,"month").endOf("month")]}},e),e(o,o)}if($(".reportrange").length>0){let i=function(n,l){$(".reportrange span").html(n.format("D MMM YY")+" - "+l.format("D MMM YY"))};var m=i,s=moment().subtract(29,"days"),o=moment();$(".reportrange").daterangepicker({startDate:s,endDate:o,ranges:{Today:[moment(),moment()],Yesterday:[moment().subtract(1,"days"),moment().subtract(1,"days")],"Last 7 Days":[moment().subtract(6,"days"),moment()],"Last 30 Days":[moment().subtract(29,"days"),moment()],"This Month":[moment().startOf("month"),moment().endOf("month")],"Last Month":[moment().subtract(1,"month").startOf("month"),moment().subtract(1,"month").endOf("month")]}},i),i(o,o)}if($(".bookingrange").length>0){let i=function(a,n){$(".bookingrange span").html(a.format("D MMM YY")+" - "+n.format("D MMM YY"))};var y=i,s=moment().subtract(6,"days"),o=moment();$(".bookingrange").daterangepicker({startDate:s,endDate:o,ranges:{Today:[moment(),moment()],Yesterday:[moment().subtract(1,"days"),moment().subtract(1,"days")],"Last 7 Days":[moment().subtract(6,"days"),moment()],"Last 30 Days":[moment().subtract(29,"days"),moment()],"This Year":[moment().startOf("year"),moment().endOf("year")],"Last Year":[moment().subtract(1,"year").startOf("year"),moment().subtract(1,"year").endOf("year")]}},i),i(s,o)}if($(".daterange").length>0&&($(".daterange").daterangepicker({autoUpdateInput:!1,ranges:{Today:[moment(),moment()],Yesterday:[moment().subtract(1,"days"),moment().subtract(1,"days")],"Last 7 Days":[moment().subtract(6,"days"),moment()],"Last 30 Days":[moment().subtract(29,"days"),moment()],"This Year":[moment().startOf("year"),moment().endOf("year")],"Next Year":[moment().add(1,"year").startOf("year"),moment().add(1,"year").endOf("year")]},locale:{cancelLabel:"Clear"}}),$("#daterange").on("input",function(){var e=$(this).val().length;$(this).css("width",e+10+"px")}),$(".daterange").on("apply.daterangepicker",function(e,t){$(this).val(t.startDate.format("MM/DD/YYYY")+" - "+t.endDate.format("MM/DD/YYYY"))}),$(".daterange").on("cancel.daterangepicker",function(e,t){$(this).val("")})),$(document).on("click",".add-complaint",function(e){e.preventDefault(),$(this).closest(".complaint-list-item").before(`
		<div class="mb-3 complaint-list-item">
			<div class="input-group">
			<input type="text" class="form-control rounded" />
			<a href="#" class="remove-complaint ms-3 p-2 bg-light text-danger rounded d-flex align-items-center justify-content-center">
				<i class="ti ti-trash fs-16"></i>
			</a>
			</div>
		</div>
		`)}),$(document).on("click",".remove-complaint",function(e){e.preventDefault(),$(this).closest(".complaint-list-item").remove()}),$(document).on("click",".add-advices",function(e){e.preventDefault(),$(this).closest(".advices-list-item").before(`
			<div class="mb-3 advices-list-item">
				<label class="form-label mb-1 text-dark fs-14 fw-medium">Advice</label>
				<div class="input-group">
					<input type="text" class="form-control rounded" />
					<a href="#" class="remove-advices ms-3 p-2 bg-light text-danger rounded d-flex align-items-center justify-content-center"><i class="ti ti-trash fs-16"></i></a>
				</div>
			</div>
		`)}),$(document).on("click",".remove-advices",function(e){e.preventDefault(),$(this).closest(".advices-list-item").remove()}),$(document).on("click",".add-invest",function(e){e.preventDefault(),$(this).closest(".invest-list-item").before(`
			<div class="mb-3 invest-list-item">
				<label class="form-label mb-1 text-dark fs-14 fw-medium">Investigation & Procedure</label>
				<div class="input-group">
					<input type="text" class="form-control rounded" />
					<a href="#" class="remove-invest ms-3 p-2 bg-light text-danger rounded d-flex align-items-center justify-content-center"><i class="ti ti-trash fs-16"></i></a>
				</div>
			</div>
		`)}),$(document).on("click",".remove-invest",function(e){e.preventDefault(),$(this).closest(".invest-list-item").remove()}),$(document).ready(function(){$(document).off("click",".add-diagnosis").on("click",".add-diagnosis",function(e){e.preventDefault();const t=`
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
			`;setTimeout(function(){$(".select"),setTimeout(function(){$(".select").select2({minimumResultsForSearch:-1,width:"100%"})},100)},100),$(".diagnosis-list").append(t)}),$(document).off("click",".remove-diagnosis").on("click",".remove-diagnosis",function(e){e.preventDefault(),$(this).closest(".diagnosis-list-item").remove()})}),$(document).ready(function(){$(document).off("click",".add-reminder").on("click",".add-reminder",function(e){e.preventDefault();const t=`
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
			`;setTimeout(function(){$(".select"),setTimeout(function(){$(".select").select2({minimumResultsForSearch:-1,width:"100%"})},100)},100),$(".reminder-list").append(t)}),$(document).off("click",".remove-reminder").on("click",".remove-reminder",function(e){e.preventDefault(),$(this).closest(".reminder-list-item").remove()})}),$(document).on("click",".add-invoice",function(e){e.preventDefault();const t=`
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
		`;setTimeout(function(){$(".select"),setTimeout(function(){$(".select").select2({minimumResultsForSearch:-1,width:"100%"})},100)},100),$(this).closest(".invoice-list-item").before(t)}),$(document).on("click",".remove-invoice",function(e){e.preventDefault(),$(this).closest(".invoice-list-item").remove()}),$(document).on("click",".add-medication",function(e){e.preventDefault(),$(this).closest(".medication-list-item").before(`
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
		`)}),$(document).on("click",".remove-medication",function(e){e.preventDefault(),$(this).closest(".medication-list-item").remove()}),$(document).on("click",".add-invoices",function(e){e.preventDefault(),$(".invoices-list tr:last").before(`
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
		`)}),$(document).on("click",".remove-invoices",function(e){e.preventDefault(),$(this).closest(".invoices-list-item").remove()}),document.querySelectorAll(".toggle-star").forEach(function(e){e.addEventListener("click",function(){this.classList.toggle("active")})}),$('[data-bs-toggle="tooltip"]').length>0){var g=[].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));g.map(function(e){return new bootstrap.Tooltip(e)})}$(".invoice-template").on("click",function(){$(".invoice-template").removeClass("active"),$(this).addClass("active")}),document.addEventListener("DOMContentLoaded",function(){const e=document.getElementById("break-hours-section");if(!e)return;const t=e.querySelector(".add-break"),i=e.querySelector(".break1");if(!t||!i)return;t.addEventListener("click",function(){const n=i.cloneNode(!0);n.querySelectorAll("input").forEach(h=>h.value="");const l=n.querySelector("p");l&&(l.textContent="New Break");const u=n.querySelector(".ti-trash");u&&u.addEventListener("click",function(){n.remove()}),i.parentNode.insertBefore(n,null)});const a=i.querySelector(".ti-trash");a&&a.addEventListener("click",function(){i.remove()})}),[...document.querySelectorAll('[data-bs-toggle="popover"]')].map(e=>new bootstrap.Popover(e));function v(){document.querySelectorAll("[data-choices]").forEach(e=>{const t={allowHTML:!0},i=e.attributes;i["data-choices-groups"]&&(t.placeholderValue="This is a placeholder set in the config"),i["data-choices-search-false"]&&(t.searchEnabled=!1),i["data-choices-search-true"]&&(t.searchEnabled=!0),i["data-choices-removeItem"]&&(t.removeItemButton=!0),i["data-choices-sorting-false"]&&(t.shouldSort=!1),i["data-choices-sorting-true"]&&(t.shouldSort=!0),i["data-choices-multiple-remove"]&&(t.removeItemButton=!0),i["data-choices-limit"]&&(t.maxItemCount=parseInt(i["data-choices-limit"].value)),i["data-choices-editItem-true"]&&(t.editItems=!0),i["data-choices-editItem-false"]&&(t.editItems=!1),i["data-choices-text-unique-true"]&&(t.duplicateItemsAllowed=!1),i["data-choices-text-disabled-true"]&&(t.addItems=!1);const a=new Choices(e,t);i["data-choices-text-disabled-true"]&&a.disable()})}if(document.addEventListener("DOMContentLoaded",v),document.querySelectorAll('[data-provider="flatpickr"]').forEach(e=>{const t={disableMobile:!0};if(e.hasAttribute("data-date-format")&&(t.dateFormat=e.getAttribute("data-date-format")),e.hasAttribute("data-enable-time")&&(t.enableTime=!0,t.dateFormat=t.dateFormat?`${t.dateFormat} H:i`:"Y-m-d H:i"),e.hasAttribute("data-altFormat")&&(t.altInput=!0,t.altFormat=e.getAttribute("data-altFormat")),e.hasAttribute("data-minDate")&&(t.minDate=e.getAttribute("data-minDate")),e.hasAttribute("data-maxDate")&&(t.maxDate=e.getAttribute("data-maxDate")),e.hasAttribute("data-default-date")){const i=e.getAttribute("data-default-date");!["true","false","",null].includes(i)&&!isNaN(Date.parse(i))&&(t.defaultDate=i)}if(e.hasAttribute("data-multiple-date")&&(t.mode="multiple"),e.hasAttribute("data-range-date")&&(t.mode="range"),e.hasAttribute("data-inline-date")){t.inline=!0;const i=e.getAttribute("data-inline-date");!["true","false","",null].includes(i)&&!isNaN(Date.parse(i))&&(t.defaultDate=i)}e.hasAttribute("data-disable-date")&&(t.disable=e.getAttribute("data-disable-date").split(",")),e.hasAttribute("data-week-number")&&(t.weekNumbers=!0),flatpickr(e,t)}),document.querySelectorAll('[data-provider="timepickr"]').forEach(e=>{const t=e.attributes,i={enableTime:!0,noCalendar:!0,dateFormat:"H:i"};t["data-time-hrs"]&&(i.time_24hr=!0),t["data-min-time"]&&(i.minTime=t["data-min-time"].value),t["data-max-time"]&&(i.maxTime=t["data-max-time"].value),t["data-default-time"]&&(i.defaultDate=t["data-default-time"].value),t["data-time-inline"]&&(i.inline=!0,i.defaultDate=t["data-time-inline"].value),flatpickr(e,i)}),jQuery().select2&&$('[data-toggle="select2"]').each(function(){const e=$(this),t={};e.attr("data-placeholder")&&(t.placeholder=e.attr("data-placeholder")),e.attr("data-allow-clear")==="true"&&(t.allowClear=!0),e.attr("data-tags")==="true"&&(t.tags=!0),e.attr("data-max-selections")&&(t.maximumSelectionLength=parseInt(e.attr("data-max-selections"))),e.attr("data-ajax--url")&&(t.ajax={url:e.attr("data-ajax--url"),dataType:"json",delay:250,data:function(i){return{q:i.term,page:i.page||1}},processResults:function(i,a){return a.page=a.page||1,{results:i.items||[],pagination:{more:i.more}}},cache:!0}),e.select2(t)}),$(".select").length>0&&$(".select").select2({minimumResultsForSearch:-1,width:"100%"}),$(window).width()>767&&$(".theiaStickySidebar").length>0&&$(".theiaStickySidebar").theiaStickySidebar({additionalMarginTop:30}),$(".daterangepick").length>0){let i=function(n,l){$(".daterangepick span").html(n.format("D MMM YY")+" - "+l.format("D MMM YY"))};var m=i,s=moment().subtract(29,"days"),o=moment();$(".daterangepick").daterangepicker({startDate:s,endDate:o,ranges:{Today:[moment(),moment()],Yesterday:[moment().subtract(1,"days"),moment().subtract(1,"days")],"Last 7 Days":[moment().subtract(6,"days"),moment()],"Last 30 Days":[moment().subtract(29,"days"),moment()],"This Month":[moment().startOf("month"),moment().endOf("month")],"Last Month":[moment().subtract(1,"month").startOf("month"),moment().subtract(1,"month").endOf("month")]}},i),i(o,o)}})();
