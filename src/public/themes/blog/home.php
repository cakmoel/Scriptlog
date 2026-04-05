<?php

if ((function_exists('latest_posts')) && (function_exists('app_reading_setting'))) {
    $latest_posts = isset(app_reading_setting()['post_per_page']) ? latest_posts(app_reading_setting()['post_per_page']) : "";
}

$galleries = function_exists('display_galleries') ? display_galleries(0, 4) : "";
$nothing_found = function_exists('nothing_found') ? nothing_found() : "";

if (function_exists('featured_post')) :
    foreach (featured_post() as $hero_headline) {
        $featured_hero_id = isset($hero_headline['ID']) ? (int)$hero_headline['ID'] : "";
        $featured_hero_img = ((isset($hero_headline['media_filename'])) && ($hero_headline['media_filename'] !== "") ? htmlout($hero_headline['media_filename']) : "");
        $featured_hero_title = isset($hero_headline['post_title']) ? htmlout($hero_headline['post_title']) : "";
    }
    ?>

<!-- Hero Section-->
<section
    style="background-image: url('<?= isset($featured_hero_img) ? invoke_frontimg($featured_hero_img, false) : theme_dir() . 'assets/img/hero.jpg'; ?>')"
    class="hero" role="img" aria-label="<?= isset($featured_hero_title) ? htmlout($featured_hero_title) : 'Hero image'; ?>">
    <div class="container">
        <div class="row">
            <div class="col-lg-7">
                <h1 <?= isset($featured_hero_id) ? 'class="h1"' : "";  ?>>
                    <?= isset($featured_hero_title) ? $featured_hero_title : "Featured post goes here"; ?></h1>
                <a <?= (!empty($featured_hero_id)) ? 'href="' . permalinks($featured_hero_id)['post'] . '" ' : 'href="' . app_url() . '/admin/login.php"';  ?>
                    class="hero-link">
                    <?= (!empty($featured_hero_id)) ? t('home.hero.discover_more') : t('home.hero.admin_panel'); ?>
                </a>
            </div>
            <a <?= (!empty($featured_hero_id)) ? 'href=".intro"' : 'href="#intro"'; ?> class="continue link-scroll"
                title="<?= t('home.hero.scroll_down'); ?>"><i class="fa fa-long-arrow-down" aria-hidden="true"></i>
                <?= t('home.hero.scroll_down'); ?>
            </a>
        </div>
    </div>
</section>

    <?php
endif;
?>

<?php
if (function_exists('sticky_page')) :
    foreach (sticky_page() as $sticky) {
        $sticky_title = isset($sticky['post_title']) ? htmlout($sticky['post_title']) : "";
        $sticky_content = isset($sticky['post_content']) ? paragraph_l2br(htmlout(paragraph_trim($sticky['post_content']))) : "";
    }
    ?>

<!-- Intro Section-->
<section <?= (!empty($sticky)) ? 'class="intro"' : 'id="intro"' ?>>
    <div class="container">
        <div class="row">
            <div class="col-lg-8">

                <h2 class="h3"><?= isset($sticky_title) ? $sticky_title : "Welcome to ScriptLog"; ?></h2>
                <p class="text-big">
                   <?= isset($sticky_content) ? $sticky_content : "Your entryway to a personal blog that lets you easily express, create, and share your ideas. 
          Whether you're a creative writer, hobbyist, tech enthusiast, personal blogger, or someone who values digital independence, 
          ScriptLog empowers you to craft your online presence."; ?>
                </p>

            </div>
        </div>
    </div>
</section>
    <?php
endif;
?>

<?php
  // Call Hello World plugin
if (function_exists('invoke_plugin')) {
    echo invoke_plugin('Hello World', "");
}
?>

