<?php

declare(strict_types=1);

namespace Craw\Controller\PublicSite;

use Craw\Controller\BaseController;
use Craw\Core\Logger;
use Craw\Core\Request;
use Craw\Core\Response;
use Craw\Core\Session;
use Craw\Core\Validator;
use Craw\Domain\DietPreference;
use Craw\Repository\DiplomaRepository;
use Craw\Repository\PaymentRepository;
use Craw\Repository\RegistrationRepository;
use Craw\Repository\TrainingRepository;
use Craw\Service\Notifier;
use Craw\Service\RegistrationService;

/**
 * Parcours d'inscription en trois etapes, sans creation de compte.
 *
 * Un brouillon est cree des la premiere etape et identifie par un jeton
 * aleatoire de 32 octets : l'abandon en cours de route ne perd rien, et aucune
 * URL ne laisse deviner l'inscription d'un tiers.
 */
final class RegistrationController extends BaseController
{
    private const CIVILITIES = ['Mme', 'M.', 'Autre', 'Ne se prononce pas'];

    public function start(Request $request, array $params): Response
    {
        $trainings = new TrainingRepository();
        $training = $trainings->current();

        if ($training === null) {
            $this->notFound();
        }

        $trainingId = (int) $training['id'];

        return $this->view('public/registration_identity', [
            'title' => 'Inscription à la formation',
            'description' => 'Formulaire d\'inscription à la formation organisée par le CRA-W '
                . 'du 15 au 19 mars 2027 à Gembloux. Sans création de compte.',
            'canonical' => url('/inscription'),
            'activeNav' => 'inscription',
            'training' => $training,
            'civilities' => self::CIVILITIES,
            'remainingSeats' => $trainings->remainingSeats($trainingId),
            'isOpen' => $trainings->isOpen($trainingId),
            'step' => 1,
        ]);
    }

    public function storeIdentity(Request $request, array $params): Response
    {
        $trainings = new TrainingRepository();
        $training = $trainings->current();

        if ($training === null) {
            $this->notFound();
        }

        $trainingId = (int) $training['id'];

        if (!$trainings->isOpen($trainingId)) {
            Session::flash('error', 'Les inscriptions à cette formation sont closes.');

            return $this->redirect('/inscription');
        }

        // Piege a robots : un humain ne remplit jamais ce champ masque.
        if (($request->input('website') ?? '') !== '') {
            Logger::warning('Soumission d\'inscription rejetee (honeypot).', ['ip' => $request->ipHash()]);

            return $this->redirect('/inscription');
        }

        $input = $request->all();
        unset($input['_token'], $input['website']);

        $needsInvoice = $request->boolean('needs_invoice');

        $validator = Validator::make($input)
            ->required('last_name', 'Nom')->maxLength('last_name', 'Nom', 100)
            ->required('first_name', 'Prénom')->maxLength('first_name', 'Prénom', 100)
            ->required('email', 'Adresse e-mail')->email('email', 'Adresse e-mail')
            ->required('phone', 'Téléphone')->phone('phone', 'Téléphone')
            ->in('civility', 'Civilité', self::CIVILITIES)
            ->date('birth_date', 'Date de naissance')
            ->maxLength('organisation', 'Organisation', 150)
            ->maxLength('job_title', 'Fonction', 150)
            ->requiredIf('street', 'Rue et numéro', $needsInvoice)
            ->requiredIf('postal_code', 'Code postal', $needsInvoice)
            ->requiredIf('city', 'Localité', $needsInvoice)
            ->maxLength('street', 'Rue et numéro', 190)
            ->maxLength('city', 'Localité', 120);

        if (($input['vat_number'] ?? '') !== '') {
            $validator->vatNumber('vat_number', 'Numéro de TVA');
        }

        if ($validator->fails()) {
            return $this->back('/inscription', $validator->errors(), $input,
                'Le formulaire comporte des erreurs. Merci de les corriger.');
        }

        $email = mb_strtolower((string) $request->input('email'));

        // RG-01 : une seule inscription active par adresse et par session.
        $registrations = new RegistrationRepository();
        $existing = $registrations->findActiveByEmail($trainingId, $email);

        if ($existing !== null) {
            return $this->back('/inscription', ['email' => 'Une inscription existe déjà pour cette adresse e-mail.'],
                $input,
                'Une inscription est déjà enregistrée avec cette adresse. Consultez le courriel de confirmation reçu.');
        }

        $service = new RegistrationService();

        $id = $service->createDraft($trainingId, [
            'civility' => $request->input('civility'),
            'last_name' => (string) $request->input('last_name'),
            'first_name' => (string) $request->input('first_name'),
            'email' => $email,
            'phone' => $request->input('phone'),
            'birth_date' => $request->input('birth_date'),
            'organisation' => $request->input('organisation'),
            'job_title' => $request->input('job_title'),
            'street' => $request->input('street'),
            'postal_code' => $request->input('postal_code'),
            'city' => $request->input('city'),
            'country' => $request->input('country') ?? 'Belgique',
            'vat_number' => $request->input('vat_number'),
            'needs_invoice' => $needsInvoice ? 1 : 0,
            'ip_hash' => $request->ipHash(),
            'user_agent' => $request->userAgent(),
        ]);

        $registration = $registrations->find($id);

        return $this->redirect('/inscription/preferences/' . (string) $registration['token']);
    }

