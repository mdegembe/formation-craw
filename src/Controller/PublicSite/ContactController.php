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
            'title' => t('meta.contact_title'),
            'description' => t('meta.contact_description'),
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

            Session::flash('success', t('flash.contact_success'));

            return $this->redirect('/contact');
        }

        $input = $request->all();
        unset($input['_token'], $input['website']);

        $validator = Validator::make($input)
            ->required('name', t('field.name'))->maxLength('name', t('field.name'), 150)
            ->required('email', t('field.email'))->email('email', t('field.email'))
            ->required('subject', t('field.subject'))->maxLength('subject', t('field.subject'), 190)
            ->required('message', t('field.message'))
            ->minLength('message', t('field.message'), 10)
            ->maxLength('message', t('field.message'), 3000);

        if ($validator->fails()) {
            return $this->back('/contact', $validator->errors(), $input, t('validation.form_errors_short'));
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

        Session::flash('success', t('flash.contact_success_full'));

        return $this->redirect('/contact');
    }
}
