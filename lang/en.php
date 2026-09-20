<?php

declare(strict_types=1);

/**
 * English interface strings.
 *
 * Only strings decided by the code (validation, flash messages, errors):
 * page and email content lives in the *.php (French) and *.en.php (English)
 * templates themselves (see View::evaluate()).
 */
return [
    // --------------------------------------------------------------- fields
    'field.last_name' => 'Last name',
    'field.first_name' => 'First name',
    'field.email' => 'Email address',
    'field.phone' => 'Phone number',
    'field.civility' => 'Title',
    'field.birth_date' => 'Date of birth',
    'field.organisation' => 'Organisation',
    'field.job_title' => 'Job title',
    'field.street' => 'Street and number',
    'field.postal_code' => 'Postal code',
    'field.city' => 'City',
    'field.vat_number' => 'VAT number',
    'field.diet' => 'Dietary preference',
    'field.diet_comment' => 'Diet details',
    'field.allergies_comment' => 'Allergy details',
    'field.logistics_comment' => 'Remarks',
    'field.name' => 'Name',
    'field.subject' => 'Subject',
    'field.message' => 'Message',

    // ----------------------------------------------------------- validation
    'validation.required' => 'The field ":label" is required.',
    'validation.min_length' => 'The field ":label" must be at least :min characters long.',
    'validation.max_length' => 'The field ":label" cannot exceed :max characters.',
    'validation.email' => 'The field ":label" must contain a valid email address.',
    'validation.phone' => 'The field ":label" must contain a valid phone number.',
    'validation.in' => 'The value chosen for ":label" is not valid.',
    'validation.date' => 'The field ":label" must contain a valid date.',
    'validation.vat_number' => 'The field ":label" must contain a valid VAT number (e.g. BE0123456789).',
    'validation.consent_health' => 'Please confirm your consent to share your allergy details with the caterer.',
    'validation.consent_gdpr' => 'You must accept the processing of your data to register.',
    'validation.consent_terms' => 'You must accept the participation and cancellation terms.',
    'validation.form_errors' => 'The form contains errors. Please correct them.',
    'validation.form_errors_short' => 'Please correct the errors in the form.',
    'validation.missing_fields' => 'Please complete the missing information.',
    'validation.confirm_missing' => 'Please tick the required boxes to finalise your registration.',

    // ---------------------------------------------------------------- flash
    'flash.registration_closed' => 'Registration for this training is closed.',
    'flash.registration_exists' => 'A registration already exists for this email address.',
    'flash.registration_exists_full' => 'A registration is already recorded with this address. Check the confirmation email you received.',
    'flash.registration_success' => 'Your registration has been recorded. A confirmation email has been sent to you.',
    'flash.registration_waitlist' => 'The training is full: your request has been placed on the waiting list. We will let you know as soon as a place becomes available.',
    'flash.registration_already' => 'This registration has already been recorded.',
    'flash.contact_success' => 'Your message has been sent.',
    'flash.contact_success_full' => 'Your message has been sent. We will reply as soon as possible.',
    'flash.login_required' => 'Please sign in to access this page.',

    // ------------------------------------------------------------- notfound
    'notfound.training' => 'No training is currently published.',
    'notfound.news_article' => 'This article does not exist or is no longer published.',
    'notfound.qr_unavailable' => 'No QR code is available for this registration.',
    'notfound.payment_link' => 'This payment link is no longer valid.',
    'notfound.registration_link' => 'This registration link is no longer valid.',
    'notfound.diploma_unavailable' => 'No diploma is available for this registration.',
    'notfound.diploma_file' => 'The diploma file could not be found.',
    'notfound.page' => 'Page not found.',
    'notfound.access_denied' => 'Access denied.',
    'notfound.bad_request' => 'Invalid request.',

    // ------------------------------------------------------------------ meta
    'meta.programme_title' => 'Training programme',
    'meta.programme_description' => 'Day-by-day schedule of the CRA-W training, from :start to :end in :location.',
    'meta.practical_title' => 'Practical information',
    'meta.practical_description' => 'Access, meals, accommodation, price and cancellation terms for the training organised by CRA-W in Gembloux.',
    'meta.legal_title' => 'Legal notice',
    'meta.legal_description' => 'Legal notice for the CRA-W training registration website.',
    'meta.privacy_title' => 'Privacy notice',
    'meta.privacy_description' => 'Processing of your personal data as part of registration for the CRA-W training: purposes, retention periods and rights.',
    'meta.accessibility_title' => 'Accessibility statement',
    'meta.accessibility_description' => 'Accessibility statement for the CRA-W training registration website.',
    'meta.contact_title' => 'Contact',
    'meta.contact_description' => 'Contact the team organising the CRA-W training in Gembloux.',
    'meta.news_title' => 'Training news',
    'meta.news_description' => 'All the news related to the training organised by the Walloon Agricultural Research Centre.',
    'meta.registration_title' => 'Training registration',
    'meta.registration_description' => 'Registration form for the CRA-W training from 15 to 19 March 2027 in Gembloux. No account required.',
    'meta.preferences_title' => 'Dietary preferences',
    'meta.preferences_description' => 'Tell us about your dietary preference and any allergies.',
    'meta.review_title' => 'Review your registration',
    'meta.review_description' => 'Check your information before confirming your registration.',
    'meta.space_title' => 'My registration',
    'meta.payment_title' => 'Payment for your registration',
    'meta.payment_description' => 'Bank details and QR code to pay for your registration by bank transfer.',
    'meta.diploma_verify_title' => 'Diploma verification',
    'meta.diploma_not_found_title' => 'Diploma not recognised',
    'meta.diploma_verify_description' => 'Verification of the authenticity of a diploma issued by CRA-W.',

    // ------------------------------------------------------------------ faq
    'faq.account.question' => 'Do I need to create an account to register?',
    'faq.account.answer' => 'No. Registration is done in one go, with no account creation. You then receive a personal link by email that lets you find your registration, your payment instructions and, at the end of the training, your diploma.',
    'faq.payment.question' => 'How does payment work?',
    'faq.payment.answer_free' => 'Participation in this session is free: no payment will be requested from you.',
    'faq.payment.answer' => 'Payment is made by bank transfer, within :days days of your registration. You will receive the bank details, a unique structured reference and a QR code that pre-fills the transfer from your banking app. Your registration is definitively confirmed once payment is received.',
    'faq.meals.question' => 'Are meals included?',
    'faq.meals.answer' => 'Yes. Coffee breaks and lunches are included in the price. The registration form lets you indicate your dietary preference as well as any allergies or intolerances: this information is passed on to the caterer.',
    'faq.diploma.question' => 'Will I receive a certificate of attendance?',
    'faq.diploma.answer' => 'Yes, a PDF certificate of attendance is issued to participants who attended at least :rate of the training sessions. It is sent by email at the end of the session.',
    'faq.cancel.question' => 'Can I cancel my registration?',
    'faq.cancel.answer' => 'Any cancellation communicated in writing more than 15 days before the start of the training entitles you to a full refund. After this deadline, the registration can be transferred to a colleague free of charge.',

    // ----------------------------------------------------------------- error
    'error.not_found_title' => 'Page not found',
    'error.forbidden_title' => 'Access denied',
    'error.session_expired_title' => 'Session expired',
    'error.too_many_requests_title' => 'Too many requests',
    'error.generic_title' => 'Error',

    // ------------------------------------------------------------------ nav
    'nav.home' => 'The training',
    'nav.programme' => 'Programme',
    'nav.practical' => 'Practical information',
    'nav.news' => 'News',
    'nav.contact' => 'Contact',
    'nav.register' => 'Register',
    'nav.contact_us' => 'Contact us',
    'nav.admin_space' => 'Administrator area',

    // ------------------------------------------------------------- language
    'lang.name' => 'English',
    'lang.switch_to' => 'Français',
];
