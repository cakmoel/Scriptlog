<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

// Note: header.php is loaded via call_theme_header() from HandleRequest.php
?>

<div class="row">
    <div class="col-lg-8">
        <?php
        $req = request_path();
        $tagSlug = $req->tag ?? '';
        $tagSlug = urldecode($tagSlug);
        $posts = posts_by_tag($tagSlug);
        ?>
        
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= app_url(); ?>"><?= t('nav.home'); ?></a></li>
                <li class="breadcrumb-item active"><?= t('nav.tag'); ?>: <?= tastybites_htmlout($tagSlug); ?></li>
            </ol>
        </nav>
        
        <h2 class="mb-4"><?= t('tag.title'); ?></h2>
        
        <?php if (empty($posts)) : ?>
        <div class="alert alert-info"><?= t('tag.empty'); ?></div>
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

