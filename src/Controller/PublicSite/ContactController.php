<?php

declare(strict_types=1);

namespace Craw\Controller\PublicSite;

use Craw\Controller\BaseController;
use Craw\Core\Database;
use Craw\Core\Logger;
use Craw\Core\Request;
use Craw\Core\Response;
use Craw\Core\Session;
use Craw\Core\Settings;
use Craw\Core\Validator;
use Craw\Repository\TrainingRepository;
use Craw\Service\MailQueue;
use Craw\Service\SeoService;

final class ContactController extends BaseController
{
    public function show(Request $request, array $params): Response
    {
        return $this->view('public/contact', [
            'title' => 'Contact',
            'description' => 'Contactez l\'équipe organisatrice de la formation du CRA-W à Gembloux.',
            'canonical' => url('/contact'),
            'activeNav' => 'contact',
            'training' => (new TrainingRepository())->current(),
            'jsonLd' => [
                SeoService::jsonLdTag([
                    '@context' => 'https://schema.org',
                    ...SeoService::organization(),
                ]),
            ],
        ]);
    }

    public function submit(Request $request, array $params): Response
    {
        if (($request->input('website') ?? '') !== '') {
            Logger::warning('Message de contact rejete (honeypot).', ['ip' => $request->ipHash()]);

            Session::flash('success', 'Votre message a bien été transmis.');

            return $this->redirect('/contact');
        }

        $input = $request->all();
        unset($input['_token'], $input['website']);

        $validator = Validator::make($input)
            ->required('name', 'Nom')->maxLength('name', 'Nom', 150)
            ->required('email', 'Adresse e-mail')->email('email', 'Adresse e-mail')
            ->required('subject', 'Objet')->maxLength('subject', 'Objet', 190)
            ->required('message', 'Message')
            ->minLength('message', 'Message', 10)
            ->maxLength('message', 'Message', 3000);

        if ($validator->fails()) {
            return $this->back('/contact', $validator->errors(), $input,
                'Merci de corriger les erreurs du formulaire.');
        }

        $name = (string) $request->input('name');
        $email = (string) $request->input('email');
        $subject = (string) $request->input('subject');
        $message = (string) $request->input('message');

        Database::insert('contact_messages', [
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
            'ip_hash' => $request->ipHash(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $recipient = (string) Settings::get('contact.email', '');

        if ($recipient !== '') {
            MailQueue::push(
                $recipient,
                'Équipe formation CRA-W',
                'contact_message',
                '[Formation] ' . $subject,
                [
                    'senderName' => $name,
                    'senderEmail' => $email,
                    'messageSubject' => $subject,
                    'messageBody' => $message,
                ]
            );
        }

        Session::flash('success', 'Votre message a bien été transmis. Nous vous répondrons dans les meilleurs délais.');

        return $this->redirect('/contact');
    }
}
