<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * ExportController
 *
 * Controller for handling export operations from admin panel
 *
 * @category  Controller Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class ExportController extends BaseApp
{
    private $exportService;
    private $userDao;
    private $view;

    private $error = [];
    private $success = [];

    public function __construct(ExportService $exportService)
    {
        $this->exportService = $exportService;
        $this->userDao = new UserDao();
    }

    /**
     * List items - not used for export
     *
     * @return string
     */
    protected function listItems()
    {
        return $this->index();
    }

    /**
     * Insert - not used for export
     *
     * @return string
     */
    public function insert()
    {
        return $this->index();
    }

    /**
     * Update - not used for export
     *
     * @param int $id
     * @return string
     */
    public function update($id = null)
    {
        return $this->index();
    }

    /**
     * Remove - not used for export
     *
     * @param int $id
     * @return string
     */
    public function remove($id = null)
    {
        return $this->index();
    }

    /**
     * Render export page
     *
     * @return string
     */
    public function index()
    {
        $this->setView('export');
        $this->setPageTitle('Export Content');
        $this->view = new View('admin', 'ui', 'export', 'index');

        $users = $this->userDao->getUsers('ID', PDO::FETCH_ASSOC);

        $this->view->set('pageTitle', $this->getPageTitle());
        $this->view->set('errors', $this->error);
        $this->view->set('success', $this->success);
        $this->view->set('users', $users);
        $this->view->set('csrfToken', generate_token());

        return $this->view->render();
    }

    /**
     * Handle export request
     *
     * @return string
     */
    public function export()
    {
        if (!isset($_POST['exportSubmit'])) {
            return $this->index();
        }

        if (!isset($_POST['csrf_token']) || !csrf_check_token('csrfToken', $_POST)) {
            $this->error[] = 'Invalid security token. Please try again.';
            return $this->index();
        }

        if (!isset($_POST['destination']) || empty($_POST['destination'])) {
            $this->error[] = 'Please select a destination platform.';
            return $this->index();
        }

        $destination = $_POST['destination'];

        if (!in_array($destination, ['wordpress', 'ghost', 'blogspot', 'scriptlog'])) {
            $this->error[] = 'Invalid destination platform selected.';
            return $this->index();
        }

        $authorId = isset($_POST['author_id']) ? (int) $_POST['author_id'] : $this->getCurrentAuthorId();
        $this->exportService->setAuthorId($authorId);

        try {
            switch ($destination) {
                case 'wordpress':
                    $result = $this->exportService->exportToWordPress();
                    break;
                case 'ghost':
                    $result = $this->exportService->exportToGhost();
                    break;
                case 'blogspot':
                    $result = $this->exportService->exportToBlogspot();
                    break;
                case 'scriptlog':
                    $result = $this->exportService->exportToScriptlog();
                    break;
                default:
                    $result = ['success' => false, 'error' => 'Unknown destination'];
            }

            if ($result['success']) {
                $stats = $result['stats'];

                $message = "Export completed successfully!\n\n";
                $message .= "Posts exported: " . $stats['posts_exported'] . "\n";
                $message .= "Pages exported: " . $stats['pages_exported'] . "\n";
                $message .= "Categories exported: " . $stats['categories_exported'] . "\n";
                $message .= "Comments exported: " . $stats['comments_exported'] . "\n";

                $this->success[] = $message;

                // Force download of the exported file
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
                header('Content-Description: File Transfer');
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . strlen($result['content']));
                echo $result['content'];
                exit();
            } else {
                $this->error[] = $result['error'] ?? 'Export failed with unknown error.';
            }
        } catch (\Throwable $e) {
            $this->error[] = 'Export error: ' . $e->getMessage();
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
