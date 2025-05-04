<?php
require_once 'includes/header.php';

// Get all event categories
$events = getEventCategories($conn);
?>

<div class="section-heading">
    <h2>Our Events</h2>
</div>

<div class="events-description" style="text-align: center; margin-bottom: 30px;">
    <p>We specialize in organizing traditional Nepali ceremonies. Browse our event categories below and choose the one that fits your needs.</p>
</div>

<div class="card-container">
    <?php foreach ($events as $event): ?>
    <div class="card">
        <div class="card-img">
            <img src="<?php echo $event['image']; ?>" alt="<?php echo $event['name']; ?>">
        </div>
        <div class="card-content">
            <h3><?php echo $event['name']; ?></h3>
            <p><?php echo $event['description']; ?></p>
            <div style="display: flex; gap: 10px;">
                <a href="event_detail.php?id=<?php echo $event['id']; ?>" class="btn btn-primary">View Details</a>
                <?php if (isLoggedIn()): ?>
                    <a href="buy_now.php?service_id=<?php echo $event['id']; ?>" class="btn btn-success">Book Now</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-success">Login to Book</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php
require_once 'includes/footer.php';
?> 