<?php 
if (!empty($reviews)): 
    foreach ($reviews as $review): 
?>
        <div class="review-card">
            <div class="d-flex justify-content-between mb-2">
                <div>
<strong><?= htmlspecialchars($review['reviewer_name']) ?></strong>
<span class="text-warning ms-2">
    <?php 
        $star_value = round($review['rating_value']);
        for ($i = 1; $i <= 5; $i++) {
            echo '<i class="fas fa-star' . ($i <= $star_value ? ' text-warning' : ' text-light') . '"></i>';
        }
    ?>
</span>
                </div>
                <small class="text-muted"><?= date('M j, Y', strtotime($review['rating_date'])) ?></small>
            </div>
            <p class="mb-0">"<?= nl2br(htmlspecialchars($review['rating_description'])) ?>"</p>
        </div>
<?php 
    endforeach; 
else: 
?>
    <div class="empty-state p-0">
        <i class="bi bi-star"></i>
        <p>No reviews have been submitted for this guide yet.</p>
    </div>
<?php endif; ?>