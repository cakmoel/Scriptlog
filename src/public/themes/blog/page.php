<?php

$retrieve_page = function_exists('rewrite_status') && rewrite_status() == 'yes' ? retrieve_page(request_path()->param1, 'yes') : retrieve_page(HandleRequest::isQueryStringRequested()['value'], 'no');

$page = (function_exists('prepare_page') && !empty($retrieve_page)) ? prepare_page($retrieve_page) : null;

$page_id = ($page !== null && $page->id() !== null) ? (int)$page->id() : "";
$page_title = ($page !== null && $page->title() !== null) ? $page->title() : "";
$page_author = ($page !== null && $page->author() !== null) ? $page->author() : "";
$page_created = ($page !== null && $page->date() !== null) ? $page->date() : "";
$img_alt = ($page !== null && $page->mediaCaption() !== null) ? $page->mediaCaption() : "";
$page_content = ($page !== null && $page->content() !== null) ? $page->content() : "";

?>

<div class="container">
    <div class="row">
        <div class="post blog-post col-lg-8">
            <div class="container">
                <div class="post-single">
                    <div class="post-thumbnail"><?= ($page !== null && $page->media() !== null && $page->media() !== '') ? invoke_responsive_image($page->media(), 'medium', true, $img_alt, 'img-fluid') : '<img src="' . theme_dir() . 'assets/img/placeholder.svg" alt="" width="730" height="486" class="img-fluid" loading="lazy" decoding="async">' ?></div>
                    <div class="post-details">
                        <div class="post-meta d-flex justify-content-between">
                            <div class="category">
                                <?= ($page_id !== "") ? link_topic((int)$page_id) : ""; ?>
                            </div>
                        </div>
                        <h1>
                            <?= $page_title; ?>
                            <a href="<?= ($page !== null) ? $page->url() : "#"; ?>" title="<?= $page_title; ?>">
                            <i class="fa fa-external-link" aria-hidden="true"></i></a>
                        </h1>
                        <div class="post-footer d-flex align-items-center flex-column flex-sm-row">
                            <a href="#" class="author d-flex align-items-center flex-wrap">
                                <div class="title"><span><i class="fa fa-user-circle" aria-hidden="true"></i> <?= $page_author; ?> </span></div>
                            </a>
                            <div class="d-flex align-items-center flex-wrap">
                                <div class="date"><i class="fa fa-calendar" aria-hidden="true"></i> <?= $page_created; ?> </div>
                            </div>
                        </div>

                        <div class="post-body">
                            <?= $page_content; ?>
                        </div>

                        <div class="post-tags">
                            <?= ($page_id !== "") ? link_tag($page_id) : ""; ?>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <?php
          include __DIR__ . '/sidebar.php';
        ?>

    </div>

</div>