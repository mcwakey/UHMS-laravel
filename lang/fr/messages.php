<?php

return [

    /* ------------------------------------------------------------------ */
    /* Accounting (journal entries, fiscal years, periods, accounts, etc.) */
    /* ------------------------------------------------------------------ */
    'accounting' => [
        'journal_drafted'          => 'Écriture de journal enregistrée en brouillon.',
        'journal_updated'          => 'Écriture de journal mise à jour.',
        'journal_posted'           => 'Écriture de journal validée.',
        'journal_reversed'         => 'Écriture de journal contrepassée.',
        'journal_cancelled'        => 'Brouillon de journal annulé.',
        'journal_cannot_edit'      => 'Les écritures validées, contrepassées ou annulées ne peuvent pas être modifiées.',
        'fiscal_year_created'      => 'Exercice fiscal créé.',
        'fiscal_year_closed'       => 'Exercice fiscal clôturé.',
        'period_created'           => 'Période comptable créée.',
        'period_closed'            => 'Période comptable clôturée.',
        'account_created'          => 'Compte créé.',
        'account_updated'          => 'Compte mis à jour.',
        'account_disabled'         => 'Compte désactivé.',
        'account_reactivated'      => 'Compte réactivé.',
        'settings_updated'         => 'Paramètres comptables mis à jour.',
        'posting_retried'          => 'Enregistrement comptable retraité avec succès (:number).',
        'posting_retry_failed'     => 'Le retraitement comptable n\'a pas produit d\'écriture. Vérifiez le statut/erreur de la source.',
        'supplier_payment_recorded'=> 'Paiement fournisseur :number enregistré.',
        'supplier_payment_reversed'=> 'Paiement :number contrepassé.',
    ],

    /* ------------------------------------------------------------------ */
    /* Accounts / Categories                                                */
    /* ------------------------------------------------------------------ */
    'accounts' => [
        'category_created'        => 'Catégorie créée avec succès.',
        'category_updated'        => 'Catégorie mise à jour avec succès.',
        'category_status_updated' => 'Statut de la catégorie mis à jour.',
    ],

    /* ------------------------------------------------------------------ */
    /* Admissions                                                           */
    /* ------------------------------------------------------------------ */
    'admissions' => [
        'admitted'          => 'Patient admis avec succès. Admission #:number',
        'discharged'        => 'Patient sorti avec succès.',
        'charge_added'      => 'Frais de service ajouté.',
        'vitals_recorded'   => 'Constantes enregistrées.',
        'ward_round_saved'  => 'Visite de salle enregistrée avec succès.',
    ],

    /* ------------------------------------------------------------------ */
    /* Analyzers                                                            */
    /* ------------------------------------------------------------------ */
    'analyzers' => [
        'created'               => 'Appareil analyseur ajouté avec succès.',
        'updated'               => 'Appareil analyseur mis à jour avec succès.',
        'deleted'               => 'Appareil analyseur supprimé.',
        'status_changed'        => 'Analyseur :name :status.',
        'cannot_delete_processing' => 'Impossible de supprimer l\'analyseur avec des messages en cours de traitement.',
        'message_requeued'      => 'Message #:id mis en file d\'attente pour retraitement.',
        'cannot_reprocess'      => 'Seuls les messages échoués ou reçus peuvent être retraités.',
        'mapping_added'         => 'Mappage de test ajouté.',
        'mapping_updated'       => 'Mappage de test mis à jour.',
        'mapping_removed'       => 'Mappage de test supprimé.',
    ],

    /* ------------------------------------------------------------------ */
    /* Appointments                                                         */
    /* ------------------------------------------------------------------ */
    'appointments' => [
        'created'           => 'Rendez-vous programmé avec succès.',
        'updated'           => 'Rendez-vous mis à jour avec succès.',
        'cancelled'         => 'Rendez-vous annulé.',
        'no_show'           => 'Rendez-vous marqué comme absence.',
        'status_changed'    => 'Statut du rendez-vous changé en :status.',
        'checked_in'        => 'Patient enregistré et visite créée avec succès.',
        'doctor_conflict'   => 'Le médecin sélectionné a un rendez-vous en conflit à cette heure.',
    ],

    /* ------------------------------------------------------------------ */
    /* Attendance                                                           */
    /* ------------------------------------------------------------------ */
    'attendance' => [
        'recorded' => 'Présence enregistrée avec succès.',
    ],

    /* ------------------------------------------------------------------ */
    /* Blood bank                                                           */
    /* ------------------------------------------------------------------ */
    'blood_bank' => [
        'crossmatch_verified'      => 'Épreuve de compatibilité vérifiée.',
        'donation_recorded'        => 'Don enregistré et unité mise en quarantaine en attente de dépistage.',
        'screening_updated'        => 'Dépistage du don mis à jour.',
        'screening_result_saved'   => 'Résultat du test de dépistage enregistré.',
        'screening_verified'       => 'Test de dépistage vérifié.',
        'donor_registered'         => 'Donneur de sang enregistré.',
        'donor_eligibility_saved'  => 'Décision d\'éligibilité du donneur enregistrée.',
        'donor_questionnaire_saved'=> 'Questionnaire du donneur enregistré.',
        'physical_assessment_saved'=> 'Évaluation physique enregistrée.',
        'unit_issued'              => 'Unité de sang délivrée.',
        'transfusion_outcome'      => 'Résultat de transfusion enregistré.',
        'transfusion_reaction'     => 'Réaction transfusionnelle enregistrée.',
        'request_created'          => 'Demande de sang et détails du receveur créés.',
        'request_approved'         => 'Demande de sang approuvée.',
        'recipient_updated'        => 'Détails du receveur mis à jour.',
        'location_added'           => 'Emplacement de stockage ajouté.',
        'location_updated'         => 'Emplacement de stockage mis à jour.',
        'location_status_updated'  => 'Emplacement de stockage :status.',
        'unit_discarded'           => 'Unité de sang éliminée.',
        'cannot_discard_issued'    => 'Les unités délivrées ou transfusées ne peuvent pas être éliminées.',
    ],

    /* ------------------------------------------------------------------ */
    /* Billing (invoices, payments, credit notes, sponsors, receivables)   */
    /* ------------------------------------------------------------------ */
    'invoices' => [
        'created'            => 'Facture :number créée avec succès.',
        'updated'            => 'Facture :number mise à jour.',
        'cancelled'          => 'Facture :number annulée.',
        'cannot_edit'        => 'Cette facture ne peut plus être modifiée.',
        'cannot_cancel'      => 'Impossible d\'annuler une facture entièrement payée.',
        'item_not_belong'    => 'L\'article n\'appartient pas à cette facture.',
        'discount_applied'   => 'Remise appliquée avec succès.',
        'discount_removed'   => 'Remise supprimée avec succès.',
        'manual_discount_note' => 'Les remises manuelles doivent être appliquées aux articles de facture avec une raison après la création de la facture.',
    ],

    'payments' => [
        'recorded'           => 'Paiement :number de :amount enregistré avec succès.',
        'reversed'           => 'Paiement :number contrepassé (contrepassation :reversal).',
        'cannot_record'      => 'Impossible d\'enregistrer un paiement sur cette facture.',
        'exceeds_balance'    => 'Le montant du paiement dépasse le solde impayé de :balance',
        'open_shift_required'=> 'Ouvrez un poste de caisse avant d\'accepter des paiements en espèces.',
    ],

    'billing' => [
        'credit_note_issued'     => ':type :number émis.',
        'credit_note_cancelled'  => 'Note de crédit :number annulée.',
        'receivable_reallocated' => 'Responsabilité du payeur réallouée avec succès.',
        'sponsor_created'        => 'Sponsor :name créé.',
        'sponsor_updated'        => 'Sponsor :name mis à jour.',
        'sponsor_toggled'        => 'Sponsor :name :status.',
    ],

    /* ------------------------------------------------------------------ */
    /* Cashier                                                              */
    /* ------------------------------------------------------------------ */
    'cashier' => [
        'shift_opened'  => 'Poste ouvert avec succès.',
        'shift_closed'  => 'Poste clôturé. Écart calculé.',
        'shift_verified'=> 'Poste vérifié.',
    ],

    /* ------------------------------------------------------------------ */
    /* Claims                                                               */
    /* ------------------------------------------------------------------ */
    'claims' => [
        'already_exists'       => 'Une demande de remboursement existe déjà pour cette facture.',
        'ready_for_review'     => 'La demande d\'assurance est prête pour examen.',
        'no_invoice'           => 'Cette visite n\'a pas de facture pour préparer une demande.',
        'prepared_from_visit'  => 'Demande d\'assurance préparée à partir de la visite.',
        'created'              => 'Demande de remboursement créée avec succès.',
        'validation_passed'    => 'Validation de la demande réussie.',
        'validation_issues'    => 'La validation de la demande présente des problèmes.',
        'ready_for_submission' => 'Demande marquée prête pour soumission.',
        'submitted'            => 'Demande soumise.',
        'cannot_review_status' => 'Cette demande ne peut pas être examinée dans son statut actuel.',
        'review_completed'     => 'Examen de la demande terminé.',
        'marked_paid'          => 'Demande marquée comme payée.',
        'payment_recorded'     => 'Paiement de la demande enregistré séparément des paiements de facture patient.',
        'appealed'             => 'La demande a été contestée et envoyée pour réexamen.',
        'item_added'           => 'Article ajouté à la demande.',
        'item_removed'         => 'Article retiré de la demande.',
        'cannot_modify'        => 'Impossible de modifier les articles de cette demande.',
        'field_updated'        => ':label mis à jour.',
        'items_pending'        => 'Veuillez vérifier tous les articles. :count article(s) encore en attente.',
    ],

    /* ------------------------------------------------------------------ */
    /* Complaints catalogue                                                 */
    /* ------------------------------------------------------------------ */
    'complaints_catalogue' => [
        'added'          => 'Plainte ajoutée au catalogue.',
        'updated'        => 'Entrée du catalogue de plaintes mise à jour.',
        'status_updated' => 'Statut du catalogue de plaintes mis à jour.',
    ],

    /* ------------------------------------------------------------------ */
    /* Consultations                                                        */
    /* ------------------------------------------------------------------ */
    'consultations' => [
        'started'                   => 'Consultation démarrée.',
        'route_queued'              => 'Session de consultation mise en file d\'attente.',
        'route_activated'           => 'Session de consultation activée.',
        'route_activated_queued'    => 'Itinéraire de consultation activé et mis en file d\'attente.',
        'route_completed'           => 'Session de consultation terminée.',
        'route_cancelled'           => 'Session de consultation annulée.',
        'followup_saved'            => 'Prochain rendez-vous / suivi enregistré.',
        'followup_updated'          => 'Prochain rendez-vous / suivi mis à jour.',
        'followup_cancelled'        => 'Prochain rendez-vous / suivi annulé.',
        'next_patient_opened'       => 'Patient suivant ouvert.',
        'completed_next_opened'     => 'Consultation terminée et patient suivant ouvert.',
        'complaint_added'           => 'Plainte ajoutée.',
        'complaint_updated'         => 'Plainte mise à jour.',
        'complaint_removed'         => 'Plainte supprimée.',
        'hopc_added'                => 'Histoire de la plainte principale ajoutée.',
        'hopc_updated'              => 'Histoire de la plainte principale mise à jour.',
        'hopc_removed'              => 'Histoire de la plainte principale supprimée.',
        'examination_added'         => 'Résultats d\'examen ajoutés.',
        'examination_updated'       => 'Résultats d\'examen mis à jour.',
        'examination_removed'       => 'Résultats d\'examen supprimés.',
        'diagnosis_added'           => 'Diagnostic ajouté.',
        'diagnosis_updated'         => 'Diagnostic mis à jour.',
        'diagnosis_removed'         => 'Diagnostic supprimé.',
        'primary_diagnosis_set'     => 'Diagnostic principal défini.',
        'treatment_added'           => 'Traitement ajouté.',
        'treatment_updated'         => 'Traitement mis à jour.',
        'treatment_removed'         => 'Traitement supprimé.',
        'prescription_created'      => 'Ordonnance :number créée et envoyée à la pharmacie.',
        'prescription_updated'      => 'Ordonnance mise à jour.',
        'prescription_deleted'      => 'Ordonnance supprimée.',
        'prescription_cannot_delete'=> 'Impossible de supprimer une ordonnance délivrée ou annulée.',
        'investigation_removed'                => 'Investigation supprimée.',
        'investigation_updated'               => 'Investigation mise à jour.',
        'investigations_added'                => ':count investigation(s) ajoutée(s).',
        'investigations_added_with_request'   => ':count investigation(s) ajoutée(s) — demande :number envoyée au département d\'investigation.',
        'lab_request_sent'          => 'Demande d\'investigation :number envoyée à :department.',
        'lab_request_updated'       => 'Demande d\'investigation mise à jour.',
        'procedure_submitted'       => 'Demande de procédure soumise (:number).',
        'procedure_updated'         => 'Demande de procédure mise à jour.',
        'visit_transitioned'        => 'Visite déplacée vers :status.',
        'referred_activated'        => 'Patient envoyé à :department et session activée.',
        'referred_queued'           => 'Patient mis en file pour :department.',
        'sent_to_investigation'     => 'Patient envoyé à :department pour investigation.',
    ],

    /* ------------------------------------------------------------------ */
    /* Consultation tasks                                                   */
    /* ------------------------------------------------------------------ */
    'consultation_tasks' => [
        'created' => 'Tâche créée.',
        'updated' => 'Tâche mise à jour.',
        'deleted' => 'Tâche supprimée.',
        'toggled' => 'Statut de la tâche modifié.',
    ],

    /* ------------------------------------------------------------------ */
    /* Counter sales                                                        */
    /* ------------------------------------------------------------------ */
    'counter_sales' => [
        'created' => 'Vente au comptoir créée. Collectez le paiement pour finaliser.',
    ],

    /* ------------------------------------------------------------------ */
    /* Departments                                                          */
    /* ------------------------------------------------------------------ */
    'departments' => [
        'created'       => 'Service créé avec succès.',
        'updated'       => 'Service mis à jour avec succès.',
        'deleted'       => 'Service supprimé avec succès.',
        'cannot_delete' => 'Impossible de supprimer le service avec des utilisateurs assignés.',
    ],

    /* ------------------------------------------------------------------ */
    /* Designations                                                         */
    /* ------------------------------------------------------------------ */
    'designations' => [
        'created'       => 'Désignation créée avec succès.',
        'updated'       => 'Désignation mise à jour avec succès.',
        'deleted'       => 'Désignation supprimée avec succès.',
        'cannot_delete' => 'Impossible de supprimer la désignation avec des utilisateurs assignés.',
    ],

    /* ------------------------------------------------------------------ */
    /* Drugs / pharmacy catalogue                                           */
    /* ------------------------------------------------------------------ */
    'drugs' => [
        'category_created'        => 'Catégorie créée avec succès.',
        'category_updated'        => 'Catégorie mise à jour avec succès.',
        'category_deleted'        => 'Catégorie supprimée avec succès.',
        'category_cannot_delete'  => 'Impossible de supprimer la catégorie avec des médicaments existants. Retirez ou réassignez les médicaments d\'abord.',
        'created'                 => 'Médicament créé avec succès.',
        'updated'                 => 'Médicament mis à jour avec succès.',
        'status_toggled'          => 'Statut du médicament modifié.',
        'toggled'                 => 'Statut du médicament modifié.',
    ],

    /* ------------------------------------------------------------------ */
    /* Emergency                                                            */
    /* ------------------------------------------------------------------ */
    'emergency' => [
        'case_created'        => 'Cas d\'urgence :number créé.',
        'case_updated'        => 'Cas d\'urgence mis à jour.',
        'bay_assigned'        => 'Baie d\'urgence assignée.',
        'bay_created'         => 'Baie d\'urgence créée.',
        'ward_bed_updated'    => 'Unité / lit mis à jour.',
        'bed_updated'         => 'Unité / lit mis à jour.',
        'billing_service_added' => 'Service facturable d\'urgence ajouté.',
        'billable_added'      => 'Service facturable d\'urgence ajouté.',
        'consumable_recorded' => 'Consommable d\'urgence enregistré.',
        'contact_added'       => 'Contact d\'urgence ajouté.',
        'contact_updated'     => 'Contact d\'urgence mis à jour.',
        'contact_removed'     => 'Contact d\'urgence supprimé.',
        'disposition_recorded'=> 'Disposition d\'urgence enregistrée.',
        'case_marked_for_admission' => 'Cas d\'urgence marqué pour admission. Completez le placement en unité.',
        'admit_disposition'   => 'Cas d\'urgence marqué pour admission. Completez le placement en unité.',
        'investigation_requested' => 'Examen(s) d\'urgence demandé(s).',
        'medication_ordered'  => 'Médicament d\'urgence prescrit et programme MAR mis à jour.',
        'note_added'          => 'Note d\'urgence ajoutée.',
        'identity_confirmed'  => 'Identité d\'urgence confirmée et fusionnée sous :number.',
        'patient_registered'  => 'Patient d\'urgence enregistré et lié sous :number.',
        'procedure_requested' => 'Procédure(s) d\'urgence demandée(s).',
        'task_added'          => 'Tâche de surveillance ajoutée.',
        'monitoring_added'    => 'Tâche de surveillance ajoutée.',
        'task_updated'        => 'Tâche mise à jour.',
        'triage_recorded'     => 'Triage d\'urgence enregistré.',
        'vitals_recorded'     => 'Constantes d\'urgence enregistrées.',
    ],

    /* ------------------------------------------------------------------ */
    /* Employees                                                            */
    /* ------------------------------------------------------------------ */
    'employees' => [
        'created' => 'Employé créé avec succès.',
        'updated' => 'Employé mis à jour avec succès.',
    ],

    /* ------------------------------------------------------------------ */
    /* Financial entries                                                    */
    /* ------------------------------------------------------------------ */
    'financial_entries' => [
        'recorded' => 'Entrée :type enregistrée avec succès.',
        'approved' => 'Entrée approuvée.',
        'deleted'  => 'Entrée supprimée.',
    ],

    /* ------------------------------------------------------------------ */
    /* ICD codes                                                            */
    /* ------------------------------------------------------------------ */
    'icd_codes' => [
        'created'        => 'Code CIM-10 ajouté avec succès.',
        'updated'        => 'Code CIM-10 mis à jour avec succès.',
        'deleted'        => 'Code CIM-10 supprimé.',
        'cannot_delete'  => 'Impossible de supprimer : ce code CIM est lié à des diagnostics existants.',
    ],

    /* ------------------------------------------------------------------ */
    /* Insurance                                                            */
    /* ------------------------------------------------------------------ */
    'insurance' => [
        'provider_created'      => 'Prestataire d\'assurance créé. Un niveau Standard a été ajouté — configurez ses limites depuis le menu du prestataire.',
        'provider_updated'      => 'Prestataire d\'assurance mis à jour avec succès.',
        'provider_status'       => 'Statut du prestataire mis à jour.',
        'tier_created'          => 'Niveau créé avec succès.',
        'tier_updated'          => 'Niveau mis à jour.',
        'tier_deleted'          => 'Niveau supprimé.',
        'tier_cannot_delete'    => 'Impossible de supprimer le niveau : des patients y sont actuellement inscrits.',
        'patient_added'         => 'Assurance ajoutée au patient.',
        'patient_updated'       => 'Assurance mise à jour.',
        'patient_removed'       => 'Assurance retirée.',
        'primary_updated'       => 'Assurance principale mise à jour.',
        'cannot_remove_default' => 'Impossible de retirer l\'assurance Cash & Carry par défaut.',
    ],

    /* ------------------------------------------------------------------ */
    /* Insurance providers                                                  */
    /* ------------------------------------------------------------------ */
    'insurance_providers' => [
        'created'        => 'Prestataire d\'assurance créé. Un niveau Standard a été ajouté — configurez ses limites depuis le menu du prestataire.',
        'updated'        => 'Prestataire d\'assurance mis à jour avec succès.',
        'status_updated' => 'Statut du prestataire mis à jour.',
    ],

    /* ------------------------------------------------------------------ */
    /* Insurance tiers                                                      */
    /* ------------------------------------------------------------------ */
    'insurance_tiers' => [
        'created'       => 'Niveau créé avec succès.',
        'updated'       => 'Niveau mis à jour.',
        'deleted'       => 'Niveau supprimé.',
        'cannot_delete' => 'Impossible de supprimer le niveau : des patients y sont actuellement inscrits.',
    ],

    /* ------------------------------------------------------------------ */
    /* Patient insurance                                                    */
    /* ------------------------------------------------------------------ */
    'patient_insurance' => [
        'added'                 => 'Assurance ajoutée au patient.',
        'updated'               => 'Assurance mise à jour.',
        'removed'               => 'Assurance retirée.',
        'primary_updated'       => 'Assurance principale mise à jour.',
        'cannot_remove_default' => 'Impossible de retirer l\'assurance Cash & Carry par défaut.',
    ],

    /* ------------------------------------------------------------------ */
    /* Patient complaints                                                   */
    /* ------------------------------------------------------------------ */
    'patient_complaints' => [
        'recorded' => 'Plainte enregistrée.',
        'updated'  => 'Plainte mise à jour.',
        'removed'  => 'Plainte supprimée.',
    ],

    /* ------------------------------------------------------------------ */
    /* Patient merge                                                        */
    /* ------------------------------------------------------------------ */
    'patient_merge' => [
        'merged'          => 'Dossiers patients fusionnés avec succès.',
        'request_created' => 'Demande de fusion de patients créée.',
    ],

    /* ------------------------------------------------------------------ */
    /* Investigation catalogue                                              */
    /* ------------------------------------------------------------------ */
    'investigation_catalogue' => [
        'consumable_saved'   => 'Consommable enregistré.',
        'consumable_removed' => 'Consommable supprimé.',
    ],

    /* ------------------------------------------------------------------ */
    /* Lab requests / results                                               */
    /* ------------------------------------------------------------------ */
    'lab' => [
        'request_accepted'              => 'Demande d\'investigation acceptée et prête pour la saisie des résultats.',
        'request_cancelled'             => 'Demande d\'investigation annulée.',
        'result_saved'                  => 'Résultat enregistré avec succès.',
        'results_saved'                 => 'Résultats enregistrés avec succès.',
        'result_verified'               => 'Résultat vérifié avec succès.',
        'accept_items_first'            => 'Acceptez et facturez les articles d\'investigation avant de saisir les résultats.',
        'items_accepted'                => ':count article(s) accepté(s) (aucun service facturable).',
        'items_accepted_with_invoice'   => ':count article(s) accepté(s) — facture :number générée.',
    ],

    /* ------------------------------------------------------------------ */
    /* Lab tests                                                            */
    /* ------------------------------------------------------------------ */
    'lab_tests' => [
        'category_created'       => 'Catégorie créée avec succès.',
        'category_updated'       => 'Catégorie mise à jour avec succès.',
        'category_deleted'       => 'Catégorie supprimée avec succès.',
        'category_cannot_delete' => 'Impossible de supprimer la catégorie avec des tests existants. Retirez ou réassignez les tests d\'abord.',
        'created'                => 'Test de laboratoire créé avec succès.',
        'updated'                => 'Test de laboratoire mis à jour avec succès.',
        'status_toggled'         => 'Statut du test de laboratoire modifié.',
        'toggled'                => 'Statut du test de laboratoire modifié.',
    ],

    /* ------------------------------------------------------------------ */
    /* Log retention                                                        */
    /* ------------------------------------------------------------------ */
    'log_retention' => [
        'updated' => 'Remplacements de rétention mis à jour.',
    ],

    /* ------------------------------------------------------------------ */
    /* Medication administration                                            */
    /* ------------------------------------------------------------------ */
    'medication_administration' => [
        'recorded'      => 'Administration de médicament enregistrée.',
        'prn_recorded'  => 'Administration de médicament PRN/SOS enregistrée.',
        'corrected'     => 'Dossier d\'administration corrigé avec journal d\'audit.',
        'order_held'    => 'Ordre médicamenteux suspendu.',
        'order_stopped' => 'Ordre médicamenteux arrêté et doses futures annulées.',
    ],

    /* ------------------------------------------------------------------ */
    /* Leave                                                                */
    /* ------------------------------------------------------------------ */
    'leave' => [
        'submitted' => 'Demande de congé soumise avec succès.',
        'approved'  => 'Demande de congé approuvée.',
        'rejected'  => 'Demande de congé rejetée.',
    ],

    /* ------------------------------------------------------------------ */
    /* Logs                                                                 */
    /* ------------------------------------------------------------------ */
    'logs' => [
        'retention_updated' => 'Remplacements de rétention mis à jour.',
    ],

    /* ------------------------------------------------------------------ */
    /* Medical patterns                                                     */
    /* ------------------------------------------------------------------ */
    'patterns' => [
        'created'                => 'Modèle ":name" créé.',
        'saved_from_consultation'=> 'Modèle ":name" enregistré depuis la consultation.',
        'updated'                => 'Modèle ":name" mis à jour.',
        'toggled'                => 'Modèle ":name" :status.',
        'deleted'                => 'Modèle ":name" supprimé.',
    ],

    /* ------------------------------------------------------------------ */
    /* Pharmacy dispensing                                                  */
    /* ------------------------------------------------------------------ */
    'pharmacy' => [
        'item_dispensed'  => 'Article délivré avec succès.',
        'items_dispensed' => 'Articles délivrés avec succès.',
    ],

    /* ------------------------------------------------------------------ */
    /* Prescriptions                                                        */
    /* ------------------------------------------------------------------ */
    'prescriptions' => [
        'cancelled' => 'Ordonnance :number annulée.',
        'billed'    => ':count article(s) facturé(s). Encaissez le paiement, puis délivrez à la pharmacie.',
    ],

    /* ------------------------------------------------------------------ */
    /* Theatre                                                              */
    /* ------------------------------------------------------------------ */
    'theatre' => [
        'room_created'              => 'Salle de bloc créée.',
        'room_updated'              => 'Salle de bloc mise à jour.',
        'room_status_updated'       => 'Statut de la salle de bloc mis à jour.',
        'room_block_recorded'       => 'Blocage de salle de bloc enregistré.',
        'room_block_removed'        => 'Blocage de salle de bloc supprimé.',
        'procedure_submitted'       => 'Demande de procédure soumise.',
        'procedure_submitted_number'=> 'Demande de procédure soumise (:number).',
        'report_unavailable'        => 'Le rapport complet de procédure n\'est disponible que pour les procédures terminées.',
        'procedure_accepted'        => 'Procédure acceptée.',
        'procedure_rejected'        => 'Procédure rejetée.',
        'procedure_billed'          => 'Procédure facturée sur la facture de visite.',
        'procedure_scheduled'       => 'Procédure planifiée.',
        'procedure_rescheduled'     => 'Procédure replanifiée.',
        'preop_saved'               => 'Signes vitaux et liste de contrôle préopératoires enregistrés.',
        'anaesthesia_saved'         => 'Note d\'anesthésie enregistrée.',
        'surgery_started'           => 'Chirurgie démarrée.',
        'operative_note_saved'      => 'Note opératoire enregistrée.',
        'surgery_done'              => 'Chirurgie marquée comme terminée.',
        'postop_saved'              => 'Note post-opératoire enregistrée.',
        'procedure_completed'       => 'Procédure terminée.',
        'procedure_cancelled'       => 'Procédure annulée.',
    ],

    /* ------------------------------------------------------------------ */
    /* MAR / Medication administration                                     */
    /* ------------------------------------------------------------------ */
    'mar' => [
        'recorded'         => 'Administration de médicament enregistrée.',
        'prn_recorded'     => 'Administration de médicament PRN/SOS enregistrée.',
        'corrected'        => 'Dossier d\'administration corrigé avec journal d\'audit.',
        'order_held'       => 'Ordre médicamenteux suspendu.',
        'order_stopped'    => 'Ordre médicamenteux arrêté et doses futures annulées.',
    ],

    /* ------------------------------------------------------------------ */
    /* Modules                                                              */
    /* ------------------------------------------------------------------ */
    'modules' => [
        'cache_flushed'             => 'Cache des modules vidé.',
        'cannot_disable'            => 'Impossible de désactiver le module principal \':name\'.',
        'cannot_disable_core'       => 'Impossible de désactiver le module principal \':name\'.',
        'disable_dependents_first'  => 'Désactivez d\'abord les modules dépendants : :modules',
        'disabled'                  => 'Module \':name\' désactivé.',
        'enable_parent_first'       => 'Activez d\'abord le module parent \':name\'.',
        'enabled'                   => 'Module \':name\' activé.',
        'status_changed'            => ':message',
    ],

    /* ------------------------------------------------------------------ */
    /* Notifications                                                        */
    /* ------------------------------------------------------------------ */
    'notifications' => [
        'broadcast_sent'   => 'Diffusion envoyée à :count utilisateur(s).',
        'preferences_saved'=> 'Préférences de notification enregistrées.',
    ],

    /* ------------------------------------------------------------------ */
    /* Notification preferences                                             */
    /* ------------------------------------------------------------------ */
    'notification_preferences' => [
        'saved' => 'Préférences de notification enregistrées.',
    ],

    /* ------------------------------------------------------------------ */
    /* Patients                                                             */
    /* ------------------------------------------------------------------ */
    'patients' => [
        'registered'            => 'Patient :number enregistré avec succès.',
        'updated'               => 'Patient mis à jour avec succès.',
        'status_changed'        => 'Statut du patient changé en :status.',
        'deceased'              => ':name a été marqué comme décédé.',
        'already_deceased'      => 'Le patient est déjà marqué comme décédé.',
        'cannot_change_deceased_status' => 'Impossible de changer le statut d\'un patient décédé.',
        'cannot_change_deceased'=> 'Impossible de changer le statut d\'un patient décédé.',
        'marked_deceased'       => ':name a été marqué comme décédé.',
        'complaint_recorded'    => 'Plainte enregistrée.',
        'complaint_updated'     => 'Plainte mise à jour.',
        'complaint_removed'     => 'Plainte supprimée.',
        'merged'                => 'Dossiers patients fusionnés avec succès.',
        'merge_requested'       => 'Demande de fusion de patients créée.',
    ],

    /* ------------------------------------------------------------------ */
    /* Payroll                                                              */
    /* ------------------------------------------------------------------ */
    'payroll' => [
        'processed'  => 'Paie traitée pour :count employé(s).',
        'approved'   => ':count fiche(s) de paie approuvée(s).',
        'paid'       => ':count fiche(s) de paie marquée(s) comme payée(s).',
        'marked_paid'=> ':count fiche(s) de paie marquée(s) comme payée(s).',
    ],

    /* ------------------------------------------------------------------ */
    /* Permissions                                                          */
    /* ------------------------------------------------------------------ */
    'permissions' => [
        'audit_refreshed' => 'Audit des permissions actualisé.',
        'direct_updated'  => 'Permissions directes mises à jour.',
    ],

    /* ------------------------------------------------------------------ */
    /* Procedure catalogue                                                  */
    /* ------------------------------------------------------------------ */
    'procedure_catalogue' => [
        'section_added'      => 'Section ajoutée.',
        'section_updated'    => 'Section mise à jour.',
        'section_removed'    => 'Section supprimée.',
        'field_added'        => 'Champ ajouté.',
        'field_updated'      => 'Champ mis à jour.',
        'field_removed'      => 'Champ supprimé.',
        'consumable_saved'   => 'Consommable enregistré.',
        'consumable_removed' => 'Consommable supprimé.',
    ],

    /* ------------------------------------------------------------------ */
    /* Procedures                                                           */
    /* ------------------------------------------------------------------ */
    'procedures' => [
        'created'          => 'Procédure ajoutée avec succès.',
        'added'            => 'Procédure ajoutée avec succès.',
        'updated'          => 'Procédure mise à jour avec succès.',
        'cancelled'        => 'Procédure annulée.',
        'completed'        => 'Procédure terminée.',
        'started'          => 'Procédure démarrée.',
        'scheduled'        => 'Procédure planifiée.',
        'toggled'          => 'Procédure :name :status.',
        'status_changed'   => 'Procédure :name :status.',
        'consent_required' => 'Cette procédure nécessite un consentement signé avant la planification.',
    ],

    /* ------------------------------------------------------------------ */
    /* Products                                                             */
    /* ------------------------------------------------------------------ */
    'products' => [
        'created'        => 'Produit créé.',
        'updated'        => 'Produit mis à jour.',
        'toggled'        => 'Statut du produit modifié.',
        'status_toggled' => 'Statut du produit modifié.',
    ],

    /* ------------------------------------------------------------------ */
    /* Product pricing                                                      */
    /* ------------------------------------------------------------------ */
    'product_pricing' => [
        'base_updated'           => 'Tarification de base mise à jour.',
        'prices_updated'         => 'Tarifs pour ":name" mis à jour.',
        'bulk_updated'           => 'Tarifs pour ":name" mis à jour.',
        'type_price_added'       => 'Tarif par type d\'assurance ajouté.',
        'type_price_updated'     => 'Tarif par type d\'assurance mis à jour.',
        'type_price_removed'     => 'Tarif par type d\'assurance supprimé.',
        'insurance_type_added'   => 'Tarif par type d\'assurance ajouté.',
        'insurance_type_updated' => 'Tarif par type d\'assurance mis à jour.',
        'insurance_type_removed' => 'Tarif par type d\'assurance supprimé.',
        'provider_price_added'   => 'Tarif spécifique au prestataire ajouté.',
        'provider_price_updated' => 'Tarif du prestataire mis à jour.',
        'provider_price_removed' => 'Tarif du prestataire supprimé.',
        'provider_added'         => 'Tarif spécifique au prestataire ajouté.',
        'provider_updated'       => 'Tarif du prestataire mis à jour.',
        'provider_removed'       => 'Tarif du prestataire supprimé.',
        'price_removed'          => 'Tarif supprimé.',
    ],

    /* ------------------------------------------------------------------ */
    /* Stock                                                                */
    /* ------------------------------------------------------------------ */
    'stock' => [
        'received'               => 'Stock reçu.',
        'adjusted'               => 'Stock ajusté.',
        'transferred'            => 'Transfert enregistré.',
        'returned'               => 'Retour enregistré.',
        'adjustment_batch'       => 'Ajustement de stock :batch enregistré.',
        'return_batch'           => 'Retour de stock :batch enregistré.',
        'transfer_batch'         => 'Transfert de stock :batch enregistré.',
        'adjustment_recorded'    => 'Ajustement de stock :number enregistré.',
        'return_recorded'        => 'Retour de stock :number enregistré.',
        'transfer_recorded'      => 'Transfert de stock :number enregistré.',
        'location_created'       => 'Emplacement de stock créé.',
        'location_updated'       => 'Emplacement de stock mis à jour.',
        'location_status'        => 'Statut modifié.',
        'cannot_deactivate_main' => 'Le dépôt principal ne peut pas être désactivé.',
        'cannot_edit_main'       => 'Le dépôt principal est un emplacement système protégé et ne peut pas être modifié ici.',
        'requisition_submitted'  => 'Demande de stock soumise.',
        'requisition_approved'   => 'Demande de stock approuvée.',
        'requisition_cancelled'  => 'Demande de stock annulée.',
        'requisition_issued'     => 'Stock émis depuis le dépôt principal. Le stock du service sera mis à jour après accusé de réception.',
        'requisition_acknowledged' => 'Stock du service accusé de réception et mis à jour.',
    ],

    /* ------------------------------------------------------------------ */
    /* Product stock                                                        */
    /* ------------------------------------------------------------------ */
    'product_stock' => [
        'received'    => 'Stock reçu.',
        'adjusted'    => 'Stock ajusté.',
        'transferred' => 'Transfert enregistré.',
        'returned'    => 'Retour enregistré.',
    ],

    /* ------------------------------------------------------------------ */
    /* Stock locations                                                      */
    /* ------------------------------------------------------------------ */
    'stock_locations' => [
        'created'                => 'Emplacement de stock créé.',
        'updated'                => 'Emplacement de stock mis à jour.',
        'toggled'                => 'Statut modifié.',
        'cannot_edit_main'       => 'Le dépôt principal est un emplacement système protégé et ne peut pas être modifié ici.',
        'cannot_deactivate_main' => 'Le dépôt principal ne peut pas être désactivé.',
    ],

    /* ------------------------------------------------------------------ */
    /* Stock requisitions                                                   */
    /* ------------------------------------------------------------------ */
    'stock_requisitions' => [
        'submitted'    => 'Demande de stock soumise.',
        'approved'     => 'Demande de stock approuvée.',
        'issued'       => 'Stock émis depuis le dépôt principal. Le stock du service sera mis à jour après accusé de réception.',
        'acknowledged' => 'Stock du service accusé de réception et mis à jour.',
        'cancelled'    => 'Demande de stock annulée.',
    ],

    /* ------------------------------------------------------------------ */
    /* Profile                                                              */
    /* ------------------------------------------------------------------ */
    'profile' => [
        'updated'          => 'Profil mis à jour avec succès.',
        'password_updated' => 'Mot de passe mis à jour avec succès.',
    ],

    /* ------------------------------------------------------------------ */
    /* Purchase orders                                                      */
    /* ------------------------------------------------------------------ */
    'purchase_orders' => [
        'created'        => 'Bon de commande créé avec succès.',
        'submitted'      => 'Bon de commande soumis pour approbation.',
        'approved'       => 'Bon de commande approuvé.',
        'items_received' => 'Articles reçus avec succès.',
        'received'       => 'Articles reçus avec succès.',
        'cancelled'      => 'Bon de commande annulé.',
        'item_added'     => 'Article ajouté au bon de commande.',
        'item_removed'   => 'Article retiré du bon de commande.',
        'cannot_modify'  => 'Impossible de modifier les articles de ce bon de commande.',
    ],

    /* ------------------------------------------------------------------ */
    /* Purchase returns                                                     */
    /* ------------------------------------------------------------------ */
    'purchase_returns' => [
        'created'   => 'Retour fournisseur créé.',
        'approved'  => 'Retour fournisseur approuvé.',
        'cancelled' => 'Retour fournisseur annulé.',
        'posted'    => 'Retour fournisseur enregistré dans le stock et le grand livre fournisseur.',
    ],

    /* ------------------------------------------------------------------ */
    /* Queue                                                                */
    /* ------------------------------------------------------------------ */
    'queue' => [
        'now_serving'  => 'Appel du numéro #:number — :name',
        'requeued'     => 'Patient remis en file sous le #:number.',
        're_queued'    => 'Patient remis en file sous le #:number.',
        'completed'    => 'File #:number marquée comme terminée.',
        'skipped'      => 'File #:number ignorée.',
        'none_waiting' => 'Aucun patient en attente dans la file de ce service.',
    ],

    /* ------------------------------------------------------------------ */
    /* Roles                                                                */
    /* ------------------------------------------------------------------ */
    'roles' => [
        'created'                 => 'Rôle créé avec succès.',
        'updated'                 => 'Rôle mis à jour avec succès.',
        'deleted'                 => 'Rôle supprimé avec succès.',
        'permissions_updated'     => 'Permissions mises à jour avec succès.',
        'cannot_delete_assigned'  => 'Impossible de supprimer le rôle avec des utilisateurs assignés.',
        'cannot_delete_users'     => 'Impossible de supprimer le rôle avec des utilisateurs assignés.',
        'cannot_delete_system'    => 'Impossible de supprimer les rôles système.',
        'cannot_rename_system'    => 'Impossible de renommer les rôles système.',
    ],

    /* ------------------------------------------------------------------ */
    /* Service catalogue                                                    */
    /* ------------------------------------------------------------------ */
    'service_catalogue' => [
        'added'          => 'Service ajouté avec succès.',
        'updated'        => 'Service mis à jour avec succès.',
        'status_changed' => 'Service :name :status.',
        'price_removed'  => 'Entrée de tarif supprimée.',
        'message'        => ':message',
    ],

    /* ------------------------------------------------------------------ */
    /* Service catalog (canonical key used by ServiceCatalogController)    */
    /* ------------------------------------------------------------------ */
    'service_catalog' => [
        'created'        => 'Service ajouté avec succès.',
        'updated'        => 'Service mis à jour avec succès.',
        'toggled'        => 'Service :name :status.',
        'prices_updated' => 'Tarifs pour ":name" mis à jour.',
        'price_removed'  => 'Entrée de tarif supprimée.',
    ],

    /* ------------------------------------------------------------------ */
    /* Service renderings                                                   */
    /* ------------------------------------------------------------------ */
    'service_renderings' => [
        'billed'        => '":name" facturé à :visit. Une tâche de rendu a été créée pour le service.',
        'started'       => 'Rendu de service démarré.',
        'rendered'      => 'Service marqué comme rendu.',
        'not_rendered'  => 'Service marqué comme non rendu.',
        'cancelled'     => 'Rendu de service annulé.',
        'notes_updated' => 'Notes de rendu mises à jour.',
    ],

    /* ------------------------------------------------------------------ */
    /* Service rendering (canonical key used by ServiceRenderingController) */
    /* ------------------------------------------------------------------ */
    'service_rendering' => [
        'billed'        => '":service" facturé à :visit. Une tâche de rendu a été créée pour le service.',
        'started'       => 'Rendu de service démarré.',
        'rendered'      => 'Service marqué comme rendu.',
        'not_rendered'  => 'Service marqué comme non rendu.',
        'cancelled'     => 'Rendu de service annulé.',
        'notes_updated' => 'Notes de rendu mises à jour.',
    ],

    /* ------------------------------------------------------------------ */
    /* Settings                                                             */
    /* ------------------------------------------------------------------ */
    'settings' => [
        'organization_updated'   => 'Paramètres de l\'organisation mis à jour avec succès.',
        'organisation_updated'   => 'Paramètres de l\'organisation mis à jour avec succès.',
        'invoice_updated'        => 'Paramètres de facturation mis à jour avec succès.',
        'payment_methods_updated'=> 'Paramètres de mode de paiement mis à jour avec succès.',
        'payment_updated'        => 'Paramètres de mode de paiement mis à jour avec succès.',
        'ward_updated'           => 'Paramètres des unités et admissions mis à jour avec succès.',
    ],

    /* ------------------------------------------------------------------ */
    /* Specialties                                                          */
    /* ------------------------------------------------------------------ */
    'specialties' => [
        'created'        => 'Spécialité créée avec succès.',
        'updated'        => 'Spécialité mise à jour avec succès.',
        'toggled'        => 'Spécialité :name :status.',
        'status_changed' => 'Spécialité :name :status.',
    ],

    /* ------------------------------------------------------------------ */
    /* Suppliers                                                            */
    /* ------------------------------------------------------------------ */
    'suppliers' => [
        'created'               => 'Fournisseur créé avec succès.',
        'updated'               => 'Fournisseur mis à jour avec succès.',
        'status_updated'        => 'Statut du fournisseur mis à jour.',
        'ledger_entry_recorded' => 'Entrée de grand livre enregistrée.',
        'ledger_recorded'       => 'Entrée de grand livre enregistrée.',
    ],

    /* ------------------------------------------------------------------ */
    /* Triage                                                               */
    /* ------------------------------------------------------------------ */
    'triage' => [
        'not_awaiting'  => 'Cette visite n\'est pas en attente de triage.',
        'not_in_triage' => 'Cette visite n\'est pas en statut TRIAGE.',
        'completed'     => 'Triage terminé. Visite déplacée vers :status.',
    ],

    /* ------------------------------------------------------------------ */
    /* User permissions                                                     */
    /* ------------------------------------------------------------------ */
    'user_permissions' => [
        'updated' => 'Permissions directes mises à jour.',
    ],

    /* ------------------------------------------------------------------ */
    /* Users                                                                */
    /* ------------------------------------------------------------------ */
    'users' => [
        'created'        => 'Utilisateur créé avec succès.',
        'updated'        => 'Utilisateur mis à jour avec succès.',
        'status_updated' => 'Statut de l\'utilisateur mis à jour.',
    ],

    /* ------------------------------------------------------------------ */
    /* Visits                                                               */
    /* ------------------------------------------------------------------ */
    'visits' => [
        'scheduled'           => 'Visite :number planifiée avec succès.',
        'created_waiting'     => 'Visite :number créée et patient ajouté à la file de triage.',
        'created_no_triage'   => 'Visite :number créée. Aucun service de consultation sélectionné — triage ignoré.',
        'updated'             => 'Visite :number mise à jour.',
        'create_failed'       => 'Échec de la création de la visite. Veuillez réessayer.',
        'failed_create'       => 'Échec de la création de la visite. Veuillez réessayer.',
        'update_failed'       => 'Échec de la mise à jour de la visite : :error',
        'failed_update'       => 'Échec de la mise à jour de la visite : :error',
        'sent_to_department'  => 'Patient envoyé au service et entrée de file créée.',
        'status_updated'      => 'Statut de la visite mis à jour à :status.',
        'cannot_transition'   => 'Impossible de passer de :from à :to.',
        'invalid_transition'  => 'Impossible de passer de :from à :to.',
        'insurance_changed'   => 'Assurance de la visite changée en :provider. Les éléments facturés existants n\'ont pas été modifiés.',
        'insurance_already_set' => 'L\'assurance de la visite est déjà définie sur l\'option sélectionnée.',
        'insurance_unchanged' => 'L\'assurance de la visite est déjà définie sur l\'option sélectionnée.',
    ],

    /* ------------------------------------------------------------------ */
    /* Vitals                                                               */
    /* ------------------------------------------------------------------ */
    'vitals' => [
        'recorded_triage'     => 'Constantes enregistrées pour :name. Veuillez diriger le patient vers un service de consultation.',
        'recorded_with_queue' => 'Constantes enregistrées pour :name. Veuillez diriger le patient vers un service de consultation.',
        'recorded'            => 'Constantes enregistrées pour :name.',
        'assigned_consultation' => 'Patient assigné à la file de consultation.',
        'assigned_to_queue'   => 'Patient assigné à la file de consultation.',
        'priority_updated'    => 'Priorité mise à jour à :priority.',
    ],

    /* ------------------------------------------------------------------ */
    /* Wards                                                                */
    /* ------------------------------------------------------------------ */
    'wards' => [
        'created'        => 'Unité \':name\' créée avec succès.',
        'updated'        => 'Unité \':name\' mise à jour avec succès.',
        'toggled'        => 'Unité \':name\' :status.',
        'status_changed' => 'Unité \':name\' :status.',
        'bed_created'    => 'Lit créé avec succès.',
        'bed_updated'    => 'Lit mis à jour avec succès.',
    ],

];
