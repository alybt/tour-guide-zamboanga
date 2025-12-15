<?php
session_start();
$current_page = basename($_SERVER['PHP_SELF']);
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tour Guide') {
    header('Location: ../../index.php');
    exit;
} else if ($_SESSION['user']['account_status'] == 'Suspended') {
    header('Location: account-suspension.php');
    exit;
} else if ($_SESSION['user']['account_status'] == 'Pending') {
    header('Location: account-pending.php');
    exit;
}

require_once "../../classes/guide.php";

$guideObj = new Guide();

$guide_ID = $guideObj->getGuide_ID($_SESSION['user']['account_ID']);
$packages = $guideObj->viewPackageByGuideID($guide_ID);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Tour Packages | Tourismo Zamboanga</title>

    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../../assets/css/guide/tour-packages.css">
    <link rel="stylesheet" href="../../assets/css/guide/dashboard.css">
</head>
<body>

    <?php 
        require_once "includes/aside-dashboard.php"; 
        include_once "includes/aside-dashboard.php";
    ?>

    <!-- Main Content -->
    <main class="main-content">

        <!-- Header -->
        <div class="header-card d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h3 class="mb-1 fw-bold">Manage Tour Packages</h3>
                <p class="text-muted mb-0">Create, edit, and manage your tour offerings.</p>
            </div>
            <div class="text-md-end">
                <div class="d-flex align-items-center gap-3 flex-wrap justify-content-md-end">
                    <span class="badge bg-success status-badge">
                        <i class="bi bi-check-circle"></i> <?= ucfirst($_SESSION['user']['account_status']) ?>
                    </span>
                    <div class="clock" id="liveClock"></div>
                </div>
                <small class="text-muted d-block mt-1">Philippine Standard Time (PST)</small>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-custom alert-success p-3">
                <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-custom alert-error p-3">
                <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="d-flex gap-2 mb-4">
            <a href="tour-packages-add.php" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle"></i> Add New Package
            </a>
        </div>

        <!-- Packages Table -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Package Name</th>
                            <th>Description</th>
                            <th>Days</th>
                            <th>Max</th>
                            <th>Min</th>
                            <th>Base Price</th>
                            <th>Discount</th>
                            <th>Spots</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($packages)): ?>
                            <?php $no = 1; foreach ($packages as $package): 
                                $schedule = $guideObj->getScheduleByID($package['schedule_ID']);
                                $people = $guideObj->getPeopleByID($schedule['numberofpeople_ID']);
                                $pricing = $guideObj->getPricingByID($people['pricing_ID']);
                                $spots = $guideObj->getSpotsByPackage($package['tourpackage_ID']);
                                $spotNames = array_map(fn($spot) => $spot['spots_name'], $spots);
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($package['tourpackage_name']) ?></strong>
                                    </td>
                                    <td class="text-truncate-200">
                                        <?= htmlspecialchars($package['tourpackage_desc']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($schedule['schedule_days']) ?></td>
                                    <td><?= htmlspecialchars($people['numberofpeople_maximum']) ?></td>
                                    <td><?= htmlspecialchars($people['numberofpeople_based']) ?></td>
                                    <td>₱<?= number_format($pricing['pricing_foradult'], 2) ?></td>
                                    <td><?= $pricing['pricing_discount'] ?>%</td>
                                    <td class="text-truncate-150">
                                        <?= !empty($spotNames) ? htmlspecialchars(implode(', ', $spotNames)) : '—' ?>
                                    </td>
                                    <td>
                                        <a href="tour-packages-edit.php?id=<?= $package['tourpackage_ID'] ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="tour-packages-delete.php?id=<?= $package['tourpackage_ID'] ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Delete this package? This cannot be undone.')">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="bi bi-box-seam fs-1 d-block mb-2"></i>
                                    You haven't created any tour packages yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Live Clock (PH Time) -->
    <script>
        function updateClock() {
            const now = new Date();
            const options = {
                timeZone: 'Asia/Manila',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            };
            document.getElementById('liveClock').textContent = now.toLocaleTimeString('en-US', options);
        }
        updateClock();
        setInterval(updateClock, 1000);
    </script>
</body>
</html>