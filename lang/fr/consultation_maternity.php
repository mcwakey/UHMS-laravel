<?php

return [
    // Phase 14R.2 — passerelle Consultation ↔ Maternité (messages de domaine).
    'maternity_context' => 'Contexte de maternité',

    'context_types' => [
        'pregnancy_profile' => 'Profil de grossesse',
        'maternity_case' => 'Dossier de maternité',
        'anc_visit' => 'Visite prénatale',
        'labor' => 'Épisode de travail',
        'delivery' => 'Dossier d\'accouchement',
        'newborn' => 'Dossier du nouveau-né',
        'postnatal' => 'Dossier postnatal',
    ],

    'link_roles' => [
        'primary' => 'Principal',
        'reviewed' => 'Consulté',
        'created' => 'Créé',
        'handoff' => 'Transfert',
        'historical' => 'Historique',
    ],

    'resolution_sources' => [
        'explicit' => 'Lien explicite',
        'visit' => 'Contexte de la même visite',
        'admission' => 'Contexte de la même admission',
        'active_profile' => 'Contexte de grossesse active',
        'none' => 'Aucun contexte de maternité',
    ],

    'statuses' => [
        'resolved' => 'Contexte de maternité résolu',
        'ambiguous' => 'Contexte de maternité ambigu',
        'none' => 'Aucun contexte de maternité',
        'invalid' => 'Contexte de maternité invalide',
    ],

    'context_linked' => 'Contexte de maternité lié.',
    'context_relinked' => 'Contexte de maternité relié.',
    'context_unlinked' => 'Contexte de maternité dissocié.',

    'link_reason' => 'Motif de la liaison',
    'unlink_reason' => 'Motif de la dissociation',

    'no_maternity_context' => 'Aucun contexte de maternité n\'est lié à cette consultation.',
    'ambiguous_maternity_context' => 'Le contexte de maternité est ambigu et doit être sélectionné explicitement.',
    'multiple_active_pregnancy_profiles' => 'Cette patiente a plusieurs profils de grossesse actifs.',

    'errors' => [
        'unsupported_target' => 'Cible de maternité non prise en charge : :target.',
        'invalid_target' => 'Le dossier de maternité n\'a pas pu être validé.',
        'patient_mismatch' => 'Le dossier de maternité appartient à une autre patiente.',
        'inconsistent_context' => 'La chaîne du dossier de maternité est incohérente et ne peut pas être liée.',
        'relink_required' => 'Un autre dossier est déjà lié pour ce contexte. Utilisez la reliaison avec un motif.',
        'reason_required' => 'Un motif est requis.',
        'link_not_found' => 'Aucun lien de maternité actif n\'a été trouvé pour ce contexte.',
    ],

    'warnings' => [
        'multiple_active_profiles' => 'Cette patiente a plusieurs profils de grossesse actifs. Sélectionnez-en un explicitement.',
        'multiple_candidate_profiles' => 'Plusieurs profils de grossesse correspondent à ce contexte. Sélectionnez-en un explicitement.',
        'link_target_missing' => 'Le dossier :context lié est introuvable.',
        'link_patient_mismatch' => 'Le dossier :context lié appartient à une autre patiente.',
        'source_of_truth' => 'Les dossiers de maternité font foi pour cette information.',
        'duplicate_data' => 'Cette information est déjà enregistrée dans le dossier de maternité.',
    ],
];
