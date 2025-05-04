<?php
require_once 'includes/header.php';

// Get all service categories
$serviceCategories = getServiceCategories($conn);
?>

<div class="section-heading">
    <h2>Our Services</h2>
</div>

<div class="services-description" style="text-align: center; margin-bottom: 30px;">
    <p>We offer a wide range of services to make your event memorable. Browse through our offerings and select the ones that meet your requirements.</p>
</div>

<div class="service-categories" style="margin-bottom: 30px;">
    <ul class="category-filters" style="display: flex; gap: 15px; justify-content: center; margin-bottom: 20px;">
        <li><a href="#" class="category-filter btn btn-outline active" data-category="all">All Services</a></li>
        <?php foreach ($serviceCategories as $category): ?>
        <li><a href="#" class="category-filter btn btn-outline" data-category="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></a></li>
        <?php endforeach; ?>
    </ul>
</div>

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
                    
                    <?php if (isLoggedIn()): ?>
                        <?php if (isServiceInCart($conn, $_SESSION['user_id'], $service['id'])): ?>
                            <a href="#" class="btn btn-secondary" disabled>Already in Cart</a>
                        <?php else: ?>
                            <a href="buy_now.php?service_id=<?php echo $service['id']; ?>" class="btn btn-success">Book Now</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary">Login to Book</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>

<?php
require_once 'includes/footer.php';
?>