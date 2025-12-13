<?php
// traits/Itinerary.php

trait Itinerary
{
    public function sendBookingItineraryEmail(
        array $booking,
        array $package,
        array $guide,
        array $spots,
        array $companions,
        string $touristEmail,
        string $touristName
    ): array {
        $html = $this->generateItineraryHTML($booking, $package, $guide, $spots, $companions, $touristName);

        $this->addRecipient($touristEmail, $touristName);
        $this->setContent(
            "Your Tour Itinerary - Booking #{$booking['booking_ID']} Confirmed!",
            $html
        );

        return $this->send()
            ? ['success' => true, 'message' => 'Itinerary sent!']
            : ['success' => false, 'message' => $this->mail->ErrorInfo];
    }

    /**
     * Generate the beautiful HTML (can also be used separately)
     */
    public function generateItineraryHTML(
        array $booking,
        array $package,
        array $guide,
        array $spots,
        array $companions,
        string $touristName
    ): string {
        $bookingID      = str_pad($booking['booking_ID'], 5, '0', STR_PAD_LEFT);
        $startDate      = date('F j, Y', strtotime($booking['booking_start_date']));
        $endDate        = date('F j, Y', strtotime($booking['booking_end_date']));
        $totalDays      = round((strtotime($endDate) - strtotime($startDate)) / 86400) + 1;
        $selfIncluded   = !empty($booking['is_selfIncluded']) || !empty($booking['booking_isselfIncluded']);
        $totalTravelers = ($selfIncluded ? 1 : 0) + count($companions);

        ob_start(); ?>

        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Itinerary #<?= $bookingID ?></title>
            <style>
                body { font-family: 'Segoe UI', sans-serif; background:#f8f9fa; margin:0; padding:20px; }
                .container { max-width:650px; margin:0 auto; background:white; border-radius:12px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.1); }
                .header { background:linear-gradient(135deg,#1e40af,#3b82f6); color:white; padding:40px 30px; text-align:center; }
                .header h1 { margin:0; font-size:32px; }
                .content { padding:30px; line-height:1.7; }
                .section h2 { color:#1e40af; border-bottom:3px solid #3b82f6; padding-bottom:8px; font-size:22px; }
                .grid { display:grid; grid-template-columns:1fr 1fr; gap:15px; margin:15px 0; }
                .grid strong { color:#1e40af; }
                .spots { background:#f0f9ff; padding:20px; border-radius:10px; border-left:5px solid #3b82f6; }
                .traveler { background:#ecfdf5; padding:12px; border-radius:8px; margin:8px 0; }
                .footer { background:#1e40af; color:white; text-align:center; padding:25px; font-size:14px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Tourismo Zamboanga</h1>
                    <h2>Booking Confirmed!</h2>
                    <h3>#<?= $bookingID ?> • <?= $booking['booking_status'] ?></h3>
                </div>
                <div class="content">
                    <p>Hi <strong><?= htmlspecialchars($touristName) ?></strong>,</p>
                    <p>Your tour is confirmed! Here’s your complete itinerary:</p>

                    <div class="section">
                        <h2>Tour Details</h2>
                        <div class="grid">
                            <div><strong>Package:</strong> <?= htmlspecialchars($package['tourpackage_name']) ?></div>
                            <div><strong>Duration:</strong> <?= $totalDays ?> day(s)</div>
                            <div><strong>Dates:</strong> <?= $startDate ?> → <?= $endDate ?></div>
                            <div><strong>Guide:</strong> <?= htmlspecialchars($guide['guide_name'] ?? 'TBA') ?></div>
                        </div>
                    </div>

                    <div class="section">
                        <h2>Spots You'll Visit</h2>
                        <div class="spots">
                            <?php foreach ($spots as $spot): ?>
                                <div style="margin:12px 0;">
                                    <strong><?= htmlspecialchars($spot['spots_name']) ?></strong><br>
                                    <small style="color:#555"><?= htmlspecialchars($spot['spots_description']) ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="section">
                        <h2>Travelers (<?= $totalTravelers ?>)</h2>
                        <?php if ($selfIncluded): ?>
                            <div class="traveler"><strong>You (Lead)</strong> — <?= htmlspecialchars($touristName) ?></div>
                        <?php endif; ?>
                        <?php foreach ($companions as $c): ?>
                            <div class="traveler">
                                <strong><?= htmlspecialchars($c['companion_name']) ?></strong>
                                — <?= htmlspecialchars($c['companion_category_name'] ?? 'Guest') ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="text-align:center; padding:30px; background:#dbeafe; border-radius:12px;">
                        <h3>Get Ready for an Amazing Adventure!</h3>
                        <p>Your guide will contact you 24 hours before departure.</p>
                        <p><strong>Emergency:</strong> +63 912 345 6789</p>
                    </div>
                </div>
                <div class="footer">
                    <p>&copy; <?= date('Y') ?> Tourismo Zamboanga</p>
                    <p>Fort Pilar, Zamboanga City | support@tourismozamboanga.com</p>
                </div>
            </div>
        </body>
        </html>

        <?php
        return ob_get_clean();
    }
}