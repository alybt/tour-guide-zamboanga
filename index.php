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
    <title>Tourismo Zamboanga - Discover the World with Local Guides</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="assets/css/public-pages/index.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-map-marked-alt"></i> Tourismo Zamboanga</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#how-it-works">How It Works</a></li>
                    <li class="nav-item"><a class="nav-link" href="#destinations">Destinations</a></li>
                    <li class="nav-item"><a class="nav-link" href="#become-guide">Become a Guide</a></li>
                    <li class="nav-item"><a class="nav-link" href="login.php">Sign In</a></li>
                    <li class="nav-item"><button class="btn btn-get-started">Get Started</button></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content text-center">
                <h1>Discover the World with Local Guides</h1>
                <p>Connect with expert local guides for authentic, personalized travel experiences</p>
                <div class="hero-search">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Where do you want to explore?">
                        <button class="btn" type="button"><i class="fas fa-search me-2"></i> Search Guides</button>
                    </div>
                </div>
                <div class="mt-4">
                    <small style="opacity: 0.8;">Popular: Sta. Cruz Beach</small>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="container">
            <div class="section-title">
                <h2>Why Choose Tourismo Zamboanga?</h2>
                <p>Experience travel like never before with verified local experts</p>
            </div>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-user-check"></i></div>
                        <h4>Local Guides</h4>
                        <p>Guides are Locals</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-star"></i></div>
                        <h4>Personalized Experiences</h4>
                        <p>Custom tours tailored to your interests, pace, and preferences for unforgettable memories</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-dollar-sign"></i></div>
                        <h4>Best Price Guarantee</h4>
                        <p>Transparent pricing with no hidden fees. Book directly with guides at competitive rates</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-comments"></i></div>
                        <h4>Instant Communication</h4>
                        <p>Chat directly with guides to plan your perfect itinerary before you arrive</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                        <h4>Secure Payments</h4>
                        <p>Protected transactions with our secure payment system and money-back guarantee</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-headset"></i></div>
                        <h4>24/7 Support</h4>
                        <p>Our customer support team is always available to assist you before, during, and after your tour</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works" id="how-it-works">
        <div class="container">
            <div class="section-title">
                <h2>How It Works</h2>
                <p>Book your perfect tour in just 3 simple steps</p>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <h5>Find Your Guide</h5>
                        <p>Search for guides in your destination and browse their profiles, reviews, and specialties</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <h5>Book & Customize</h5>
                        <p>Request a booking or choose a pre-made tour package. Chat with your guide to customize your experience</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <h5>Explore & Enjoy</h5>
                        <p>Meet your guide and embark on an unforgettable journey through the eyes of a local</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Popular Destinations -->
    <section class="destinations-section" id="destinations">
        <div class="container">
            <div class="section-title">
                <h2>Popular Destinations</h2>
                <p>Explore the world's most amazing places with local experts</p>
            </div>
            <div class="row">
                <?php $i = 0;
                    foreach($tourspots as $spot){
                        if ($i++ >= 6) break;
                        $image = $tourmanagerObj->getAllSpotsImages($spot['spots_ID']);
                        ?>
                        <div class="col-md-4">
                            <div class="destination-card">
                                <img src="<?= $image['spotsimage_PATH'];?>" alt="<?= $spot['spots_name'] ?? ''; ?>">
                                <div class="destination-overlay">
                                    <h4><?= $spot['spots_name'] ?? ''; ?></h4>
                                </div>
                            </div>
                        </div>
                    <?php } ?> 
                
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section" id="become-guide">
        <div class="container">
            <h2>Ready to Start Your Journey?</h2>
            <p>Join thousands of travelers who have discovered the world through local eyes</p>
            <div class="cta-buttons">
                <button class="btn btn-cta-primary">Find a Guide</button>
                <button class="btn btn-cta-secondary">Become a Guide</button>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h3 class="mb-3"><i class="fas fa-map-marked-alt" style="color: var(--accent);"></i> Tourismo Zamboanga</h3>
                    <p style="color: var(--secondary-accent);">Connecting travelers with authentic local experiences worldwide.</p>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-md-2 mb-4 footer-links">
                    <h5>Company</h5>
                    <ul>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Press</a></li>
                        <li><a href="#">Blog</a></li>
                    </ul>
                </div>
                <div class="col-md-2 mb-4 footer-links">
                    <h5>Support</h5>
                    <ul>
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Contact Us</a></li>
                        <li><a href="#">Trust & Safety</a></li>
                        <li><a href="#">FAQs</a></li>
                    </ul>
                </div>
                <div class="col-md-2 mb-4 footer-links">
                    <h5>Guides</h5>
                    <ul>
                        <li><a href="#">Become a Guide</a></li>
                        <li><a href="#">Guide Resources</a></li>
                        <li><a href="#">Guide Login</a></li>
                        <li><a href="#">Success Stories</a></li>
                    </ul>
                </div>
                <div class="col-md-2 mb-4 footer-links">
                    <h5>Legal</h5>
                    <ul>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Cookie Policy</a></li>
                        <li><a href="#">Disclaimer</a></li>
                    </ul>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; 2025 Tourismo Zamboanga. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Navbar scroll effect
            $(window).scroll(function() {
                if ($(this).scrollTop() > 50) {
                    $('.navbar').addClass('scrolled');
                } else {
                    $('.navbar').removeClass('scrolled');
                }
            });

            // Smooth scrolling
            $('a[href^="#"]').on('click', function(e) {
                e.preventDefault();
                const target = $(this.getAttribute('href'));
                if (target.length) {
                    $('html, body').stop().animate({
                        scrollTop: target.offset().top - 70
                    }, 1000);
                }
            });

            // Hero search
            $('.hero-search button').on('click', function() {
                const query = $('.hero-search input').val();
                alert('Searching for guides in: ' + query);
            });

            // CTA buttons
            $('.btn-cta-primary, .btn-get-started').on('click', function() {
                window.location.href = 'search-guides.html';
            });

            $('.btn-cta-secondary').on('click', function() {
                window.location.href = 'become-guide.html';
            });
        });
    </script>
</body>
</html>



    