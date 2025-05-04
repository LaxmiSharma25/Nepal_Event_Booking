    </main>
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section about">
                    <h2>About Us</h2>
                    <p>Nepali Event Booking System provides comprehensive event planning services for traditional Nepali ceremonies like Bratabandh, Marriage, Mehendi, and more.</p>
                    <div class="contact">
                        <span><i class="fas fa-phone"></i> +977 9812345678</span>
                        <span><i class="fas fa-envelope"></i> info@nepalieventbooking.com</span>
                    </div>
                    <div class="socials">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="footer-section links">
                    <h2>Quick Links</h2>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="events.php">Events</a></li>
                        <li><a href="services.php">Services</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact</a></li>
                        <li><a href="terms.php">Terms & Conditions</a></li>
                        <li><a href="privacy.php">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="footer-section contact-form">
                    <h2>Contact Us</h2>
                    <form action="contact_process.php" method="post">
                        <input type="email" name="email" class="text-input contact-input" placeholder="Your email address...">
                        <textarea name="message" class="text-input contact-input" placeholder="Your message..."></textarea>
                        <button type="submit" class="btn btn-primary">Send</button>
                    </form>
                </div>
            </div>
            <div class="footer-bottom">
                &copy; <?php echo date('Y'); ?> Nepali Event Booking System | All rights reserved
            </div>
        </div>
    </footer>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="assets/js/script.js"></script>
</body>
</html> 