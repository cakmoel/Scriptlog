<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

class LanguageController
{
    private $languageService;
    private $view;
    protected $pageTitle;

    public function __construct()
    {
        $this->languageService = new LanguageService();
    }

    public function index(): void
    {
        $this->setView('language-list');
        $this->setPageTitle('Languages');

        $this->view->set('languages', $this->languageService->getAllLanguages());
        $this->view->set('pageTitle', $this->getPageTitle());
        $this->view->set('csrfToken', csrf_generate_token('language'));

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
        $messages = [
            'languageCreated' => 'Language has been created successfully.',
            'languageUpdated' => 'Language has been updated successfully.',
            'languageDeleted' => 'Language has been deleted successfully.',
            'defaultSet' => 'Default language has been set.',
            'cacheRegenerated' => 'Translation cache has been regenerated.',
        ];

        return $messages[$status] ?? 'Operation completed successfully.';
    }

    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setView('language-form');
            $this->setPageTitle('Add Language');

            $this->view->set('pageTitle', $this->getPageTitle());
            $this->view->set('action', 'create');
            $this->view->set('csrfToken', csrf_generate_token('language'));
            $this->view->set('language', null);

            $this->view->render();
            return;
        }

        try {
            if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {
                throw new ServiceException("Invalid security token");
            }

            $this->languageService->createLanguage([
                'lang_code' => $_POST['lang_code'] ?? '',
                'lang_name' => $_POST['lang_name'] ?? '',
                'lang_native' => $_POST['lang_native'] ?? '',
                'lang_locale' => $_POST['lang_locale'] ?? null,
                'lang_direction' => $_POST['lang_direction'] ?? 'ltr',
                'lang_sort' => (int) ($_POST['lang_sort'] ?? 0),
                'lang_is_default' => isset($_POST['lang_is_default']) ? 1 : 0,
            ]);

            $_SESSION['status'] = 'languageCreated';
            direct_page('index.php?load=languages&status=languageCreated', 302);
        } catch (Throwable $th) {
            $_SESSION['error'] = $th->getMessage();
            direct_page('index.php?load=languages&error=createFailed', 302);
        }
    }

    public function edit(int $id): void
    {
        $language = $this->languageService->getLanguageById($id);
        if (!$language) {
            direct_page('index.php?load=languages&error=notFound', 404);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setView('language-form');
            $this->setPageTitle('Edit Language');

            $this->view->set('language', $language);
            $this->view->set('pageTitle', $this->getPageTitle());
            $this->view->set('action', 'edit');
            $this->view->set('csrfToken', csrf_generate_token('language'));

            $this->view->render();
            return;
        }

        try {
            if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {
                throw new ServiceException("Invalid security token");
            }

            $this->languageService->updateLanguage($id, [
                'lang_code' => $_POST['lang_code'] ?? $language['lang_code'],
                'lang_name' => $_POST['lang_name'] ?? $language['lang_name'],
                'lang_native' => $_POST['lang_native'] ?? $language['lang_native'],
                'lang_locale' => $_POST['lang_locale'] ?? null,
                'lang_direction' => $_POST['lang_direction'] ?? 'ltr',
                'lang_sort' => (int) ($_POST['lang_sort'] ?? 0),
                'lang_is_default' => isset($_POST['lang_is_default']) ? 1 : 0,
                'lang_is_active' => isset($_POST['lang_is_active']) ? 1 : 0,
            ]);

            $_SESSION['status'] = 'languageUpdated';
            direct_page('index.php?load=languages&status=languageUpdated', 302);
        } catch (Throwable $th) {
            $_SESSION['error'] = $th->getMessage();
            direct_page('index.php?load=languages&error=updateFailed', 302);
        }
    }

    public function delete(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            direct_page('index.php?load=languages', 302);
            return;
        }

        try {
            if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {
                throw new ServiceException("Invalid security token");
            }

            $this->languageService->deleteLanguage($id);

            $_SESSION['status'] = 'languageDeleted';
            direct_page('index.php?load=languages&status=languageDeleted', 302);
        } catch (Throwable $th) {
            $_SESSION['error'] = $th->getMessage();
            direct_page('index.php?load=languages&error=deleteFailed', 302);
        }
    }

    public function setDefault(int $id): void
    {
        try {
            $this->languageService->setDefaultLanguage($id);
            $_SESSION['status'] = 'defaultSet';
        } catch (Throwable $th) {
            $_SESSION['error'] = $th->getMessage();
        }

        direct_page('index.php?load=languages', 302);
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
