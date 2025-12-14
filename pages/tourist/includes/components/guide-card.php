<?php
    foreach ($topfiveguides as $guide){
        $rating = $guideObj->guideRatingAndCount($guide['account_ID'] ?? '');
        $average_rating = $rating['average_rating'] ?? 0;
        $rating_count = $rating['rating_count'] ?? 0;
        $display_rating = round($average_rating * 2) / 2;
?>

<div class="col-md-3">
    <div class="guide-card">
        <img src="<?= $guide['account_profilepic'] ?? '';?>" alt="Guide">
        <div class="guide-card-body">
            <h5><?= $guide['guide_name']; ?></h5>
            <!-- <p class="text-muted mb-1"><i class="fas fa-map-marker-alt"></i> Paris, France</p> -->
            <div class="rating mb-2">
                <?php 
                for ($i = 1; $i <= 5; $i++) {
                    if ($i <= $display_rating) { 
                        echo '<i class="fas fa-star text-warning"></i>';
                    } elseif ($i - 0.5 == $display_rating) { 
                        echo '<i class="fas fa-star-half-alt text-warning"></i>';
                    } else { 
                        echo '<i class="far fa-star text-muted"></i>';  
                    }
                }
                ?>
                <span class="text-muted">(<?= $rating_count ?>)</span>
            </div>
            <p class="mb-2"><small><i class="fas fa-language"></i> <?= $guide['guide_languages'] ?></small></p>
            <div class="d-flex justify-content-between align-items-center">
                <!-- <span class="fw-bold" style="color: var(--accent);">$45/hr</span> -->
                <a href="guide-profile.php?guide_id=<?= $guide['guide_ID'] ?>" class="text-decoration-none">
                    <button class="btn btn-primary btn-sm">View Profile</button>
                </a>
            </div>
        </div>
    </div>
</div>

<?php } ?>