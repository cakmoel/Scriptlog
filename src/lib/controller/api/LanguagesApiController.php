<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

class LanguagesApiController extends ApiController
{
    private $languageService;

    public function __construct()
    {
        parent::__construct();
        $this->languageService = new LanguageService();
    }

    public function index($params = []): void
    {
        $this->requiresAuth = false;

        try {
            $languages = $this->languageService->getActiveLanguages();
            ApiResponse::success($languages);
        } catch (\Throwable $e) {
            ApiResponse::error($e->getMessage(), 500, 'FETCH_ERROR');
        }
    }

    public function show($params = []): void
    {
        $this->requiresAuth = false;

        $code = isset($params[0]) ? $params[0] : '';

        if (empty($code)) {
            ApiResponse::badRequest('Language code is required');
            return;
        }

        try {
            $language = $this->languageService->getLanguageByCode($code);

            if (!$language) {
                ApiResponse::notFound('Language not found');
                return;
            }

            ApiResponse::success($language);
        } catch (\Throwable $e) {
            ApiResponse::error($e->getMessage(), 500, 'FETCH_ERROR');
        }
    }

    public function default($params = []): void
    {
        $this->requiresAuth = false;

        try {
            $language = $this->languageService->getDefaultLanguage();

            if (!$language) {
                ApiResponse::notFound('Default language not found');
                return;
            }

            ApiResponse::success($language);
        } catch (\Throwable $e) {
            ApiResponse::error($e->getMessage(), 500, 'FETCH_ERROR');
        }
    }

    public function store($params = []): void
    {
        $this->requiresAuth = true;

        if (!$this->hasPermission(['administrator'])) {
            ApiResponse::forbidden('Permission denied');
            return;
        }

        $validationErrors = $this->validateRequired($this->requestData, ['lang_code', 'lang_name', 'lang_native']);

        if ($validationErrors) {
            ApiResponse::unprocessableEntity('Validation failed', $validationErrors);
            return;
        }

        try {
            $id = $this->languageService->createLanguage([
                'lang_code' => $this->requestData['lang_code'],
                'lang_name' => $this->requestData['lang_name'],
                'lang_native' => $this->requestData['lang_native'],
                'lang_locale' => $this->requestData['lang_locale'] ?? null,
                'lang_direction' => $this->requestData['lang_direction'] ?? 'ltr',
                'lang_sort' => $this->requestData['lang_sort'] ?? 0,
                'lang_is_default' => !empty($this->requestData['lang_is_default']) ? 1 : 0,
            ]);

            ApiResponse::created(['id' => $id], 'Language created successfully');
        } catch (\Throwable $e) {
            ApiResponse::error($e->getMessage(), 500, 'CREATE_ERROR');
        }
    }

    public function update($params = []): void
    {
        $this->requiresAuth = true;

        if (!$this->hasPermission(['administrator'])) {
            ApiResponse::forbidden('Permission denied');
            return;
        }

        $code = isset($params[0]) ? $params[0] : '';

        if (empty($code)) {
            ApiResponse::badRequest('Language code is required');
            return;
        }

        $language = $this->languageService->getLanguageByCode($code);

        if (!$language) {
            ApiResponse::notFound('Language not found');
            return;
        }

        try {
            $this->languageService->updateLanguage($language['ID'], $this->requestData);
            ApiResponse::success(['id' => $language['ID']], 'Language updated successfully');
        } catch (\Throwable $e) {
            ApiResponse::error($e->getMessage(), 500, 'UPDATE_ERROR');
        }
    }

    public function destroy($params = []): void
    {
        $this->requiresAuth = true;

        if (!$this->hasPermission(['administrator'])) {
            ApiResponse::forbidden('Permission denied');
            return;
        }

        $code = isset($params[0]) ? $params[0] : '';

        if (empty($code)) {
            ApiResponse::badRequest('Language code is required');
            return;
        }

        $language = $this->languageService->getLanguageByCode($code);

        if (!$language) {
            ApiResponse::notFound('Language not found');
            return;
        }

        try {
            $this->languageService->deleteLanguage($language['ID']);
            ApiResponse::success(null, 'Language deleted successfully');
        } catch (\Throwable $e) {
            ApiResponse::error($e->getMessage(), 500, 'DELETE_ERROR');
        }
    }

    public function setDefault($params = []): void
    {
        $this->requiresAuth = true;

        if (!$this->hasPermission(['administrator'])) {
            ApiResponse::forbidden('Permission denied');
            return;
        }

        $code = isset($params[0]) ? $params[0] : '';

        if (empty($code)) {
            ApiResponse::badRequest('Language code is required');
            return;
        }

        $language = $this->languageService->getLanguageByCode($code);

        if (!$language) {
            ApiResponse::notFound('Language not found');
            return;
        }

        try {
            $this->languageService->setDefaultLanguage($language['ID']);
            ApiResponse::success(['id' => $language['ID']], 'Default language set successfully');
        } catch (\Throwable $e) {
            ApiResponse::error($e->getMessage(), 500, 'UPDATE_ERROR');
        }
    }
}
