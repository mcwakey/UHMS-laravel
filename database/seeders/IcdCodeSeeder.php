<?php

namespace Database\Seeders;

use App\Models\IcdCode;
use Illuminate\Database\Seeder;

class IcdCodeSeeder extends Seeder
{
    public function run(): void
    {
        $codes = $this->getCommonCodes();

        foreach ($codes as $code) {
            IcdCode::firstOrCreate(
                ['code' => $code['code']],
                $code
            );
        }

        $this->command->info('Seeded ' . count($codes) . ' ICD-10 codes.');
    }

    /**
     * Common ICD-10 codes frequently used in Ghanaian clinical practice.
     */
    private function getCommonCodes(): array
    {
        return [
            // Chapter I — Certain infectious and parasitic diseases (A00–B99)
            ['code' => 'A01.0', 'description' => 'Typhoid fever', 'category' => 'Infectious diseases', 'chapter' => 'I', 'is_billable' => true],
            ['code' => 'A09', 'description' => 'Infectious gastroenteritis and colitis, unspecified', 'category' => 'Infectious diseases', 'chapter' => 'I', 'is_billable' => true],
            ['code' => 'A15.0', 'description' => 'Tuberculosis of lung', 'category' => 'Infectious diseases', 'chapter' => 'I', 'is_billable' => true],
            ['code' => 'A15.9', 'description' => 'Respiratory tuberculosis, unspecified', 'category' => 'Infectious diseases', 'chapter' => 'I', 'is_billable' => true],
            ['code' => 'A16.9', 'description' => 'Respiratory tuberculosis, unspecified (bact not confirmed)', 'category' => 'Infectious diseases', 'chapter' => 'I', 'is_billable' => true],
            ['code' => 'B20', 'description' => 'Human immunodeficiency virus [HIV] disease', 'category' => 'Infectious diseases', 'chapter' => 'I', 'is_billable' => true],
            ['code' => 'B24', 'description' => 'Unspecified human immunodeficiency virus [HIV] disease', 'category' => 'Infectious diseases', 'chapter' => 'I', 'is_billable' => true],
            ['code' => 'B50.9', 'description' => 'Plasmodium falciparum malaria, unspecified', 'category' => 'Infectious diseases', 'chapter' => 'I', 'is_billable' => true],
            ['code' => 'B51.9', 'description' => 'Plasmodium vivax malaria without complication', 'category' => 'Infectious diseases', 'chapter' => 'I', 'is_billable' => true],
            ['code' => 'B54', 'description' => 'Unspecified malaria', 'category' => 'Infectious diseases', 'chapter' => 'I', 'is_billable' => true],
            ['code' => 'B37.0', 'description' => 'Candidal stomatitis (Oral thrush)', 'category' => 'Infectious diseases', 'chapter' => 'I', 'is_billable' => true],
            ['code' => 'B35.0', 'description' => 'Tinea barbae and tinea capitis', 'category' => 'Infectious diseases', 'chapter' => 'I', 'is_billable' => true],
            ['code' => 'B82.9', 'description' => 'Intestinal parasitism, unspecified', 'category' => 'Infectious diseases', 'chapter' => 'I', 'is_billable' => true],
            ['code' => 'A06.9', 'description' => 'Amoebiasis, unspecified', 'category' => 'Infectious diseases', 'chapter' => 'I', 'is_billable' => true],
            ['code' => 'A04.9', 'description' => 'Bacterial intestinal infection, unspecified', 'category' => 'Infectious diseases', 'chapter' => 'I', 'is_billable' => true],

            // Chapter II — Neoplasms (C00–D48)
            ['code' => 'C50.9', 'description' => 'Malignant neoplasm of breast, unspecified', 'category' => 'Neoplasms', 'chapter' => 'II', 'is_billable' => true],
            ['code' => 'C53.9', 'description' => 'Malignant neoplasm of cervix uteri, unspecified', 'category' => 'Neoplasms', 'chapter' => 'II', 'is_billable' => true],
            ['code' => 'C61', 'description' => 'Malignant neoplasm of prostate', 'category' => 'Neoplasms', 'chapter' => 'II', 'is_billable' => true],
            ['code' => 'C18.9', 'description' => 'Malignant neoplasm of colon, unspecified', 'category' => 'Neoplasms', 'chapter' => 'II', 'is_billable' => true],
            ['code' => 'D25.9', 'description' => 'Leiomyoma of uterus, unspecified (Uterine fibroids)', 'category' => 'Neoplasms', 'chapter' => 'II', 'is_billable' => true],

            // Chapter III — Diseases of blood (D50–D89)
            ['code' => 'D50.9', 'description' => 'Iron deficiency anaemia, unspecified', 'category' => 'Blood diseases', 'chapter' => 'III', 'is_billable' => true],
            ['code' => 'D57.0', 'description' => 'Sickle-cell anaemia with crisis', 'category' => 'Blood diseases', 'chapter' => 'III', 'is_billable' => true],
            ['code' => 'D57.1', 'description' => 'Sickle-cell anaemia without crisis', 'category' => 'Blood diseases', 'chapter' => 'III', 'is_billable' => true],
            ['code' => 'D64.9', 'description' => 'Anaemia, unspecified', 'category' => 'Blood diseases', 'chapter' => 'III', 'is_billable' => true],

            // Chapter IV — Endocrine, nutritional and metabolic diseases (E00–E90)
            ['code' => 'E03.9', 'description' => 'Hypothyroidism, unspecified', 'category' => 'Endocrine diseases', 'chapter' => 'IV', 'is_billable' => true],
            ['code' => 'E05.9', 'description' => 'Thyrotoxicosis, unspecified', 'category' => 'Endocrine diseases', 'chapter' => 'IV', 'is_billable' => true],
            ['code' => 'E10.9', 'description' => 'Type 1 diabetes mellitus without complications', 'category' => 'Endocrine diseases', 'chapter' => 'IV', 'is_billable' => true],
            ['code' => 'E11.9', 'description' => 'Type 2 diabetes mellitus without complications', 'category' => 'Endocrine diseases', 'chapter' => 'IV', 'is_billable' => true],
            ['code' => 'E11.2', 'description' => 'Type 2 diabetes mellitus with kidney complications', 'category' => 'Endocrine diseases', 'chapter' => 'IV', 'is_billable' => true],
            ['code' => 'E11.5', 'description' => 'Type 2 diabetes mellitus with peripheral circulatory complications', 'category' => 'Endocrine diseases', 'chapter' => 'IV', 'is_billable' => true],
            ['code' => 'E46', 'description' => 'Unspecified protein-energy malnutrition', 'category' => 'Endocrine diseases', 'chapter' => 'IV', 'is_billable' => true],
            ['code' => 'E66.9', 'description' => 'Obesity, unspecified', 'category' => 'Endocrine diseases', 'chapter' => 'IV', 'is_billable' => true],
            ['code' => 'E78.5', 'description' => 'Hyperlipidaemia, unspecified', 'category' => 'Endocrine diseases', 'chapter' => 'IV', 'is_billable' => true],
            ['code' => 'E87.6', 'description' => 'Hypokalaemia', 'category' => 'Endocrine diseases', 'chapter' => 'IV', 'is_billable' => true],

            // Chapter V — Mental and behavioural disorders (F00–F99)
            ['code' => 'F10.2', 'description' => 'Mental and behavioural disorders due to use of alcohol, dependence syndrome', 'category' => 'Mental disorders', 'chapter' => 'V', 'is_billable' => true],
            ['code' => 'F20.9', 'description' => 'Schizophrenia, unspecified', 'category' => 'Mental disorders', 'chapter' => 'V', 'is_billable' => true],
            ['code' => 'F32.9', 'description' => 'Depressive episode, unspecified', 'category' => 'Mental disorders', 'chapter' => 'V', 'is_billable' => true],
            ['code' => 'F41.9', 'description' => 'Anxiety disorder, unspecified', 'category' => 'Mental disorders', 'chapter' => 'V', 'is_billable' => true],

            // Chapter VI — Diseases of nervous system (G00–G99)
            ['code' => 'G40.9', 'description' => 'Epilepsy, unspecified', 'category' => 'Nervous system diseases', 'chapter' => 'VI', 'is_billable' => true],
            ['code' => 'G43.9', 'description' => 'Migraine, unspecified', 'category' => 'Nervous system diseases', 'chapter' => 'VI', 'is_billable' => true],
            ['code' => 'G44.2', 'description' => 'Tension-type headache', 'category' => 'Nervous system diseases', 'chapter' => 'VI', 'is_billable' => true],

            // Chapter VII — Diseases of the eye (H00–H59)
            ['code' => 'H10.9', 'description' => 'Conjunctivitis, unspecified', 'category' => 'Eye diseases', 'chapter' => 'VII', 'is_billable' => true],
            ['code' => 'H40.9', 'description' => 'Glaucoma, unspecified', 'category' => 'Eye diseases', 'chapter' => 'VII', 'is_billable' => true],
            ['code' => 'H25.9', 'description' => 'Senile cataract, unspecified', 'category' => 'Eye diseases', 'chapter' => 'VII', 'is_billable' => true],

            // Chapter VIII — Diseases of the ear (H60–H95)
            ['code' => 'H66.9', 'description' => 'Otitis media, unspecified', 'category' => 'Ear diseases', 'chapter' => 'VIII', 'is_billable' => true],
            ['code' => 'H65.9', 'description' => 'Nonsuppurative otitis media, unspecified', 'category' => 'Ear diseases', 'chapter' => 'VIII', 'is_billable' => true],

            // Chapter IX — Diseases of the circulatory system (I00–I99)
            ['code' => 'I10', 'description' => 'Essential (primary) hypertension', 'category' => 'Circulatory diseases', 'chapter' => 'IX', 'is_billable' => true],
            ['code' => 'I11.9', 'description' => 'Hypertensive heart disease without heart failure', 'category' => 'Circulatory diseases', 'chapter' => 'IX', 'is_billable' => true],
            ['code' => 'I20.9', 'description' => 'Angina pectoris, unspecified', 'category' => 'Circulatory diseases', 'chapter' => 'IX', 'is_billable' => true],
            ['code' => 'I21.9', 'description' => 'Acute myocardial infarction, unspecified', 'category' => 'Circulatory diseases', 'chapter' => 'IX', 'is_billable' => true],
            ['code' => 'I25.9', 'description' => 'Chronic ischaemic heart disease, unspecified', 'category' => 'Circulatory diseases', 'chapter' => 'IX', 'is_billable' => true],
            ['code' => 'I50.9', 'description' => 'Heart failure, unspecified', 'category' => 'Circulatory diseases', 'chapter' => 'IX', 'is_billable' => true],
            ['code' => 'I63.9', 'description' => 'Cerebral infarction, unspecified (Stroke)', 'category' => 'Circulatory diseases', 'chapter' => 'IX', 'is_billable' => true],
            ['code' => 'I64', 'description' => 'Stroke, not specified as haemorrhage or infarction', 'category' => 'Circulatory diseases', 'chapter' => 'IX', 'is_billable' => true],
            ['code' => 'I84.9', 'description' => 'Unspecified haemorrhoids without complication', 'category' => 'Circulatory diseases', 'chapter' => 'IX', 'is_billable' => true],

            // Chapter X — Diseases of the respiratory system (J00–J99)
            ['code' => 'J00', 'description' => 'Acute nasopharyngitis [common cold]', 'category' => 'Respiratory diseases', 'chapter' => 'X', 'is_billable' => true],
            ['code' => 'J02.9', 'description' => 'Acute pharyngitis, unspecified', 'category' => 'Respiratory diseases', 'chapter' => 'X', 'is_billable' => true],
            ['code' => 'J03.9', 'description' => 'Acute tonsillitis, unspecified', 'category' => 'Respiratory diseases', 'chapter' => 'X', 'is_billable' => true],
            ['code' => 'J06.9', 'description' => 'Acute upper respiratory infection, unspecified (URTI)', 'category' => 'Respiratory diseases', 'chapter' => 'X', 'is_billable' => true],
            ['code' => 'J11.1', 'description' => 'Influenza with other respiratory manifestations', 'category' => 'Respiratory diseases', 'chapter' => 'X', 'is_billable' => true],
            ['code' => 'J15.9', 'description' => 'Bacterial pneumonia, unspecified', 'category' => 'Respiratory diseases', 'chapter' => 'X', 'is_billable' => true],
            ['code' => 'J18.9', 'description' => 'Pneumonia, unspecified', 'category' => 'Respiratory diseases', 'chapter' => 'X', 'is_billable' => true],
            ['code' => 'J20.9', 'description' => 'Acute bronchitis, unspecified', 'category' => 'Respiratory diseases', 'chapter' => 'X', 'is_billable' => true],
            ['code' => 'J42', 'description' => 'Unspecified chronic bronchitis', 'category' => 'Respiratory diseases', 'chapter' => 'X', 'is_billable' => true],
            ['code' => 'J44.9', 'description' => 'Chronic obstructive pulmonary disease, unspecified (COPD)', 'category' => 'Respiratory diseases', 'chapter' => 'X', 'is_billable' => true],
            ['code' => 'J45.9', 'description' => 'Asthma, unspecified', 'category' => 'Respiratory diseases', 'chapter' => 'X', 'is_billable' => true],
            ['code' => 'J46', 'description' => 'Status asthmaticus (Acute severe asthma)', 'category' => 'Respiratory diseases', 'chapter' => 'X', 'is_billable' => true],

            // Chapter XI — Diseases of the digestive system (K00–K93)
            ['code' => 'K02.9', 'description' => 'Dental caries, unspecified', 'category' => 'Digestive diseases', 'chapter' => 'XI', 'is_billable' => true],
            ['code' => 'K21.0', 'description' => 'Gastro-oesophageal reflux disease with oesophagitis', 'category' => 'Digestive diseases', 'chapter' => 'XI', 'is_billable' => true],
            ['code' => 'K25.9', 'description' => 'Gastric ulcer, unspecified', 'category' => 'Digestive diseases', 'chapter' => 'XI', 'is_billable' => true],
            ['code' => 'K26.9', 'description' => 'Duodenal ulcer, unspecified', 'category' => 'Digestive diseases', 'chapter' => 'XI', 'is_billable' => true],
            ['code' => 'K29.7', 'description' => 'Gastritis, unspecified', 'category' => 'Digestive diseases', 'chapter' => 'XI', 'is_billable' => true],
            ['code' => 'K35.9', 'description' => 'Acute appendicitis, unspecified', 'category' => 'Digestive diseases', 'chapter' => 'XI', 'is_billable' => true],
            ['code' => 'K40.9', 'description' => 'Unilateral or unspecified inguinal hernia without obstruction or gangrene', 'category' => 'Digestive diseases', 'chapter' => 'XI', 'is_billable' => true],
            ['code' => 'K59.0', 'description' => 'Constipation', 'category' => 'Digestive diseases', 'chapter' => 'XI', 'is_billable' => true],
            ['code' => 'K70.3', 'description' => 'Alcoholic cirrhosis of liver', 'category' => 'Digestive diseases', 'chapter' => 'XI', 'is_billable' => true],
            ['code' => 'K76.0', 'description' => 'Fatty (change of) liver, not elsewhere classified', 'category' => 'Digestive diseases', 'chapter' => 'XI', 'is_billable' => true],
            ['code' => 'K80.2', 'description' => 'Calculus of gallbladder without cholecystitis', 'category' => 'Digestive diseases', 'chapter' => 'XI', 'is_billable' => true],

            // Chapter XII — Diseases of skin (L00–L99)
            ['code' => 'L02.9', 'description' => 'Cutaneous abscess, furuncle and carbuncle, unspecified', 'category' => 'Skin diseases', 'chapter' => 'XII', 'is_billable' => true],
            ['code' => 'L03.9', 'description' => 'Cellulitis, unspecified', 'category' => 'Skin diseases', 'chapter' => 'XII', 'is_billable' => true],
            ['code' => 'L20.9', 'description' => 'Atopic dermatitis, unspecified', 'category' => 'Skin diseases', 'chapter' => 'XII', 'is_billable' => true],
            ['code' => 'L30.9', 'description' => 'Dermatitis, unspecified (Eczema)', 'category' => 'Skin diseases', 'chapter' => 'XII', 'is_billable' => true],
            ['code' => 'L50.9', 'description' => 'Urticaria, unspecified', 'category' => 'Skin diseases', 'chapter' => 'XII', 'is_billable' => true],

            // Chapter XIII — Diseases of musculoskeletal system (M00–M99)
            ['code' => 'M06.9', 'description' => 'Rheumatoid arthritis, unspecified', 'category' => 'Musculoskeletal diseases', 'chapter' => 'XIII', 'is_billable' => true],
            ['code' => 'M13.9', 'description' => 'Arthritis, unspecified', 'category' => 'Musculoskeletal diseases', 'chapter' => 'XIII', 'is_billable' => true],
            ['code' => 'M17.9', 'description' => 'Gonarthrosis [osteoarthritis of knee], unspecified', 'category' => 'Musculoskeletal diseases', 'chapter' => 'XIII', 'is_billable' => true],
            ['code' => 'M25.5', 'description' => 'Pain in joint', 'category' => 'Musculoskeletal diseases', 'chapter' => 'XIII', 'is_billable' => true],
            ['code' => 'M54.5', 'description' => 'Low back pain', 'category' => 'Musculoskeletal diseases', 'chapter' => 'XIII', 'is_billable' => true],
            ['code' => 'M54.9', 'description' => 'Dorsalgia, unspecified (Back pain)', 'category' => 'Musculoskeletal diseases', 'chapter' => 'XIII', 'is_billable' => true],
            ['code' => 'M79.3', 'description' => 'Panniculitis, unspecified', 'category' => 'Musculoskeletal diseases', 'chapter' => 'XIII', 'is_billable' => true],

            // Chapter XIV — Diseases of genitourinary system (N00–N99)
            ['code' => 'N18.9', 'description' => 'Chronic kidney disease, unspecified', 'category' => 'Genitourinary diseases', 'chapter' => 'XIV', 'is_billable' => true],
            ['code' => 'N20.0', 'description' => 'Calculus of kidney (Kidney stone)', 'category' => 'Genitourinary diseases', 'chapter' => 'XIV', 'is_billable' => true],
            ['code' => 'N30.0', 'description' => 'Acute cystitis', 'category' => 'Genitourinary diseases', 'chapter' => 'XIV', 'is_billable' => true],
            ['code' => 'N39.0', 'description' => 'Urinary tract infection, site not specified (UTI)', 'category' => 'Genitourinary diseases', 'chapter' => 'XIV', 'is_billable' => true],
            ['code' => 'N40', 'description' => 'Hyperplasia of prostate (BPH)', 'category' => 'Genitourinary diseases', 'chapter' => 'XIV', 'is_billable' => true],
            ['code' => 'N73.0', 'description' => 'Acute parametritis and pelvic cellulitis (PID)', 'category' => 'Genitourinary diseases', 'chapter' => 'XIV', 'is_billable' => true],
            ['code' => 'N76.0', 'description' => 'Acute vaginitis', 'category' => 'Genitourinary diseases', 'chapter' => 'XIV', 'is_billable' => true],

            // Chapter XV — Pregnancy, childbirth & puerperium (O00–O99)
            ['code' => 'O00.9', 'description' => 'Ectopic pregnancy, unspecified', 'category' => 'Pregnancy conditions', 'chapter' => 'XV', 'is_billable' => true],
            ['code' => 'O03.9', 'description' => 'Spontaneous abortion, complete or unspecified, without complication', 'category' => 'Pregnancy conditions', 'chapter' => 'XV', 'is_billable' => true],
            ['code' => 'O14.9', 'description' => 'Pre-eclampsia, unspecified', 'category' => 'Pregnancy conditions', 'chapter' => 'XV', 'is_billable' => true],
            ['code' => 'O24.9', 'description' => 'Diabetes mellitus in pregnancy, unspecified (Gestational diabetes)', 'category' => 'Pregnancy conditions', 'chapter' => 'XV', 'is_billable' => true],
            ['code' => 'O42.9', 'description' => 'Premature rupture of membranes, unspecified', 'category' => 'Pregnancy conditions', 'chapter' => 'XV', 'is_billable' => true],
            ['code' => 'O80', 'description' => 'Single spontaneous delivery', 'category' => 'Pregnancy conditions', 'chapter' => 'XV', 'is_billable' => true],
            ['code' => 'O82', 'description' => 'Single delivery by caesarean section', 'category' => 'Pregnancy conditions', 'chapter' => 'XV', 'is_billable' => true],

            // Chapter XVI — Perinatal conditions (P00–P96)
            ['code' => 'P07.3', 'description' => 'Other preterm infants', 'category' => 'Perinatal conditions', 'chapter' => 'XVI', 'is_billable' => true],
            ['code' => 'P22.9', 'description' => 'Respiratory distress of newborn, unspecified', 'category' => 'Perinatal conditions', 'chapter' => 'XVI', 'is_billable' => true],
            ['code' => 'P59.9', 'description' => 'Neonatal jaundice, unspecified', 'category' => 'Perinatal conditions', 'chapter' => 'XVI', 'is_billable' => true],

            // Chapter XVIII — Symptoms, signs & abnormal findings (R00–R99)
            ['code' => 'R05', 'description' => 'Cough', 'category' => 'Symptoms and signs', 'chapter' => 'XVIII', 'is_billable' => true],
            ['code' => 'R10.4', 'description' => 'Other and unspecified abdominal pain', 'category' => 'Symptoms and signs', 'chapter' => 'XVIII', 'is_billable' => true],
            ['code' => 'R11', 'description' => 'Nausea and vomiting', 'category' => 'Symptoms and signs', 'chapter' => 'XVIII', 'is_billable' => true],
            ['code' => 'R19.7', 'description' => 'Diarrhoea, unspecified', 'category' => 'Symptoms and signs', 'chapter' => 'XVIII', 'is_billable' => true],
            ['code' => 'R50.9', 'description' => 'Fever, unspecified', 'category' => 'Symptoms and signs', 'chapter' => 'XVIII', 'is_billable' => true],
            ['code' => 'R51', 'description' => 'Headache', 'category' => 'Symptoms and signs', 'chapter' => 'XVIII', 'is_billable' => true],
            ['code' => 'R52', 'description' => 'Pain, unspecified', 'category' => 'Symptoms and signs', 'chapter' => 'XVIII', 'is_billable' => true],
            ['code' => 'R53', 'description' => 'Malaise and fatigue', 'category' => 'Symptoms and signs', 'chapter' => 'XVIII', 'is_billable' => true],
            ['code' => 'R55', 'description' => 'Syncope and collapse', 'category' => 'Symptoms and signs', 'chapter' => 'XVIII', 'is_billable' => true],
            ['code' => 'R73.9', 'description' => 'Hyperglycaemia, unspecified', 'category' => 'Symptoms and signs', 'chapter' => 'XVIII', 'is_billable' => true],

            // Chapter XIX — Injury, poisoning (S00–T98)
            ['code' => 'S00.9', 'description' => 'Superficial injury of head, unspecified', 'category' => 'Injuries', 'chapter' => 'XIX', 'is_billable' => true],
            ['code' => 'S06.0', 'description' => 'Concussion', 'category' => 'Injuries', 'chapter' => 'XIX', 'is_billable' => true],
            ['code' => 'S52.9', 'description' => 'Fracture of forearm, part unspecified', 'category' => 'Injuries', 'chapter' => 'XIX', 'is_billable' => true],
            ['code' => 'S62.0', 'description' => 'Fracture of navicular [scaphoid] bone of hand', 'category' => 'Injuries', 'chapter' => 'XIX', 'is_billable' => true],
            ['code' => 'S72.0', 'description' => 'Fracture of neck of femur', 'category' => 'Injuries', 'chapter' => 'XIX', 'is_billable' => true],
            ['code' => 'S82.9', 'description' => 'Fracture of lower leg, unspecified', 'category' => 'Injuries', 'chapter' => 'XIX', 'is_billable' => true],
            ['code' => 'T14.0', 'description' => 'Superficial injury of unspecified body region', 'category' => 'Injuries', 'chapter' => 'XIX', 'is_billable' => true],
            ['code' => 'T14.1', 'description' => 'Open wound of unspecified body region', 'category' => 'Injuries', 'chapter' => 'XIX', 'is_billable' => true],
            ['code' => 'T30.0', 'description' => 'Burn of unspecified body region, unspecified degree', 'category' => 'Injuries', 'chapter' => 'XIX', 'is_billable' => true],
            ['code' => 'T78.4', 'description' => 'Allergy, unspecified', 'category' => 'Injuries', 'chapter' => 'XIX', 'is_billable' => true],

            // Chapter XX — External causes (V01–Y98)
            ['code' => 'V89.2', 'description' => 'Traffic accident (Road traffic accident, unspecified)', 'category' => 'External causes', 'chapter' => 'XX', 'is_billable' => false],

            // Chapter XXI — Factors influencing health status (Z00–Z99)
            ['code' => 'Z00.0', 'description' => 'General medical examination', 'category' => 'Health status factors', 'chapter' => 'XXI', 'is_billable' => false],
            ['code' => 'Z01.0', 'description' => 'Examination of eyes and vision', 'category' => 'Health status factors', 'chapter' => 'XXI', 'is_billable' => false],
            ['code' => 'Z23', 'description' => 'Need for immunization against single bacterial diseases', 'category' => 'Health status factors', 'chapter' => 'XXI', 'is_billable' => false],
            ['code' => 'Z30.0', 'description' => 'General counselling and advice on contraception', 'category' => 'Health status factors', 'chapter' => 'XXI', 'is_billable' => false],
            ['code' => 'Z34.9', 'description' => 'Supervision of normal pregnancy, unspecified (Antenatal care)', 'category' => 'Health status factors', 'chapter' => 'XXI', 'is_billable' => false],
            ['code' => 'Z39.0', 'description' => 'Care and examination immediately after delivery (Postnatal care)', 'category' => 'Health status factors', 'chapter' => 'XXI', 'is_billable' => false],
        ];
    }
}
