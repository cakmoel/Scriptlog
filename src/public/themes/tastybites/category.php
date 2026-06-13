<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

// Note: header.php is loaded via call_theme_header() from HandleRequest.php
?>

<div class="row">
    <div class="col-lg-8">
        <?php
        $req = request_path();
        $categorySlug = $req->category ?? '';
        
        $topicModel = initialize_topic();
        $topic = $topicModel->getTopicBySlug($categorySlug);
        
        if (empty($topic)) :
            direct_page('index.php?load=404', 404);
        endif;
        
        $topic = $topic[0] ?? $topic;
        $topicId = $topic['ID'];
        $postsData = posts_by_category($topicId);
        $posts = $postsData['entries'] ?? [];
        ?>
        
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= app_url(); ?>"><?= t('nav.home'); ?></a></li>
                <li class="breadcrumb-item active"><?= tastybites_htmlout($topic['topic_title']); ?></li>
            </ol>
        </nav>
        
        <h2 class="mb-4"><?= t('nav.category_title'); ?>: <?= tastybites_htmlout($topic['topic_title']); ?></h2>
        
        <?php if (empty($posts)) : ?>
        <div class="alert alert-info"><?= t('blog.no_posts'); ?></div>
        <?php else : ?>
            <?php foreach ($posts as $post) : 
                $postLink = permalinks($post['ID'])['post'];
                $featuredImage = invoke_frontimg($post['media_filename'] ?? '', true);
            ?>
            <article class="card">
                <?php if (!empty($post['media_filename'])) : ?>
                <a href="<?= $postLink; ?>"><?= $featuredImage; ?></a>
                <?php endif; ?>
                <div class="card-body">
                    <h2 class="card-title"><a href="<?= $postLink; ?>"><?= tastybites_htmlout($post['post_title']); ?></a></h2>
                    <p class="card-text"><?= tastybites_htmlout($post['post_summary'] ?? substr(strip_tags($post['post_content']), 0, 200)); ?>...</p>
                    <a href="<?= $postLink; ?>" class="btn btn-outline-primary"><?= t('nav.read_more'); ?></a>
                </div>
            </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-4">
        <?php require dirname(__FILE__) . '/sidebar.php'; ?>
    </div>
</div>

