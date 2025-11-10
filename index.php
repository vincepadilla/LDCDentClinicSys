<?php
session_start();
include_once('./login/config.php');
include('chat.php');

$isLoggedIn = isset($_SESSION['valid'], $_SESSION['userID']) && $_SESSION['valid'] === true;

$fname = $lname = $email = $phone = $birthdate = $gender = $age = $address = '';

$todayStr = date('Y-m-d');
$oneMonthLater = date('Y-m-d', strtotime('+1 month'));

if ($isLoggedIn) {
    $user_id = $_SESSION['userID'];

    $query = "
        SELECT ua.email, ua.first_name, ua.last_name, ua.phone,
               ua.birthdate, ua.gender, ua.address
        FROM user_account ua
        WHERE ua.user_id = ?
        LIMIT 1
    ";

    if ($stmt = $con->prepare($query)) {
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $stmt->bind_result($email, $fname, $lname, $phone, $birthdate, $gender, $address);
        $stmt->fetch();
        $stmt->close();

        if (!empty($birthdate)) {
            try {
                $birthDateObj = new DateTime($birthdate);
                $todayObj = new DateTime();
                $age = $todayObj->diff($birthDateObj)->y;
            } catch (Exception $e) {
                $age = '';
            }
        }
    }
}

// ✅ Override with POST data if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    $fname = htmlspecialchars($_POST['fname'] ?? $fname);
    $lname = htmlspecialchars($_POST['lname'] ?? $lname);
    $email = htmlspecialchars($_POST['email'] ?? $email);
    $phone = htmlspecialchars($_POST['phone'] ?? $phone);
    $birthdate = htmlspecialchars($_POST['birthdate'] ?? $birthdate);
    $gender = htmlspecialchars($_POST['gender'] ?? $gender);
    $address = htmlspecialchars($_POST['address'] ?? $address);

    // Recalculate age if birthdate is updated
    if (!empty($birthdate)) {
        try {
            $birthDateObj = new DateTime($birthdate);
            $todayObj = new DateTime();
            $age = $todayObj->diff($birthDateObj)->y;
        } catch (Exception $e) {
            $age = '';
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landero Dental Clinic - Professional Dental Care</title>
    <link rel="stylesheet" href="styles.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <nav class="navbar">
                <a href="#" class="logo">
                    <img src="landerologo.png" alt="Landero Dental Clinic Logo">
                    <span>Landero Dental Clinic</span>
                </a>
                
                <ul class="nav-links">
                    <li><a href="#services">Services</a></li>
                    <li><a href="#dentists">Dentists</a></li>
                    <li><a href="#contact">Contact</a></li>
                    <?php if (isset($_SESSION['valid'])): ?>
                        <li><a href="login/account.php" class="nav-btn">Account</a></li> 
                    <?php else: ?>
                        <li><a href="login/login.php" class="nav-btn">Login</a></li> 
                    <?php endif; ?>
                </ul>

                <div class="menu-toggle">
                    <i class="fas fa-bars"></i>
                </div>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="container">
            <div class="hero-content">
                <h1>Your Smile Deserves the Best Care</h1>
                <p>Professional dental care in a comfortable and friendly environment</p>
                <div class="btn-container">
                    <a href="#services" class="btn btn-primary">Book an Appointment</a>
                    <a href="learnmore.php" class="btn btn-outline">Learn More</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services" id="services">
        <div class="container">
            <div class="section-title">
                <h2>Our Services</h2>
                <p>Comprehensive dental care for the whole family</p>
            </div>
            
            <div class="services-grid">
                <div class="service-card" data-service="S001">
                    <div class="service-icon">
                        <i class="fa-solid fa-stethoscope"></i>
                    </div>
                    <h3>General Dentistry</h3>
                    <p>Regular checkups, cleanings, Fillings, and Preventive Care.</p>
                    <button class="service-book-btn">Book Appointment</button>
                </div>
                
                <div class="service-card" data-service="S002">
                    <div class="service-icon">
                        <i class="fas fa-teeth"></i>
                    </div>
                    <h3>Orthodontics</h3>
                    <p>Braces and aligners for a perfectly straight smile.</p>
                    <button class="service-book-btn">Book Appointment</button>
                </div>

                <div class="service-card" data-service="S003">
                    <div class="service-icon">
                        <i class="fas fa-tooth"></i>
                    </div>
                    <h3>Oral Surgery</h3>
                    <p>Gentle extractions and surgical care for a healthier smile.</p>
                    <button class="service-book-btn">Book Appointment</button>
                </div>

                <div class="service-card" data-service="S004">
                    <div class="service-icon">
                        <i class="fa-solid fa-face-grimace"></i>
                    </div>
                    <h3>Endodontics</h3>
                    <p>Save your natural teeth with expert root canal care.</p>
                    <button class="service-book-btn">Book Appointment</button>
                </div>

                <div class="service-card" data-service="S005">
                    <div class="service-icon">
                        <i class="fas fa-teeth-open"></i>
                    </div>
                    <h3>Prosthodontics (Pustiso)</h3>
                    <p>Bring back your perfect smile with natural-looking tooth replacements.</p>
                    <button class="service-book-btn">Book Appointment</button>
                </div>

            </div>

            <div class="text-center">
                <a href="view_services.php" class="btn btn-services">View Services Description</a>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="testimonials" id="dentists">
        <div class="container">
            <div class="section-title">
                <h2>Our Dentist</h2>
                <p>Meet Our Professional Dentist</p>
            </div>
            
            <div class="dentist-grid">
                <div class="dentist-card">
                    <div class="dentist-image">
                        <img src="dentisticon.png" alt="Dr. Michelle Landero">
                    </div>
                    <div class="dentist-info">
                        <h4>Dr. Michelle Landero</h4>
                        <p class="specialty">Dentist</p>
                        <p class="experience">With over 10 years of experience in providing exceptional dental care.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-logo">
                        <i class="fas fa-tooth"></i>
                        <h3>Landero Dental Clinic</h3>
                    </div>
                    <p>Providing exceptional dental care with a personal touch since 2011.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                
                <div class="footer-col">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="#services">Services</a></li>
                        <li><a href="#dentists">Dentists</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="blogs.php">Blogs</a></li>
                        <li><a href="#contact">Contact</a></li>
                        <li><a href="location.php">Location</a></li>
                    </ul>
                </div>
                
                <div class="footer-col" id="contact">
                    <h3>Contact Us</h3>
                    <ul class="contact-info">
                        <li>
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Anahaw St. Comembo Taguig City</span>
                        </li>
                        <li>
                            <i class="fas fa-phone"></i>
                            <span>09228611987</span>
                        </li>
                        <li>
                            <i class="fas fa-envelope"></i>
                            <span>landerodentalclinic@gmail.com</span>
                        </li>
                        <li>
                            <i class="fas fa-clock"></i>
                            <span>Mon-Sun: 8AM-8PM</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 Landero Dental Clinic. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Appointment Popup Modal -->
    <div id="appointmentModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Book Your Appointment</h2>
            <form action="payment.php" method="POST" id="appointmentForm">
                <input type="hidden" name="fname" value="<?php echo htmlspecialchars($fname); ?>">
                <input type="hidden" name="lname" value="<?php echo htmlspecialchars($lname); ?>">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <input type="hidden" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                <input type="hidden" name="gender" value="<?php echo htmlspecialchars($gender); ?>">
                <input type="hidden" name="address" value="<?php echo htmlspecialchars($address); ?>">
                <input type="hidden" name="birthdate" value="<?php echo htmlspecialchars($birthdate); ?>">
                <input type="hidden" name="age" value="<?php echo htmlspecialchars($age); ?>">
                
                 <!-- Service and Sub-Service side by side -->
            <div class="service-fields-row">
                <div class="form-group">
                    <label for="popup_service">Service Needed</label>
                    <select id="popup_service" name="service_id" required onchange="updateSubServices()" disabled>
                        <option value="">Select a service</option>
                        <option value="S001">General Dentistry</option>
                        <option value="S002">Orthodontics</option>
                        <option value="S003">Oral Surgery</option>
                        <option value="S004">Endodontics</option>
                        <option value="S005">Prosthodontics</option>
                    </select>
                </div>

                <div class="form-group" id="popup-sub-service-container" style="display: none;">
                    <label for="popup_sub_service">Sub-Service</label>
                    <select id="popup_sub_service" name="sub_service" required>
                        <option value="">Select a sub-service</option>
                    </select>
                </div>
            </div>

                <div class="form-group">
                    <label for="popup_branch">Select Branch</label>
                    <select id="popup_branch" name="branch" required>
                        <option value="">Select Branch</option>
                        <option value="comembo">Comembo Branch</option>
                        <option value="taytay">Taytay Rizal Branch</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="popup_date">Preferred Date</label>
                    <input type="date" id="popup_date" name="date"
                        min="<?php echo $today; ?>"
                        max="<?php echo $oneMonthLater; ?>"
                        required>
                </div>

                <div class="form-group">
                    <label for="popup_time">Preferred Time</label>
                    <select id="popup_time" name="time" required>
                        <option value="">Select a time</option>
                        <option value="firstBatch">Morning (8AM-9AM)</option>
                        <option value="secondBatch">Morning (9AM-10AM)</option>
                        <option value="thirdBatch">Morning (10AM-11AM)</option>
                        <option value="fourthBatch">Afternoon (11AM-12PM)</option>
                        <option value="fifthBatch">Afternoon (1PM-2PM)</option>
                        <option value="sixthBatch">Afternoon (2PM-3PM)</option>
                        <option value="sevenBatch">Afternoon (3PM-4PM)</option>
                        <option value="eightBatch">Afternoon (4PM-5PM)</option>
                        <option value="nineBatch">Afternoon (5PM-6PM)</option>
                        <option value="tenBatch">Evening (6PM-7PM)</option>
                        <option value="lastBatch">Evening (7PM-8PM)</option>
                    </select>
                </div>

                <?php if (!$isLoggedIn): ?>
                    <div class="login-required-message" style="background-color: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin-bottom: 15px; border-radius: 5px;">
                        <p style="margin: 0; color: #856404;">
                            <i class="fas fa-info-circle"></i> You need to <a href="login/login.php" style="color: #0066cc; text-decoration: underline;">log in</a> to book an appointment.
                        </p>
                    </div>
                <?php endif; ?>
                
                <div class="submit-btn">
                    <button type="submit" class="btn btn-primary" id="popupBookBtn" <?php echo !$isLoggedIn ? 'disabled' : ''; ?>>BOOK APPOINTMENT</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>                    
   <script>
    // Mobile menu toggle
    document.querySelector('.menu-toggle').addEventListener('click', function() {
        document.querySelector('.nav-links').classList.toggle('active');
    });

    // Smooth scrolling
    document.querySelectorAll(".nav-links a").forEach(anchor => {
        anchor.addEventListener("click", function (event) {
            if (this.getAttribute("href").startsWith("#")) {
                event.preventDefault();
                const targetId = this.getAttribute("href").substring(1);
                const targetElement = document.getElementById(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({ behavior: "smooth" });
                }
                document.querySelector('.nav-links').classList.remove('active'); // close menu
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Set minimum date for appointment (tomorrow)
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(today.getDate() + 1);
        const dd = String(tomorrow.getDate()).padStart(2, '0');
        const mm = String(tomorrow.getMonth() + 1).padStart(2, '0');
        const yyyy = tomorrow.getFullYear();
        const minDate = yyyy + '-' + mm + '-' + dd;
        const popupDate = document.getElementById('popup_date');
        if (popupDate) popupDate.min = minDate;

        // Initialize modal functionality
        initializeModal();
    });

    const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>; 

    function initializeModal() {
        const modal = document.getElementById("appointmentModal");
        const serviceCards = document.querySelectorAll(".service-book-btn");
        const closeModal = document.querySelector(".close-modal");

        if (!modal || !closeModal) return;

        // Open modal only if logged in
        serviceCards.forEach(card => {
            card.addEventListener("click", function() {
                console.log("Clicked service button"); // Debug
                console.log("isLoggedIn:", isLoggedIn);

                if (!isLoggedIn) {
                    if (confirm("You need to log in before booking. Do you want to log in now?")) {
                        window.location.href = "./login/login.php";
                    }
                    return; // stop modal from opening
                }

                const serviceCard = this.closest('.service-card');
                if (!serviceCard) {
                    console.log("Could not find closest service-card");
                    return;
                }
                const serviceId = serviceCard.getAttribute('data-service');
                console.log("Service ID:", serviceId);

                const serviceSelect = document.getElementById('popup_service');
                if (serviceSelect) {
                    serviceSelect.value = serviceId;
                    updateSubServices();
                }

                console.log("Opening modal...");
                modal.style.display = "block";
            });
        });

        // Close modal
        closeModal.addEventListener("click", function() {
            modal.style.display = "none";
        });

        // Close modal when clicking outside
        window.addEventListener("click", function(event) {
            if (event.target === modal) {
                modal.style.display = "none";
            }
        });
    }

    // Update sub-services function
    function updateSubServices() {
        const serviceSelect = document.getElementById("popup_service");
        if (!serviceSelect) return;

        const service = serviceSelect.value;
        const subServiceContainer = document.getElementById("popup-sub-service-container");
        const subServiceSelect = document.getElementById("popup_sub_service");
        if (!subServiceContainer || !subServiceSelect) return;

        subServiceSelect.innerHTML = '<option value="">Select a sub-service</option>';

        if (service === "S001") {
            subServiceSelect.innerHTML += '<option value="Checkups">Checkups</option>';
            subServiceSelect.innerHTML += '<option value="Cleaning">Oral Prophylaxis (Cleaning)</option>';
            subServiceSelect.innerHTML += '<option value="Flouride">Flouride Application</option>';
            subServiceSelect.innerHTML += '<option value="Pit and Fissure Sealants">Pit & Fissure Sealants</option>';
            subServiceSelect.innerHTML += '<option value="Tooth Restoration">Tooth Restoration (Pasta)</option>';
            subServiceContainer.style.display = 'block';

        } else if (service === "S002") {
            subServiceSelect.innerHTML += '<option value="Braces">Braces</option>';
            subServiceSelect.innerHTML += '<option value="Retainers">Retainers</option>';
            subServiceContainer.style.display = 'block';

        } else if(service == "S003") {
            subServiceSelect.innerHTML += '<option value="Tooth Extraction">Tooth Extraction(Bunot)</option>';
            subServiceContainer.style.display = 'block';

        } else if(service == "S004") {
            subServiceSelect.innerHTML += '<option value="Root Canal Treatment">Root Canal Treatment</option>';
            subServiceContainer.style.display = 'block';

        } else if(service == "S005") {
            subServiceSelect.innerHTML += '<option value="Crowns">Crowns</option>';
            subServiceSelect.innerHTML += '<option value="Dentures">Dentures</option>';
            subServiceContainer.style.display = 'block';
        }
        
        else {
            subServiceContainer.style.display = 'none';
        }
    }

    // Time availability check for popup
    function checkAvailability() {
        var selectedDate = $("#popup_date").val();
        if (selectedDate) {
            $.ajax({
                url: 'getAppointments.php',
                type: 'GET',
                data: { date: selectedDate },
                dataType: 'json',
                success: function(bookedSlots) {
                    $("#popup_time option").prop("disabled", false);
                    bookedSlots.forEach(slot => {
                        $("#popup_time option[value='" + slot + "']").prop("disabled", true);
                    });
                    if ($("#popup_time option:selected").prop("disabled")) {
                        $("#popup_time").val('');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching appointment data:", error);
                }
            });
        } else {
            $("#popup_time option").prop("disabled", false);
            $("#popup_time").val('');
        }
    }

    const popupDateInput = document.getElementById('popup_date');
    if (popupDateInput) popupDateInput.addEventListener('change', checkAvailability);

    const appointmentForm = document.getElementById('appointmentForm');
    if (appointmentForm) {
        appointmentForm.addEventListener('submit', function(e) {
            if (!isLoggedIn) {
                e.preventDefault();
                if (confirm("You need to log in before booking. Do you want to log in now?")) {
                    window.location.href = "./login/login.php";
                }
            }
        });
    }
</script>

</body>
</html>