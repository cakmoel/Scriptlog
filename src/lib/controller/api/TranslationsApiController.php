<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

class TranslationsApiController extends ApiController
{
    private $translationService;
    private $languageService;

    public function __construct()
    {
        parent::__construct();
        $this->translationService = new TranslationService();
        $this->languageService = new LanguageService();
    }

    public function index($params = []): void
    {
        $this->requiresAuth = false;

        $langCode = isset($params[0]) ? $params[0] : 'en';

        try {
            $translations = $this->translationService->getTranslations($langCode);
            ApiResponse::success($translations);
        } catch (\Throwable $e) {
            ApiResponse::error($e->getMessage(), 500, 'FETCH_ERROR');
        }
    }

    public function show($params = []): void
    {
        $this->requiresAuth = false;

        $langCode = isset($params[0]) ? $params[0] : 'en';
        $key = isset($params[1]) ? $params[1] : '';

        if (empty($key)) {
            ApiResponse::badRequest('Translation key is required');
            return;
        }

        try {
            $translations = $this->translationService->getTranslations($langCode);

            foreach ($translations as $t) {
                if ($t['translation_key'] === $key) {
                    ApiResponse::success($t);
                    return;
                }
            }

            ApiResponse::notFound('Translation not found');
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

        $langCode = isset($params[0]) ? $params[0] : '';

        $validationErrors = $this->validateRequired($this->requestData, ['lang_code', 'translation_key', 'translation_value']);

        if ($validationErrors) {
            ApiResponse::unprocessableEntity('Validation failed', $validationErrors);
            return;
        }

        try {
            $id = $this->translationService->createTranslation([
                'lang_code' => $this->requestData['lang_code'],
                'translation_key' => $this->requestData['translation_key'],
                'translation_value' => $this->requestData['translation_value'],
                'translation_context' => $this->requestData['translation_context'] ?? null,
                'is_html' => !empty($this->requestData['is_html']) ? 1 : 0,
            ]);

            ApiResponse::created(['id' => $id], 'Translation created successfully');
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

        $id = isset($params[0]) ? (int)$params[0] : 0;

        if ($id <= 0) {
            ApiResponse::badRequest('Translation ID is required');
            return;
        }

        $validationErrors = $this->validateRequired($this->requestData, ['translation_value']);

        if ($validationErrors) {
            ApiResponse::unprocessableEntity('Validation failed', $validationErrors);
            return;
        }

        try {
            $this->translationService->updateTranslation($id, [
                'translation_value' => $this->requestData['translation_value'],
                'translation_context' => $this->requestData['translation_context'] ?? null,
                'is_html' => !empty($this->requestData['is_html']) ? 1 : 0,
            ]);

            ApiResponse::success(['id' => $id], 'Translation updated successfully');
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

        $id = isset($params[0]) ? (int)$params[0] : 0;

        if ($id <= 0) {
            ApiResponse::badRequest('Translation ID is required');
            return;
        }

        try {
            $this->translationService->deleteTranslation($id);
            ApiResponse::success(null, 'Translation deleted successfully');
        } catch (\Throwable $e) {
            ApiResponse::error($e->getMessage(), 500, 'DELETE_ERROR');
        }
    }

    public function export($params = []): void
    {
        $this->requiresAuth = false;

        $langCode = isset($params[0]) ? $params[0] : 'en';

        try {
            $translations = $this->translationService->exportToArray($langCode);
            ApiResponse::success($translations);
        } catch (\Throwable $e) {
            ApiResponse::error($e->getMessage(), 500, 'EXPORT_ERROR');
        }
    }

    public function import($params = []): void
    {
        $this->requiresAuth = true;

        if (!$this->hasPermission(['administrator'])) {
            ApiResponse::forbidden('Permission denied');
            return;
        }

        $langCode = isset($params[0]) ? $params[0] : '';

        if (empty($langCode)) {
            ApiResponse::badRequest('Language code is required');
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (!is_array($data)) {
            ApiResponse::badRequest('Invalid JSON data');
            return;
        }

        try {
            $count = $this->translationService->importFromArray($langCode, $data);
            ApiResponse::created(['count' => $count], "Imported {$count} translations successfully");
        } catch (\Throwable $e) {
            ApiResponse::error($e->getMessage(), 500, 'IMPORT_ERROR');
        }
    }

    public function cache($params = []): void
    {
        $this->requiresAuth = true;

        if (!$this->hasPermission(['administrator'])) {
            ApiResponse::forbidden('Permission denied');
            return;
        }

        $langCode = isset($params[0]) ? $params[0] : 'en';

        try {
            $this->translationService->regenerateCache($langCode);
            ApiResponse::success(null, 'Cache regenerated successfully');
        } catch (\Throwable $e) {
            ApiResponse::error($e->getMessage(), 500, 'CACHE_ERROR');
        }
    }
}
