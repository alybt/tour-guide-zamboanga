<?php if (!empty($guidePackages)): ?>
                        <?php foreach ($guidePackages as $package): ?>
                            <div class="package-card">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="mb-2">
                                            <a href="tour-packages-view.php?id=<?= $package['tourpackage_ID'] ?>" 
                                               class="text-decoration-none" style="color: var(--secondary-color);">
                                                <?= htmlspecialchars($package['tourpackage_name']) ?>
                                            </a>
                                        </h5>
                                        <p class="text-muted mb-2">
                                            <?= htmlspecialchars(substr($package['tourpackage_desc'], 0, 150)) ?>...
                                        </p>
                                        <div class="d-flex gap-3 flex-wrap">
                                            <span class="badge bg-light text-dark">
                                                <i class="bi bi-calendar"></i> <?= $package['schedule_days'] ?> days
                                            </span>
                                            <span class="badge bg-light text-dark">
                                                <i class="bi bi-people"></i> <?= $package['numberofpeople_based'] ?> - <?= $package['numberofpeople_maximum'] ?> pax
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                        <h6 class="fw-bold mb-3" style="color: var(--accent); font-size: 1.2rem;">
                                            <?= $package['pricing_currency'] ?> <?= number_format($package['pricing_foradult'], 2) ?>
                                        </h6>
                                        <a href="tour-packages-view.php?id=<?= $package['tourpackage_ID'] ?>" 
                                           class="btn btn-warning btn-sm" style="background: var(--accent); color: white; border: none;">
                                            View & Book
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state profile-card">
                            <i class="bi bi-inbox"></i>
                            <p>This guide hasn't created any tour packages yet.</p>
                        </div>
                    <?php endif; ?>