<!-- random post/featured post -->
<section class="featured-posts no-padding-top">
    <div class="container">

        <?php

        if (function_exists('random_posts')) :
            $r = 0;

            foreach (random_posts(0, 6) as $random_post) :
                $r++;

                $random_post_id = isset($random_post['ID']) ? (int)$random_post['ID'] : 0;
                $random_post_img = ((isset($random_post['media_filename'])) && ($random_post['media_filename'] !== "") ? htmlout($random_post['media_filename']) : "");
                $random_post_author = (isset($random_post['user_login']) || isset($random_post['user_fullname']) ? htmlout($random_post['user_login']) : htmlout($random_post['user_fullname']));
                $random_post_title = isset($random_post['post_title']) ? htmlout($random_post['post_title']) : "";
                $random_post_content = isset($random_post['post_content']) ? paragraph_l2br(htmlout(paragraph_trim($random_post['post_content']))) : "";
                $random_post_created = isset($random_post['post_modified']) ? htmlout(make_date($random_post['post_modified'])) : htmlout(make_date($random_post['post_date']));
                $total_comment = isset($random_post['total_comments']) ? (int)$random_post['total_comments'] : 0;

                if ($r % 2 == 1) :
                    ?>

        <!-- Random Post-->
        <div class="row d-flex align-items-stretch">
            <div class="text col-lg-7">
                <div class="text-inner d-flex align-items-center">
                    <div class="content">

                        <header class="post-header">
                            <div class="category">
                                <?= isset($random_post['topics_data']) ? format_topics($random_post['topics_data']) : ""; ?>

                            </div>
                            <a href="<?= isset($random_post_id) ? permalinks($random_post_id)['post'] : "javascript:void(0)"; ?>"
                                title="<?= isset($random_post_title) ? $random_post_title : ""; ?>">
                                <h2 class="h4"><?= isset($random_post_title) ? $random_post_title : ""; ?></h2>
                            </a>
                        </header>

                        <p><?= isset($random_post_content) ? $random_post_content : ""; ?></p>
                        <footer class="post-footer d-flex align-items-center">
                            <div class="author d-flex align-items-center flex-wrap">
                                <div class="title">
                                    <span><i class="fa fa-user-circle" aria-hidden="true"></i>
                                        <?= isset($random_post_author) ? $random_post_author : ""; ?> </span>
                                </div>
                            </div>
                            <div class="date"><i class="fa fa-calendar" aria-hidden="true"></i>
                                <?= isset($random_post_created) ? $random_post_created : ""; ?>
                            </div>
                            <div class="comments"><i class="icon-comment" aria-hidden="true"></i><?= $total_comment; ?>
                            </div>
                        </footer>
                    </div>
                </div>
            </div>
            <div class="image col-lg-5">
                    <?= isset($random_post_img) ? invoke_responsive_image($random_post_img, 'thumbnail', true, isset($random_post['media_caption']) ? htmlout($random_post['media_caption']) : htmlout($random_post['post_title']), 'img-fluid') : '<img src="https://via.placeholder.com/516x344" alt="" width="516" height="344" class="img-fluid" loading="lazy" decoding="async">'; ?>
            </div>

        </div>

                <?php else :
                    ?>

        <div class="row d-flex align-items-stretch">
            <div class="image col-lg-5">
                    <?= isset($random_post_img) ? invoke_responsive_image($random_post_img, 'thumbnail', true, isset($random_post['media_caption']) ? htmlout($random_post['media_caption']) : htmlout($random_post['post_title']), 'img-fluid') : '<img src="https://via.placeholder.com/516x344" alt="" width="516" height="344" class="img-fluid" loading="lazy" decoding="async">'; ?>
            </div>
            <div class="text col-lg-7">
                <div class="text-inner d-flex align-items-center">
                    <div class="content">

                        <header class="post-header">
                            <div class="category">
                                <?= isset($random_post['topics_data']) ? format_topics($random_post['topics_data']) : ""; ?>

                            </div>
                            <a
                                href="<?= isset($random_post_id) ? permalinks($random_post_id)['post'] : "javascript:void(0)"; ?>">
                                <h2 class="h4"><?= isset($random_post_title) ? $random_post_title : ""; ?></h2>
                            </a>
                        </header>

                        <p><?= isset($random_post_content) ? $random_post_content : ""; ?></p>
                        <footer class="post-footer d-flex align-items-center">
                            <div class="author d-flex align-items-center flex-wrap">
                                <div class="title"><span><i class="fa fa-user-circle" aria-hidden="true"></i>
                                        <?= $random_post_author; ?> </span></div>
                            </div>
                            <div class="date"><i class="fa fa-calendar" aria-hidden="true"></i>
                                <?= isset($random_post_created) ? $random_post_created : ""; ?>
                            </div>
                            <div class="comments"><i class="icon-comment" aria-hidden="true"></i><?= $total_comment; ?>
                            </div>
                        </footer>
                    </div>
                </div>
            </div>
        </div>

                    <?php
                endif;
            endforeach;
        else :
            echo $nothing_found;
        endif;
        ?>

    </div>
    <!--.container-->
