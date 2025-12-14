<?php if (!empty($bookings)): ?>
                        <?php foreach ($bookings as $booking): 
                            if (in_array($booking['booking_status'], ['Completed','Cancelled','Cancelled - No Refund','Refunded','Failed','Rejected by the Guide','Booking Expired — Payment Not Completed','Booking Expired — Guide Did Not Confirm in Time'])) continue;
                            
                            $statusDetail = getStatusDetails($booking['booking_status']);
                                $cardClass = str_replace('status-', '', $statusDetail['class']);
                            
                            
                            $guideName = $booking['guide_name'] ?? 'Local Guide'; 
                            $guidePhoto = $booking['guide_photo'] ?? '' . rand(1, 70);
                            $tourName = htmlspecialchars($booking['tourpackage_name'] ?? 'Untitled Tour');
                            $tourPrice = number_format($booking['total_price'] ?? 0.00, 2);
                            $tourPax = $booking['pax_count'] ?? 1;

                            
                            $timeRemaining = getTimeRemaining($booking['booking_start_date'] . ' ' . ($booking['booking_start_time'] ?? '00:00:00'));
                        ?>
                        <div class="booking-card <?= $cardClass ?> booking-item" data-category="<?= $cardClass ?>">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="guide-info">
                                    <img src="<?= $guidePhoto ?>" class="guide-avatar" alt="Guide">
                                    <div>
                                        <h6 class="mb-1 fw-bold"><?= $tourName ?></h6>
                                        <p class="text-muted mb-1 small">with <?= htmlspecialchars($guideName) ?></p>
                                        <div class="text-warning small">
                                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                                            <span class="text-muted">(<?= $booking['guide_rating_count'] ?? rand(50, 250) ?>)</span>
                                        </div>
                                    </div>
                                </div>
                                <span class="status-badge <?= $statusDetail['class'] ?>">
                                    <i class="fas <?= $statusDetail['icon'] ?> me-1"></i> <?= $statusDetail['text'] ?>
                                </span>
                            </div>

                            <div class="booking-details py-2">
                                <div class="detail-row small">
                                    <strong><i class="fas fa-calendar me-2"></i>Date:</strong>
                                    <span><?= date('M j, Y', strtotime($booking['booking_start_date'])) ?> 
                                        <?php if ($booking['booking_start_date'] !== $booking['booking_end_date']): ?>
                                            → <?= date('M j, Y', strtotime($booking['booking_end_date'])) ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="detail-row small">
                                    <strong><i class="fas fa-clock me-2"></i>Time:</strong>
                                    <span><?= date('g:i A', strtotime($booking['booking_start_time'] ?? '00:00:00')) ?></span>
                                </div>
                            </div>
                            
                            <?php if ($booking['booking_status'] == 'Approved' && $timeRemaining): ?>
                                <div class="countdown-timer mb-3 small">
                                    <i class="fas fa-clock me-2"></i>
                                    <strong>Starts in: <?= $timeRemaining ?></strong>
                                </div>
                            <?php elseif (in_array($booking['booking_status'], ['Pending for Payment'])): ?>
                                <div class="alert alert-danger mb-3 py-2 small">
                                    <i class="fas fa-credit-card me-2"></i>
                                    **Payment required** to confirm.
                                </div>
                            <?php endif; ?>

                            <div class="d-flex gap-2 flex-wrap pt-2">
                                <a href="booking-view.php?id=<?= $booking['booking_ID'] ?>" class="btn btn-primary btn-sm flex-fill">
                                    <i class="fas fa-eye me-1"></i> View
                                </a>
                                
                                <?php if ($booking['booking_status'] == 'Pending for Payment'): ?>
                                    <a href="payment-form.php?id=<?= $booking['booking_ID'] ?>" class="btn btn-success btn-sm flex-fill">
                                        <i class="fas fa-money-bill-wave me-1"></i> Pay
                                    </a>
                                <?php endif; ?>
                                
                                <a href="booking-cancel.php?id=<?= $booking['booking_ID'] ?>" class="btn btn-danger btn-sm cancel-booking flex-fill d-flex justify-content-center align-items-center" data-name="<?= $tourName ?>" style="opacity: 0.8;">
                                    <i class="fas fa-trash-alt me-1"></i> Cancel
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state text-center p-5 bg-white rounded border">
                            <i class="fas fa-inbox fs-1 text-muted"></i>
                            <h5 class="mt-3">No Active Bookings</h5>
                            <p class="text-muted small">Start exploring tours!</p>
                            <a href="tour-packages-browse.php" class="btn btn-primary mt-2">Explore Tours</a>
                        </div>
                    <?php endif; ?>