<?php

namespace Tests\Feature\Consultations;

use App\Models\ConsultationSpecialtyEntry;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ConsultationSpecialtyTemplate;
use App\Models\DoctorConsultationPreference;
use App\Models\User;
use App\Services\Consultation\Specialty\ConsultationSpecialtyProfileService;
use Database\Seeders\ConsultationSpecialtySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

class ConsultationSpecialtyFoundationTest extends TestCase
{
    use RefreshDatabase;

    private const PROFILE_CODES = [
        'general_medicine',
        'physiotherapy',
        'ophthalmology',
        'dental',
    ];

    public function test_seeder_creates_default_specialty_profiles(): void
    {
        $this->seed(ConsultationSpecialtySeeder::class);
        $this->seed(ConsultationSpecialtySeeder::class);

        foreach (self::PROFILE_CODES as $code) {
            $this->assertDatabaseHas('consultation_specialty_profiles', [
                'code' => $code,
            ]);
        }

        $this->assertSame(4, ConsultationSpecialtyProfile::query()->count());
    }

    public function test_general_medicine_exists_and_is_active(): void
    {
        $this->seed(ConsultationSpecialtySeeder::class);

        $profile = ConsultationSpecialtyProfile::query()
            ->byCode(ConsultationSpecialtyProfile::GENERAL_MEDICINE)
            ->firstOrFail();

        $this->assertTrue($profile->is_active);
        $this->assertTrue($profile->isGeneral());
        $this->assertSame('General Medicine', $profile->translatedName());
    }

    public function test_each_seeded_profile_has_visible_ordered_sections(): void
    {
        $this->seed(ConsultationSpecialtySeeder::class);

        ConsultationSpecialtyProfile::query()->whereIn('code', self::PROFILE_CODES)->get()->each(function (ConsultationSpecialtyProfile $profile): void {
            $sections = $profile->activeSections()->get();

            $this->assertNotEmpty($sections);
            $this->assertTrue($sections->every(fn ($section) => $section->is_visible));
            $this->assertSame(
                $sections->pluck('display_order')->sort()->values()->all(),
                $sections->pluck('display_order')->values()->all(),
            );
        });
    }

    public function test_section_keys_are_unique_per_profile(): void
    {
        $this->seed(ConsultationSpecialtySeeder::class);

        ConsultationSpecialtyProfile::query()->with('sections')->get()->each(function (ConsultationSpecialtyProfile $profile): void {
            $keys = $profile->sections->pluck('section_key');

            $this->assertSame($keys->count(), $keys->unique()->count());
        });
    }

    public function test_default_profile_service_returns_general_medicine(): void
    {
        $profile = app(ConsultationSpecialtyProfileService::class)->getDefaultProfile();

        $this->assertSame(ConsultationSpecialtyProfile::GENERAL_MEDICINE, $profile->code);
        $this->assertTrue($profile->is_active);
    }

    public function test_doctor_consultation_preference_can_be_created_for_user(): void
    {
        $this->seed(ConsultationSpecialtySeeder::class);

        $user = User::factory()->create();
        $profile = ConsultationSpecialtyProfile::query()->byCode('general_medicine')->firstOrFail();

        $preference = DoctorConsultationPreference::query()->create([
            'user_id' => $user->id,
            'default_consultation_specialty_profile_id' => $profile->id,
            'pinned_actions' => ['prescription', 'investigations'],
            'preferred_layout' => 'default',
            'compact_mode' => true,
            'metadata' => ['workspace' => 'specialist_foundation'],
        ]);

        $this->assertTrue($preference->compact_mode);
        $this->assertSame(['prescription', 'investigations'], $preference->pinned_actions);
        $this->assertTrue($preference->user->is($user));
        $this->assertTrue($preference->defaultSpecialtyProfile->is($profile));
    }

    public function test_specialty_profile_relationships_work(): void
    {
        $this->seed(ConsultationSpecialtySeeder::class);

        $profile = ConsultationSpecialtyProfile::query()->byCode('general_medicine')->firstOrFail();

        $template = ConsultationSpecialtyTemplate::query()->create([
            'consultation_specialty_profile_id' => $profile->id,
            'name' => 'Default SOAP',
            'type' => 'soap',
            'is_default' => true,
            'is_active' => true,
        ]);

        $entry = ConsultationSpecialtyEntry::query()->create([
            'consultation_specialty_profile_id' => $profile->id,
            'section_key' => 'summary',
            'entry' => ['text' => 'Stable foundation entry'],
        ]);

        $this->assertTrue($profile->sections()->exists());
        $this->assertTrue($profile->defaultTemplates()->first()->is($template));
        $this->assertTrue($profile->entries()->first()->is($entry));
    }

    public function test_translation_keys_exist_for_seeded_profiles_and_sections(): void
    {
        $this->seed(ConsultationSpecialtySeeder::class);

        $sectionKeys = ConsultationSpecialtyProfile::query()
            ->with('sections')
            ->get()
            ->flatMap(fn (ConsultationSpecialtyProfile $profile) => $profile->sections->pluck('section_key'))
            ->unique();

        foreach (['en', 'fr'] as $locale) {
            foreach (self::PROFILE_CODES as $code) {
                $this->assertTrue(Lang::has("consultation_specialties.profiles.{$code}", $locale));
            }

            foreach ($sectionKeys as $sectionKey) {
                $this->assertTrue(Lang::has("consultation_specialties.sections.{$sectionKey}", $locale));
            }
        }
    }
}
