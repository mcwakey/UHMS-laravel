<?php

namespace Database\Factories;

use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientFactory extends Factory
{
    protected $model = Patient::class;

    // Ghanaian-style first names
    private static array $ghFirstNamesMale = [
        'Kwame','Kofi','Kweku','Yaw','Kwabena','Kojo','Kwasi','Fiifi','Nana','Ato',
        'Bright','Emmanuel','Samuel','Daniel','Michael','Joseph','Richard','Francis','Eric','Philip',
        'Isaac','Benjamin','Solomon','Felix','Albert','George','Anthony','Peter','Paul','John',
    ];
    private static array $ghFirstNamesFemale = [
        'Akua','Ama','Abena','Adwoa','Afua','Akosua','Adjoa','Efua','Afia','Abenaa',
        'Grace','Patience','Comfort','Mercy','Esther','Rejoice','Maame','Abigail','Rita','Beatrice',
        'Sandra','Linda','Victoria','Gloria','Cecilia','Elizabeth','Janet','Alice','Helena','Agnes',
    ];
    private static array $ghLastNames = [
        'Asante','Mensah','Owusu','Boateng','Amponsah','Agyeman','Appiah','Frimpong','Osei','Darko',
        'Nyarko','Sarpong','Bonsu','Acquah','Amoah','Adomako','Baah','Bediako','Antwi','Asare',
        'Opoku','Acheampong','Forson','Nkrumah','Agyei','Ofori','Anane','Tetteh','Quaye','Quartey',
        'Nortey','Laryea','Lamptey','Armah','Aryee','Adjei','Aidoo','Adusei','Koomson','Andoh',
    ];

    private static array $ghRegions = [
        'Greater Accra','Ashanti','Western','Central','Eastern','Volta',
        'Northern','Upper East','Upper West','Bono','Bono East','Ahafo',
        'Western North','Oti','North East','Savannah',
    ];

    private static array $ghCities = [
        'Accra','Kumasi','Tamale','Cape Coast','Sekondi-Takoradi','Koforidua',
        'Ho','Bolgatanga','Wa','Sunyani','Techiman',
    ];

    private static array $ghTowns = [
        'Kasoa','Madina','Awoshie','Tema','Ashaiman','Achimota','Adenta','Haatso',
        'Lapaz','Dansoman','Bubiashie','Osu','Labone','Airport Hills','Spintex',
        'Adum','Bantama','Suame','Asokwa','Nhyiaeso',
        'Agona Swedru','Winneba','Saltpond','Mankessim','Elmina',
        'Nkawkaw','Suhum','Akim Oda','Nsawam','Aburi',
        'Hohoe','Keta','Aflao','Sogakope','Kpando',
    ];

    private static array $ghOccupations = [
        'Trader','Teacher','Farmer','Driver','Student','Civil Servant','Nurse',
        'Mechanic','Carpenter','Electrician','Tailor','Hairdresser','Business Owner',
        'Accountant','Lawyer','Banker','Engineer','Security Guard','Retired','Unemployed',
    ];

    public function definition(): array
    {
        $gender  = fake()->randomElement(Gender::cases());
        $isMale  = $gender === Gender::MALE;

        $firstName = $isMale
            ? fake()->randomElement(self::$ghFirstNamesMale)
            : fake()->randomElement(self::$ghFirstNamesFemale);

        $lastName = fake()->randomElement(self::$ghLastNames);

        // Realistic Ghanaian mobile numbers (024x, 025x, 026x, 027x, 053x, 054x, 055x, 059x)
        $prefixes = ['0241','0242','0244','0246','0247','0248','0249','0251','0261','0271','0277','0531','0541','0551'];
        $phone = fake()->randomElement($prefixes) . fake()->numerify('######');

        return [
            'patient_number'  => Patient::generatePatientNumber(),
            'first_name'      => $firstName,
            'last_name'       => $lastName,
            'other_names'     => fake()->boolean(30) ? fake()->randomElement(self::$ghFirstNamesMale + self::$ghFirstNamesFemale) : null,
            'date_of_birth'   => fake()->date('Y-m-d', '-15 years'),
            'gender'          => $gender,
            'blood_group'     => fake()->randomElement(BloodGroup::cases()),
            'marital_status'  => fake()->randomElement(MaritalStatus::cases()),
            'religion'        => fake()->randomElement(['Christianity','Islam','Traditional','None']),
            'occupation'      => fake()->randomElement(self::$ghOccupations),
            'phone'           => $phone,
            'phone_secondary' => fake()->boolean(25) ? fake()->randomElement($prefixes) . fake()->numerify('######') : null,
            'email'           => fake()->boolean(40) ? strtolower($firstName . '.' . $lastName . fake()->numberBetween(1, 99) . '@gmail.com') : null,
            'address'         => fake()->boolean(70) ? fake()->buildingNumber() . ' ' . fake()->streetName() : null,
            'city'            => fake()->randomElement(self::$ghCities),
            'town'            => fake()->randomElement(self::$ghTowns),
            'region'          => fake()->randomElement(self::$ghRegions),
            'digital_address' => fake()->boolean(35) ? strtoupper(fake()->lexify('??')) . '-' . fake()->numerify('###') . '-' . fake()->numerify('####') : null,
            'ghana_card_number' => fake()->boolean(50) ? 'GHA-' . fake()->numerify('#########') . '-' . fake()->numerify('#') : null,
            'allergies'       => fake()->boolean(20) ? fake()->randomElement(['Penicillin','Sulfa drugs','Aspirin','Ibuprofen','Peanuts','Latex','Dust','Pollen']) : null,
            'chronic_conditions' => fake()->boolean(15) ? fake()->randomElement(['Hypertension','Diabetes Mellitus Type 2','Asthma','Sickle Cell Disease','Malaria (recurrent)','Arthritis']) : null,
            'status'          => fake()->randomElement(['active','active','active','active','inactive']),
            'registered_by'   => 1, // system admin
        ];
    }
}
