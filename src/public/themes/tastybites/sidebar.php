<?php
defined('SCRIPTLOG') || die('Direct access not permitted');
?>

<aside class="sidebar">
    <div class="search-form">
        <form method="GET" action="" class="search-form">
            <input type="hidden" name="csrf" value="<?= block_csrf(); ?>">
            <div class="input-group">
                <input type="text" name="q" class="form-control" placeholder="<?= t('nav.search_placeholder'); ?>" required>
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit">
                        <i class="fa fa-search"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <div class="widget">
        <h4><?= t('blog.categories'); ?></h4>
        <ul class="categories-list">
            <?php
            $categories = sidebar_topics();
            if (!empty($categories)) :
                foreach ($categories as $cat) :
                    $catLink = (rewrite_status() === 'yes') ? permalinks($cat['topic_slug'])['cat'] : permalinks($cat['ID'])['cat'];
            ?>
            <li><a href="<?= $catLink; ?>"><?= tastybites_htmlout($cat['topic_title']); ?></a></li>
            <?php 
                endforeach;
            else :
            ?>
            <li><a href="#"><?= t('blog.no_posts'); ?></a></li>
            <?php endif; ?>
        </ul>
    </div>
    
    <div class="widget">
        <h4><?= t('blog.popular'); ?></h4>
        <ul class="recent-posts-list">
            <?php
            $recentPosts = latest_posts(5, 'random');
            if (!empty($recentPosts)) :
                foreach ($recentPosts as $rpost) :
                    $rpostLink = permalinks($rpost['ID'])['post'];
            ?>
            <li>
                <a href="<?= $rpostLink; ?>" class="post-title"><?= tastybites_htmlout($rpost['post_title']); ?></a>
                <div class="post-date"><?= tastybites_make_date($rpost['post_date']); ?></div>
            </li>
            <?php 
                endforeach;
            else :
            ?>
            <li><span class="text-muted"><?= t('blog.no_posts'); ?></span></li>
            <?php endif; ?>
        </ul>
    </div>
    
    <div class="widget">
        <h4><?= t('blog.archive'); ?></h4>
        <ul class="categories-list">
            <?php
            $archList = retrieve_archives();
            if (!empty($archList)) :
                foreach ($archList as $arch) :
                    $archLink = app_url() . '/archive/' . sprintf('%02d', $arch['month']) . '/' . $arch['year'];
            ?>
            <li><a href="<?= $archLink; ?>"><?= date('F', mktime(0, 0, 0, $arch['month'], 1)); ?> <?= $arch['year']; ?></a></li>
            <?php 
                endforeach;
            else :
            ?>
            <li><span class="text-muted"><?= t('archive.empty'); ?></span></li>
            <?php endif; ?>
        </ul>
    </div>
</aside>