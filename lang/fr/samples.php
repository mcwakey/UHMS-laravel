<?php

return [

    // ── Libellés de statut (App\Enums\SampleStatus) ────────────────────
    'status' => [
        'pending'   => 'En attente de prélèvement',
        'collected' => 'Prélevé',
        'received'  => 'Reçu',
        'rejected'  => 'Rejeté',
        'disposed'  => 'Éliminé',
    ],

    // ── Libellés des types de prélèvement (config/specimens.php) ───────
    'specimen' => [
        'whole_blood' => 'Sang total',
        'serum'       => 'Sérum',
        'plasma'      => 'Plasma',
        'urine'       => 'Urine',
        'stool'       => 'Selles',
        'csf'         => 'LCR',
        'sputum'      => 'Expectoration',
        'swab'        => 'Écouvillon',
        'tissue'      => 'Tissu',
        'other'       => 'Autre',
    ],

    // ── Panneau de la page de demande ──────────────────────────────────
    'panel_title'          => 'Échantillons / Prélèvements',
    'generate_btn'         => 'Générer les échantillons',
    'generate_missing_btn' => 'Générer les manquants',
    'none_yet'             => 'Aucun échantillon généré pour cette demande.',
    'sample_col'           => 'Échantillon',
    'specimen_col'         => 'Prélèvement',
    'items_col'            => 'Analyses',
    'status_col'           => 'Statut',
    'chain_col'            => 'Chaîne de traçabilité',
    'actions_col'          => 'Actions',
    'unassigned_note'      => ":count analyse(s) ne sont pas encore liées à un échantillon. Générez les échantillons pour les inclure.",

    // ── Actions et fenêtres ────────────────────────────────────────────
    'collect_btn'            => 'Prélever',
    'collect_title'          => 'Prélever l\'échantillon',
    'barcode_label'          => 'Code-barres / ID échantillon',
    'barcode_hint'           => 'Sert à rattacher les résultats de l\'automate à cet échantillon. Par défaut, le numéro d\'échantillon.',
    'container_label'        => 'Contenant / Tube',
    'notes_label'            => 'Notes',
    'cancel_btn'             => 'Annuler',
    'mark_collected_btn'     => 'Marquer prélevé',
    'receive_btn'            => 'Réceptionner',
    'receive_confirm_title'  => 'Réceptionner cet échantillon ?',
    'receive_confirm_text'   => 'L\'échantillon :number sera enregistré comme reçu au laboratoire. Les résultats pourront alors être saisis.',
    'reject_btn'             => 'Rejeter',
    'reject_title'           => 'Rejeter l\'échantillon',
    'reject_hint'            => 'Un échantillon rejeté doit être re-prélevé avant la saisie des résultats.',
    'reject_confirm_text'    => 'Rejeter l\'échantillon :number ? Un motif est requis.',
    'rejection_reason_label' => 'Motif du rejet',
    'select_reason'          => '— Choisir un motif —',
    'rejection_reasons'      => [
        'Hémolysé',
        'Quantité insuffisante',
        'Coagulé',
        'Mauvais contenant',
        'Mal étiqueté / non étiqueté',
        'Fuite pendant le transport',
        'Contaminé',
        'Périmé / transport retardé',
    ],
    'dispose_btn'            => 'Éliminer',
    'dispose_confirm_title'  => 'Éliminer cet échantillon ?',
    'dispose_confirm_text'   => 'Marquer l\'échantillon :number comme éliminé. Action définitive.',
    'collect_confirm_title'  => 'Prélever cet échantillon ?',
    'collect_confirm_text'   => 'Marquer l\'échantillon :number comme prélevé avec son code-barres et son contenant par défaut.',

    // ── Blocage de la saisie des résultats ─────────────────────────────
    'awaiting_sample_badge'  => 'Échantillon en attente',
    'awaiting_sample_title'  => 'Le prélèvement de cette analyse n\'a pas encore été reçu au laboratoire.',

    // ── Page file d\'attente ───────────────────────────────────────────
    'queue_title'          => 'Suivi des prélèvements',
    'queue_subtitle'       => 'Prélever, réceptionner, rejeter et éliminer les échantillons de laboratoire.',
    'stat_pending'         => 'En attente de prélèvement',
    'stat_collected'       => 'Prélevés',
    'stat_received_today'  => 'Reçus aujourd\'hui',
    'stat_rejected'        => 'Rejetés',
    'search_placeholder'   => 'Rechercher n° échantillon, code-barres, patient, n° demande...',
    'all_status'           => 'Tous les statuts',
    'all_specimens'        => 'Tous les prélèvements',
    'none_found'           => 'Aucun échantillon trouvé.',
    'view_request'         => 'Ouvrir la demande',

    // ── Catalogue des analyses : prélèvement par défaut ────────────────
    'default_specimen_label' => 'Type de prélèvement par défaut',
    'default_specimen_none'  => '— Aucun (par défaut à la génération) —',
    'default_specimen_hint'  => 'Le prélèvement sur lequel cette analyse est habituellement réalisée. Détermine le regroupement de la demande en échantillons.',

    // ── Messages flash du contrôleur ───────────────────────────────────
    'generated_count'    => ':count échantillon(s) généré(s).',
    'collected_success'  => 'Échantillon :number marqué comme prélevé.',
    'received_success'   => 'Échantillon :number réceptionné.',
    'rejected_success'   => 'Échantillon :number rejeté.',
    'disposed_success'   => 'Échantillon :number éliminé.',

    // ── Erreurs ────────────────────────────────────────────────────────
    'errors' => [
        'terminal'                  => 'Cet échantillon est dans un état final et ne peut plus changer.',
        'receive_requires_collected'=> 'Seul un échantillon prélevé peut être réceptionné.',
        'already_disposed'          => 'Cet échantillon a déjà été éliminé.',
        'result_blocked'            => 'Prélèvement non reçu : l\'échantillon de cette analyse doit être reçu au laboratoire avant la saisie des résultats.',
    ],

    // ── Journal d\'activité / parcours ──────────────────────────────────
    'log' => [
        'generated' => ':count échantillon(s) généré(s) pour :number',
        'collected' => 'Échantillon prélevé : :number',
        'received'  => 'Échantillon reçu : :number',
        'rejected'  => 'Échantillon rejeté : :number (:reason)',
        'disposed'  => 'Échantillon éliminé : :number',
    ],
    'pathway' => [
        'collected' => 'Prélèvement effectué',
        'received'  => 'Prélèvement reçu',
        'rejected'  => 'Prélèvement rejeté',
    ],
];
