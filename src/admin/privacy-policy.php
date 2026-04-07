<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$policyId = isset($_GET['Id']) ? intval($_GET['Id']) : 0;

if (false === $app->authenticator->userAccessControl(ActionConst::PRIVACY)) {
    direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
}

$privacyPolicyDao = class_exists('PrivacyPolicyDao') ? new PrivacyPolicyDao() : null;
$errors = [];
$status = [];

try {
    switch ($action) {
        case 'new-policy':
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && $privacyPolicyDao !== null) {
                $locale = isset($_POST['locale']) ? strip_tags($_POST['locale']) : 'en';
                $policyTitle = isset($_POST['policy_title']) ? trim($_POST['policy_title']) : '';
                $policyContent = isset($_POST['policy_content']) ? $_POST['policy_content'] : '';
                $isDefault = isset($_POST['is_default']) ? 1 : 0;

                if (empty($policyTitle)) {
                    $errors[] = "Policy title is required.";
                }

                if (empty($policyContent)) {
                    $errors[] = "Policy content is required.";
                }

                if (empty($errors)) {
                    if ($isDefault) {
                        $privacyPolicyDao->clearDefaultPolicy();
                    }

                    $privacyPolicyDao->createPolicy([
                        'locale' => $locale,
                        'policy_title' => $policyTitle,
                        'policy_content' => $policyContent,
                        'is_default' => $isDefault
                    ]);

                    $_SESSION['status'] = 'policyCreated';
                    direct_page('index.php?load=privacy-policy', 302);
                }
            }
            break;

        case 'edit-policy':
            if ($privacyPolicyDao !== null && $policyId > 0) {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $policyTitle = isset($_POST['policy_title']) ? trim($_POST['policy_title']) : '';
                    $policyContent = isset($_POST['policy_content']) ? $_POST['policy_content'] : '';
                    $isDefault = isset($_POST['is_default']) ? 1 : 0;

                    if (empty($policyTitle)) {
                        $errors[] = "Policy title is required.";
                    }

                    if (empty($policyContent)) {
                        $errors[] = "Policy content is required.";
                    }

                    if (empty($errors)) {
                        if ($isDefault) {
                            $privacyPolicyDao->clearDefaultPolicy();
                        }

                        $privacyPolicyDao->updatePolicy((int)$policyId, [
                            'policy_title' => $policyTitle,
                            'policy_content' => $policyContent,
                            'is_default' => $isDefault
                        ]);

                        $_SESSION['status'] = 'policyUpdated';
                        direct_page('index.php?load=privacy-policy', 302);
                    }
                }

                $policy = $privacyPolicyDao->findById((int)$policyId);
                if (!$policy) {
                    direct_page('index.php?load=privacy-policy', 302);
                }
            }
            break;

        case 'delete-policy':
            if ($privacyPolicyDao !== null && $policyId > 0) {
                $privacyPolicyDao->deletePolicy((int)$policyId);
                $_SESSION['status'] = 'policyDeleted';
                direct_page('index.php?load=privacy-policy', 302);
            }
            break;

        case 'setDefault':
            if ($privacyPolicyDao !== null && $policyId > 0) {
                $privacyPolicyDao->clearDefaultPolicy();
                $privacyPolicyDao->setAsDefaultPolicy((int)$policyId);
                $_SESSION['status'] = 'policyDefaultSet';
                direct_page('index.php?load=privacy-policy', 302);
            }
            break;

        default:
            break;
    }
} catch (Throwable $th) {
    $errors[] = $th->getMessage();
}

$languages = [];
if (class_exists('LanguageDao')) {
    $languageDao = new LanguageDao();
    $languages = $languageDao->findActiveLanguages();
}

$policies = [];
if ($privacyPolicyDao !== null) {
    $policies = $privacyPolicyDao->fetchAll();
}

$pageTitle = admin_translate('nav.privacy_policy');
$createLink = generate_request('index.php', 'get', ['privacy-policy', 'new-policy', 0], false)['link'];

$showForm = in_array($action, ['new-policy', 'edit-policy']);

if ($showForm) {
    $policy = null;
    if ($action === 'edit-policy' && $policyId > 0 && $privacyPolicyDao !== null) {
        $policy = $privacyPolicyDao->findById((int)$policyId);
        if (!$policy) {
            direct_page('index.php?load=privacy-policy', 302);
        }
    }
    include dirname(__FILE__) . DS . 'ui' . DS . 'privacy' . DS . 'privacy-policy-form.php';
} else {
    include dirname(__FILE__) . DS . 'ui' . DS . 'privacy' . DS . 'privacy-policy-editor.php';
}
