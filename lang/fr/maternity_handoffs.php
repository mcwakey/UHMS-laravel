<?php

/*
 * Phase 14R.5 — passerelles Consultation / Urgences / Hospitalisation /
 * Maternité. Parité récursive stricte avec lang/en/maternity_handoffs.php.
 */

return [

    'ownership' => [
        'operational_owner' => 'Responsable opérationnel',
        'consultation_encounter' => 'Épisode de consultation',
        'emergency_episode' => 'Épisode d\'urgence',
        'admission_episode' => 'Épisode d\'hospitalisation',
        'maternity_longitudinal_record' => 'Dossier longitudinal de maternité',
        'source_of_truth' => 'Source de vérité',
        'handoff_context' => 'Contexte de transfert',
    ],

    'cards' => [
        'pregnancy' => 'Profil de grossesse',
        'anc' => 'Visite prénatale',
        'labor' => 'Épisode de travail',
        'delivery' => 'Dossier d\'accouchement',
        'newborn' => 'Dossier du nouveau-né',
        'postnatal' => 'Dossier post-natal',
        'record_ref' => ':type n° :id',
        'record_count' => ':count dossier(s) de nouveau-né',
        'open_record' => 'Ouvrir le dossier',
        'no_context' => 'Aucun contexte de maternité n\'est lié à ce dossier.',
        'title' => 'Contexte de maternité',
    ],

    'fields' => [
        'status' => 'Statut',
        'gestational_age' => 'Âge gestationnel',
        'edd' => 'DPA',
        'dating_method' => 'Méthode de datation',
        'risk' => 'Risque',
        'latest_visit' => 'Dernière visite',
        'visit_number' => 'Numéro de visite',
        'next_visit' => 'Prochaine visite',
        'stage' => 'Phase',
        'started_at' => 'Début',
        'escalation' => 'Escalade',
        'emergency_escalation_flagged' => 'Escalade vers les urgences signalée',
        'delivered_at' => 'Accouchement',
        'mode' => 'Mode',
        'outcome' => 'Issue',
        'newborn_count' => 'Nouveau-nés',
        'birth_weights' => 'Poids de naissance',
        'apgar_5' => 'Apgar (5 min)',
        'mother_ready' => 'Mère prête',
        'newborn_ready' => 'Nouveau-né prêt',
        'not_ready' => 'Pas prêt',
        'referral' => 'Référence',
        'referral_required' => 'Référence requise',
    ],

    'risks' => [
        'previous_caesarean' => 'Césarienne antérieure',
        'previous_pph' => 'HPP antérieure',
        'hypertensive' => 'Risque de trouble hypertensif',
        'diabetes' => 'Risque de diabète',
        'multiple' => 'Grossesse multiple',
    ],

    'consultation' => [
        'title' => 'Transferts vers la maternité',
        'create_admission_request' => 'Créer une demande d\'hospitalisation',
        'existing_admission_request' => 'Demande d\'hospitalisation existante',
        'admission_request_from_consultation' => 'Demande d\'hospitalisation créée depuis la consultation',
        'refer_obstetrics' => 'Référer en obstétrique/maternité',
        'remains_gynaecology' => 'Cette consultation reste une consultation de gynécologie.',
        'open_obstetrics_referral' => 'Ouvrir la référence obstétrique',
        'open_postnatal_review' => 'Ouvrir la revue post-natale',
        'explicit_link_required' => 'Liez un profil de grossesse avant de créer une demande d\'hospitalisation.',
        'no_billing_posted' => 'Cette action ne génère aucune facturation et aucune hospitalisation.',
    ],

    'emergency' => [
        'title' => 'Contexte de maternité aux urgences',
        'link_pregnancy_profile' => 'Lier un profil de grossesse',
        'create_pregnancy_profile' => 'Créer un profil de grossesse',
        'start_or_open_labor' => 'Démarrer/ouvrir le travail',
        'create_or_open_admission_request' => 'Créer/ouvrir une demande d\'hospitalisation',
        'emergency_owns_acute_care' => 'Les urgences restent responsables des soins aigus.',
        'maternity_owns_pregnancy' => 'La maternité est responsable de la grossesse et du travail.',
        'suggested_context' => 'Contexte suggéré',
        'suggested_context_notice' => 'Un dossier de maternité existe pour cette visite. Il est affiché à titre de suggestion et n\'a pas été lié.',
        'confirm_link' => 'Confirmer le lien',
        'ambiguous_profile' => 'Profil de grossesse ambigu — choisissez-en un explicitement.',
        'no_context_notice' => 'Aucun profil de grossesse n\'est lié. Rien n\'a été créé automatiquement.',
    ],

    'admission' => [
        'title' => 'Contexte de maternité de l\'hospitalisation',
        'context_from_request' => 'Contexte issu de la demande d\'hospitalisation',
        'context_linked_directly' => 'Contexte lié directement',
        'open_pregnancy_profile' => 'Ouvrir le profil de grossesse',
        'open_labor' => 'Ouvrir le travail',
        'open_delivery' => 'Ouvrir l\'accouchement',
        'open_newborn' => 'Ouvrir le nouveau-né',
        'open_postnatal' => 'Ouvrir le dossier post-natal',
        'context_conflict' => 'Conflit de contexte de maternité — vérification requise.',
        'ambiguous_legacy_source' => 'Cette demande possède une source maternité héritée dont le type de dossier ne peut être déterminé. Liez explicitement le bon contexte.',
        'admission_owns' => 'Le lit, le service, les soins infirmiers, les médicaments et la sortie restent gérés par l\'hospitalisation.',
        'clinical_writes_in_maternity' => 'Les dossiers cliniques sont saisis dans l\'espace maternité.',
    ],

    'handoffs' => [
        'created' => 'Transfert créé',
        'already_exists' => 'Le transfert existe déjà',
        'unavailable' => 'Transfert indisponible',
        'blocked' => 'Transfert bloqué',
        'record_reused' => 'Dossier réutilisé',
        'duplicate_prevented' => 'Doublon évité',
        'context_propagation_complete' => 'Propagation du contexte terminée',
        'context_propagation_conflict' => 'Conflit de propagation du contexte',
        'return_to_consultation' => 'Retour à la consultation',
        'return_to_emergency' => 'Retour aux urgences',
        'return_to_admission' => 'Retour à l\'hospitalisation',
        'return_to_maternity' => 'Retour à la maternité',
    ],

    'postnatal' => [
        'review' => 'Revue post-natale',
        'readiness_advisory' => 'La disponibilité post-natale est indicative.',
        'record_observations_in_maternity' => 'Saisissez les observations dans la maternité.',
        'no_observations_duplicated' => 'Aucune observation n\'a été dupliquée.',
        'link_case' => 'Lier le dossier post-natal',
    ],

    'messages' => [
        'context_linked' => 'Contexte de maternité lié.',
        'context_relinked' => 'Contexte de maternité remplacé. Le lien précédent est conservé dans l\'historique.',
        'context_unlinked' => 'Contexte de maternité délié. Le lien précédent est conservé dans l\'historique.',
        'profile_created' => 'Profil de grossesse n° :id créé et lié.',
        'labor_started' => 'Épisode de travail n° :id démarré.',
        'labor_reused' => 'L\'épisode de travail n° :id est déjà actif et a été réutilisé.',
        'admission_request_created' => 'Demande d\'hospitalisation n° :id créée.',
        'admission_request_exists' => 'La demande d\'hospitalisation n° :id est déjà ouverte et a été réutilisée.',
        'emergency_case_created' => 'Dossier d\'urgence n° :id créé.',
        'emergency_case_exists' => 'Le dossier d\'urgence n° :id est déjà actif et a été réutilisé.',
        'obstetrics_referral_created' => 'Référence obstétrique créée. Cette consultation reste une consultation de gynécologie.',
        'obstetrics_referral_exists' => 'Une référence obstétrique existe déjà pour cette visite.',
        'obstetrics_referral_unavailable' => 'Aucun service de consultation d\'obstétrique n\'est configuré. Utilisez le flux standard de création de consultation.',
        'postnatal_review_linked' => 'Dossier post-natal lié pour revue. Les observations sont saisies dans la maternité.',
        'handoff_unavailable' => 'Ce transfert n\'est pas disponible dans cet environnement.',
    ],

];
