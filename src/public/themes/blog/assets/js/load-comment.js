$(document).ready(function () {
    let offset = 0;
    
    const $commentsContainer = $("#comments");
    const $loadMoreBtn = $("#load-more");

    // Exit silently if comments section doesn't exist on this page
    if (!$commentsContainer.length) {
        return;
    }

    // Read post ID from data attribute (use .attr() for reliability)
    const post_id = parseInt($commentsContainer.attr("data-post-id")) || 0;
    const limit = window.CommentSettings?.limit ?? 3; // fallback just in case

    if (!post_id || post_id <= 0) {
        console.error("Post ID missing or invalid in #comments data attribute.");
        $commentsContainer.html("<p class='text-danger'>Cannot load comments. Missing post ID.</p>");
        $loadMoreBtn.hide();
        return;
    }

    function escapeHtml(text) {
        return $('<div/>').text(text).html();
    }

    function loadComments() {
        $.ajax({
            url: "/fetch-comments.php",
            type: "GET",
            data: { post_id: post_id, offset: offset },
            dataType: "json",
            success: function (comments) {
                if (Array.isArray(comments) && comments.length > 0) {
                    comments.forEach(comment => {
                        $commentsContainer.append(`
                            <div class="comment mb-3 p-3 border rounded shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title">${escapeHtml(comment.comment_author_name)}</h5>
                                    <p class="card-text">${escapeHtml(comment.comment_content)}</p>
                                    <p class="card-text"><small class="text-muted">${comment.comment_date}</small></p>
                                </div>
                            </div>
                        `);
                    });

                    offset += comments.length;

                    if (comments.length < limit) {
                        $loadMoreBtn.text("No More Comments").prop("disabled", true);
                    }
                } else {
                    $loadMoreBtn.text("No More Comments").prop("disabled", true);
                }
            },
            error: function (xhr, status, error) {
                console.error("Error loading comments:", error);
                $commentsContainer.append(`<p class="text-danger">Failed to load comments. Please try again later.</p>`);
                $loadMoreBtn.hide();
            }
        });
    }

    $loadMoreBtn.on("click", function () {
        loadComments();
    });

    // Load first batch on page load
    loadComments();
});
