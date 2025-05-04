// Wait for DOM to load
document.addEventListener('DOMContentLoaded', function() {
    // Alert messages auto close
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 500);
        }, 3000);
    });

    // Mobile menu toggle
    const navItems = document.querySelectorAll('nav ul li.dropdown');
    navItems.forEach(item => {
        if (window.innerWidth <= 768) {
            item.addEventListener('click', function() {
                const dropdownMenu = this.querySelector('.dropdown-menu');
                if (dropdownMenu.style.display === 'block') {
                    dropdownMenu.style.display = 'none';
                } else {
                    dropdownMenu.style.display = 'block';
                }
            });
        }
    });

    // Form validation for registration
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match');
            }
        });
    }

    // Form validation for checkout
    const checkoutForm = document.getElementById('checkout-form');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            const eventDate = document.getElementById('event-date').value;
            const today = new Date().toISOString().split('T')[0];
            
            if (eventDate < today) {
                e.preventDefault();
                alert('Event date cannot be in the past');
            }
        });
    }

    // Image preview for file uploads
    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('image-preview');
    
    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.addEventListener('load', function() {
                    imagePreview.setAttribute('src', this.result);
                    imagePreview.style.display = 'block';
                });
                
                reader.readAsDataURL(file);
            }
        });
    }

    // Quantity increment/decrement
    const quantityBtns = document.querySelectorAll('.quantity-btn');
    quantityBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.closest('.quantity-control').querySelector('input');
            const currentValue = parseInt(input.value);
            
            if (this.classList.contains('quantity-plus')) {
                input.value = currentValue + 1;
            } else if (this.classList.contains('quantity-minus') && currentValue > 1) {
                input.value = currentValue - 1;
            }
        });
    });

    // Service filter by category
    const categoryFilters = document.querySelectorAll('.category-filter');
    if (categoryFilters.length > 0) {
        categoryFilters.forEach(filter => {
            filter.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all filters
                categoryFilters.forEach(f => f.classList.remove('active'));
                
                // Add active class to clicked filter
                this.classList.add('active');
                
                const categoryId = this.getAttribute('data-category');
                const serviceCards = document.querySelectorAll('.service-item');
                
                serviceCards.forEach(card => {
                    if (categoryId === 'all') {
                        card.style.display = 'block';
                    } else if (card.getAttribute('data-category') === categoryId) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    }

    // Testimonial slider
    let currentSlide = 0;
    const testimonials = document.querySelectorAll('.testimonial');
    
    if (testimonials.length > 0) {
        function showSlide(n) {
            testimonials.forEach(slide => slide.style.display = 'none');
            testimonials[n].style.display = 'block';
        }
        
        function nextSlide() {
            currentSlide = (currentSlide + 1) % testimonials.length;
            showSlide(currentSlide);
        }
        
        // Initial display
        showSlide(currentSlide);
        
        // Auto rotate every 5 seconds
        setInterval(nextSlide, 5000);
    }

    // Hamburger menu toggle
    const hamburger = document.querySelector('.hamburger-menu');
    const navMenu = document.querySelector('.nav-menu');
    
    if (hamburger) {
        hamburger.addEventListener('click', function() {
            hamburger.classList.toggle('active');
            navMenu.classList.toggle('active');
            document.body.classList.toggle('menu-open');
        });
    }
    
    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
        const isClickInsideMenu = navMenu.contains(e.target);
        const isClickOnHamburger = hamburger.contains(e.target);
        
        if (navMenu.classList.contains('active') && !isClickInsideMenu && !isClickOnHamburger) {
            hamburger.classList.remove('active');
            navMenu.classList.remove('active');
            document.body.classList.remove('menu-open');
        }
    });
    
    // Handle dropdown on mobile
    const dropdowns = document.querySelectorAll('.dropdown');
    dropdowns.forEach(dropdown => {
        if (window.innerWidth <= 991) {
            dropdown.querySelector('a').addEventListener('click', function(e) {
                e.preventDefault();
                this.parentNode.classList.toggle('active');
            });
        }
    });
    
    // Resize event to reset menu state
    window.addEventListener('resize', function() {
        if (window.innerWidth > 991) {
            hamburger.classList.remove('active');
            navMenu.classList.remove('active');
            document.body.classList.remove('menu-open');
            
            // Reset dropdown behavior
            dropdowns.forEach(dropdown => {
                dropdown.classList.remove('active');
                dropdown.querySelector('a').removeEventListener('click', function(e) {
                    e.preventDefault();
                    this.parentNode.classList.toggle('active');
                });
            });
        }
    });
}); 