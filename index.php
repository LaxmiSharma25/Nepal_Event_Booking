<?php
require_once 'includes/header.php';
?>

<section class="hero">
    <div class="hero-content">
        <h1>Plan Your Perfect Nepali Event</h1>
        <p>We provide comprehensive services for traditional Nepali ceremonies like Bratabandh, Marriage, Mehendi, and more.</p>
        <div class="hero-buttons">
            <a href="events.php" class="btn btn-primary">Explore Events</a>
            <a href="services.php" class="btn btn-outline">View Services</a>
        </div>
    </div>
</section>

<section class="featured-events">
    <div class="section-heading">
        <h2>Our Featured Events</h2>
    </div>
    <div class="card-container">
        <?php
        $events = getEventCategories($conn);
        foreach ($events as $event) {
        ?>
        <div class="card">
            <div class="card-img">
                <img src="<?php echo $event['image']; ?>" alt="<?php echo $event['name']; ?>">
            </div>
            <div class="card-content">
                <h3><?php echo $event['name']; ?></h3>
                <p><?php echo $event['description']; ?></p>
                <div style="display: flex; gap: 10px;">
                    <a href="event_detail.php?id=<?php echo $event['id']; ?>" class="btn btn-primary">View Details</a>
                    <a href="buy_now.php?service_id=<?php echo $event['id']; ?>" class="btn btn-success">Book Now</a>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>
</section>

<section class="services-overview">
    <div class="section-heading">
        <h2>Our Services</h2>
    </div>
    <div class="services-grid">
        <div class="service-card">
            <div class="service-icon">
                <i class="fas fa-camera"></i>
            </div>
            <h3 class="service-title">Photography</h3>
            <p>Professional photography services to capture your special moments.</p>
        </div>
        <div class="service-card">
            <div class="service-icon">
                <i class="fas fa-building"></i>
            </div>
            <h3 class="service-title">Hall Booking</h3>
            <p>Spacious and comfortable venues for all your event needs.</p>
        </div>
        <div class="service-card">
            <div class="service-icon">
                <i class="fas fa-utensils"></i>
            </div>
            <h3 class="service-title">Catering</h3>
            <p>Delicious traditional Nepali food and beverages for your guests.</p>
        </div>
        <div class="service-card">
            <div class="service-icon">
                <i class="fas fa-paint-brush"></i>
            </div>
            <h3 class="service-title">Decoration</h3>
            <p>Beautiful decorations to make your event more memorable.</p>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="section-heading">
        <h2>Ready to Book Your Event?</h2>
    </div>
    <div style="text-align: center; margin-bottom: 30px;">
        <p>Get started with your event planning today and make it memorable.</p>
        <?php if (isLoggedIn()): ?>
            <a href="events.php" class="btn btn-primary">Book Now</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-primary">Login to Book</a>
            <a href="register.php" class="btn btn-secondary">Register</a>
        <?php endif; ?>
    </div>
</section>

<section class="testimonials">
    <div class="section-heading">
        <h2>What Our Clients Say</h2>
    </div>
    <div class="testimonial-slider">
        <div class="testimonial">
            <div class="testimonial-content">
                <p>"We had an amazing experience with Nepali Event Booking for our wedding. The team was professional and everything was perfect."</p>
            </div>
            <div class="testimonial-author">
                <p>Rahul and Priya</p>
            </div>
        </div>
        <div class="testimonial">
            <div class="testimonial-content">
                <p>"The Bratabandh ceremony for my son was beautifully arranged. Every detail was taken care of. Highly recommended!"</p>
            </div>
            <div class="testimonial-author">
                <p>Sunita Sharma</p>
            </div>
        </div>
        <div class="testimonial">
            <div class="testimonial-content">
                <p>"Their Mehendi service was exceptional. The designs were beautiful and the artists were professional. Will definitely use their services again."</p>
            </div>
            <div class="testimonial-author">
                <p>Anjali Thapa</p>
            </div>
        </div>
    </div>
</section>

<?php
require_once 'includes/footer.php';
?> 