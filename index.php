<?php 
session_start();

require_once "classes/tour-manager.php";
require_once "classes/booking.php";


$bookingObj = new Booking();
$updateBookings = $bookingObj->updateBookings();
$tourmanagerObj = new TourManager();


$tourspots = $tourmanagerObj->getAllSpots();
$tourcategory = $tourmanagerObj->getCategoryandImage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tourismo Zamboanga</title>
    
    <!-- Bootstrap File -->
    <link rel="stylesheet" href="assets/css/bootstrap-grid.css">
    <link rel="stylesheet" href="assets/css/bootstrap-reboot.css">
    <link rel="stylesheet" href="assets/css/bootstrap.css">

    <!-- Vendor CSS Files
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
    <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet"> -->

    <!--Inner CSS Files -->
    
    
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/header.css">
    
    <!-- Bootstrap -->
    <link rel="stylesheet" href="vendor/components/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/vendor/twbs/bootstrap/dist/css/bootstrap.min.css">
    
</head>
<body>
    <header class = "header">
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">Tourismo Zamboanga</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link active" href="#">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Tour Packages</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Tour Spots</a></li>
            </ul>
            <a href="login.php" class="btn btn-info ms-lg-3">Sign In</a>
            </div>
        </div>
        </nav>

    </header>
    <main>
        <section id="hero">
        <!-- Slideshow background: multiple .slide children -->
            <div class="slideshow" aria-hidden="true">
                <div class="slide" style="background-image: url('assets/img/tour-spots/fort-pilar/1.jpg');"></div>
                <div class="slide" style="background-image: url('assets/img/tour-spots/great-santa-cruz-island/15.jpg');"></div>
                <div class="slide" style="background-image: url('assets/img/tour-spots/fort-pilar/2.jpg');"></div>
                <div class="slide" style="background-image: url('assets/img/tour-spots/fort-pilar/15.jpg');"></div>
                <div class="slide" style="background-image: url('assets/img/tour-spots/great-santa-cruz-island/4.jpg');"></div>
                <!-- add more .slide divs as needed -->
            </div>

            <!-- Foreground content (on top of slideshow) -->
            <div class="info d-flex align-items-center">
                <div class="container">
                <div class="row justify-content-center" data-aos="fade-up" data-aos-delay="100">
                    <div class="col-lg-8 text-center">
                    <h2>Want to travel in Zamboanga?</h2>
                    <p>And save time and effort to have a guide</p>
                    <!-- <a href="login.php" class="btn-get-started">Connect with a Local Guide Now!</a> -->
                    </div>
                </div>
                <div class="container">
                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <div class="search-container">
                                    <input type="text" class="form-control search-input" placeholder="Search...">
                                    <i class="fas fa-search search-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id = "about">
            

        </section>

        <section id = "tour-spots">
            <?php foreach ($tourspots as $tour){ ?>
                <div class = "card-section">
                    <div class="card" style="">
                        <img src="..." class="card-img-top" alt="<?= $tour['spots_name']; ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?= $tour['spots_name']; ?></h5>
                            <p class="card-text"><?= $tour['spots_description'];?></p>
                            <a href="#" class="btn btn-primary">Book a tour</a>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </section>

        <section id = "marketing">
            

        </section>

        <section id="category">
            <div class="container-fluid">
                <h2> Find your tour by interest</h2>
                <br>
                <div class="card-tile-section"> 
                    <?php 
                    if (is_array($tourcategory) && count($tourcategory) > 0) {
                        foreach ($tourcategory as $t) { 
                            $image_paths = explode(',', $t['images']);
                            $first_image = trim($image_paths[0]);
                    ?>
                            <div class="category-tile"> 
                                <div class="card" style="background: linear-gradient(rgba(0, 0, 0, 0.42), rgba(0, 0, 0, 0.75)), url('<?= $first_image; ?>');">
                                    <div class="card-description">
                                        <h2><?= $t['spots_category']; ?></h2>
                                    </div>
                                    <a class="card-link" href="#" ></a>
                                </div>
                            </div>
                    <?php 
                        } 
                    } else {
                        echo '<p>No tour categories found.</p>';
                    }
                    ?>
                </div>
            </div>
        </section>
        <section id = "tourpackages">

        </section>

    </main>


    <!-- Scripts -->
    <script>
        (function preloadImgs(urls){
            urls.forEach(u => {
            const img = new Image();
            img.src = u;
            });
        })([
            'assets/img/tour-spots/fort-pilar/1.jpg',
            'assets/img/tour-spots/fort-pilar/2.jpg',
            'assets/img/tour-spots/fort-pilar/15.jpg'
        ]);
        window.addEventListener("scroll", function() {
        const navbar = document.querySelector(".navbar");
        const hero = document.querySelector("#hero");

        if (window.scrollY > hero.offsetHeight - 80) {
            navbar.classList.add("scrolled");
        } else {
            navbar.classList.remove("scrolled");
        }
        });
    </script>



    <!-- Vendor JS Files
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/php-email-form/validate.js"></script>
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
    <script src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
    <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
    <script src="assets/vendor/glightbox/js/glightbox.min.js"></script> -->

    <!-- ✅ Bootstrap JS (includes Popper.js for dropdowns/collapse)
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script> -->
    
    <script src="assets/vendor/components/jquery/jquery.min.js"></script>
    <script src="assets/vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>



    