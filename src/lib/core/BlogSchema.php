<?php

use Melbahja\Seo\Schema;
use Melbahja\Seo\Schema\Thing;

/**
 * class BlogSchema 
 * 
 * @category Core 
 * @author M.Noermoehammad
 * @license MIT
 * 
 */
class BlogSchema
{

    /**
     * Generate schema.org structured data for a blog post.
     *
     * @param int $postId The ID of the blog post.
     * @return string JSON-LD structured data.
     */
    public static function generateBlogPostSchema(int $post)
    {
         
        // Retrieve blog post content using FrontHelper
        $post = FrontHelper::grabPreparedFrontPostById($post);


        // Determine the most specific CreativeWork type
        $type = self::determineCreativeWorkType($post['post_type']);

        // Prepare schema.org structured data

        $singlePost = new Thing($type);
        $singlePost->headline = escape_html($post['post_title']);
        $singlePost->description = escape_html($post['post_summary'])?? substr(strip_tags($post['post_content']), 0, 200);
        $singlePost->url = self::baseURL() . DIRECTORY_SEPARATOR .  'post' . DIRECTORY_SEPARATOR . $post['ID'] . DS . $post['post_slug'];
        $singlePost->datePublished = isset($post['post_date']) ? escape_html($post['post_date']) : date(DATE_ATOM);
        $singlePost->dateModified = isset($post['post_modified']) ? escape_html($post['post_modified']) : escape_html($post['post_date']);
        $singlePost->articleBody = strip_tags(escape_html($post['post_content']));
        $singlePost->wordCount = str_word_count(strip_tags($post['post_content']));
        $singlePost->isFamilyFriendly = $post['post_visibility'] == 'public' && empty($post['post_password']);
        $singlePost->keywords = $post['post_tags'] ? explode(',', $post['post_tags']) : [];

        // Add author information 
        if (!empty($post['user_fullname'])) {
            $singlePost->author = new Thing('Person', [
                '@id' => self::baseURL() . '/#author',
                'name' => escape_html($post['user_fullname']) ?? ''
            ]);
        }

        // Add image information 
        if (!empty($post['media_filename'])) {
            $singlePost->image = new Thing('ImageObject', [
                '@id' => self::baseURL() . '/#image',
                'url' => invoke_frontimg($post['media_filename']) ?? '  ',
                'caption' => escape_html($post['media_caption']) ?? '',
                'width' => 640,
                'height' => 427
            ]);
        }

        // Add publisher information
        $singlePost->publisher = new Thing('Organization', [
            '@id' => self::baseURL() . '/#organization',
            'name' => escape_html(app_info()['site_name']),
            'logo' => new Thing('ImageObject', [
                'url' => app_url() . DS . APP_IMAGE . 'scriptlog-1200x630.jpg',
                'width' => 1200,
                'height' => 630
            ])
        ]);

        $webpage = new Thing("WebPage", [
            '@id' => self::baseURL() . DIRECTORY_SEPARATOR . 'post' . DIRECTORY_SEPARATOR . escape_html($post['ID']) . DIRECTORY_SEPARATOR . escape_html($post['post_slug']) . '/#webpage',
            'name' => escape_html($post['post_title']),
            'url' => self::baseURL() . DIRECTORY_SEPARATOR . 'post' . DIRECTORY_SEPARATOR . $post['ID'] . DS . $post['post_slug']
        ]);

       $schema = new Schema($singlePost, $webpage);
    
       return $schema;

    }

    protected static function determineCreativeWorkType(string $postType): string
    {
        $mapping = [
            'blog' => 'BlogPosting',
            'article' => 'Article',
            'news' => 'NewsArticle',
            'page' => 'WebPage'
        ];

        return $mapping[$postType] ?? 'BlogPosting';
    }
    
    private static function baseURL(): string
    {
        return rtrim(app_url(), '/');
    }
}