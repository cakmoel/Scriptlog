<?php

namespace Scriptlog\Service;

defined('SCRIPTLOG') || die('Direct access not permitted');

/**
 * Application service resolving the ready-to-print content of a single post.
 *
 * Owns the protected-vs-public render decision and the sanitization pipeline
 * (double html_entity_decode, style-attribute strip, htmLawed whitelist) that
 * previously lived inline in the single.php template. It never queries the
 * database itself: the decrypted content is supplied by an injected decrypt
 * callback (defaulting to the global decrypt_post() helper) so the service
 * stays unit-testable without a live connection.
 *
 * @category Service
 * @package  Scriptlog
 * @author   Scriptlog Team
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 *
 * @psalm-suppress UnusedClass -- consumed by public/themes (single.php), which
 *                 are outside the Psalm scan tree (lib/ only).
 */
class ProtectedPostService
{
    /**
     * Callable responsible for decrypting a protected post's content.
     *
     * @var callable|null
     */
    private $decryptPost;

    /**
     * htmLawed attribute blacklist applied to post content.
     *
     * @var array
     */
    private $denyAttributes = [
        'style', 'onclick', 'onerror', 'onload', 'onmouseover',
        'onfocus', 'onblur', 'onchange', 'onsubmit', 'onkeydown',
        'onkeyup', 'onkeypress'
    ];

    /**
     * Constructor.
     *
     * @param callable|null $decryptPost Callable (int $id, string $password) => array
     *                                   defaulting to the global decrypt_post() helper
     *                                   when available.
     */
    public function __construct(?callable $decryptPost = null)
    {
        $this->decryptPost = $decryptPost
            ?: (function_exists('decrypt_post') ? 'decrypt_post' : null);
    }

    /**
     * Resolve the content branch and unlocked state for a post row.
     *
     * Determines whether the post is password-protected, whether the current
     * session has unlocked it, and produces the sanitized, ready-to-print
     * HTML content for the public/unlocked branches.
     *
     * @param array $post          The raw post row (requires ID, post_visibility,
     *                             and post_content keys).
     * @param array $unlockedPosts Session store mapping post ID => password.
     * @return array{id:int,is_protected:bool,is_unlocked:bool,show_password_form:bool,content:string}
     */
    public function resolve(array $post, array $unlockedPosts = [])
    {
        $id = isset($post['ID']) ? (int)$post['ID'] : 0;
        $visibility = isset($post['post_visibility'])
            ? (string)$post['post_visibility']
            : 'public';

        $isProtected = ($visibility === 'protected');
        $isUnlocked = $isProtected
            && $id > 0
            && isset($unlockedPosts[$id]);

        $rawContent = '';

        if ($isProtected && $isUnlocked && $this->decryptPost) {
            $decrypted = call_user_func($this->decryptPost, $id, $unlockedPosts[$id]);
            if (isset($decrypted['post_content'])) {
                $rawContent = $decrypted['post_content'];
            }
        } elseif (!$isProtected) {
            $rawContent = isset($post['post_content'])
                ? $post['post_content']
                : "Content not found";
        }

        $content = ($rawContent !== '') ? $this->sanitizeContent($rawContent) : '';

        return [
            'id' => $id,
            'is_protected' => $isProtected,
            'is_unlocked' => $isUnlocked,
            'show_password_form' => ($isProtected && !$isUnlocked),
            'content' => $content
        ];
    }

    /**
     * Sanitize post content for safe output.
     *
     * Applies the double html_entity_decode, strips inline style attributes,
     * then runs the content through htmLawed with an event-handler/style
     * blacklist. Preserves the exact pipeline previously inline in single.php.
     *
     * @param string $content Raw, unsanitized post content.
     * @return string Sanitized HTML safe to echo into the template.
     */
    public function sanitizeContent(string $content): string
    {
        $decoded = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
        $decoded = html_entity_decode($decoded, ENT_QUOTES, 'UTF-8');

        $clean = preg_replace('/\s*style="[^"]*"/', '', $decoded) ?? '';
        $clean = preg_replace('/\s*style=[^>\s]*/', '', $clean) ?? '';

        if (function_exists('htmLawed')) {
            return htmLawed($clean, array(
                'deny_attribute' => implode(',', $this->denyAttributes),
                'keep_bad' => 0
            ));
        }

        return $clean;
    }
}
