<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

class TranslationController
{
    private $translationService;
    private $languageService;
    private $view;
    protected $pageTitle;

    public function __construct()
    {
        $this->translationService = new TranslationService();
        $this->languageService = new LanguageService();
    }

    public function index(): void
    {
        // Determine which language to show:
        // 1. If URL has lang=all, show all translations (special case)
        // 2. If URL has lang=X, use that language
        // 3. Otherwise use session/cookie locale

        if (isset($_GET['lang']) && $_GET['lang'] === 'all') {
            $langCode = 'all';
        } elseif (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'ar', 'zh', 'fr', 'ru', 'es', 'id'])) {
            $langCode = $_GET['lang'];
        } else {
            // Fall back to session/cookie locale
            $langCode = admin_get_locale();
        }
        $context = $_GET['context'] ?? null;
        $search = $_GET['search'] ?? null;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 50;

        $showAll = ($langCode === 'all');
        $totalCount = 0;

        if ($showAll) {
            $totalCount = $this->translationService->countAllTranslations();
            $translations = $this->translationService->getAllTranslations($page, $perPage);
        } elseif ($search) {
            $totalCount = $this->translationService->countSearch($langCode, $search);
            $translations = $this->translationService->searchTranslations($langCode, $search, $page, $perPage);
        } elseif ($context) {
            $totalCount = $this->translationService->countByContext($langCode, $context);
            $translations = $this->translationService->getTranslationsByContext($langCode, $context, $page, $perPage);
        } else {
            $totalCount = $this->translationService->countAll($langCode);
            $translations = $this->translationService->getTranslations($langCode, $page, $perPage);
        }

        $totalPages = (int) ceil($totalCount / $perPage);
        $pagination = [
            'page' => $page,
            'perPage' => $perPage,
            'totalCount' => $totalCount,
            'totalPages' => $totalPages,
            'startIndex' => ($page - 1) * $perPage + 1,
            'endIndex' => min($page * $perPage, $totalCount)
        ];

        $this->setView('translation-editor');
        $this->setPageTitle('Translations');

        $this->view->set('translations', $translations);
        $this->view->set('languages', $this->languageService->getActiveLanguages());
        $this->view->set('currentLang', $langCode);
        $this->view->set('currentContext', $context);
        $this->view->set('searchQuery', $search);
        $this->view->set('pagination', $pagination);
        $this->view->set('contexts', $this->translationService->getContexts());
        $this->view->set('pageTitle', $this->getPageTitle());
        $this->view->set('csrfToken', csrf_generate_token('translation'));

        $errors = [];
        $status = [];

        if (isset($_SESSION['error'])) {
            $errors[] = $_SESSION['error'];
            unset($_SESSION['error']);
        }

        if (isset($_SESSION['status'])) {
            $status[] = $this->getStatusMessage($_SESSION['status']);
            unset($_SESSION['status']);
        }

        $this->view->set('errors', $errors);
        $this->view->set('status', $status);

        $this->view->render();
    }

    private function getStatusMessage(string $status): string
    {
        if (strpos($status, 'imported') === 0) {
            $count = (int) substr($status, 8);
            return "Successfully imported {$count} translations.";
        }

        $messages = [
            'translationCreated' => 'Translation has been created successfully.',
            'translationUpdated' => 'Translation has been updated successfully.',
            'translationDeleted' => 'Translation has been deleted successfully.',
            'cacheRegenerated' => 'Translation cache has been regenerated.',
        ];

        return $messages[$status] ?? 'Operation completed successfully.';
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            direct_page('index.php?load=translations', 302);
            return;
        }

        try {
            if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {
                throw new ServiceException("Invalid security token");
            }

            $id = (int) ($_POST['id'] ?? 0);
            $value = $_POST['value'] ?? '';

            if ($id <= 0) {
                throw new ServiceException("Invalid translation ID");
            }

            $this->translationService->updateTranslation($id, [
                'translation_value' => $value,
            ]);

            $_SESSION['status'] = 'translationUpdated';
            direct_page('index.php?load=translations', 302);
        } catch (Throwable $th) {
            $_SESSION['error'] = $th->getMessage();
            direct_page('index.php?load=translations&error=updateFailed', 302);
        }
    }

    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            direct_page('index.php?load=translations', 302);
            return;
        }

        try {
            if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {
                throw new ServiceException("Invalid security token");
            }

            $this->translationService->createTranslation([
                'lang_code' => $_POST['lang_code'] ?? 'en',
                'translation_key' => $_POST['translation_key'] ?? '',
                'translation_value' => $_POST['translation_value'] ?? '',
                'translation_context' => $_POST['translation_context'] ?? null,
            ]);

            $_SESSION['status'] = 'translationCreated';
            direct_page('index.php?load=translations&lang=' . ($_POST['lang_code'] ?? 'en'), 302);
        } catch (Throwable $th) {
            $_SESSION['error'] = $th->getMessage();
            direct_page('index.php?load=translations&error=createFailed', 302);
        }
    }

    public function delete(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            direct_page('index.php?load=translations', 302);
            return;
        }

        try {
            if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {
                throw new ServiceException("Invalid security token");
            }

            $this->translationService->deleteTranslation($id);

            $_SESSION['status'] = 'translationDeleted';
        } catch (Throwable $th) {
            $_SESSION['error'] = $th->getMessage();
        }

        direct_page('index.php?load=translations', 302);
    }

    public function export(): void
    {
        $langCode = $_GET['lang'] ?? 'en';

        try {
            $translations = $this->translationService->exportToArray($langCode);

            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="' . $langCode . '-translations.json"');

            echo json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (Throwable $th) {
            http_response_code(500);
            echo json_encode(['error' => $th->getMessage()]);
        }
    }

    public function import(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            direct_page('index.php?load=translations', 302);
            return;
        }

        try {
            if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {
                throw new ServiceException("Invalid security token");
            }

            $langCode = $_POST['lang_code'] ?? 'en';

            if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
                throw new ServiceException("No file uploaded");
            }

            $content = file_get_contents($_FILES['import_file']['tmp_name']);
            $translations = json_decode($content, true);

            if (!is_array($translations)) {
                throw new ServiceException("Invalid JSON file");
            }

            $count = $this->translationService->importFromArray($langCode, $translations);

            $_SESSION['status'] = "imported{$count}";
            direct_page("index.php?load=translations&lang={$langCode}", 302);
        } catch (Throwable $th) {
            $_SESSION['error'] = $th->getMessage();
            direct_page('index.php?load=translations&error=importFailed', 302);
        }
    }

    public function regenerateCache(): void
    {
        $langCode = $_GET['lang'] ?? 'en';

        try {
            $this->translationService->regenerateCache($langCode);
            $_SESSION['status'] = 'cacheRegenerated';
        } catch (Throwable $th) {
            $_SESSION['error'] = $th->getMessage();
        }

        direct_page("index.php?load=translations&lang={$langCode}", 302);
    }

    protected function setView(string $viewName): void
    {
        $this->view = new View('admin', 'ui', 'setting', $viewName);
    }

    public function setPageTitle(string $title): void
    {
        $this->pageTitle = $title;
    }

    public function getPageTitle(): string
    {
        return $this->pageTitle;
    }
}
