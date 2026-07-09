<?php

/*
|--------------------------------------------------------------------------
| Specimen / Sample Types
|--------------------------------------------------------------------------
|
| Catalog of specimen types a laboratory sample can be. Each investigation
| (lab test) may declare a `default_specimen_type` pointing at one of these
| codes, which drives how a request's items are grouped into samples — one
| sample per distinct specimen type ("one sample per specimen type").
|
| Labels are resolved from lang/{locale}/samples.php under the
| `specimen.<code>` key, falling back to the `label` defined here. Colours
| are Bootstrap contextual colours; icons are Tabler icon classes.
|
*/

return [

    // Specimen type assigned to items whose test has no explicit mapping.
    'default' => 'serum',

    'types' => [
        'whole_blood' => ['label' => 'Whole Blood',  'container' => 'EDTA tube',      'color' => 'danger',    'icon' => 'ti-droplet'],
        'serum'       => ['label' => 'Serum',         'container' => 'SST (gold) tube', 'color' => 'warning',   'icon' => 'ti-test-pipe'],
        'plasma'      => ['label' => 'Plasma',        'container' => 'Li-Heparin tube', 'color' => 'warning',   'icon' => 'ti-test-pipe'],
        'urine'       => ['label' => 'Urine',         'container' => 'Urine container', 'color' => 'info',      'icon' => 'ti-flask'],
        'stool'       => ['label' => 'Stool',         'container' => 'Stool container', 'color' => 'secondary', 'icon' => 'ti-flask'],
        'csf'         => ['label' => 'CSF',           'container' => 'Sterile tube',    'color' => 'primary',   'icon' => 'ti-test-pipe'],
        'sputum'      => ['label' => 'Sputum',        'container' => 'Sputum container', 'color' => 'secondary', 'icon' => 'ti-flask'],
        'swab'        => ['label' => 'Swab',          'container' => 'Transport swab',  'color' => 'secondary', 'icon' => 'ti-brush'],
        'tissue'      => ['label' => 'Tissue',        'container' => 'Formalin pot',    'color' => 'purple',    'icon' => 'ti-microscope'],
        'other'       => ['label' => 'Other',         'container' => null,              'color' => 'secondary', 'icon' => 'ti-flask'],
    ],
];
