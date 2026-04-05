<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * ImportController
 *
 * Controller for handling import operations from admin panel
 *
 * @category  Controller Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class ImportController extends BaseApp
{
    private $migrationService;
    private $userDao;
    private $view;

    private $error = [];
    private $success = [];

    public function __construct(MigrationService $migrationService)
    {
        $this->migrationService = $migrationService;
        $this->userDao = new UserDao();
    }

    /**
     * List items - not used for import
     *
     * @return string
     */
    protected function listItems()
    {
        return $this->index();
    }

    /**
     * Insert - not used for import
     *
     * @return string
     */
    public function insert()
    {
        return $this->index();
    }

    /**
     * Update - not used for import
     *
     * @param int $id
     * @return string
     */
    public function update($id = null)
    {
        return $this->index();
    }

    /**
     * Remove - not used for import
     *
     * @param int $id
     * @return string
     */
    public function remove($id = null)
    {
        return $this->index();
    }

    /**
     * Render import page
     *
     * @return string
     */
    public function index()
    {
        $this->setView('import');
        $this->setPageTitle('Import Content');
        $this->view = new View('admin', 'ui', 'import', 'index');

        $users = $this->userDao->getUsers('ID', PDO::FETCH_ASSOC);

        $this->view->set('pageTitle', $this->getPageTitle());
        $this->view->set('errors', $this->error);
        $this->view->set('success', $this->success);
        $this->view->set('users', $users);
        $this->view->set('csrfToken', generate_token());

        return $this->view->render();
    }

    /**
     * Handle import request
     *
     * @return string
     */
    public function import()
    {
        if (!isset($_POST['importSubmit'])) {
            return $this->index();
        }

        if (!isset($_POST['csrf_token']) || !csrf_check_token('csrfToken', $_POST)) {
            $this->error[] = 'Invalid security token. Please try again.';
            return $this->index();
        }

        if (!isset($_POST['source']) || empty($_POST['source'])) {
            $this->error[] = 'Please select a source platform.';
            return $this->index();
        }

        $source = $_POST['source'];

        if (!in_array($source, ['wordpress', 'ghost', 'blogspot', 'scriptlog'])) {
            $this->error[] = 'Invalid source platform selected.';
            return $this->index();
        }

        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            $this->error[] = 'Please upload a valid import file.';
            return $this->index();
        }

        $file = $_FILES['import_file'];
        $filename = $file['name'];
        $tmpFile = $file['tmp_name'];

        $allowedExtensions = ['xml', 'json'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions)) {
            $this->error[] = 'Invalid file type. Please upload an XML or JSON file.';
            return $this->index();
        }

        $content = file_get_contents($tmpFile);

        if (empty($content)) {
            $this->error[] = 'The uploaded file is empty or could not be read.';
            return $this->index();
        }

        $authorId = isset($_POST['author_id']) ? (int) $_POST['author_id'] : $this->getCurrentAuthorId();
        $this->migrationService->setAuthorId($authorId);

        try {
            switch ($source) {
                case 'wordpress':
                    $result = $this->migrationService->importFromWordPress($content);
                    break;
                case 'ghost':
                    $result = $this->migrationService->importFromGhost($content);
                    break;
                case 'blogspot':
                    $result = $this->migrationService->importFromBlogspot($content);
                    break;
                case 'scriptlog':
                    $result = $this->migrationService->importFromScriptlog($content);
                    break;
                default:
                    $result = ['success' => false, 'error' => 'Unknown source'];
            }

            if ($result['success']) {
                $stats = $result['stats'];

                $message = "Import completed successfully!\n\n";
                $message .= "Posts imported: " . $stats['posts_created'] . "\n";
                $message .= "Pages imported: " . $stats['pages_created'] . "\n";
                $message .= "Categories created: " . $stats['categories_created'] . "\n";
                $message .= "Comments imported: " . $stats['comments_created'] . "\n";

                if ($stats['posts_skipped'] > 0) {
                    $message .= "Posts skipped (duplicates): " . $stats['posts_skipped'] . "\n";
                }

                if (!empty($stats['errors'])) {
                    $message .= "\nErrors:\n" . implode("\n", array_slice($stats['errors'], 0, 5));
                    if (count($stats['errors']) > 5) {
                        $message .= "\n... and " . (count($stats['errors']) - 5) . " more errors";
                    }
                }

                $this->success[] = $message;
            } else {
                $this->error[] = $result['error'] ?? 'Import failed with unknown error.';
            }
        } catch (\Throwable $e) {
            $this->error[] = 'Import error: ' . $e->getMessage();
        }

        return $this->index();
    }

    /**
     * Preview import data
     *
     * @return string
     */
    public function preview()
    {
        if (!isset($_POST['previewSubmit'])) {
            return $this->index();
        }

        if (!isset($_POST['csrf_token']) || !csrf_check_token('csrfToken', $_POST)) {
            $this->error[] = 'Invalid security token. Please try again.';
            return $this->index();
        }

        if (!isset($_POST['source']) || empty($_POST['source'])) {
            $this->error[] = 'Please select a source platform.';
            return $this->index();
        }

        $source = $_POST['source'];

        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            $this->error[] = 'Please upload a valid import file.';
            return $this->index();
        }

        $file = $_FILES['import_file'];
        $content = file_get_contents($file['tmp_name']);

        try {
            $preview = $this->migrationService->previewImport($content, $source);

            if ($preview['success']) {
                $this->view = new View('admin', 'ui', 'import', 'preview');
                $this->setPageTitle('Import Preview');

                $this->view->set('pageTitle', $this->getPageTitle());
                $this->view->set('preview', $preview);
                $this->view->set('source', $source);

                return $this->view->render();
            } else {
                $this->error[] = $preview['error'];
            }
        } catch (\Throwable $e) {
            $this->error[] = 'Preview error: ' . $e->getMessage();
        }

        return $this->index();
    }

    /**
     * Set view
     *
     * @param string $viewName
     */
    protected function setView($viewName)
    {
        // Views are handled inline
    }

    /**
     * Get current author ID from session
     *
     * @return int
     */
    private function getCurrentAuthorId()
    {
        if (isset(Session::getInstance()->scriptlog_session_id)) {
            return (int) Session::getInstance()->scriptlog_session_id;
        }

        return 1;
    }
}