</section>

<!-- divider section -->
<?php
if (function_exists('featured_post')) :
    foreach (featured_post() as $divider_content) {
        $featured_divider_id = isset($divider_content['ID']) ? (int)$divider_content['ID'] : "";
        $featured_divider_img = (isset($divider_content['media_filename']) && $divider_content['media_filename'] != "") ? htmlout($divider_content['media_filename']) : "";
        $featured_divider_title = isset($divider_content['post_title']) ? htmlout($divider_content['post_title']) : "";
    }
    ?>

<section
    style="background-image: url(<?= isset($featured_divider_img) ? invoke_frontimg($featured_divider_img) : 'https://picsum.photos/1920/1280'; ?>)"
    class="divider" role="img" aria-label="<?= isset($featured_divider_title) ? htmlout($featured_divider_title) : 'Divider image'; ?>">
    <div class="container">
        <div class="row">
            <div class="col-md-7">
                <h2 class="h2"><?= isset($featured_divider_title) ? $featured_divider_title : ""; ?> </h2>
                <a <?= (!empty($featured_divider_id)) ? 'href="' . permalinks($featured_divider_id)['post'] . '" ' : 'href="' . app_url() . '/admin/login.php"';  ?>
                    class="hero-link">
                    <?= (!empty($featured_divider_id)) ? 'View More' : 'Go to administrator panel'; ?>
                </a>
            </div>
        </div>
    </div>

</section>

    <?php
endif;
?>

<!-- Latest Post -->
<section class="latest-posts">
    <div class="container">
        <header>

            <?php
            if ($latest_posts) :
                ?>

            <h2><?= t('home.latest_posts.title'); ?></h2>
        </header>
        <div class="row">

                <?php
                foreach ($latest_posts as $latest_post) :
                    $latest_post_id = isset($latest_post['ID']) ? (int)$latest_post['ID'] : "";
                    $latest_post_title = isset($latest_post['post_title']) ? htmlout($latest_post['post_title']) : "";
                    $latest_post_content = isset($latest_post['post_content']) ? paragraph_l2br(htmlout(paragraph_trim($latest_post['post_content']))) : "";
                    $latest_post_img = ((isset($latest_post['media_filename'])) && ($latest_post['media_filename'] !== "") ? htmlout($latest_post['media_filename']) : "");
                    $latest_img_caption = isset($latest_post['media_caption']) ? htmlout($latest_post['media_caption']) : "";
                    $latest_post_created = isset($latest_post['modified_at']) ? htmlout(make_date($latest_post['modified_at'])) : htmlout(make_date($latest_post['created_at']));

                    ?>

            <div class="post col-md-4">
                <div class="post-thumbnail"><a
                        href="<?= isset($latest_post_id) ? permalinks($latest_post_id)['post'] : "javascript:void(0)"; ?>"
                        title="<?= $latest_post_title; ?>">
                    <?= isset($latest_post_img) ? invoke_responsive_image($latest_post_img, 'thumbnail', true, isset($latest_img_caption) ? $latest_img_caption : $latest_post_title, 'img-fluid') : '<img src="https://via.placeholder.com/640x450" alt="" width="640" height="450" class="img-fluid" loading="lazy" decoding="async">' ?></a></div>
                <div class="post-details">
                    <div class="post-meta d-flex justify-content-between">
                        <div class="date"><?= $latest_post_created; ?></div>
                        <div class="category">
                        <?= isset($latest_post['topics_data']) ? format_topics($latest_post['topics_data']) : ""; ?>
                        </div>
                    </div>
                    <a href="<?= isset($latest_post_id) ? permalinks($latest_post_id)['post'] : "javascript:void(0)"; ?>"
                        title="<?= $latest_post_title; ?>">
                        <h3 class="h4"><?= $latest_post_title; ?></h3>
                    </a>
                    <p class="text-muted"><?= isset($latest_post_content) ? $latest_post_content : ""; ?> </p>
                </div>
            </div>

                    <?php
                endforeach;
            else :
                echo $nothing_found;
            endif;
            ?>

        </div>

    </div>

