<?php if (!defined('SCRIPTLOG')) {
    exit();
} ?>

<div class="content-wrapper">

  <section class="content-header">
    <h1><?= (isset($pageTitle)) ? $pageTitle : ""; ?>
      <small>Import Preview</small>
    </h1>
    <ol class="breadcrumb">
      <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard" aria-hidden="true"></i> Home </a></li>
      <li><a href="index.php?load=import">Import</a></li>
      <li class="active">Preview</li>
    </ol>
  </section>

  <!-- Main content -->
  <section class="content">
    <div class="row">
      <div class="col-md-12">
        
        <div class="alert alert-info">
          <h4><i class="icon fa fa-info" aria-hidden="true"></i> Preview Information</h4>
          <p>This is a preview of the data that will be imported. No data has been imported yet.</p>
        </div>
        
        <div class="box box-primary">
          <div class="box-header with-border">
            <h3 class="box-title">Source Information</h3>
          </div>
          <div class="box-body">
            
            <?php if (!empty($preview['site_info'])) : ?>
            <table class="table table-bordered">
                <?php if (!empty($preview['site_info']['title'])) : ?>
              <tr>
                <th style="width: 200px;">Title</th>
                <td><?= safe_html($preview['site_info']['title']); ?></td>
              </tr>
                <?php endif; ?>
                <?php if (!empty($preview['site_info']['url'])) : ?>
              <tr>
                <th style="width: 200px;">URL</th>
                <td><?= safe_html($preview['site_info']['url']); ?></td>
              </tr>
                <?php endif; ?>
                <?php if (!empty($preview['site_info']['site_url'])) : ?>
              <tr>
                <th style="width: 200px;">Site URL</th>
                <td><?= safe_html($preview['site_info']['site_url']); ?></td>
              </tr>
                <?php endif; ?>
              <tr>
                <th>Platform</th>
                <td><?= ucfirst(safe_html($source)); ?></td>
              </tr>
            </table>
            <?php endif; ?>
            
          </div>
        </div>
        
        <div class="box box-primary">
          <div class="box-header with-border">
            <h3 class="box-title">Content Summary</h3>
          </div>
          <div class="box-body">
            
            <div class="row">
              <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box">
                  <span class="info-box-icon bg-aqua"><i class="fa fa-pencil"></i></span>
                  <div class="info-box-content">
                    <span class="info-box-text">Posts</span>
                    <span class="info-box-number"><?= $preview['posts_count']; ?></span>
                  </div>
                </div>
              </div>
              
              <?php if (isset($preview['pages_count'])) : ?>
              <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box">
                  <span class="info-box-icon bg-green"><i class="fa fa-file-text-o"></i></span>
                  <div class="info-box-content">
                    <span class="info-box-text">Pages</span>
                    <span class="info-box-number"><?= $preview['pages_count']; ?></span>
                  </div>
                </div>
              </div>
              <?php endif; ?>

              <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box">
                  <span class="info-box-icon bg-yellow"><i class="fa fa-folder-open"></i></span>
                  <div class="info-box-content">
                    <span class="info-box-text">Categories</span>
                    <span class="info-box-number"><?= $preview['categories_count']; ?></span>
                  </div>
                </div>
              </div>

              <?php if (isset($preview['tags_count'])) : ?>
              <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box">
                  <span class="info-box-icon bg-red"><i class="fa fa-tags"></i></span>
                  <div class="info-box-content">
                    <span class="info-box-text">Tags</span>
                    <span class="info-box-number"><?= $preview['tags_count']; ?></span>
                  </div>
                </div>
              </div>
              <?php endif; ?>
            </div>
            
          </div>
        </div>

        <?php if (!empty($preview['posts'])) : ?>
        <div class="box box-info">
          <div class="box-header with-border">
            <h3 class="box-title">Items to be Imported (Sample)</h3>
          </div>
          <div class="box-body table-responsive">
            <table class="table table-striped table-hover">
              <thead>
                <tr>
                  <th>Title</th>
                  <th>Slug</th>
                  <th>Type</th>
                  <th>Status</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($preview['posts'] as $post) : ?>
                <tr>
                  <td><?= safe_html($post['title']); ?></td>
                  <td><code><?= safe_html($post['slug']); ?></code></td>
                  <td><span class="label label-default"><?= safe_html($post['type']); ?></span></td>
                  <td><span class="label label-info"><?= safe_html($post['status']); ?></span></td>
                  <td><?= safe_html($post['date']); ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <?php if ($preview['posts_count'] > 10) : ?>
            <p class="text-muted text-center"><br>... and <?= $preview['posts_count'] - 10; ?> more items.</p>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>

        <div class="box">
          <div class="box-footer">
            <a href="index.php?load=import" class="btn btn-default btn-flat"><i class="fa fa-arrow-left" aria-hidden="true"></i> Back to Import</a>
            <p class="pull-right text-warning">Note: To perform the actual import, go back and click the "Import" button.</p>
          </div>
        </div>
        
      </div>
    </div>
  </section>
</div>
