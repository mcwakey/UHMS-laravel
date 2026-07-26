<?php

/* Phase 14R.6 — rapport de réconciliation O&G en simulation. Parité avec en/. */

return [
    'dry_run_only' => 'Simulation uniquement — aucune modification de la base de données.',
    'apply_mode_unavailable' => 'Le mode application n\'est pas disponible en phase 14R.6.',
    'no_database_changes' => 'Aucune modification de la base de données n\'a été effectuée.',
    'classification' => 'Classification',
    'count' => 'Nombre',
    'parser_warning' => 'Avertissement d\'analyse',
    'recommended_action' => 'Action recommandée',
    'original_entry_preserved' => 'La saisie de spécialité d\'origine est préservée.',
    'report_written' => 'Rapport écrit dans :path',

    'classifications' => [
        'safe_to_link' => 'Liaison sûre',
        'safe_to_migrate' => 'Migration sûre',
        'conflict_requires_review' => 'Conflit à examiner',
        'historical_only' => 'Historique uniquement',
        'insufficient_context' => 'Contexte insuffisant',
    ],

    'actions' => [
        'create_bridge_link_only' => 'Créer uniquement un lien de passerelle',
        'create_target_record_then_link' => 'Créer le dossier cible, puis lier',
        'review_manually' => 'Examiner manuellement',
        'preserve_as_encounter_history' => 'Conserver comme historique de l\'épisode',
    ],
];