</section>

<!-- Newsletter Section -->
<section class="newsletter no-padding-top">
    <div class="container">

        <div class="row">

        </div>

    </div>
</section>

<!-- Gallery Section -->
<section class="gallery no-padding">
    <div class="row">

        <?php
        if ($galleries) :
            foreach ($galleries as $gallery) :
                $img_filename = isset($gallery['media_filename']) ? htmlout($gallery['media_filename']) : "";
                $img_alt = isset($gallery['media_caption']) ? htmlout($gallery['media_caption']) : "";

                ?>

        <div class="mix col-lg-3 col-md-3 col-sm-6">
            <div class="item">
                <a href="<?= isset($img_filename) ? (function_exists('invoke_webp_image') ? invoke_webp_image($img_filename, false) : $img_filename) : "https://via.placeholder.com/640x450"; ?>"
                    data-fancybox="gallery" class="image">
                    <?= isset($img_filename) ? invoke_gallery_image($img_filename, isset($img_alt) ? $img_alt : "") : '<img src="https://via.placeholder.com/640x450" alt="" class="img-fluid" width="640" height="450" loading="lazy" decoding="async">' ?>
                    <div class="overlay d-flex align-items-center justify-content-center"><i class="icon-search"
                            aria-hidden="true"></i></div>
                </a>
            </div>
        </div>

                <?php
            endforeach;
        else :
            ?>
        <div class="mix col-lg-3 col-md-3 col-sm-6">
            <div class="item"><a href="https://picsum.photos/640/450" data-fancybox="gallery" class="image">
                    <img src="https://picsum.photos/640/450" alt="This is a gallery" class="img-fluid">
                    <div class="overlay d-flex align-items-center justify-content-center"><i class="icon-search"
                            aria-hidden="true"></i></div>
                </a></div>
        </div>
        <div class="mix col-lg-3 col-md-3 col-sm-6">
            <div class="item"><a href="https://picsum.photos/640/450" data-fancybox="gallery" class="image">
                    <img src="https://picsum.photos/640/450" alt="This is a gallery" class="img-fluid">
                    <div class="overlay d-flex align-items-center justify-content-center"><i class="icon-search"
                            aria-hidden="true"></i></div>
                </a></div>
        </div>
        <div class="mix col-lg-3 col-md-3 col-sm-6">
            <div class="item"><a href="https://picsum.photos/640/450" data-fancybox="gallery" class="image">
                    <img src="https://picsum.photos/640/450" alt="" class="img-fluid">
                    <div class="overlay d-flex align-items-center justify-content-center"><i class="icon-search"
                            aria-hidden="true"></i></div>
                </a></div>
        </div>
        <div class="mix col-lg-3 col-md-3 col-sm-6">
            <div class="item"><a href="https://picsum.photos/640/450" data-fancybox="gallery" class="image">
                    <img src="https://picsum.photos/640/450" alt="" class="img-fluid">
                    <div class="overlay d-flex align-items-center justify-content-center"><i class="icon-search"
                            aria-hidden="true"></i></div>
                </a></div>
        </div>
            <?php
        endif;
        ?>
    </div>
</section>