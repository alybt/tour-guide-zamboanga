<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Admin' || $_SESSION['user']['account_status'] == 'Suspended') {
    header('Location: ../../index.php');
    exit;
}

require_once "../../classes/tour-manager.php";
require_once "../../classes/guide.php";

$tourManager = new TourManager();
$guideObj = new Guide();

// Get all tour packages with their related information
$packages = $tourManager->viewAllPackages();

// Get all guides for reference
$guides = $guideObj->viewAllGuide();
$guidesById = [];
foreach ($guides as $guide) {
    $guidesById[$guide['guide_ID']] = $guide;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tour Packages | Tourismo Zamboanga</title>

    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>

    <style>
        :root {
            --primary-color: #ffffff;
            --secondary-color: #213638;
            --accent: #E5A13E;
            --secondary-accent: #CFE7E5;
            --text-dark: #2d3436;
            --text-light: #636e72;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: var(--text-dark);
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 260px;
            background: var(--secondary-color);
            color: var(--primary-color);
            padding-top: 1.5rem;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar .logo {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--accent);
            text-align: center;
            margin-bottom: 2rem;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.85);
            padding: 0.85rem 1.5rem;
            border-radius: 0;
            transition: all 0.2s;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(229, 161, 62, 0.15);
            color: var(--accent);
        }

        .sidebar .nav-link i {
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }

        .sidebar .nav-text {
            white-space: nowrap;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 2rem;
            transition: all 0.3s ease;
        }

        .header-card {
            background: var(--primary-color);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(33, 54, 56, 0.08);
            padding: 1.75rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(207, 231, 229, 0.3);
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-weight: 600;
        }

        .clock {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: var(--secondary-color);
            font-size: 1.1rem;
        }

        .table-card {
            background: var(--primary-color);
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border: 1px solid rgba(207, 231, 229, 0.4);
            overflow: hidden;
        }

        .table thead {
            background-color: var(--secondary-color);
            color: var(--primary-color);
        }

        .table thead th {
            font-weight: 600;
            border: none;
        }

        .table tbody tr:hover {
            background-color: rgba(207, 231, 229, 0.2);
        }

        .btn-action {
            padding: 0.35rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 8px;
        }

        .alert-custom {
            border-radius: 12px;
            font-weight: 500;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
            }
            .sidebar .nav-text,
            .sidebar .logo span {
                display: none;
            }
            .main-content {
                margin-left: 80px;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 1rem;
            }
            .table-responsive {
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>

    <?php include 'includes/dashboard.php'; ?>

    <!-- Main Content -->
    <main class="main-content">

        <!-- Header -->
        <div class="header-card d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h3 class="mb-1 fw-bold">Manage Tour Packages</h3>
                <p class="text-muted mb-0">View, edit, or delete existing tour packages.</p>
            </div>
            <div class="text-md-end">
                <div class="d-flex align-items-center gap-3 flex-wrap justify-content-md-end">
                    <span class="badge bg-success status-badge">
                        <i class="bi bi-shield-check"></i> Active
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

        <!-- Add New Package Button -->
        <div class="mb-4 text-end">
            <a href="tour-packages-add.php" class="btn btn-primary btn-lg">
                <i class="bi bi-plus-circle me-2"></i>Add New Package
            </a>
        </div>

        <!-- Packages Table -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th>Package Name</th>
                            <th>Description</th>
                            <th>Guide</th>
                            <th>Days</th>
                            <th>Max People</th>
                            <th>Min People</th>
                            <th>Base Price</th>
                            <th>Discount</th>
                            <th>Tour Spots</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($packages as $package): 
                            $spots = $tourManager->getSpotsByPackage($package['tourpackage_ID']);
                            $spotNames = array_map(function($spot) {
                                return $spot['spots_name'];
                            }, $spots);
                        ?>
                        <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td><strong><?= htmlspecialchars($package['tourpackage_name']); ?></strong></td>
                            <td><?= htmlspecialchars($package['tourpackage_desc']); ?></td>
                            <td><?= htmlspecialchars($guidesById[$package['guide_ID']]['guide_name'] ?? 'Unknown'); ?></td>
                            <td class="text-center"><?= htmlspecialchars($package['schedule_days']); ?></td>
                            <td class="text-center"><?= htmlspecialchars($package['numberofpeople_maximum']); ?></td>
                            <td class="text-center"><?= htmlspecialchars($package['numberofpeople_based']); ?></td>
                            <td><?= htmlspecialchars(($package['pricing_currency'] ?? '') . ' ' . number_format($package['pricing_foradult'] ?? 0, 2)); ?></td>
                            <td><?= htmlspecialchars(($package['pricing_currency'] ?? '') . ' ' . number_format($package['pricing_discount'] ?? 0, 2)); ?></td>
                            <td><?= htmlspecialchars(implode(', ', $spotNames)); ?></td>
                            <td class="text-center">
                                    
                                    <?php 
                                    $isSuspended = ($package['tourpackage_status'] ?? 'Active') === 'Suspended';
                                    $btnClass = $isSuspended ? 'btn-outline-success' : 'btn-outline-danger';
                                    $icon = $isSuspended ? 'bi-play-circle' : 'bi-pause-circle';
                                    $text = $isSuspended ? 'Resume' : 'Suspend';
                                    ?>

                                    <button type="button" 
                                            class="btn <?= $btnClass ?> btn-action btn-sm tourpackage-suspend-btn"
                                            data-id="<?= $package['tourpackage_ID']; ?>"
                                            data-current-status="<?= $package['tourpackage_status'] ?? 'Active'; ?>">
                                        <i class="bi <?= $icon ?>"></i> <?= $text ?>
                                    </button>
                                    <a href="tour-packages-delete.php?id=<?= $package['tourpackage_ID'] ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Delete this package? This cannot be undone.')">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                </td>
                        </tr>
                        <?php endforeach; ?>

                        <?php if (empty($packages)): ?>
                        <tr>
                            <td colspan="11" class="text-center py-4 text-muted">No tour packages found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- Bootstrap JS -->
    <script src="../../assets/vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Live Clock -->
    <script>
    function tourpackageSuspend() {
        document.querySelectorAll('.tourpackage-suspend-btn').forEach(button => {
            button.addEventListener('click', function() {
                const packageId = this.getAttribute('data-id');
                const currentStatus = this.getAttribute('data-current-status');

                if (!confirm(`Are you sure you want to ${currentStatus === 'Active' ? 'suspend' : 'resume'} this tour package?`)) {
                    return;
                }

                fetch('tour-packages-suspended.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: packageId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update button appearance
                        const newStatus = data.newStatus;
                        const isSuspended = newStatus === 'Suspended';

                        this.setAttribute('data-current-status', newStatus);

                        if (isSuspended) {
                            this.classList.remove('btn-outline-success');
                            this.classList.add('btn-outline-danger');
                            this.innerHTML = '<i class="bi bi-play-circle"></i> Resume';
                        } else {
                            this.classList.remove('btn-outline-danger');
                            this.classList.add('btn-outline-success');
                            this.innerHTML = '<i class="bi bi-pause-circle"></i> Suspend';
                        }

                        // Optional: show toast/alert
                        alert(data.message);
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('An error occurred. Check console.');
                });
            });
        });
    }

    // Run on page load
    document.addEventListener('DOMContentLoaded', tourpackageSuspend);
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