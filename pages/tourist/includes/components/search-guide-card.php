<?php 
    $guides = $guideObj->viewAllGuideForSearch();
    foreach ($guides as $guide){ 
    $rating = $guideObj->guideRatingAndCount($guide['account_ID'] ?? '');
        $average_rating = $rating['average_rating'] ?? 0;
        $rating_count = $rating['rating_count'] ?? 0;
        $display_rating = round($average_rating * 2) / 2;
        $languagesArray = [];
        $languagesArray = array_map('trim', explode(',', $guide['guide_languages']));
?>
<div class="col-md-6 col-lg-4">
    <div class="guide-card">
        <div class="guide-card-img">
            <div class="online-status" title="Online Now"></div>
            <img src="<?= $guide['account_profilepic']?>" alt="Guide"> 
        </div>
        <div class="guide-card-body">
            <h4 class="guide-name"><?= $guide['guide_name'] ?></h4>
            <div class="guide-rating">
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
                <span><?= $average_rating ?></span> <small class="text-muted">(<?= $rating_count ?>)</small>
            </div>
            <div class="guide-languages">
                <?php foreach ($languagesArray as $lang) { ?>
                <span class="language-badge"><?= $lang ?></span>
                <?php } ?>
            </div>
            <a href="guide-profile.php?guide_id=<?= $guide['guide_ID'] ?>">
                    <button class="btn btn-view-profile">View Profile</button>
                </a>
            
        </div>
    </div>
</div>

<?php } ?>