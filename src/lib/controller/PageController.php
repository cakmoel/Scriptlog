<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class PageController
 *
 * @category  Class PageController extends BaseApp
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class PageController extends BaseApp
{
    /**
     * view
     *
     * @var object
     *
     */
    private $view;

    /**
     * pageService
     *
     * @var object
     *
     */
    private $pageService;

    public function __construct(PageService $pageService)
    {
        $this->pageService = $pageService;
    }

    /**
     * listItems()
     *
     * @return mixed
     *
     */
    public function listItems()
    {

        $errors = array();
        $status = array();
        $checkError = true;
        $checkStatus = false;

        if (isset($_SESSION['error'])) {
            $checkError = false;
            ($_SESSION['error'] == 'pageNotFound') ? array_push($errors, "Error: Page Not Found") : "";
            unset($_SESSION['error']);
        }

        if (isset($_SESSION['status'])) {
            $checkStatus = true;
            ($_SESSION['status'] == 'pageAdded') ? array_push($status, "New page added") : "";
            ($_SESSION['status'] == 'pageUpdated') ? array_push($status, "Page has been updated") : "";
            ($_SESSION['status'] == 'pageDeleted') ? array_push($status, "Page deleted") : "";
            unset($_SESSION['status']);
        }

        $this->setView('all-pages');
        $this->setPageTitle('Pages');
        $this->view->set('pageTitle', $this->getPageTitle());

        if (!$checkError) {
            $this->view->set('errors', $errors);
        }

        if ($checkStatus) {
            $this->view->set('status', $status);
        }

        $this->view->set('pagesTotal', $this->pageService->totalPages());
        $this->view->set('pages', $this->pageService->grabPages('page'));
        return $this->view->render();
    }

    /**
     * insert
     *
     * insert new page record
     *
     */
    public function insert()
    {

        $medialib = new MediaDao();
        $errors = array();
        $checkError = true;
        $user_level = $this->pageService->pageAuthorLevel();

        if (isset($_POST['pageFormSubmit'])) {
            $filters = [
              'post_title' => isset($_POST['post_title']) ? Sanitize::severeSanitizer($_POST['post_title']) : "",
              'post_content' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
              'post_date' => isset($_POST['post_date']) ? Sanitize::mildSanitizer($_POST['post_date']) : "",
              'image_id' => FILTER_SANITIZE_NUMBER_INT,
              'post_summary' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
              'post_status' => isset($_POST['post_status']) ? Sanitize::mildSanitizer($_POST['post_status']) : "",
              'post_sticky' => FILTER_SANITIZE_NUMBER_INT,
              'post_locale' => isset($_POST['post_locale']) ? Sanitize::mildSanitizer($_POST['post_locale']) : "en"
            ];

            $form_fields = ['post_title' => 200, 'post_summary' => 320, 'post_content' => 50000];

            try {
                if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
                    header("Status: 400 Bad Request");
                    throw new AppException("Sorry, unpleasant attempt detected!");
                }

                if (check_form_request($_POST, ['page_id', 'post_title', 'post_content', 'post_date', 'image_id', 'post_summary', 'post_status', 'post_sticky']) == false) {
                    header($_SERVER["SERVER_PROTOCOL"] . ' 413 Payload Too Large', true, 413);
                    header('Status: 413 Payload Too Large');
                    header('Retry-After: 3600');
                    throw new AppException("Sorry, Unpleasant attempt detected");
                }

                if ((empty($_POST['post_title'])) || (empty($_POST['post_content']))) {
                    $checkError = false;
                    array_push($errors, "Please enter a required field");
                }

                if (true === form_size_validation($form_fields)) {
                    $checkError = false;
                    array_push($errors, "Form data is longer than allowed");
                }

                if (false === sanitize_selection_box(distill_post_request($filters)['post_status'], ['publish' => 'Publish', 'draft' => 'Draft'])) {
                    $checkError = false;
                    array_push($errors, "Please choose the available value provided");
                }

                if (!empty($_POST['post_date']) && validate_date($_POST['post_date']) === false) {
                    $checkError = false;
                    array_push($errors, "Please fix your date format");
                }

                if (!$checkError) {
                    $this->setView('edit-page');
                    $this->setPageTitle('Add New Page');
                    $this->setFormAction(ActionConst::NEWPAGE);
                    $this->view->set('pageTitle', $this->getPageTitle());
                    $this->view->set('formAction', $this->getFormAction());
                    $this->view->set('errors', $errors);
                    $this->view->set('formData', $_POST);
                    $this->view->set('medialibs', $medialib->imageRadioButton());
                    $this->view->set('postStatus', $this->pageService->postStatusDropDown());
                    $this->view->set('pageLocale', $this->pageService->localeDropDown(distill_post_request($filters)['post_locale']));
                    $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
                } else {
                    list($width, $height) = (empty($_POST['image'])) ? getimagesize(app_url() . '/public/files/pictures/nophoto.jpg') : "";

                    $media_access = (isset($_POST['post_status']) && ($_POST['post_status'] === 'publish')) ? 'public' : 'private';

                    if (empty($_POST['image_id'])) {
                        clearstatcache();

                        $media_metavalue = array(
                          'Origin' => "nophoto.jpg",
                          'File type' => "image/jpg",
                          'File size' => format_size_unit(filesize(__DIR__ . '/../../' . APP_IMAGE . "nophoto.jpg")),
                          'Uploaded at' => date("Y-m-d H:i:s"),
                          'Dimension' => $width . 'x' . $height
                        );

                        $bind_media = [
                          'media_filename' => "nophoto.jpg",
                          'media_caption' => prevent_injection(distill_post_request($filters)['post_title']),
                          'media_type' => "image/jpg",
                          'media_target' => 'page',
                          'media_user' => $user_level,
                          'media_access' => $media_access,
                          'media_status' => '1'
                        ];

                        $append_media = $medialib->createMedia($bind_media);

                        $mediameta = [
                          'media_id' => $append_media,
                          'meta_key' => "nophoto.jpg",
                          'meta_value' => json_encode($media_metavalue)
                        ];

                        $medialib->createMediaMeta($mediameta);

                        $this->pageService->setPageImage($append_media);
                    } else {
                        if ((isset($_POST['image_id'])) && (!empty($_POST['image_id']))) {
                            $this->pageService->setPageImage((int)distill_post_request($filters)['image_id']);
                        }
                    }

                    $this->pageService->setPageAuthor((int)$this->pageService->pageAuthorId());
                    $this->pageService->setPageLocale(distill_post_request($filters)['post_locale']);

                    if (empty($_POST['post_date'])) {
                        $this->pageService->setPostDate(date("Y-m-d H:i:s"));
                    } else {
                        $this->pageService->setPostDate(distill_post_request($filters)['post_date']);
                    }

                    $this->pageService->setPageTitle(distill_post_request($filters)['post_title']);
                    $this->pageService->setPageSlug(distill_post_request($filters)['post_title']);
                    $this->pageService->setPageContent(distill_post_request($filters)['post_content']);
                    $this->pageService->setMetaDesc(distill_post_request($filters)['post_summary']);
                    $this->pageService->setPublish(distill_post_request($filters)['post_status']);
                    $this->pageService->setComment('close');
                    $this->pageService->setPostType('page');

                    if (empty($_POST['post_sticky'])) {
                        $this->pageService->setSticky(0);
                    } else {
                        $this->pageService->setSticky(distill_post_request($filters)['post_sticky']);
                    }

                    $this->pageService->addPage();
                    $_SESSION['status'] = "pageAdded";
                    direct_page('index.php?load=pages&status=pageAdded', 200);
                }
            } catch (\Throwable $th) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($th);
            } catch (AppException $e) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($e);
            }
        } else {
            $this->setView('edit-page');
            $this->setPageTitle('Add New Page');
            $this->setFormAction(ActionConst::NEWPAGE);
            $this->view->set('pageTitle', $this->getPageTitle());
            $this->view->set('formAction', $this->getFormAction());
            $this->view->set('medialibs', $medialib->imageRadioButton());
            $this->view->set('postStatus', $this->pageService->postStatusDropDown());
            $this->view->set('pageLocale', $this->pageService->localeDropDown());
            $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
        }

        return $this->view->render();
    }

    /**
     * update()
     *
     * update an existing record based on it's ID
     *
     * @param int|num $id
     *
     */
    public function update($id)
    {

        $medialib = new MediaDao();
        $errors = array();
        $checkError = true;
        $user_level = $this->pageService->pageAuthorLevel();

        if (!$getPage = $this->pageService->grabPage($id)) {
            $_SESSION['error'] = "pageNotFound";
            direct_page('index.php?load=pages&error=pageNotFound', 404);
        }

        $data_page = array(
          'ID' => $getPage['ID'],
          'media_id' => $getPage['media_id'],
          'post_date' => $getPage['post_date'],
          'post_modified' => $getPage['post_modified'],
          'post_title' => $getPage['post_title'],
          'post_content' => $getPage['post_content'],
          'post_summary' => $getPage['post_summary'],
          'post_status' => $getPage['post_status'],
          'post_sticky' => $getPage['post_sticky'],
          'post_locale' => $getPage['post_locale']
        );

        if (isset($_POST['pageFormSubmit'])) {
            $filters = [
              'page_id' => FILTER_SANITIZE_NUMBER_INT,
              'image_id' => FILTER_SANITIZE_NUMBER_INT,
              'post_title' => isset($_POST['post_title']) ? Sanitize::severeSanitizer($_POST['post_title']) : "",
              'post_content' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
              'post_modified' => isset($_POST['post_modified']) ? Sanitize::mildSanitizer($_POST['post_modified']) : "",
              'post_summary' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
              'post_status' => isset($_POST['post_status']) ? Sanitize::mildSanitizer($_POST['post_status']) : "",
              'post_sticky' => FILTER_SANITIZE_NUMBER_INT,
              'post_locale' => isset($_POST['post_locale']) ? Sanitize::mildSanitizer($_POST['post_locale']) : "en"
            ];

            $form_fields = ['post_title' => 200, 'post_summary' => 320, 'post_content' => 50000];

            try {
                if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
                    header("Status: 400 Bad Request");
                    throw new AppException("Sorry, unpleasant attempt detected!");
                }

                if (check_form_request($_POST, ['page_id', 'post_title', 'post_content', 'post_date', 'image_id', 'post_summary', 'post_status', 'post_sticky']) === false) {
                    header(APP_PROTOCOL . ' 413 Payload Too Large', true, 413);
                    header('Status: 413 Payload Too Large');
                    header('Retry-After: 3600');
                    throw new AppException("Sorry, Unpleasant attempt detected");
                }

                if ((empty($_POST['post_title'])) || (empty($_POST['post_content']))) {
                    $checkError = false;
                    array_push($errors, "Please enter a required field");
                }

                if (true === form_size_validation($form_fields)) {
                    $checkError = false;
                    array_push($errors, "Form data is longer than allowed");
                }

                if (false === sanitize_selection_box(distill_post_request($filters)['post_status'], ['publish' => 'Publish', 'draft' => 'Draft'])) {
                    $checkError = false;
                    array_push($errors, "Please choose the available value provided");
                }

                if (!empty($_POST['post_modfied']) && validate_date($_POST['post_modified']) === false) {
                    $checkError = false;
                    array_push($errors, "Please fix your date format");
                }

                if (!$checkError) {
                    $this->setView('edit-page');
                    $this->setPageTitle('Edit Page');
                    $this->setFormAction(ActionConst::EDITPAGE);
                    $this->view->set('pageTitle', $this->getPageTitle());
                    $this->view->set('formAction', $this->getFormAction());
                    $this->view->set('errors', $errors);
                    $this->view->set('pageData', $data_page);
                    $this->view->set('medialibs', $medialib->imageRadioButton($getPage['media_id']));
                    $this->view->set('postStatus', $this->pageService->postStatusDropDown($getPage['post_status']));
                    $this->view->set('pageLocale', $this->pageService->localeDropDown(distill_post_request($filters)['post_locale']));
                    $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
                } else {
                    list($width, $height) = (empty($_POST['image'])) ? getimagesize(app_url() . '/public/files/pictures/nophoto.jpg') : "";

                    $media_access = (isset($_POST['post_status']) && ($_POST['post_status'] === 'publish')) ? 'public' : 'private';

                    if ((isset($getPage['media_id'])) && ($getPage['media_id'] === 0)) {
                        clearstatcache();

                        $media_metavalue = array(
                          'Origin' => "nophoto.jpg",
                          'File type' => "image/jpg",
                          'File size' => format_size_unit(filesize(__DIR__ . '/../../' . APP_IMAGE . "nophoto.jpg")),
                          'Uploaded at' => date("Y-m-d H:i:s"),
                          'Dimension' => $width . 'x' . $height
                        );

                        $bind_media = [
                          'media_filename' => "nophoto.jpg",
                          'media_caption' => prevent_injection(distill_post_request($filters)['post_title']),
                          'media_type' => "image/jpg",
                          'media_target' => 'page',
                          'media_user' => $user_level,
                          'media_access' => $media_access,
                          'media_status' => '1'
                        ];

                        $append_media = $medialib->createMedia($bind_media);

                        $mediameta = [
                          'media_id' => $append_media,
                          'meta_key' => "nophoto.jpg",
                          'meta_value' => json_encode($media_metavalue)
                        ];

                        $medialib->createMediaMeta($mediameta);

                        $this->pageService->setPageImage($append_media);
                    } else {
                        if ((isset($_POST['image_id'])) && (!empty($_POST['image_id']))) {
                            $this->pageService->setPageImage((int)distill_post_request($filters)['image_id']);
                        }
                    }

                    $this->pageService->setPageId((int)distill_post_request($filters)['page_id']);

                    if (empty($_POST['post_modified'])) {
                        $this->pageService->setPostModified(date("Y-m-d H:i:s"));
                    } else {
                        $this->pageService->setPostModified(distill_post_request($filters)['post_modified']);
                    }

                    $this->pageService->setPageAuthor((int)$this->pageService->pageAuthorId());
                    $this->pageService->setPageTitle(distill_post_request($filters)['post_title']);
                    $this->pageService->setPageSlug(distill_post_request($filters)['post_title']);
                    $this->pageService->setPageContent(distill_post_request($filters)['post_content']);
                    $this->pageService->setMetaDesc(distill_post_request($filters)['post_summary']);
                    $this->pageService->setPublish(distill_post_request($filters)['post_status']);
                    $this->pageService->setPostType('page');
                    $this->pageService->setPageLocale(distill_post_request($filters)['post_locale']);

                    if (empty($_POST['post_sticky'])) {
                        $this->pageService->setSticky(0);
                    } else {
                        $this->pageService->setSticky(distill_post_request($filters)['post_sticky']);
                    }

                    $this->pageService->modifyPage();
                    $_SESSION['status'] = "pageUpdated";
                    direct_page('index.php?load=pages&status=pageUpdated', 302);
                }
            } catch (\Throwable $th) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($th);
            } catch (AppException $e) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($e);
            }
        } else {
            $this->setView('edit-page');
            $this->setPageTitle('Edit Page');
            $this->setFormAction(ActionConst::EDITPAGE);
            $this->view->set('pageTitle', $this->getPageTitle());
            $this->view->set('formAction', $this->getFormAction());
            $this->view->set('pageData', $data_page);
            $this->view->set('medialibs', $medialib->imageRadioButton($getPage['media_id']));
            $this->view->set('postStatus', $this->pageService->postStatusDropDown($getPage['post_status']));
            $this->view->set('pageLocale', $this->pageService->localeDropDown($data_page['post_locale'] ?? 'en'));
            $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
        }

        return $this->view->render();
    }

    /**
     * remove()
     *
     * remove existing record based on it's ID
     *
     * @param int|numeric $id
     *
     */
    public function remove($id)
    {

        $checkError = true;
        $errors = array();

        if (isset($_GET['Id'])) {
            $getPage = $this->pageService->grabPage($id, 'page');

            try {
                if (!filter_input(INPUT_GET, 'Id', FILTER_SANITIZE_NUMBER_INT)) {
                    header($_SERVER["SERVER_PROTOCOL"] . MESSAGE_BADREQUEST, true, 400);
                    header("Status: 400 Bad Request");
                    throw new AppException(MESSAGE_UNPLEASANT_ATTEMPT);
                }

                if (!filter_var($id, FILTER_VALIDATE_INT)) {
                    header($_SERVER["SERVER_PROTOCOL"] . MESSAGE_BADREQUEST, true, 400);
                    header("Status: 400 Bad Request");
                    throw new AppException(MESSAGE_UNPLEASANT_ATTEMPT);
                }

                if (!$getPage) {
                    $checkError = false;
                    array_push($errors, 'Error: Page not found');
                }

                if (!$checkError) {
                    $this->setView('all-pages');
                    $this->setPageTitle('Page not found');
                    $this->view->set('pageTitle', $this->getPageTitle());
                    $this->view->set('errors', $errors);
                    $this->view->set('pagesTotal', $this->pageService->totalPages());
                    $this->view->set('pages', $this->pageService->grabPages('page'));
                    return $this->view->render();
                } else {
                    $this->pageService->setPageId($id);
                    $this->pageService->removePage();
                    $_SESSION['status'] = "pageDeleted";
                    direct_page('index.php?load=pages&status=pageDeleted', 302);
                }
            } catch (Throwable $th) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($th);
            } catch (AppException $e) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($e);
            }
        }
    }

    /**
     * setView
     *
     * @param string $viewName
     *
     */
    protected function setView($viewName)
    {
        $this->view = new View('admin', 'ui', 'pages', $viewName);
    }
}
