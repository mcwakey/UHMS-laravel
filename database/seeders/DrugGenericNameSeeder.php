<?php

namespace Database\Seeders;

use App\Models\DrugGenericName;
use Illuminate\Database\Seeder;

/**
 * Seeds a baseline list of WHO essential / commonly-used generic drug names.
 * Idempotent: uses firstOrCreate on the unique `name` column.
 */
class DrugGenericNameSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // Analgesics / antipyretics
            ['Paracetamol',         'N02BE01', 'Analgesic / Antipyretic'],
            ['Ibuprofen',           'M01AE01', 'NSAID'],
            ['Diclofenac',          'M01AB05', 'NSAID'],
            ['Aspirin',             'B01AC06', 'NSAID / Antiplatelet'],
            ['Tramadol',            'N02AX02', 'Opioid analgesic'],
            ['Morphine',            'N02AA01', 'Opioid analgesic'],
            ['Pethidine',           'N02AB02', 'Opioid analgesic'],

            // Antibiotics
            ['Amoxicillin',         'J01CA04', 'Penicillin antibiotic'],
            ['Amoxicillin + Clavulanic Acid', 'J01CR02', 'Penicillin + beta-lactamase inhibitor'],
            ['Ampicillin',          'J01CA01', 'Penicillin antibiotic'],
            ['Cloxacillin',         'J01CF02', 'Penicillin antibiotic'],
            ['Ceftriaxone',         'J01DD04', '3rd-gen cephalosporin'],
            ['Cefuroxime',          'J01DC02', '2nd-gen cephalosporin'],
            ['Cefixime',            'J01DD08', '3rd-gen cephalosporin'],
            ['Ciprofloxacin',       'J01MA02', 'Fluoroquinolone'],
            ['Levofloxacin',        'J01MA12', 'Fluoroquinolone'],
            ['Azithromycin',        'J01FA10', 'Macrolide'],
            ['Erythromycin',        'J01FA01', 'Macrolide'],
            ['Clarithromycin',      'J01FA09', 'Macrolide'],
            ['Doxycycline',         'J01AA02', 'Tetracycline'],
            ['Tetracycline',        'J01AA07', 'Tetracycline'],
            ['Metronidazole',       'J01XD01', 'Antiprotozoal / antibacterial'],
            ['Gentamicin',          'J01GB03', 'Aminoglycoside'],
            ['Cotrimoxazole',       'J01EE01', 'Sulfonamide combination'],
            ['Nitrofurantoin',      'J01XE01', 'Urinary anti-infective'],

            // Antimalarials
            ['Artemether + Lumefantrine', 'P01BF01', 'ACT antimalarial'],
            ['Artesunate',          'P01BE03', 'Antimalarial'],
            ['Quinine',             'P01BC01', 'Antimalarial'],
            ['Chloroquine',         'P01BA01', 'Antimalarial'],
            ['Sulfadoxine + Pyrimethamine', 'P01BD51', 'Antimalarial'],

            // Antifungals
            ['Fluconazole',         'J02AC01', 'Antifungal'],
            ['Ketoconazole',        'J02AB02', 'Antifungal'],
            ['Nystatin',            'A07AA02', 'Antifungal'],
            ['Griseofulvin',        'D01BA01', 'Antifungal'],

            // Antivirals
            ['Acyclovir',           'J05AB01', 'Antiviral (herpes)'],
            ['Zidovudine',          'J05AF01', 'Antiretroviral (NRTI)'],
            ['Lamivudine',          'J05AF05', 'Antiretroviral (NRTI)'],
            ['Tenofovir',           'J05AF07', 'Antiretroviral (NRTI)'],
            ['Efavirenz',           'J05AG03', 'Antiretroviral (NNRTI)'],
            ['Nevirapine',          'J05AG01', 'Antiretroviral (NNRTI)'],

            // Antihelminthics
            ['Albendazole',         'P02CA03', 'Anthelmintic'],
            ['Mebendazole',         'P02CA01', 'Anthelmintic'],
            ['Praziquantel',        'P02BA01', 'Anthelmintic'],

            // Cardiovascular
            ['Atenolol',            'C07AB03', 'Beta-blocker'],
            ['Bisoprolol',          'C07AB07', 'Beta-blocker'],
            ['Propranolol',         'C07AA05', 'Beta-blocker'],
            ['Amlodipine',          'C08CA01', 'Calcium-channel blocker'],
            ['Nifedipine',          'C08CA05', 'Calcium-channel blocker'],
            ['Enalapril',           'C09AA02', 'ACE inhibitor'],
            ['Lisinopril',          'C09AA03', 'ACE inhibitor'],
            ['Captopril',           'C09AA01', 'ACE inhibitor'],
            ['Losartan',            'C09CA01', 'ARB'],
            ['Hydrochlorothiazide', 'C03AA03', 'Thiazide diuretic'],
            ['Furosemide',          'C03CA01', 'Loop diuretic'],
            ['Spironolactone',      'C03DA01', 'Potassium-sparing diuretic'],
            ['Digoxin',             'C01AA05', 'Cardiac glycoside'],
            ['Methyldopa',          'C02AB02', 'Antihypertensive (centrally acting)'],
            ['Hydralazine',         'C02DB02', 'Vasodilator'],
            ['Nitroglycerin',       'C01DA02', 'Nitrate'],
            ['Isosorbide Dinitrate','C01DA08', 'Nitrate'],
            ['Atorvastatin',        'C10AA05', 'Statin'],
            ['Simvastatin',         'C10AA01', 'Statin'],
            ['Warfarin',            'B01AA03', 'Anticoagulant'],
            ['Heparin',             'B01AB01', 'Anticoagulant'],
            ['Clopidogrel',         'B01AC04', 'Antiplatelet'],

            // Endocrine / Diabetes
            ['Metformin',           'A10BA02', 'Biguanide'],
            ['Glibenclamide',       'A10BB01', 'Sulfonylurea'],
            ['Gliclazide',          'A10BB09', 'Sulfonylurea'],
            ['Insulin (Regular)',   'A10AB01', 'Short-acting insulin'],
            ['Insulin (NPH)',       'A10AC01', 'Intermediate-acting insulin'],
            ['Levothyroxine',       'H03AA01', 'Thyroid hormone'],
            ['Carbimazole',         'H03BB01', 'Antithyroid'],
            ['Hydrocortisone',      'H02AB09', 'Glucocorticoid'],
            ['Prednisolone',        'H02AB06', 'Glucocorticoid'],
            ['Dexamethasone',       'H02AB02', 'Glucocorticoid'],

            // Respiratory
            ['Salbutamol',          'R03AC02', 'Beta-2 agonist'],
            ['Aminophylline',       'R03DA05', 'Xanthine bronchodilator'],
            ['Theophylline',        'R03DA04', 'Xanthine bronchodilator'],
            ['Beclomethasone',      'R03BA01', 'Inhaled corticosteroid'],
            ['Cetirizine',          'R06AE07', 'Antihistamine'],
            ['Loratadine',          'R06AX13', 'Antihistamine'],
            ['Chlorpheniramine',    'R06AB04', 'Antihistamine'],
            ['Promethazine',        'R06AD02', 'Antihistamine'],

            // Gastrointestinal
            ['Omeprazole',          'A02BC01', 'Proton-pump inhibitor'],
            ['Pantoprazole',        'A02BC02', 'Proton-pump inhibitor'],
            ['Ranitidine',          'A02BA02', 'H2 antagonist'],
            ['Famotidine',          'A02BA03', 'H2 antagonist'],
            ['Hyoscine Butylbromide','A03BB01', 'Antispasmodic'],
            ['Loperamide',          'A07DA03', 'Antidiarrhoeal'],
            ['ORS',                 'A07CA',   'Oral rehydration salts'],
            ['Domperidone',         'A03FA03', 'Antiemetic'],
            ['Metoclopramide',      'A03FA01', 'Antiemetic'],
            ['Ondansetron',         'A04AA01', 'Antiemetic (5-HT3 antagonist)'],

            // CNS / Psychiatry
            ['Diazepam',            'N05BA01', 'Benzodiazepine'],
            ['Lorazepam',           'N05BA06', 'Benzodiazepine'],
            ['Phenobarbital',       'N03AA02', 'Barbiturate / Anticonvulsant'],
            ['Phenytoin',           'N03AB02', 'Anticonvulsant'],
            ['Carbamazepine',       'N03AF01', 'Anticonvulsant'],
            ['Sodium Valproate',    'N03AG01', 'Anticonvulsant'],
            ['Haloperidol',         'N05AD01', 'Antipsychotic'],
            ['Chlorpromazine',      'N05AA01', 'Antipsychotic'],
            ['Risperidone',         'N05AX08', 'Antipsychotic'],
            ['Amitriptyline',       'N06AA09', 'Tricyclic antidepressant'],
            ['Fluoxetine',          'N06AB03', 'SSRI'],
            ['Sertraline',          'N06AB06', 'SSRI'],

            // Vitamins / supplements
            ['Folic Acid',          'B03BB01', 'Vitamin'],
            ['Ferrous Sulphate',    'B03AA07', 'Iron supplement'],
            ['Vitamin B Complex',   'A11EA',   'Vitamin'],
            ['Vitamin C (Ascorbic Acid)', 'A11GA01', 'Vitamin'],
            ['Vitamin A (Retinol)', 'A11CA01', 'Vitamin'],
            ['Vitamin D3 (Cholecalciferol)', 'A11CC05', 'Vitamin'],
            ['Calcium Carbonate',   'A02AC01', 'Mineral / Antacid'],
            ['Multivitamin',        null,      'Vitamin'],
            ['Zinc Sulphate',       'A12CB01', 'Mineral'],

            // Anaesthesia
            ['Lidocaine',           'N01BB02', 'Local anaesthetic'],
            ['Bupivacaine',         'N01BB01', 'Local anaesthetic'],
            ['Ketamine',            'N01AX03', 'General anaesthetic'],
            ['Propofol',            'N01AX10', 'General anaesthetic'],
            ['Halothane',           'N01AB01', 'Inhalational anaesthetic'],
            ['Atropine',            'A03BA01', 'Anticholinergic'],
            ['Adrenaline (Epinephrine)', 'C01CA24', 'Sympathomimetic'],

            // IV fluids
            ['Normal Saline 0.9%',     'B05XA03', 'IV fluid'],
            ['Ringer\'s Lactate',      'B05BB01', 'IV fluid'],
            ['Dextrose 5%',            'B05BA03', 'IV fluid'],
            ['Dextrose Saline',        'B05BB02', 'IV fluid'],
            ['Sodium Bicarbonate',     'B05XA02', 'Electrolyte'],
            ['Potassium Chloride',     'B05XA01', 'Electrolyte'],
            ['Magnesium Sulphate',     'B05XA05', 'Electrolyte / Anticonvulsant'],

            // Reproductive / OB
            ['Oxytocin',               'H01BB02', 'Uterotonic'],
            ['Ergometrine',            'G02AB03', 'Uterotonic'],
            ['Misoprostol',            'G02AD06', 'Uterotonic / antiulcer'],
            ['Medroxyprogesterone',    'G03DA02', 'Progestin'],
            ['Combined Oral Contraceptive', 'G03AA', 'Contraceptive'],

            // TB
            ['Isoniazid',              'J04AC01', 'Antitubercular'],
            ['Rifampicin',             'J04AB02', 'Antitubercular'],
            ['Ethambutol',             'J04AK02', 'Antitubercular'],
            ['Pyrazinamide',           'J04AK01', 'Antitubercular'],
            ['Streptomycin',           'J01GA01', 'Aminoglycoside (TB)'],
        ];

        foreach ($rows as [$name, $atc, $cls]) {
            DrugGenericName::firstOrCreate(
                ['name' => $name],
                ['atc_code' => $atc, 'therapeutic_class' => $cls, 'is_active' => true]
            );
        }
    }
}