    public function preferences(Request $request, array $params): Response
    {
        [$registration, $training] = $this->draft((string) $params['token']);

        return $this->view('public/registration_preferences', [
            'title' => 'Préférences alimentaires',
            'description' => 'Indiquez votre régime alimentaire et vos éventuelles allergies.',
            'canonical' => url('/inscription'),
            'noindex' => true,
            'training' => $training,
            'registration' => $registration,
            'diets' => DietPreference::cases(),
            'step' => 2,
        ]);
    }

    public function storePreferences(Request $request, array $params): Response
    {
        [$registration] = $this->draft((string) $params['token']);

        $input = $request->all();
        unset($input['_token']);

        $diet = (string) ($request->input('diet') ?? 'standard');
        $hasAllergies = $request->boolean('has_allergies');
        $dietCase = DietPreference::tryFrom($diet);

        $validator = Validator::make($input)
            ->required('diet', 'Régime alimentaire')
            ->in('diet', 'Régime alimentaire', DietPreference::values())
            ->maxLength('diet_comment', 'Précision sur le régime', 500)
            ->maxLength('allergies_comment', 'Détail des allergies', 500)
            ->maxLength('logistics_comment', 'Remarques', 500)
            // Un commentaire est exige quand le choix ne se suffit pas a lui-meme.
            ->requiredIf('diet_comment', 'Précision sur le régime', $dietCase?->requiresComment() ?? false)
            ->requiredIf('allergies_comment', 'Détail des allergies', $hasAllergies);

        if ($hasAllergies) {
            // Donnee de sante : consentement explicite et distinct (RGPD art. 9).
            $validator->accepted(
                'consent_health',
                'Merci de confirmer votre accord pour la transmission de vos allergies au traiteur.'
            );
        }

        if ($validator->fails()) {
            return $this->back(
                '/inscription/preferences/' . (string) $registration['token'],
                $validator->errors(),
                $input,
                'Merci de compléter les informations manquantes.'
            );
        }

        $now = date('Y-m-d H:i:s');

        (new RegistrationRepository())->update((int) $registration['id'], [
            'diet' => $diet,
            'diet_comment' => $request->input('diet_comment'),
            'has_allergies' => $hasAllergies ? 1 : 0,
            'allergies_comment' => $hasAllergies ? $request->input('allergies_comment') : null,
            'logistics_comment' => $request->input('logistics_comment'),
            'consent_health_at' => $hasAllergies ? $now : null,
        ]);

        return $this->redirect('/inscription/recapitulatif/' . (string) $registration['token']);
    }

    public function review(Request $request, array $params): Response
    {
        [$registration, $training] = $this->draft((string) $params['token']);

        return $this->view('public/registration_review', [
            'title' => 'Récapitulatif de votre inscription',
            'description' => 'Vérifiez vos informations avant de valider votre inscription.',
            'canonical' => url('/inscription'),
            'noindex' => true,
            'training' => $training,
            'registration' => $registration,
            'dietLabel' => DietPreference::tryLabel((string) $registration['diet']),
            'step' => 3,
        ]);
    }

