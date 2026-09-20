<?php

declare(strict_types=1);

/**
 * Chaines d'interface en francais (langue par defaut).
 *
 * Ne contient que les textes decides par le code (validation, messages flash,
 * erreurs) : le contenu des pages et des courriels vit dans les gabarits
 * *.php (francais) et *.en.php (anglais).
 */
return [
    // ---------------------------------------------------------------- champs
    'field.last_name' => 'Nom',
    'field.first_name' => 'Prénom',
    'field.email' => 'Adresse e-mail',
    'field.phone' => 'Téléphone',
    'field.civility' => 'Civilité',
    'field.birth_date' => 'Date de naissance',
    'field.organisation' => 'Organisation',
    'field.job_title' => 'Fonction',
    'field.street' => 'Rue et numéro',
    'field.postal_code' => 'Code postal',
    'field.city' => 'Localité',
    'field.vat_number' => 'Numéro de TVA',
    'field.diet' => 'Régime alimentaire',
    'field.diet_comment' => 'Précision sur le régime',
    'field.allergies_comment' => 'Détail des allergies',
    'field.logistics_comment' => 'Remarques',
    'field.name' => 'Nom',
    'field.subject' => 'Objet',
    'field.message' => 'Message',

    // ----------------------------------------------------------- validation
    'validation.required' => 'Le champ « :label » est obligatoire.',
    'validation.min_length' => 'Le champ « :label » doit comporter au moins :min caractères.',
    'validation.max_length' => 'Le champ « :label » ne peut pas dépasser :max caractères.',
    'validation.email' => 'Le champ « :label » doit contenir une adresse e-mail valide.',
    'validation.phone' => 'Le champ « :label » doit contenir un numéro de téléphone valide.',
    'validation.in' => 'La valeur choisie pour « :label » n\'est pas valide.',
    'validation.date' => 'Le champ « :label » doit contenir une date valide.',
    'validation.vat_number' => 'Le champ « :label » doit contenir un numéro de TVA valide (ex. BE0123456789).',
    'validation.consent_health' => 'Merci de confirmer votre accord pour la transmission de vos allergies au traiteur.',
    'validation.consent_gdpr' => 'Vous devez accepter le traitement de vos données pour vous inscrire.',
    'validation.consent_terms' => 'Vous devez accepter les conditions de participation et d\'annulation.',
    'validation.form_errors' => 'Le formulaire comporte des erreurs. Merci de les corriger.',
    'validation.form_errors_short' => 'Merci de corriger les erreurs du formulaire.',
    'validation.missing_fields' => 'Merci de compléter les informations manquantes.',
    'validation.confirm_missing' => 'Merci de cocher les cases requises pour finaliser votre inscription.',

    // ---------------------------------------------------------------- flash
    'flash.registration_closed' => 'Les inscriptions à cette formation sont closes.',
    'flash.registration_exists' => 'Une inscription existe déjà pour cette adresse e-mail.',
    'flash.registration_exists_full' => 'Une inscription est déjà enregistrée avec cette adresse. Consultez le courriel de confirmation reçu.',
    'flash.registration_success' => 'Votre inscription est enregistrée. Un courriel de confirmation vous a été envoyé.',
    'flash.registration_waitlist' => 'La formation est complète : votre demande est enregistrée en liste d\'attente. Nous vous préviendrons dès qu\'une place se libère.',
    'flash.registration_already' => 'Cette inscription est déjà enregistrée.',
    'flash.contact_success' => 'Votre message a bien été transmis.',
    'flash.contact_success_full' => 'Votre message a bien été transmis. Nous vous répondrons dans les meilleurs délais.',
    'flash.login_required' => 'Veuillez vous connecter pour accéder à cette page.',

    // ------------------------------------------------------------- notfound
    'notfound.training' => 'Aucune formation n\'est actuellement publiée.',
    'notfound.news_article' => 'Cette actualité n\'existe pas ou n\'est plus publiée.',
    'notfound.qr_unavailable' => 'Aucun QR code disponible pour cette inscription.',
    'notfound.payment_link' => 'Ce lien de paiement n\'est plus valide.',
    'notfound.registration_link' => 'Ce lien d\'inscription n\'est plus valide.',
    'notfound.diploma_unavailable' => 'Aucun diplôme n\'est disponible pour cette inscription.',
    'notfound.diploma_file' => 'Le fichier du diplôme est introuvable.',
    'notfound.page' => 'Page introuvable.',
    'notfound.access_denied' => 'Accès refusé.',
    'notfound.bad_request' => 'Requête invalide.',

    // ----------------------------------------------------------------- meta
    'meta.programme_title' => 'Programme de la formation',
    'meta.programme_description' => 'Déroulé jour par jour de la formation CRA-W, du :start au :end à :location.',
    'meta.practical_title' => 'Informations pratiques',
    'meta.practical_description' => 'Accès, repas, hébergement, tarif et conditions d\'annulation de la formation organisée par le CRA-W à Gembloux.',
    'meta.legal_title' => 'Mentions légales',
    'meta.legal_description' => 'Mentions légales du site d\'inscription à la formation du CRA-W.',
    'meta.privacy_title' => 'Déclaration de confidentialité',
    'meta.privacy_description' => 'Traitement de vos données personnelles dans le cadre de l\'inscription à la formation du CRA-W : finalités, durées de conservation et droits.',
    'meta.accessibility_title' => 'Déclaration d\'accessibilité',
    'meta.accessibility_description' => 'Déclaration d\'accessibilité du site d\'inscription à la formation du CRA-W.',
    'meta.contact_title' => 'Contact',
    'meta.contact_description' => 'Contactez l\'équipe organisatrice de la formation du CRA-W à Gembloux.',
    'meta.news_title' => 'Actualités de la formation',
    'meta.news_description' => 'Toutes les actualités liées à la formation organisée par le Centre wallon de Recherches agronomiques.',
    'meta.registration_title' => 'Inscription à la formation',
    'meta.registration_description' => 'Formulaire d\'inscription à la formation organisée par le CRA-W du 15 au 19 mars 2027 à Gembloux. Sans création de compte.',
    'meta.preferences_title' => 'Préférences alimentaires',
    'meta.preferences_description' => 'Indiquez votre régime alimentaire et vos éventuelles allergies.',
    'meta.review_title' => 'Récapitulatif de votre inscription',
    'meta.review_description' => 'Vérifiez vos informations avant de valider votre inscription.',
    'meta.space_title' => 'Mon inscription',
    'meta.payment_title' => 'Paiement de votre inscription',
    'meta.payment_description' => 'Coordonnées bancaires et QR code pour régler votre inscription par virement.',
    'meta.diploma_verify_title' => 'Vérification d\'un diplôme',
    'meta.diploma_not_found_title' => 'Diplôme non reconnu',
    'meta.diploma_verify_description' => 'Vérification de l\'authenticité d\'un diplôme délivré par le CRA-W.',

    // ------------------------------------------------------------------ faq
    'faq.account.question' => 'Dois-je créer un compte pour m\'inscrire ?',
    'faq.account.answer' => 'Non. L\'inscription se fait en une seule fois, sans création de compte. Vous recevez ensuite un lien personnel par courriel qui vous permet de retrouver votre inscription, vos instructions de paiement et, en fin de formation, votre diplôme.',
    'faq.payment.question' => 'Comment se déroule le paiement ?',
    'faq.payment.answer_free' => 'La participation à cette session est gratuite : aucun paiement ne vous sera demandé.',
    'faq.payment.answer' => 'Le paiement s\'effectue par virement bancaire, dans les :days jours suivant votre inscription. Vous recevez les coordonnées bancaires, une communication structurée unique et un QR code permettant de pré-remplir le virement depuis votre application bancaire. Votre inscription est définitivement confirmée à réception du paiement.',
    'faq.meals.question' => 'Les repas sont-ils compris ?',
    'faq.meals.answer' => 'Oui. Les pauses café et les repas de midi sont inclus dans le prix. Le formulaire d\'inscription vous permet de signaler votre régime alimentaire ainsi que vos allergies ou intolérances : ces informations sont transmises au traiteur.',
    'faq.diploma.question' => 'Obtiendrai-je un certificat de participation ?',
    'faq.diploma.answer' => 'Oui, un diplôme de participation au format PDF est délivré aux participants ayant assisté à au moins :rate des créneaux de la formation. Il est envoyé par courriel à l\'issue de la session.',
    'faq.cancel.question' => 'Puis-je annuler mon inscription ?',
    'faq.cancel.answer' => 'Toute annulation communiquée par écrit plus de 15 jours avant le début de la formation donne lieu à un remboursement intégral. Passé ce délai, l\'inscription peut être transférée à un collègue sans frais.',

    // ---------------------------------------------------------------- erreur
    'error.not_found_title' => 'Page introuvable',
    'error.forbidden_title' => 'Accès refusé',
    'error.session_expired_title' => 'Session expirée',
    'error.too_many_requests_title' => 'Trop de requêtes',
    'error.generic_title' => 'Erreur',

    // ------------------------------------------------------------------ nav
    'nav.home' => 'La formation',
    'nav.programme' => 'Programme',
    'nav.practical' => 'Infos pratiques',
    'nav.news' => 'Actualités',
    'nav.contact' => 'Contact',
    'nav.register' => 'S\'inscrire',
    'nav.contact_us' => 'Nous contacter',
    'nav.admin_space' => 'Espace administrateur',

    // -------------------------------------------------------------- langue
    'lang.name' => 'Français',
    'lang.switch_to' => 'English',
];
