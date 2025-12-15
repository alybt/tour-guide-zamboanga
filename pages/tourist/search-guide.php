<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tourist') {
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



?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Your Perfect Guide - Tourismo Zamboanga</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #ffffff;
            --secondary-color: #213638;
            --accent: #E5A13E;
            --secondary-accent: #CFE7E5;
            --muted-color: gainsboro;
        }

        body {
            background-color: var(--muted-color);
            margin-top: 5rem;
        } 

        .navbar {
            background-color: var(--secondary-color) !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            color: var(--primary-color) !important;
            font-weight: bold;
            font-size: 1.5rem;
        }

        .navbar-brand i {
            color: var(--accent);
        }

        .nav-link {
            color: var(--secondary-accent) !important;
        }

        .nav-link:hover {
            color: var(--accent) !important;
        }

        .search-hero {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #2d4a4d 100%);
            color: white;
            padding: 60px 0 40px;
            margin-bottom: 30px;
        }

        .search-box {
            background: white;
            border-radius: 50px;
            padding: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .search-box input {
            border: none;
            padding: 10px 20px;
        }

        .search-box input:focus {
            outline: none;
            box-shadow: none;
        }

        .search-box button {
            background-color: var(--accent);
            border: none;
            color: var(--secondary-color);
            padding: 10px 30px;
            border-radius: 50px;
            font-weight: 600;
        }

        .filter-sidebar {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
        }

        .filter-title {
            color: var(--secondary-color);
            font-weight: bold;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--accent);
        }

        .filter-section {
            margin-bottom: 25px;
        }

        .filter-section h6 {
            color: var(--secondary-color);
            font-weight: 600;
            margin-bottom: 12px;
        }

        .form-check-input:checked {
            background-color: var(--accent);
            border-color: var(--accent);
        }

        .price-range {
            margin-top: 10px;
        }

        .guide-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s;
            margin-bottom: 25px;
            height: 100%;
        }

        .guide-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .guide-card-img {
            position: relative;
            height: 250px;
            overflow: hidden;
        }

        .guide-card-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .guide-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: var(--accent);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .online-status {
            position: absolute;
            top: 15px;
            left: 15px;
            width: 12px;
            height: 12px;
            background-color: #00ff00;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 0 10px rgba(0,255,0,0.5);
        }

        .guide-card-body {
            padding: 20px;
        }

        .guide-name {
            color: var(--secondary-color);
            font-weight: bold;
            font-size: 1.3rem;
            margin-bottom: 5px;
        }

        .guide-location {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .guide-rating {
            color: var(--accent);
            font-weight: 600;
            margin-bottom: 15px;
        }

        .guide-rating i {
            color: var(--accent);
        }

        .guide-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding: 12px;
            background-color: var(--secondary-accent);
            border-radius: 8px;
        }

        .guide-stat {
            text-align: center;
            flex: 1;
        }

        .guide-stat .number {
            font-weight: bold;
            color: var(--secondary-color);
            font-size: 1.1rem;
        }

        .guide-stat .label {
            font-size: 0.75rem;
            color: #6c757d;
        }

        .guide-languages {
            margin-bottom: 15px;
        }

        .language-badge {
            display: inline-block;
            background-color: var(--secondary-accent);
            color: var(--secondary-color);
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.75rem;
            margin-right: 5px;
            margin-bottom: 5px;
        }

        .guide-price {
            color: var(--accent);
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .guide-price small {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: normal;
        }

        .btn-view-profile {
            background-color: var(--accent);
            border-color: var(--accent);
            color: var(--secondary-color);
            font-weight: 600;
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .btn-view-profile:hover {
            background-color: #d89435;
            transform: scale(1.02);
        }

        .btn-favorite {
            position: absolute;
            bottom: 15px;
            right: 15px;
            background: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            color: var(--accent);
            font-size: 1.2rem;
            transition: all 0.3s;
        }

        .btn-favorite:hover {
            transform: scale(1.1);
        }

        .btn-favorite.active {
            background-color: var(--accent);
            color: white;
        }

        .results-header {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .sort-dropdown {
            border: 1px solid var(--accent);
            border-radius: 8px;
            padding: 8px 15px;
            color: var(--secondary-color);
        }

        .sort-dropdown:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(229, 161, 62, 0.25);
        }

        .specialty-tag {
            background-color: var(--secondary-color);
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.7rem;
            display: inline-block;
            margin-right: 5px;
            margin-bottom: 5px;
        }

        .view-toggle {
            display: flex;
            gap: 10px;
        }

        .view-toggle button {
            border: 2px solid var(--accent);
            background: white;
            color: var(--accent);
            padding: 8px 15px;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .view-toggle button.active {
            background: var(--accent);
            color: var(--secondary-color);
        }

        .pagination {
            margin-top: 30px;
        }

        .pagination .page-link {
            color: var(--accent);
            border-color: var(--accent);
        }

        .pagination .page-link:hover {
            background-color: var(--accent);
            color: white;
        }

        .pagination .page-item.active .page-link {
            background-color: var(--accent);
            border-color: var(--accent);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'?>

    <div class="container">
        <div class="row">
            
    
            <!-- Main Content -->
            <main class="col-md-9">
                <!-- Result -->
                <!-- <div class="results-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Found <strong>127 Guides</strong> in Rome, Italy</h5>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="view-toggle">
                                <button class="active" id="gridView"><i class="fas fa-th"></i></button>
                                <button id="listView"><i class="fas fa-list"></i></button>
                            </div>
                            <select class="sort-dropdown">
                                <option>Sort by: Recommended</option>
                                <option>Highest Rated</option>
                                <option>Most Popular</option>
                                <option>Price: Low to High</option>
                                <option>Price: High to Low</option>
                                <option>Newest</option>
                            </select>
                        </div>
                    </div>
                </div> -->
                

                <div class="row" id="guideGrid">
                    <?php include 'includes/components/search-guide-card.php' ?>
                </div>


            </main>
        </div>
    </div>
</body>
</html>