    public function confirm(Request $request, array $params): Response
    {
        [$registration, $training] = $this->draft((string) $params['token']);

        $validator = Validator::make($request->all())
            ->accepted('consent_gdpr', 'Vous devez accepter le traitement de vos données pour vous inscrire.')
            ->accepted('consent_terms', 'Vous devez accepter les conditions de participation et d\'annulation.');

        if ($validator->fails()) {
            return $this->back(
                '/inscription/recapitulatif/' . (string) $registration['token'],
                $validator->errors(),
                [],
                'Merci de cocher les cases requises pour finaliser votre inscription.'
            );
        }

        $now = date('Y-m-d H:i:s');
        $registrations = new RegistrationRepository();

        $registrations->update((int) $registration['id'], [
            'consent_gdpr_at' => $now,
            'consent_terms_at' => $now,
        ]);

        $confirmed = (new RegistrationService())->confirm((int) $registration['id'], $training);

        Notifier::registrationConfirmed($confirmed, $training);

        if ((string) $confirmed['status'] === 'waitlist') {
            Session::flash(
                'warning',
                'La formation est complète : votre demande est enregistrée en liste d\'attente. '
                . 'Nous vous préviendrons dès qu\'une place se libère.'
            );

            return $this->redirect('/mon-inscription/' . (string) $confirmed['token']);
        }

        Session::flash('success', 'Votre inscription est enregistrée. Un courriel de confirmation vous a été envoyé.');

        if ((int) $confirmed['amount_due_cents'] <= 0) {
            return $this->redirect('/mon-inscription/' . (string) $confirmed['token']);
        }

        return $this->redirect('/inscription/paiement/' . (string) $confirmed['token']);
    }

    /** Espace participant : statut, paiement, diplome. */
    public function space(Request $request, array $params): Response
    {
        $registrations = new RegistrationRepository();
        $registration = $registrations->findByToken((string) $params['token']);

        if ($registration === null || (string) $registration['status'] === 'draft') {
            $this->notFound('Ce lien d\'inscription n\'est plus valide.');
        }

        $training = (new TrainingRepository())->find((int) $registration['training_id']);

        if ($training === null) {
            $this->notFound();
        }

        return $this->view('public/registration_space', [
            'title' => 'Mon inscription',
            'noindex' => true,
            'canonical' => url('/'),
            'registration' => $registration,
            'training' => $training,
            'payment' => (new PaymentRepository())->findByRegistration((int) $registration['id']),
            'diploma' => (new DiplomaRepository())->latestForRegistration((int) $registration['id']),
            'dietLabel' => DietPreference::tryLabel((string) $registration['diet']),
        ]);
    }

    public function downloadDiploma(Request $request, array $params): Response
    {
        $registration = (new RegistrationRepository())->findByToken((string) $params['token']);

        if ($registration === null) {
            $this->notFound();
        }

        $diploma = (new DiplomaRepository())->latestForRegistration((int) $registration['id']);

        if ($diploma === null) {
            $this->notFound('Aucun diplôme n\'est disponible pour cette inscription.');
        }

        $path = storage_path((string) $diploma['file_path']);

        if (!is_file($path)) {
            $this->notFound('Le fichier du diplôme est introuvable.');
        }

        return Response::inline(
            (string) file_get_contents($path),
            sprintf('diplome-%s.pdf', (string) $diploma['number']),
            'application/pdf'
        );
    }

    /**
     * Charge un brouillon a partir de son jeton.
     *
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function draft(string $token): array
    {
        $registration = (new RegistrationRepository())->findByToken($token);

        if ($registration === null) {
            $this->notFound('Ce lien d\'inscription n\'est plus valide.');
        }

        // Une inscription deja confirmee ne repasse pas par le formulaire :
        // on renvoie la personne vers son espace plutot que d'afficher une erreur.
        if ((string) $registration['status'] !== 'draft') {
            Session::flash('info', 'Cette inscription est déjà enregistrée.');

            $this->redirectAway('/mon-inscription/' . $token);
        }

        $training = (new TrainingRepository())->find((int) $registration['training_id']);

        if ($training === null) {
            $this->notFound();
        }

        return [$registration, $training];
    }
}
