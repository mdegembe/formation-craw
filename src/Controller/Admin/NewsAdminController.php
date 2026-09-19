<?php

declare(strict_types=1);

namespace Craw\Controller\Admin;

use Craw\Core\Audit;
use Craw\Core\Auth;
use Craw\Core\Request;
use Craw\Core\Response;
use Craw\Core\Session;
use Craw\Core\Validator;
use Craw\Domain\Markdown;
use Craw\Repository\NewsRepository;
use Craw\Service\ImageProcessor;

final class NewsAdminController extends AdminController
{
    public function index(Request $request, array $params): Response
    {
        return $this->adminView('admin/news_index', [
            'title' => 'Actualités — Administration',
            'activeAdminNav' => 'news',
            'articles' => (new NewsRepository())->all(),
        ]);
    }

    public function create(Request $request, array $params): Response
    {
        return $this->adminView('admin/news_form', [
            'title' => 'Nouvelle actualité — Administration',
            'activeAdminNav' => 'news',
            'article' => null,
        ]);
    }

    public function edit(Request $request, array $params): Response
    {
        $article = (new NewsRepository())->find((int) $params['id']);

        if ($article === null) {
            $this->notFound('Actualité introuvable.');
        }

        return $this->adminView('admin/news_form', [
            'title' => 'Modifier « ' . str_excerpt((string) $article['title'], 40) . ' »',
            'activeAdminNav' => 'news',
            'article' => $article,
        ]);
    }

    public function store(Request $request, array $params): Response
    {
        $repository = new NewsRepository();

        $validated = $this->validate($request, '/admin/actualites/nouvelle');

        if ($validated instanceof Response) {
            return $validated;
        }

        $data = $validated;
        $data['slug'] = $repository->uniqueSlug((string) ($request->input('slug') ?: $data['title']));
        $data['author_id'] = Auth::id();

        $cover = $this->storeCover($request);

        if ($cover instanceof Response) {
            return $cover;
        }

        if ($cover !== null) {
            $data['cover_path'] = $cover;
        }

        $id = $repository->create($data);

        Audit::record('news.create', 'news', $id, ['title' => $data['title']], $request->ipHash());
        Session::flash('success', 'Actualité créée.');

        return $this->redirect('/admin/actualites/' . $id);
    }

    public function update(Request $request, array $params): Response
    {
        $repository = new NewsRepository();
        $id = (int) $params['id'];
        $article = $repository->find($id);

        if ($article === null) {
            $this->notFound('Actualité introuvable.');
        }

        $validated = $this->validate($request, '/admin/actualites/' . $id);

        if ($validated instanceof Response) {
            return $validated;
        }

        $data = $validated;
        $data['slug'] = $repository->uniqueSlug(
            (string) ($request->input('slug') ?: $data['title']),
            $id
        );

        $cover = $this->storeCover($request);

        if ($cover instanceof Response) {
            return $cover;
        }

        if ($cover !== null) {
            // La couverture precedente n'est plus referencee : on la supprime.
            $previous = $article['cover_path'] ?? null;

            if (is_string($previous) && $previous !== '' && is_file(storage_path('uploads/news/' . $previous))) {
                @unlink(storage_path('uploads/news/' . $previous));
            }

            $data['cover_path'] = $cover;
        }

        if ($request->boolean('remove_cover')) {
            $previous = $article['cover_path'] ?? null;

            if (is_string($previous) && $previous !== '' && is_file(storage_path('uploads/news/' . $previous))) {
                @unlink(storage_path('uploads/news/' . $previous));
            }

            $data['cover_path'] = null;
        }

        $repository->update($id, $data);

        Audit::record('news.update', 'news', $id, ['title' => $data['title']], $request->ipHash());
        Session::flash('success', 'Actualité enregistrée.');

        return $this->redirect('/admin/actualites/' . $id);
    }

    public function destroy(Request $request, array $params): Response
    {
        Auth::requireAdmin();

        $repository = new NewsRepository();
        $id = (int) $params['id'];
        $article = $repository->find($id);

        if ($article === null) {
            $this->notFound('Actualité introuvable.');
        }

        $cover = $article['cover_path'] ?? null;

        if (is_string($cover) && $cover !== '' && is_file(storage_path('uploads/news/' . $cover))) {
            @unlink(storage_path('uploads/news/' . $cover));
        }

        $repository->delete($id);

        Audit::record('news.delete', 'news', $id, ['title' => (string) $article['title']], $request->ipHash());
        Session::flash('success', 'Actualité supprimée.');

        return $this->redirect('/admin/actualites');
    }

    /**
     * @return array<string, mixed>|Response
     */
    private function validate(Request $request, string $backPath): array|Response
    {
        $input = $request->all();
        unset($input['_token']);

        $validator = Validator::make($input)
            ->required('title', 'Titre')->maxLength('title', 'Titre', 190)
            ->maxLength('excerpt', 'Chapô', 500)
            ->required('body', 'Corps de l\'article')
            ->maxLength('meta_title', 'Titre SEO', 190)
            ->maxLength('meta_description', 'Description SEO', 255)
            ->in('status', 'Statut', ['draft', 'published']);

        if ($validator->fails()) {
            return $this->back($backPath, $validator->errors(), $input, 'Le formulaire comporte des erreurs.');
        }

        $status = (string) ($request->input('status') ?? 'draft');
        $body = (string) $request->input('body');
        $excerpt = (string) ($request->input('excerpt') ?? '');

        if ($excerpt === '') {
            $excerpt = str_excerpt(Markdown::toText($body), 220);
        }

        $publishedAt = $request->input('published_at');

        if ($status === 'published') {
            // Sans date explicite, on publie maintenant.
            $publishedAt = $publishedAt !== null && $publishedAt !== ''
                ? date('Y-m-d H:i:s', (int) strtotime($publishedAt))
                : date('Y-m-d H:i:s');
        } else {
            $publishedAt = null;
        }

        $metaDescription = (string) ($request->input('meta_description') ?? '');

        return [
            'title' => (string) $request->input('title'),
            'excerpt' => $excerpt,
            'body' => $body,
            'cover_alt' => $request->input('cover_alt'),
            'status' => $status,
            'published_at' => $publishedAt,
            'meta_title' => $request->input('meta_title'),
            'meta_description' => $metaDescription !== ''
                ? mb_substr($metaDescription, 0, 255)
                : mb_substr($excerpt, 0, 155),
        ];
    }

    /**
     * @return string|null|Response Nom du fichier stocke, null si aucun envoi.
     */
    private function storeCover(Request $request): string|null|Response
    {
        $file = $request->file('cover');

        if ($file === null) {
            return null;
        }

        try {
            $stored = ImageProcessor::store($file, storage_path('uploads/news'), 1600, 1000);

            return $stored['path'];
        } catch (\RuntimeException $exception) {
            Session::flash('error', 'Image de couverture : ' . $exception->getMessage());

            return $this->redirect('/admin/actualites');
        }
    }
}
