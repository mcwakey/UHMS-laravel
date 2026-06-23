<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\CountryCity;
use App\Models\CountryRegion;
use App\Models\CountryTown;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountryLocationSeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            'GH' => [
                'name' => 'Ghana',
                'iso3' => 'GHA',
                'dial_code' => '+233',
                'regions' => $this->ghanaLocationDataset(),
            ],
            'TG' => [
                'name' => 'Togo',
                'iso3' => 'TGO',
                'dial_code' => '+228',
                'regions' => $this->togoRegions(),
            ],
        ];

        DB::transaction(function () use ($countries) {
            $countryOrder = 1;

            foreach ($countries as $iso2 => $data) {
                $country = Country::updateOrCreate(
                    ['iso2' => $iso2],
                    [
                        'name' => $data['name'],
                        'iso3' => $data['iso3'],
                        'dial_code' => $data['dial_code'],
                        'sort_order' => $countryOrder++,
                        'is_active' => true,
                    ]
                );

                $regionOrder = 1;

                foreach ($data['regions'] as $regionName => $regionData) {
                    $region = CountryRegion::updateOrCreate(
                        [
                            'country_id' => $country->id,
                            'name' => $regionName,
                        ],
                        [
                            'code' => $regionData['code'],
                            'sort_order' => $regionOrder++,
                            'is_active' => true,
                        ]
                    );

                    foreach ($regionData['cities'] as $cityName => $towns) {
                        $city = CountryCity::updateOrCreate(
                            [
                                'country_region_id' => $region->id,
                                'name' => $cityName,
                            ],
                            [
                                'type' => 'city',
                                'is_active' => true,
                            ]
                        );

                        foreach ($towns as $town) {
                            CountryTown::updateOrCreate(
                                [
                                    'country_city_id' => $city->id,
                                    'name' => $town,
                                ],
                                ['is_active' => true]
                            );
                        }
                    }
                }
            }
        });
    }

    private function ghanaLocationDataset(): array
    {
        $towns = [
            'Accra' => ['Airport Residential', 'Cantonments', 'Osu', 'Labone', 'Adabraka', 'Ridge', 'Dansoman', 'Kaneshie', 'Achimota', 'East Legon'],
            'Tema' => ['Community 1', 'Community 2', 'Community 4', 'Community 7', 'Community 9', 'Community 12', 'Sakumono'],
            'Madina' => ['Madina Zongo', 'Madina Estates', 'Redco', 'Social Welfare', 'Ritz Junction'],
            'Adenta' => ['Adenta Barrier', 'Frafraha', 'Ashaley Botwe', 'New Legon', 'Commandos'],
            'Ashaiman' => ['Tulaku', 'Newtown', 'Lebanon', 'Valco Flat', 'Zenu'],
            'Kumasi' => ['Adum', 'Bantama', 'Asokwa', 'Ahodwo', 'Suame', 'Tafo', 'Ayigya', 'Patasi', 'Santasi', 'Kejetia'],
            'Obuasi' => ['Tutuka', 'Anyinam', 'Boete', 'Bogobiri', 'Akaporiso'],
            'Cape Coast' => ['Pedu', 'Abura', 'Kotokraba', 'Siwdu', 'University Area'],
            'Sekondi-Takoradi' => ['Sekondi', 'Takoradi', 'Anaji', 'Kwesimintsim', 'Effia', 'Apremdo'],
            'Koforidua' => ['Betom', 'Srodae', 'Effiduase', 'Oyoko', 'Jumapo'],
            'Ho' => ['Ho Central', 'Bankoe', 'Ahoe', 'Dome', 'Fiave'],
            'Tamale' => ['Aboabo', 'Kalpohin', 'Lamashegu', 'Sakasaka', 'Vittin'],
            'Bolgatanga' => ['Bolga Central', 'Zuarungu', 'Tindonsobligo', 'Sumbrungu', 'Sherigu'],
            'Wa' => ['Wa Central', 'Kpaguri', 'Dobile', 'Kambali', 'Bamahu'],
            'Sunyani' => ['Sunyani Central', 'Penkwase', 'Estate', 'Abesim', 'New Dormaa'],
            'Techiman' => ['Techiman Central', 'Kentampo Road', 'Hansua', 'Tuobodom Road', 'Forikrom'],
        ];

        $regions = [
            'Greater Accra' => ['code' => 'GA', 'cities' => ['Accra', 'Tema', 'Madina', 'Adenta', 'Ashaiman', 'Teshie', 'Nungua', 'Kasoa', 'Dodowa', 'Prampram']],
            'Ashanti' => ['code' => 'AS', 'cities' => ['Kumasi', 'Obuasi', 'Ejisu', 'Mampong', 'Konongo', 'Bekwai', 'Agogo', 'Asante Akim Agogo', 'Offinso', 'Nkawie']],
            'Western' => ['code' => 'WR', 'cities' => ['Sekondi-Takoradi', 'Tarkwa', 'Axim', 'Prestea', 'Shama', 'Elubo', 'Dixcove', 'Bogoso']],
            'Central' => ['code' => 'CR', 'cities' => ['Cape Coast', 'Winneba', 'Kasoa', 'Mankessim', 'Agona Swedru', 'Elmina', 'Saltpond', 'Dunkwa-on-Offin']],
            'Eastern' => ['code' => 'ER', 'cities' => ['Koforidua', 'Akosombo', 'Nkawkaw', 'Akim Oda', 'Suhum', 'Aburi', 'Nsawam', 'Akwatia']],
            'Volta' => ['code' => 'VR', 'cities' => ['Ho', 'Hohoe', 'Keta', 'Sogakope', 'Aflao', 'Anloga', 'Kpando']],
            'Northern' => ['code' => 'NR', 'cities' => ['Tamale', 'Yendi', 'Savelugu', 'Tolon', 'Gushegu', 'Bimbilla']],
            'Upper East' => ['code' => 'UE', 'cities' => ['Bolgatanga', 'Bawku', 'Navrongo', 'Paga', 'Zebilla', 'Sandema']],
            'Upper West' => ['code' => 'UW', 'cities' => ['Wa', 'Lawra', 'Jirapa', 'Tumu', 'Nandom']],
            'Bono' => ['code' => 'BO', 'cities' => ['Sunyani', 'Berekum', 'Dormaa Ahenkro', 'Wenchi']],
            'Bono East' => ['code' => 'BE', 'cities' => ['Techiman', 'Kintampo', 'Atebubu', 'Nkoranza', 'Yeji']],
            'Ahafo' => ['code' => 'AF', 'cities' => ['Goaso', 'Bechem', 'Duayaw Nkwanta', 'Kenyasi', 'Hwidiem']],
            'Western North' => ['code' => 'WN', 'cities' => ['Sefwi Wiawso', 'Bibiani', 'Enchi', 'Juaboso', 'Asankragwa']],
            'Oti' => ['code' => 'OT', 'cities' => ['Dambai', 'Nkwanta', 'Kete Krachi', 'Jasikan', 'Kadjebi']],
            'North East' => ['code' => 'NE', 'cities' => ['Nalerigu', 'Walewale', 'Gambaga', 'Bunkpurugu', 'Yagaba']],
            'Savannah' => ['code' => 'SV', 'cities' => ['Damongo', 'Bole', 'Salaga', 'Sawla', 'Buipe']],
        ];

        return $this->withDefaultTowns($regions, $towns);
    }

    private function togoRegions(): array
    {
        return [
            'Maritime' => ['code' => 'M', 'cities' => [
                'Lome' => ['Agoe', 'Tokoin', 'Be', 'Adidogome', 'Nyekonakpoe'],
                'Aneho' => ['Aneho Central', 'Glidji', 'Zebe'],
                'Tsevie' => ['Tsevie Central', 'Daviemodji', 'Kpodji'],
            ]],
            'Plateaux' => ['code' => 'P', 'cities' => [
                'Atakpame' => ['Atakpame Central', 'Agbonou', 'Tchakpali'],
                'Kpalime' => ['Kpalime Central', 'Kuma', 'Lavie'],
            ]],
            'Centrale' => ['code' => 'C', 'cities' => [
                'Sokode' => ['Sokode Central', 'Komah', 'Kpangalam'],
                'Tchamba' => ['Tchamba Central', 'Koussountou', 'Alibi'],
            ]],
            'Kara' => ['code' => 'K', 'cities' => [
                'Kara' => ['Kara Central', 'Tomde', 'Chaminade'],
                'Niamtougou' => ['Niamtougou Central', 'Baga', 'Koka'],
            ]],
            'Savanes' => ['code' => 'S', 'cities' => [
                'Dapaong' => ['Dapaong Central', 'Nassable', 'Korbongou'],
                'Mango' => ['Mango Central', 'Sadori', 'Gando'],
            ]],
        ];
    }

    private function withDefaultTowns(array $regions, array $towns): array
    {
        foreach ($regions as $regionName => $region) {
            $cities = [];

            foreach ($region['cities'] as $city) {
                $cities[$city] = $towns[$city] ?? [
                    "{$city} Central",
                    "{$city} New Town",
                    "{$city} Market Area",
                ];
            }

            $regions[$regionName]['cities'] = $cities;
        }

        return $regions;
    }
}
