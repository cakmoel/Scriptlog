<?php if (!defined('SCRIPTLOG')) { exit(); } ?>

<?php 
$statistics = $statistics ?? [];
$fileDistribution = $fileDistribution ?? [];
?>

<div class="row">
  <div class="col-lg-3 col-xs-6">
    <div class="small-box bg-aqua">
      <div class="inner">
        <h3><?= $statistics['total_downloads'] ?? 0; ?></h3>
        <p>Total Downloads</p>
      </div>
      <div class="icon">
        <i class="fa fa-download"></i>
      </div>
    </div>
  </div>
  
  <div class="col-lg-3 col-xs-6">
    <div class="small-box bg-green">
      <div class="inner">
        <h3><?= $statistics['active_links'] ?? 0; ?></h3>
        <p>Active Links</p>
      </div>
      <div class="icon">
        <i class="fa fa-link"></i>
      </div>
    </div>
  </div>
  
  <div class="col-lg-3 col-xs-6">
    <div class="small-box bg-red">
      <div class="inner">
        <h3><?= $statistics['expired_links'] ?? 0; ?></h3>
        <p>Expired Links</p>
      </div>
      <div class="icon">
        <i class="fa fa-clock-o"></i>
      </div>
    </div>
  </div>
  
  <div class="col-lg-3 col-xs-6">
    <div class="small-box bg-yellow">
      <div class="inner">
        <h3><?= $statistics['total_files'] ?? 0; ?></h3>
        <p>Total Files</p>
      </div>
      <div class="icon">
        <i class="fa fa-file"></i>
      </div>
    </div>
  </div>
</div>

<?php if (!empty($fileDistribution)) : ?>
<div class="row">
  <div class="col-md-12">
    <div class="box box-primary">
      <div class="box-header with-border">
        <h3 class="box-title">File Type Distribution</h3>
      </div>
      <div class="box-body">
        <div class="table-responsive">
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>File Type</th>
                <th>Downloads</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($fileDistribution as $dist) : ?>
              <tr>
                <td><?= safe_html($dist['media_type']); ?></td>
                <td><?= (int)$dist['count']; ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
