<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

// Note: header.php is loaded via call_theme_header() from HandleRequest.php

$featured_posts = function_exists('featured_post') ? featured_post() : [];
$latest_posts = function_exists('latest_posts') ? latest_posts(6) : [];
$galleries = function_exists('display_galleries') ? display_galleries(0, 4) : "";
$nothing_found = function_exists('nothing_found') ? nothing_found() : "";

$hero_post = !empty($featured_posts) ? reset($featured_posts) : null;
?>

<!-- Hero Section -->
<?php if (!empty($hero_post)) : ?>
<section class="hero" style="background-image: url('<?= !empty($hero_post['media_filename']) ? invoke_frontimg($hero_post['media_filename'], false) : theme_dir() . 'assets/img/hero.jpg'; ?>')">
    <div class="container">
        <h1><?= tastybites_htmlout($hero_post['post_title']); ?></h1>
        <p><?= tastybites_htmlout(truncate_tags($hero_post['post_content'], 30)); ?></p>
        <a href="<?= permalinks($hero_post['ID'])['post']; ?>" class="btn btn-primary mt-4"><?= t('nav.read_more'); ?></a>
    </div>
</section>
<?php endif; ?>

<!-- Main Content -->
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-12">
            <h2 class="mb-4"><?= t('blog.latest'); ?></h2>
            
            <?php if (!empty($latest_posts)) : ?>
            <div class="row">
                <?php foreach ($latest_posts as $post) : 
                    $postLink = permalinks($post['ID'])['post'];
                    $featuredImage = !empty($post['media_filename']) ? invoke_frontimg($post['media_filename'], true) : '';
                    $categories = retrieves_topic_simple($post['ID']);
                    $comments = total_comment($post['ID']);
                ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card">
                        <?php if (!empty($post['media_filename'])) : ?>
                        <a href="<?= $postLink; ?>">
                            <?= $featuredImage; ?>
                        </a>
                        <?php endif; ?>
                        <div class="card-body">
                            <div class="post-meta">
                                <?php if (!empty($categories)) : ?>
                                <span class="category-badge"><?= $categories; ?></span>
                                <?php endif; ?>
                                <span class="ml-2"><?= tastybites_make_date($post['post_date']); ?> by <?= tastybites_htmlout($post['user_login']); ?></span>
                            </div>
                            <h5 class="card-title">
                                <a href="<?= $postLink; ?>"><?= tastybites_htmlout($post['post_title']); ?></a>
                            </h5>
                            <p class="card-text"><?= tastybites_htmlout(truncate_tags($post['post_content'], 25)); ?></p>
                            <a href="<?= $postLink; ?>" class="btn btn-outline-primary"><?= t('nav.read_more'); ?></a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else : ?>
            <?= $nothing_found; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

// Note: header.php is loaded via call_theme_header() from HandleRequest.php