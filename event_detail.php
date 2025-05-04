<?php
require_once 'includes/header.php';

// Check if event ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "Event ID is required";
    $_SESSION['message_type'] = "error";
    redirect('events.php');
}

$eventId = (int)$_GET['id'];

// Get event details
$sql = "SELECT * FROM event_categories WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $eventId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $_SESSION['message'] = "Event not found";
    $_SESSION['message_type'] = "error";
    redirect('events.php');
}

$event = $result->fetch_assoc();

// Get service categories
$serviceCategories = getServiceCategories($conn);
?>

<div class="event-detail">
    <div class="section-heading">
        <h2><?php echo $event['name']; ?></h2>
    </div>
    
    <div class="event-image" style="text-align: center; margin-bottom: 30px;">
        <img src="<?php echo $event['image']; ?>" alt="<?php echo $event['name']; ?>" style="max-width: 100%; height: auto; max-height: 400px; border-radius: 8px;">
    </div>
    
    <div class="event-description" style="margin-bottom: 30px;">
        <h3>Description</h3>
        <p><?php echo $event['description']; ?></p>
    </div>
    
    <div class="event-services">
        <div class="section-heading">
            <h3>Services for <?php echo $event['name']; ?></h3>
        </div>
        
        <div class="service-categories" style="margin-bottom: 30px;">
            <ul class="category-filters" style="display: flex; gap: 15px; justify-content: center; margin-bottom: 20px;">
                <li><a href="#" class="category-filter btn btn-outline active" data-category="all">All Services</a></li>
                <?php foreach ($serviceCategories as $category): ?>
                <li><a href="#" class="category-filter btn btn-outline" data-category="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="services-list">
            <?php foreach ($serviceCategories as $category): ?>
                <?php 
                $services = getServicesByCategory($conn, $category['id']);
                if (empty($services)) continue;
                ?>
                
                <div class="service-category-section" style="margin-bottom: 30px;">
                    <h3><?php echo $category['name']; ?></h3>
                    <div class="card-container">
                        <?php foreach ($services as $service): ?>
                        <div class="card service-item" data-category="<?php echo $service['category_id']; ?>">
                            <div class="card-img">
                                <img src="assets/images/services/<?php echo $service['image']; ?>" alt="<?php echo $service['name']; ?>">
                            </div>
                            <div class="card-content">
                                <h3><?php echo $service['name']; ?></h3>
                                <p><?php echo $service['description']; ?></p>
                                <span class="price"><?php echo formatPrice($service['price']); ?></span>
                                
                                <div class="button-group" style="display: flex; gap: 10px; margin-top: 10px;">
                                <?php if (isLoggedIn()): ?>
                                    <?php if (isServiceInCart($conn, $_SESSION['user_id'], $service['id'])): ?>
                                        <a href="#" class="btn btn-secondary" disabled>Already in Cart</a>
                                    <?php else: ?>
                                        <a href="buy_now.php?service_id=<?php echo $service['id']; ?>&event_id=<?php echo $eventId; ?>" class="btn btn-success">Book Now</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-primary">Login to Book</a>
                                <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?> 