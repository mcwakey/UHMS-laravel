<?php

namespace Tests\Feature;

use App\Enums\Priority;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Patient;
use App\Models\SmsMessage;
use App\Models\SmsMessageRecipient;
use App\Models\User;
use App\Models\Visit;
use App\Services\PatientPrivacyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PatientPrivacyPhase3WorkflowMaskingTest extends TestCase
{
    use RefreshDatabase;

    protected Patient $patient;
    protected Visit $visit;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (array_merge(config('patient_privacy.permissions'), [
            'patients.view',
            'visits.view',
            'consultations.view',
            'invoices.view',
            'payments.view',
            'reports.view',
            'logs.view',
            'integrations.sms.view',
        ]) as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $registrar = User::factory()->create();

        $this->patient = Patient::factory()->create([
            'first_name' => 'Ama',
            'last_name' => 'Privacy',
            'phone' => '0241234567',
            'email' => 'ama.privacy@example.com',
            'ghana_card_number' => 'GHA123456789',
            'allergies' => 'Penicillin allergy',
            'chronic_conditions' => 'Diabetes',
            'registered_by' => $registrar->id,
        ]);

        $this->visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_type' => VisitType::OUTPATIENT,
            'status' => VisitStatus::REGISTERED,
            'priority' => Priority::NORMAL,
            'created_by' => $registrar->id,
        ])->load('patient');
    }

    public function test_consultation_workspace_patient_card_masks_contact_and_level_three_fields(): void
    {
        $this->actingAs($this->userWith(['consultations.view']));

        $html = Blade::render('<x-patient-long-card :visit="$visit" />', [
            'visit' => $this->visit,
        ]);

        $this->assertStringContainsString('024****567', $html);
        $this->assertStringNotContainsString('0241234567', $html);
        $this->assertStringNotContainsString('Penicillin allergy', $html);
        $this->assertStringNotContainsString('Diabetes', $html);
    }

    public function test_pii_permission_reveals_level_two_but_not_level_three_in_workflow_cards(): void
    {
        $this->actingAs($this->userWith(['consultations.view', 'patients.pii.view']));

        $html = Blade::render('<x-patient-long-card :visit="$visit" />', [
            'visit' => $this->visit,
        ]);

        $this->assertStringContainsString('0241234567', $html);
        $this->assertStringNotContainsString('Penicillin allergy', $html);
        $this->assertStringNotContainsString('Diabetes', $html);
    }

    public function test_clinical_sensitive_permission_reveals_level_three_in_workflow_cards(): void
    {
        $this->actingAs($this->userWith(['consultations.view', 'patients.clinical_sensitive.view']));

        $html = Blade::render('<x-patient-long-card :visit="$visit" />', [
            'visit' => $this->visit,
        ]);

        $this->assertStringContainsString('Penicillin allergy', $html);
        $this->assertStringContainsString('Diabetes', $html);
    }

    public function test_lab_pharmacy_and_service_rendering_shared_patient_card_masks_patient_contact_fields(): void
    {
        $this->actingAs($this->userWith(['patients.view']));

        $html = Blade::render('<x-patient-card :patient="$patient" />', [
            'patient' => $this->patient,
        ]);

        $this->assertStringContainsString('024****567', $html);
        $this->assertStringNotContainsString('0241234567', $html);
        $this->assertStringNotContainsString('Penicillin allergy', $html);
    }

    public function test_billing_invoice_report_and_print_surfaces_mask_patient_contact_fields(): void
    {
        $this->actingAs($this->userWith(['invoices.view', 'reports.view']));

        $html = Blade::render(
            '<x-patient-protected-field field="phone" :value="$patient->phone" />',
            ['patient' => $this->patient]
        );
        $printHtml = Blade::render(
            '<x-patient-protected-field field="phone" :value="$patient->phone" mode="export" />',
            ['patient' => $this->patient]
        );

        $this->assertSame('024****567', trim($html));
        $this->assertSame('024****567', trim($printHtml));
    }

    public function test_export_mode_reveals_sensitive_fields_only_with_export_sensitive_permission(): void
    {
        $privacy = app(PatientPrivacyService::class);

        $this->actingAs($this->userWith(['patients.contact.view']));
        $this->assertSame('0241234567', $privacy->display('phone', $this->patient->phone));
        $this->assertSame('024****567', $privacy->displayForExport('phone', $this->patient->phone));

        $this->actingAs($this->userWith(['patients.export_sensitive.view']));
        $this->assertSame('0241234567', $privacy->displayForExport('phone', $this->patient->phone));
    }

    public function test_consultation_preview_print_masks_phone_and_identity_by_default(): void
    {
        $this->actingAs($this->userWith(['consultations.view']));

        $html = Blade::render(
            '<x-consultation-preview :visit="$visit" :generated-at="now()" />',
            ['visit' => $this->visit]
        );

        $this->assertStringContainsString('024****567', $html);
        $this->assertStringContainsString('GHA******789', $html);
        $this->assertStringNotContainsString('0241234567', $html);
        $this->assertStringNotContainsString('GHA123456789', $html);
    }

    public function test_activity_log_ui_masks_historical_patient_phone_email_values(): void
    {
        $this->actingAs($this->userWith(['logs.view']));

        $activity = Activity::create([
            'log_name' => 'patient',
            'description' => 'Patient updated',
            'subject_type' => Patient::class,
            'subject_id' => $this->patient->id,
            'causer_type' => User::class,
            'causer_id' => auth()->id(),
            'event' => 'UPDATED',
            'properties' => [
                'module' => 'PATIENTS',
                'action' => 'UPDATED',
                'old' => ['phone' => '0241234567', 'email' => 'ama.privacy@example.com'],
                'attributes' => ['phone' => '0249999999', 'email' => 'ama.new@example.com'],
                'metadata' => ['recipient_phone' => '0241234567'],
            ],
        ]);

        $html = view('settings.activity-log-show', ['activity' => $activity->fresh()])->render();

        $this->assertStringContainsString('024****567', $html);
        $this->assertStringContainsString('am****@example.com', $html);
        $this->assertStringNotContainsString('0241234567', $html);
        $this->assertStringNotContainsString('ama.privacy@example.com', $html);
    }

    public function test_sms_admin_views_mask_recipients_without_contact_permission(): void
    {
        $this->actingAs($this->userWith(['integrations.sms.view']));

        $message = SmsMessage::create([
            'message_uuid' => 'SMS-PRIVACY-1',
            'message_body' => 'Privacy test',
            'message_type' => 'manual',
            'status' => SmsMessage::STATUS_SENT,
            'max_retries' => 3,
        ]);

        SmsMessageRecipient::create([
            'sms_message_id' => $message->id,
            'phone_number' => '0241234567',
            'normalized_phone_number' => '233241234567',
            'status' => SmsMessageRecipient::STATUS_SENT,
        ]);

        $html = view('admin.integrations.sms.messages.show', [
            'message' => $message->load('recipients.deliveryReports', 'provider', 'template'),
        ])->render();

        $this->assertStringContainsString('024****567', $html);
        $this->assertStringNotContainsString('0241234567', $html);
        $this->assertStringNotContainsString('233241234567', $html);
    }

    private function userWith(array $permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user->fresh();
    }
}
