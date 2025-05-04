<?php
require_once 'includes/header.php';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);
    
    $errors = [];
    
    // Validate name
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    // Validate email
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email is not valid";
    }
    
    // Validate subject
    if (empty($subject)) {
        $errors[] = "Subject is required";
    }
    
    // Validate message
    if (empty($message)) {
        $errors[] = "Message is required";
    }
    
    // If no errors, process contact form
    if (empty($errors)) {
        // In a real application, send email or save to database
        // For now, just show success message
        $_SESSION['message'] = "Thank you for contacting us. We will get back to you soon!";
        $_SESSION['message_type'] = "success";
        redirect('contact.php');
    }
}
?>

<div class="section-heading">
    <h2>Contact Us</h2>
</div>

<div class="contact-container" style="display: flex; flex-wrap: wrap; gap: 30px; margin-bottom: 50px;">
    <div class="contact-info" style="flex: 1; min-width: 300px;">
        <h3>Get in Touch</h3>
        <p>Have questions about our services or need more information? Reach out to us using the contact details below or fill out the contact form.</p>
        
        <div class="contact-details" style="margin-top: 30px;">
            <div class="contact-item" style="display: flex; align-items: flex-start; margin-bottom: 20px;">
                <i class="fas fa-map-marker-alt" style="font-size: 1.2rem; color: #e63946; margin-right: 15px; margin-top: 5px;"></i>
                <div>
                    <h4>Address</h4>
                    <p>123 Durbar Marg, Kathmandu, Nepal</p>
                </div>
            </div>
            
            <div class="contact-item" style="display: flex; align-items: flex-start; margin-bottom: 20px;">
                <i class="fas fa-phone" style="font-size: 1.2rem; color: #e63946; margin-right: 15px; margin-top: 5px;"></i>
                <div>
                    <h4>Phone</h4>
                    <p>+977 9812345678 / +977 01-4567890</p>
                </div>
            </div>
            
            <div class="contact-item" style="display: flex; align-items: flex-start; margin-bottom: 20px;">
                <i class="fas fa-envelope" style="font-size: 1.2rem; color: #e63946; margin-right: 15px; margin-top: 5px;"></i>
                <div>
                    <h4>Email</h4>
                    <p>info@nepalieventbooking.com</p>
                </div>
            </div>
            
            <div class="contact-item" style="display: flex; align-items: flex-start; margin-bottom: 20px;">
                <i class="fas fa-clock" style="font-size: 1.2rem; color: #e63946; margin-right: 15px; margin-top: 5px;"></i>
                <div>
                    <h4>Business Hours</h4>
                    <p>Monday - Friday: 9:00 AM - 6:00 PM<br>
                    Saturday: 10:00 AM - 4:00 PM<br>
                    Sunday: Closed</p>
                </div>
            </div>
        </div>
        
        <div class="social-links" style="margin-top: 30px;">
            <h4>Connect With Us</h4>
            <div style="display: flex; gap: 15px; margin-top: 10px;">
                <a href="#" style="font-size: 1.5rem; color: #e63946;"><i class="fab fa-facebook"></i></a>
                <a href="#" style="font-size: 1.5rem; color: #e63946;"><i class="fab fa-instagram"></i></a>
                <a href="#" style="font-size: 1.5rem; color: #e63946;"><i class="fab fa-twitter"></i></a>
                <a href="#" style="font-size: 1.5rem; color: #e63946;"><i class="fab fa-youtube"></i></a>
            </div>
        </div>
    </div>
    
    <div class="contact-form" style="flex: 1; min-width: 300px;">
        <div class="form-container">
            <h3>Send us a Message</h3>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form action="" method="post">
                <div class="form-group">
                    <label for="name">Your Name</label>
                    <input type="text" name="name" id="name" class="form-control" value="<?php echo isset($name) ? $name : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Your Email</label>
                    <input type="email" name="email" id="email" class="form-control" value="<?php echo isset($email) ? $email : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" name="subject" id="subject" class="form-control" value="<?php echo isset($subject) ? $subject : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea name="message" id="message" class="form-control" rows="5" required><?php echo isset($message) ? $message : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Send Message</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="map-container" style="margin-bottom: 50px;">
    <h3>Our Location</h3>
    <div class="map" style="height: 400px; margin-top: 20px; border-radius: 8px; overflow: hidden;">
        <!-- Replace with your Google Maps embed code or use a static image -->
        <img src="assets/images/map.jpg" alt="Our Location Map" style="width: 100%; height: 100%; object-fit: cover;">
    </div>
</div>

<?php
require_once 'includes/footer.php';
?> 