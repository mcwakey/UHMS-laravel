<?php

use App\Http\Controllers\Admin\Maternity\AntenatalVisitController;
use App\Http\Controllers\Admin\Maternity\DeliveryRecordController;
use App\Http\Controllers\Admin\Maternity\LaborEpisodeController;
use App\Http\Controllers\Admin\Maternity\MaternityBillingReadinessController;
use App\Http\Controllers\Admin\Maternity\MaternityCaseController;
use App\Http\Controllers\Admin\Maternity\MaternityDashboardController;
use App\Http\Controllers\Admin\Maternity\MaternityReportController;
use App\Http\Controllers\Admin\Maternity\NewbornRecordController;
use App\Http\Controllers\Admin\Maternity\PostnatalCaseController;
use App\Http\Controllers\Admin\Maternity\PregnancyProfileController;
use Illuminate\Support\Facades\Route;

/*
 * Maternity module routes. Mounted twice:
 *  - /admin/maternity  (generic admin access, name prefix admin.maternity.)
 *  - /maternity        (maternity workspace, name prefix maternity.)
 * Keep this file flat (no nested groups) so both mounts stay identical.
 */

        Route::get('/', MaternityDashboardController::class)->name('dashboard')->middleware('can:maternity.dashboard.view');
        Route::get('pregnancies', [PregnancyProfileController::class, 'index'])->name('pregnancies.index')->middleware('can:maternity.pregnancy.view');
        Route::get('pregnancies/create', [PregnancyProfileController::class, 'create'])->name('pregnancies.create')->middleware('can:maternity.pregnancy.create');
        Route::post('pregnancies', [PregnancyProfileController::class, 'store'])->name('pregnancies.store')->middleware('can:maternity.pregnancy.create');
        Route::get('pregnancies/{pregnancyProfile}', [PregnancyProfileController::class, 'show'])->name('pregnancies.show')->middleware('can:maternity.pregnancy.view');
        Route::get('pregnancies/{pregnancyProfile}/antenatal', [AntenatalVisitController::class, 'index'])->name('pregnancies.antenatal.index')->middleware('can:maternity.anc.view');
        Route::get('pregnancies/{pregnancyProfile}/antenatal/create', [AntenatalVisitController::class, 'create'])->name('pregnancies.antenatal.create')->middleware('can:maternity.anc.record');
        Route::post('pregnancies/{pregnancyProfile}/antenatal', [AntenatalVisitController::class, 'store'])->name('pregnancies.antenatal.store')->middleware('can:maternity.anc.record');
        Route::get('pregnancies/{pregnancyProfile}/labor/create', [LaborEpisodeController::class, 'create'])->name('pregnancies.labor.create')->middleware('can:maternity.labor.start');
        Route::get('pregnancies/{pregnancyProfile}/antenatal/{antenatalVisit}/labor/create', [LaborEpisodeController::class, 'createFromAntenatal'])->name('pregnancies.antenatal.labor.create')->middleware('can:maternity.labor.start');
        Route::post('pregnancies/{pregnancyProfile}/labor', [LaborEpisodeController::class, 'store'])->name('pregnancies.labor.store')->middleware('can:maternity.labor.start');
        Route::get('pregnancies/{pregnancyProfile}/edit', [PregnancyProfileController::class, 'edit'])->name('pregnancies.edit')->middleware('can:maternity.pregnancy.update');
        Route::patch('pregnancies/{pregnancyProfile}', [PregnancyProfileController::class, 'update'])->name('pregnancies.update')->middleware('can:maternity.pregnancy.update');
        Route::patch('pregnancies/{pregnancyProfile}/status', [PregnancyProfileController::class, 'status'])->name('pregnancies.status')->middleware('can:maternity.pregnancy.risk.manage');
        Route::get('antenatal/{antenatalVisit}', [AntenatalVisitController::class, 'show'])->name('antenatal.show')->middleware('can:maternity.anc.view');
        Route::get('antenatal/{antenatalVisit}/edit', [AntenatalVisitController::class, 'edit'])->name('antenatal.edit')->middleware('can:maternity.anc.update');
        Route::patch('antenatal/{antenatalVisit}', [AntenatalVisitController::class, 'update'])->name('antenatal.update')->middleware('can:maternity.anc.update');
        Route::patch('antenatal/{antenatalVisit}/cancel', [AntenatalVisitController::class, 'cancel'])->name('antenatal.cancel')->middleware('can:maternity.anc.cancel');
        Route::post('antenatal/{antenatalVisit}/referral', [AntenatalVisitController::class, 'referral'])->name('antenatal.referral')->middleware('can:maternity.anc.referral.create');
        Route::post('antenatal/{antenatalVisit}/admission-request', [AntenatalVisitController::class, 'admissionRequest'])->name('antenatal.admission-request')->middleware('can:maternity.anc.admission.request');
        Route::get('labor', [LaborEpisodeController::class, 'index'])->name('labor.index')->middleware('can:maternity.labor.view');
        Route::get('labor/observations/{laborObservation}', [LaborEpisodeController::class, 'showObservation'])->name('labor.observations.show')->middleware('can:maternity.labor.view');
        Route::get('labor/observations/{laborObservation}/edit', [LaborEpisodeController::class, 'editObservation'])->name('labor.observations.edit')->middleware('can:maternity.labor.observation.update');
        Route::patch('labor/observations/{laborObservation}', [LaborEpisodeController::class, 'updateObservation'])->name('labor.observations.update')->middleware('can:maternity.labor.observation.update');
        Route::patch('labor/observations/{laborObservation}/cancel', [LaborEpisodeController::class, 'cancelObservation'])->name('labor.observations.cancel')->middleware('can:maternity.labor.observation.cancel');
        Route::get('labor/{laborEpisode}', [LaborEpisodeController::class, 'show'])->name('labor.show')->middleware('can:maternity.labor.view');
        Route::get('labor/{laborEpisode}/edit', [LaborEpisodeController::class, 'edit'])->name('labor.edit')->middleware('can:maternity.labor.update');
        Route::patch('labor/{laborEpisode}', [LaborEpisodeController::class, 'update'])->name('labor.update')->middleware('can:maternity.labor.update');
        Route::patch('labor/{laborEpisode}/stage', [LaborEpisodeController::class, 'stage'])->name('labor.stage')->middleware('can:maternity.labor.update');
        Route::patch('labor/{laborEpisode}/close', [LaborEpisodeController::class, 'close'])->name('labor.close')->middleware('can:maternity.labor.close');
        Route::patch('labor/{laborEpisode}/cancel', [LaborEpisodeController::class, 'cancel'])->name('labor.cancel')->middleware('can:maternity.labor.cancel');
        Route::get('labor/{laborEpisode}/observations/create', [LaborEpisodeController::class, 'createObservation'])->name('labor.observations.create')->middleware('can:maternity.labor.observe');
        Route::post('labor/{laborEpisode}/observations', [LaborEpisodeController::class, 'storeObservation'])->name('labor.observations.store')->middleware('can:maternity.labor.observe');
        Route::get('labor/{laborEpisode}/delivery/create', [DeliveryRecordController::class, 'create'])->name('labor.delivery.create')->middleware('can:maternity.delivery.record');
        Route::post('labor/{laborEpisode}/delivery', [DeliveryRecordController::class, 'store'])->name('labor.delivery.store')->middleware('can:maternity.delivery.record');
        Route::post('labor/{laborEpisode}/admission-request', [LaborEpisodeController::class, 'admissionRequest'])->name('labor.admission-request')->middleware('can:maternity.labor.admission.request');
        Route::post('labor/{laborEpisode}/theatre-escalation', [LaborEpisodeController::class, 'theatreEscalation'])->name('labor.theatre-escalation')->middleware('can:maternity.labor.escalate');
        Route::post('labor/{laborEpisode}/emergency-escalation', [LaborEpisodeController::class, 'emergencyEscalation'])->name('labor.emergency-escalation')->middleware('can:maternity.labor.escalate');
        Route::get('deliveries/{deliveryRecord}', [DeliveryRecordController::class, 'show'])->name('deliveries.show')->middleware('can:maternity.delivery.view');
        Route::get('deliveries/{deliveryRecord}/newborns', [NewbornRecordController::class, 'index'])->name('deliveries.newborns.index')->middleware('can:maternity.newborn.view');
        Route::get('deliveries/{deliveryRecord}/newborns/create', [NewbornRecordController::class, 'create'])->name('deliveries.newborns.create')->middleware('can:maternity.newborn.record');
        Route::post('deliveries/{deliveryRecord}/newborns', [NewbornRecordController::class, 'store'])->name('deliveries.newborns.store')->middleware('can:maternity.newborn.record');
        Route::post('deliveries/{deliveryRecord}/newborns/bulk-create', [NewbornRecordController::class, 'bulkCreate'])->name('deliveries.newborns.bulk-create')->middleware('can:maternity.newborn.record');
        Route::get('deliveries/{deliveryRecord}/edit', [DeliveryRecordController::class, 'edit'])->name('deliveries.edit')->middleware('can:maternity.delivery.update');
        Route::patch('deliveries/{deliveryRecord}', [DeliveryRecordController::class, 'update'])->name('deliveries.update')->middleware('can:maternity.delivery.update');
        Route::patch('deliveries/{deliveryRecord}/complete', [DeliveryRecordController::class, 'complete'])->name('deliveries.complete')->middleware('can:maternity.delivery.complete');
        Route::get('newborns/{newbornRecord}', [NewbornRecordController::class, 'show'])->name('newborns.show')->middleware('can:maternity.newborn.view');
        Route::get('newborns/{newbornRecord}/edit', [NewbornRecordController::class, 'edit'])->name('newborns.edit')->middleware('can:maternity.newborn.update');
        Route::patch('newborns/{newbornRecord}', [NewbornRecordController::class, 'update'])->name('newborns.update')->middleware('can:maternity.newborn.update');
        Route::patch('newborns/{newbornRecord}/status', [NewbornRecordController::class, 'status'])->name('newborns.status')->middleware('can:maternity.birth_outcome.manage');
        Route::patch('newborns/{newbornRecord}/close', [NewbornRecordController::class, 'close'])->name('newborns.close')->middleware('can:maternity.newborn.close');
        Route::post('newborns/{newbornRecord}/link-patient', [NewbornRecordController::class, 'linkPatient'])->name('newborns.link-patient')->middleware('can:maternity.newborn.link_patient');
        Route::post('newborns/{newbornRecord}/create-patient', [NewbornRecordController::class, 'createPatient'])->name('newborns.create-patient')->middleware('can:maternity.newborn.create_patient');
        Route::get('reports', [MaternityReportController::class, 'index'])->name('reports.index')->middleware('can:maternity.reports.view');
        Route::get('reports/antenatal', [MaternityReportController::class, 'antenatal'])->name('reports.antenatal')->middleware('can:maternity.reports.view');
        Route::get('reports/labor', [MaternityReportController::class, 'labor'])->name('reports.labor')->middleware('can:maternity.reports.view');
        Route::get('reports/deliveries', [MaternityReportController::class, 'deliveries'])->name('reports.deliveries')->middleware('can:maternity.reports.view');
        Route::get('reports/newborns', [MaternityReportController::class, 'newborns'])->name('reports.newborns')->middleware('can:maternity.reports.view');
        Route::get('reports/postnatal', [MaternityReportController::class, 'postnatal'])->name('reports.postnatal')->middleware('can:maternity.reports.view');
        Route::get('reports/risk', [MaternityReportController::class, 'risk'])->name('reports.risk')->middleware('can:maternity.reports.view');
        Route::get('reports/export', [MaternityReportController::class, 'export'])->name('reports.export')->middleware('can:maternity.reports.export');
        Route::get('billing-readiness', [MaternityBillingReadinessController::class, 'show'])->name('billing-readiness.show')->middleware('can:maternity.billing_readiness.view');
        Route::patch('billing-readiness', [MaternityBillingReadinessController::class, 'update'])->name('billing-readiness.update')->middleware('can:maternity.billing_readiness.manage');
        Route::get('postnatal', [PostnatalCaseController::class, 'index'])->name('postnatal.index')->middleware('can:maternity.postnatal.view');
        Route::post('deliveries/{deliveryRecord}/postnatal', [PostnatalCaseController::class, 'store'])->name('deliveries.postnatal.store')->middleware('can:maternity.postnatal.open');
        Route::get('postnatal/{postnatalCase}', [PostnatalCaseController::class, 'show'])->name('postnatal.show')->middleware('can:maternity.postnatal.view');
        Route::patch('postnatal/{postnatalCase}', [PostnatalCaseController::class, 'update'])->name('postnatal.update')->middleware('can:maternity.postnatal.update');
        Route::patch('postnatal/{postnatalCase}/status', [PostnatalCaseController::class, 'status'])->name('postnatal.status')->middleware('can:maternity.postnatal.discharge.manage');
        Route::patch('postnatal/{postnatalCase}/close', [PostnatalCaseController::class, 'close'])->name('postnatal.close')->middleware('can:maternity.postnatal.close');
        Route::patch('postnatal/{postnatalCase}/cancel', [PostnatalCaseController::class, 'cancel'])->name('postnatal.cancel')->middleware('can:maternity.postnatal.cancel');
        Route::get('postnatal/{postnatalCase}/mother-observations/create', [PostnatalCaseController::class, 'createMotherObservation'])->name('postnatal.mother-observations.create')->middleware('can:maternity.postnatal.mother.record');
        Route::post('postnatal/{postnatalCase}/mother-observations', [PostnatalCaseController::class, 'storeMotherObservation'])->name('postnatal.mother-observations.store')->middleware('can:maternity.postnatal.mother.record');
        Route::get('postnatal/mother-observations/{observation}', [PostnatalCaseController::class, 'showMotherObservation'])->name('postnatal.mother-observations.show')->middleware('can:maternity.postnatal.view');
        Route::get('postnatal/mother-observations/{observation}/edit', [PostnatalCaseController::class, 'editMotherObservation'])->name('postnatal.mother-observations.edit')->middleware('can:maternity.postnatal.mother.update');
        Route::patch('postnatal/mother-observations/{observation}', [PostnatalCaseController::class, 'updateMotherObservation'])->name('postnatal.mother-observations.update')->middleware('can:maternity.postnatal.mother.update');
        Route::patch('postnatal/mother-observations/{observation}/cancel', [PostnatalCaseController::class, 'cancelMotherObservation'])->name('postnatal.mother-observations.cancel')->middleware('can:maternity.postnatal.mother.update');
        Route::get('postnatal/{postnatalCase}/newborns/{newbornRecord}/observations/create', [PostnatalCaseController::class, 'createNewbornObservation'])->name('postnatal.newborn-observations.create')->middleware('can:maternity.postnatal.newborn.record');
        Route::post('postnatal/{postnatalCase}/newborns/{newbornRecord}/observations', [PostnatalCaseController::class, 'storeNewbornObservation'])->name('postnatal.newborn-observations.store')->middleware('can:maternity.postnatal.newborn.record');
        Route::get('postnatal/newborn-observations/{observation}', [PostnatalCaseController::class, 'showNewbornObservation'])->name('postnatal.newborn-observations.show')->middleware('can:maternity.postnatal.view');
        Route::get('postnatal/newborn-observations/{observation}/edit', [PostnatalCaseController::class, 'editNewbornObservation'])->name('postnatal.newborn-observations.edit')->middleware('can:maternity.postnatal.newborn.update');
        Route::patch('postnatal/newborn-observations/{observation}', [PostnatalCaseController::class, 'updateNewbornObservation'])->name('postnatal.newborn-observations.update')->middleware('can:maternity.postnatal.newborn.update');
        Route::patch('postnatal/newborn-observations/{observation}/cancel', [PostnatalCaseController::class, 'cancelNewbornObservation'])->name('postnatal.newborn-observations.cancel')->middleware('can:maternity.postnatal.newborn.update');
        Route::post('cases', [MaternityCaseController::class, 'store'])->name('cases.store')->middleware('can:maternity.case.create');
        Route::get('cases/{maternityCase}', [MaternityCaseController::class, 'show'])->name('cases.show')->middleware('can:maternity.case.view');
        Route::patch('cases/{maternityCase}', [MaternityCaseController::class, 'update'])->name('cases.update')->middleware('can:maternity.case.update');
        Route::patch('cases/{maternityCase}/close', [MaternityCaseController::class, 'close'])->name('cases.close')->middleware('can:maternity.case.close');
        Route::post('cases/{maternityCase}/admission-request', [MaternityCaseController::class, 'admissionRequest'])->name('cases.admission-request')->middleware('can:maternity.admission.request');
