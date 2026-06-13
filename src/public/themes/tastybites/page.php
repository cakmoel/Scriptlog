<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

// Note: header.php is loaded via call_theme_header() from HandleRequest.php
?>

<div class="row">
    <div class="col-lg-8">
        <?php
        $req = request_path();
        $pageSlug = $req->page ?? '';
        $page = retrieve_page($pageSlug, 'yes');
        
        if (empty($page)) :
            direct_page('index.php?load=404', 404);
        endif;
        
        $page = $page[0] ?? $page;
        ?>
        <article class="page-content">
            <h1><?= tastybites_htmlout($page['post_title']); ?></h1>
            
            <?php if (!empty($page['media_filename'])) : ?>
            <?= invoke_frontimg($page['media_filename'], false); ?>
            <?php endif; ?>
            
            <div class="post-content">
                <?= $page['post_content']; ?>
            </div>
        </article>
    </div>
    
    <div class="col-lg-4">
        <?php require dirname(__FILE__) . '/sidebar.php'; ?>
    </div>
</div>

