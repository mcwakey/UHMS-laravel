<?php

return [
    // Page titles / headers
    'lab_analyzers' => 'Analyseurs de laboratoire',
    'analyzers' => 'Analyseurs',
    'analyzer_detail_title' => ':name — Détail de l\'analyseur',
    'analyzer_diagnostics' => 'Diagnostic des analyseurs',
    'message_log' => 'Journal des messages',
    'message_log_diagnostics' => 'Journal des messages et diagnostic',

    // Stats - index
    'total_devices' => 'Total des appareils',
    'hl7_devices' => 'Appareils HL7',
    'test_mappings' => 'Correspondances de tests',

    // Stats - diagnostics
    'received' => 'Reçu',
    'processing' => 'En traitement',
    'processed' => 'Traité',
    'failed' => 'Échoué',
    'today_total' => 'Total du jour',
    'today_processed' => 'Traités aujourd\'hui',

    // Filters
    'all_analyzers' => 'Tous les analyseurs',
    'all_protocols' => 'Tous les protocoles',
    'duplicate' => 'Doublon',
    'search_device' => 'Rechercher un appareil...',
    'sample_id_placeholder' => 'ID d\'échantillon...',

    // Table headers
    'device' => 'Appareil',
    'protocol' => 'Protocole',
    'connection' => 'Connexion',
    'mappings' => 'Correspondances',
    'messages' => 'Messages',
    'last_connected' => 'Dernière connexion',
    'direction' => 'Direction',
    'sample_id' => 'ID d\'échantillon',
    'attempts' => 'Tentatives',
    'size' => 'Taille',
    'analyzer' => 'Analyseur',

    // Values
    'never' => 'Jamais',
    'unknown' => 'Inconnu',

    // Empty states
    'no_messages_found' => 'Aucun message trouvé.',
    'no_devices' => 'Aucun appareil analyseur configuré pour le moment.',
    'no_mappings' => 'Aucune correspondance de test configurée. Ajoutez des correspondances pour activer l\'association automatique des résultats.',
    'no_messages_received' => 'Aucun message reçu pour le moment.',

    // View message modal
    'reprocess' => 'Retraiter',
    'raw_message' => 'Message brut',
    'error' => 'Erreur',
    'message_content' => 'Contenu du message',

    // Add / edit analyzer
    'add_analyzer' => 'Ajouter un analyseur',
    'add_analyzer_device' => 'Ajouter un appareil analyseur',
    'edit_analyzer_device' => 'Modifier l\'appareil analyseur',
    'update_analyzer' => 'Mettre à jour l\'analyseur',
    'device_name' => 'Nom de l\'appareil',
    'manufacturer' => 'Fabricant',
    'model' => 'Modèle',
    'connection_type' => 'Type de connexion',
    'ip_address' => 'Adresse IP',
    'port' => 'Port',
    'com_port' => 'Port COM',
    'baud_rate' => 'Débit en bauds',
    'device_name_placeholder' => 'ex. Mindray BC-5000',
    'manufacturer_placeholder' => 'ex. Mindray',
    'model_placeholder' => 'ex. BC-5000',
    'conn_tcp' => 'TCP/IP',
    'conn_serial' => 'Série (COM)',
    'delete_analyzer_confirm' => 'Supprimer cet analyseur ? Cela supprimera également toutes ses correspondances de tests.',

    // Show - device info
    'device_information' => 'Informations sur l\'appareil',
    'created' => 'Créé',
    'listener_command' => 'Commande d\'écoute',
    'start_tcp_listener' => 'Démarrer l\'écouteur TCP pour cet analyseur :',
    'or_listen_all' => 'Ou écouter sur tous les analyseurs actifs :',

    // Mappings
    'test_code_mappings' => 'Correspondances de codes de test',
    'add_mapping' => 'Ajouter une correspondance',
    'analyzer_code' => 'Code analyseur',
    'lab_test' => 'Test de laboratoire',
    'test_code' => 'Code de test',
    'conversion_factor' => 'Facteur de conversion',
    'recent_messages' => 'Messages récents',
    'add_test_mapping' => 'Ajouter une correspondance de test',
    'edit_test_mapping' => 'Modifier la correspondance de test',
    'analyzer_test_code' => 'Code de test de l\'analyseur',
    'analyzer_test_code_placeholder' => 'Code envoyé par l\'analyseur (ex. WBC, HGB, PLT)',
    'analyzer_test_code_hint' => 'Le code de test tel qu\'il apparaît dans le message de sortie de l\'analyseur.',
    'map_to_lab_test' => 'Associer à un test de laboratoire',
    'select_lab_test' => '— Sélectionner un test de laboratoire —',
    'unit_conversion_factor' => 'Facteur de conversion d\'unité',
    'unit_conversion_hint' => 'Multipliez la valeur de l\'analyseur par ce facteur. Laissez à 1.0 si les unités correspondent.',
    'update_mapping' => 'Mettre à jour la correspondance',
    'remove_mapping_confirm' => 'Supprimer cette correspondance ?',
];
