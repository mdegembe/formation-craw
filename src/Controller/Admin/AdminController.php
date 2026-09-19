<?php

declare(strict_types=1);

namespace Craw\Controller\Admin;

use Craw\Controller\BaseController;
use Craw\Core\Response;
use Craw\Repository\TrainingRepository;

/**
 * Base commune aux ecrans du back-office : resolution de la formation
 * courante et donnees partagees par le gabarit d'administration.
 */
abstract class AdminController extends BaseController
{
    protected TrainingRepository $trainings;

    public function __construct()
    {
        $this->trainings = new TrainingRepository();
    }

    /**
     * @return array<string, mixed>
     */
    protected function training(): array
    {
        $training = $this->trainings->current();

        if ($training === null) {
            $this->notFound('Aucune formation n\'est configurée. Lancez « php bin/console.php seed ».');
        }

        return $training;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function adminView(string $template, array $data = [], int $status = 200): Response
    {
        $data['noindex'] = true;
        $data['training'] ??= $this->training();

        return $this->view($template, $data, $status);
    }
}
