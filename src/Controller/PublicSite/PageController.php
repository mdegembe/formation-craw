<?php

declare(strict_types=1);

namespace Craw\Controller\PublicSite;

use Craw\Controller\BaseController;
use Craw\Core\Request;
use Craw\Core\Response;
use Craw\Core\Settings;
use Craw\Domain\Markdown;
use Craw\Repository\TrainingRepository;
use Craw\Service\SeoService;

final class PageController extends BaseController
{
    public function programme(Request $request, array $params): Response
    {
        $trainings = new TrainingRepository();
        $training = $trainings->current();

        if ($training === null) {
            $this->notFound();
        }

        return $this->view('public/programme', [
            'title' => 'Programme de la formation',
            'description' => sprintf(
                'Déroulé jour par jour de la formation CRA-W, du %s au %s à %s.',
                fr_date((string) $training['starts_on']),
                fr_date((string) $training['ends_on']),
                (string) $training['location_name']
            ),
            'canonical' => url('/programme'),
            'activeNav' => 'programme',
            'training' => $training,
            'days' => $trainings->daysGrouped((int) $training['id']),
            'jsonLd' => [
                SeoService::jsonLdTag(SeoService::breadcrumbJsonLd([
                    ['label' => 'Accueil', 'url' => '/'],
                    ['label' => 'Programme', 'url' => '/programme'],
                ])),
            ],
        ]);
    }

    public function practical(Request $request, array $params): Response
    {
        $trainings = new TrainingRepository();
        $training = $trainings->current();

        if ($training === null) {
            $this->notFound();
        }

        $faq = $this->faq($training);

        return $this->view('public/practical', [
            'title' => 'Informations pratiques',
            'description' => 'Accès, repas, hébergement, tarif et conditions d\'annulation '
                . 'de la formation organisée par le CRA-W à Gembloux.',
            'canonical' => url('/infos-pratiques'),
            'activeNav' => 'infos',
            'training' => $training,
            'practicalHtml' => Markdown::toHtml((string) ($training['practical_info'] ?? '')),
            'faq' => $faq,
            'jsonLd' => [
                SeoService::jsonLdTag(SeoService::faqJsonLd($faq)),
                SeoService::jsonLdTag(SeoService::breadcrumbJsonLd([
                    ['label' => 'Accueil', 'url' => '/'],
                    ['label' => 'Infos pratiques', 'url' => '/infos-pratiques'],
                ])),
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $training
     *
     * @return array<int, array{question: string, answer: string}>
     */
    private function faq(array $training): array
    {
        $price = (int) $training['price_cents'];
        $deadlineDays = (int) Settings::get('payment.deadline_days', 15);

        return [
            [
                'question' => 'Dois-je créer un compte pour m\'inscrire ?',
                'answer' => 'Non. L\'inscription se fait en une seule fois, sans création de compte. '
                    . 'Vous recevez ensuite un lien personnel par courriel qui vous permet de retrouver '
                    . 'votre inscription, vos instructions de paiement et, en fin de formation, votre diplôme.',
            ],
            [
                'question' => 'Comment se déroule le paiement ?',
                'answer' => $price <= 0
                    ? 'La participation à cette session est gratuite : aucun paiement ne vous sera demandé.'
                    : sprintf(
                        'Le paiement s\'effectue par virement bancaire, dans les %d jours suivant votre '
                        . 'inscription. Vous recevez les coordonnées bancaires, une communication structurée '
                        . 'unique et un QR code permettant de pré-remplir le virement depuis votre application '
                        . 'bancaire. Votre inscription est définitivement confirmée à réception du paiement.',
                        $deadlineDays
                    ),
            ],
            [
                'question' => 'Les repas sont-ils compris ?',
                'answer' => 'Oui. Les pauses café et les repas de midi sont inclus dans le prix. '
                    . 'Le formulaire d\'inscription vous permet de signaler votre régime alimentaire '
                    . 'ainsi que vos allergies ou intolérances : ces informations sont transmises au traiteur.',
            ],
            [
                'question' => 'Obtiendrai-je un certificat de participation ?',
                'answer' => sprintf(
                    'Oui, un diplôme de participation au format PDF est délivré aux participants ayant '
                    . 'assisté à au moins %s des créneaux de la formation. Il est envoyé par courriel '
                    . 'à l\'issue de la session.',
                    \Craw\Domain\AttendanceCalculator::format(Settings::minAttendanceRate())
                ),
            ],
            [
                'question' => 'Puis-je annuler mon inscription ?',
                'answer' => 'Toute annulation communiquée par écrit plus de 15 jours avant le début de la '
                    . 'formation donne lieu à un remboursement intégral. Passé ce délai, l\'inscription peut '
                    . 'être transférée à un collègue sans frais.',
            ],
        ];
    }

    public function legal(Request $request, array $params): Response
    {
        return $this->view('public/legal', [
            'title' => 'Mentions légales',
            'description' => 'Mentions légales du site d\'inscription à la formation du CRA-W.',
            'canonical' => url('/mentions-legales'),
        ]);
    }

    public function privacy(Request $request, array $params): Response
    {
        return $this->view('public/privacy', [
            'title' => 'Déclaration de confidentialité',
            'description' => 'Traitement de vos données personnelles dans le cadre de l\'inscription '
                . 'à la formation du CRA-W : finalités, durées de conservation et droits.',
            'canonical' => url('/confidentialite'),
            'retentionYears' => (int) Settings::get('privacy.retention_years', 3),
        ]);
    }

    public function accessibility(Request $request, array $params): Response
    {
        return $this->view('public/accessibility', [
            'title' => 'Déclaration d\'accessibilité',
            'description' => 'Déclaration d\'accessibilité du site d\'inscription à la formation du CRA-W.',
            'canonical' => url('/accessibilite'),
        ]);
    }
}
