<?php if (!defined('SCRIPTLOG')) { exit(); } ?>

<div class="modal fade" id="downloadHistoryModal" tabindex="-1" role="dialog" aria-labelledby="downloadHistoryModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title" id="downloadHistoryModalLabel">
          <i class="fa fa-history"></i> Download History
        </h4>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>#</th>
                <th>IP Address</th>
                <th>User Agent</th>
                <th>Date/Time</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="downloadHistoryContent">
              <tr>
                <td colspan="5" class="text-center">Loading...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
function viewDownloadHistory(mediaId) {
  var modal = $('#downloadHistoryModal');
  var content = $('#downloadHistoryContent');
  
  content.html('<tr><td colspan="5" class="text-center">Loading...</td></tr>');
  modal.modal('show');
  
  $.ajax({
    url: 'index.php?load=downloads&action=getHistory',
    type: 'GET',
    data: { media_id: mediaId },
    dataType: 'json',
    success: function(response) {
      if (response.success && response.history.length > 0) {
        var html = '';
        var no = 1;
        response.history.forEach(function(item) {
          var statusClass = item.status === 'success' ? 'label-success' : 'label-danger';
          html += '<tr>';
          html += '<td>' + no + '</td>';
          html += '<td><code>' + item.ip_address + '</code></td>';
          html += '<td><small>' + item.user_agent + '</small></td>';
          html += '<td>' + item.downloaded_at + '</td>';
          html += '<td><span class="label ' + statusClass + '">' + item.status + '</span></td>';
          html += '</tr>';
          no++;
        });
        content.html(html);
      } else {
        content.html('<tr><td colspan="5" class="text-center">No download history found.</td></tr>');
      }
    },
    error: function() {
      content.html('<tr><td colspan="5" class="text-center text-danger">Error loading history.</td></tr>');
    }
  });
}
</script>