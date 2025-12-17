<!-- card-template.php -->
<?php
?>
<div class="col" style = "height: 30rem" >
    <div class="card h-100 shadow-sm border-0 rounded-3 overflow-hidden" >
        <div class="bg-image hover-overlay ripple" data-mdb-ripple-color="light">
            <img src="../../../img/tour-spots/11-islands/1.jpg"
                 class="img-fluid w-100 rounded-top" style="height: 200px; object-fit: cover;"
                 alt="<?= htmlspecialchars($package['tourpackage_name']) ?>" />
            <a href="#!"><div class="mask" style="background-color: rgba(251,251,251,0.15);"></div></a>
        </div>

        <div class="card-body d-flex flex-column">
            <h5 class="card-title fw-bold">
                <a href="tour-packages-view.php?id=<?= $package['tourpackage_ID'] ?>"
                   class="text-dark text-decoration-none">
                    <?= htmlspecialchars($package['tourpackage_name']) ?>
                </a>
            </h5>

            <ul class="list-inline mb-2 d-flex align-items-center">
                <?= buildStarList($avg, $count) ?>
            </ul>

            <p class="card-text flex-grow-1">
                <?= htmlspecialchars($package['tourpackage_desc']) ?>
            </p>

            <hr class="my-3">

            <p class="mb-1">
                <strong>PAX:</strong>
                <?= $package['numberofpeople_based'] ?>
                <?php if ($package['numberofpeople_based'] != $package['numberofpeople_maximum']): ?>
                    – <?= $package['numberofpeople_maximum'] ?>
                <?php endif; ?>
            </p>

            <p class="mb-2 text-success fw-semibold">
                from <?= htmlspecialchars($package['pricing_currency']) ?> <?= number_format($package['pricing_foradult'], 2) ?> per adult
            </p>

            <a href="tour-packages-view.php?id=<?= $package['tourpackage_ID'] ?>"
               class="btn btn-warning mt-auto w-100 fw-semibold" style = "background-color: var(--accent); color: var(--secondary-color);">View Details</a>
        </div>
    </div>
</div>