<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PhpMailer/src/Exception.php';
require 'PhpMailer/src/PHPMailer.php';
require 'PhpMailer/src/SMTP.php';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php#contact-form");
    exit();
}

// Sanitize and validate input
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Validation
$errors = [];

if (empty($name)) {
    $errors[] = "Name is required.";
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Valid email address is required.";
}

if (empty($message)) {
    $errors[] = "Message is required.";
}

// If validation fails, redirect back with error
if (!empty($errors)) {
    $errorMsg = urlencode(implode(" ", $errors));
    header("Location: index.php?error=" . $errorMsg . "#contact-form");
    exit();
}

// Email configuration
$clinicEmail = 'landerodentalclinic@gmail.com';
$clinicName = 'Lander Dental Clinic';

// SMTP Configuration (using Gmail)
$smtpUsername = 'vincehenrick.padilla0712@gmail.com';
$smtpPassword = 'xazs imyr lepb yjuq';

try {
    // Create PHPMailer instance
    $mail = new PHPMailer(true);

    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUsername;
    $mail->Password = $smtpPassword;
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';

    // Email to clinic
    $mail->setFrom($smtpUsername, 'Contact Form - Lander Dental Clinic');
    $mail->addAddress($clinicEmail, $clinicName);
    $mail->addReplyTo($email, $name);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'New Contact Form Submission - ' . $name;
    $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #2c5f8d; color: white; padding: 20px; border-radius: 5px 5px 0 0; }
                .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
                .field { margin-bottom: 15px; }
                .label { font-weight: bold; color: #2c5f8d; }
                .value { margin-top: 5px; padding: 10px; background-color: white; border-left: 3px solid #2c5f8d; }
                .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>New Contact Form Submission</h2>
                </div>
                <div class='content'>
                    <div class='field'>
                        <div class='label'>From:</div>
                        <div class='value'>{$name} ({$email})</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Message:</div>
                        <div class='value'>" . nl2br(htmlspecialchars($message)) . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Submitted:</div>
                        <div class='value'>" . date('F j, Y, g:i a') . "</div>
                    </div>
                </div>
                <div class='footer'>
                    <p>This email was sent from the contact form on your website.</p>
                    <p>You can reply directly to this email to respond to {$name}.</p>
                </div>
            </div>
        </body>
        </html>
    ";

    // Plain text version
    $mail->AltBody = "New Contact Form Submission\n\n" .
                     "From: {$name} ({$email})\n\n" .
                     "Message:\n{$message}\n\n" .
                     "Submitted: " . date('F j, Y, g:i a');

    // Send email to clinic
    $mail->send();

    // Send confirmation email to user
    $mail->clearAddresses();
    $mail->clearReplyTos();
    
    $mail->setFrom($smtpUsername, $clinicName);
    $mail->addAddress($email, $name);
    
    $mail->Subject = 'Thank you for contacting Lander Dental Clinic';
    $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #2c5f8d; color: white; padding: 20px; border-radius: 5px 5px 0 0; text-align: center; }
                .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
                .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Thank You for Contacting Us!</h2>
                </div>
                <div class='content'>
                    <p>Dear {$name},</p>
                    <p>Thank you for reaching out to Lander Dental Clinic. We have received your message and will get back to you as soon as possible.</p>
                    <p><strong>Your Message:</strong></p>
                    <p style='background-color: white; padding: 15px; border-left: 3px solid #2c5f8d;'>" . nl2br(htmlspecialchars($message)) . "</p>
                    <p>Our team typically responds within 24-48 hours during business hours (Mon - Sun: 8:00 AM - 8:00 PM).</p>
                    <p>If you have any urgent concerns, please call us at <strong>0922 861 1987</strong>.</p>
                    <p>Best regards,<br>Lander Dental Clinic Team</p>
                </div>
                <div class='footer'>
                    <p>Lander Dental Clinic<br>
                    Mon - Sun: 8:00 AM - 8:00 PM<br>
                    Phone: 0922 861 1987<br>
                    Email: landerodentalclinic@gmail.com</p>
                </div>
            </div>
        </body>
        </html>
    ";

    $mail->AltBody = "Thank you for contacting Lander Dental Clinic!\n\n" .
                     "Dear {$name},\n\n" .
                     "Thank you for reaching out to Lander Dental Clinic. We have received your message and will get back to you as soon as possible.\n\n" .
                     "Your Message:\n{$message}\n\n" .
                     "Our team typically responds within 24-48 hours during business hours (Mon - Sun: 8:00 AM - 8:00 PM).\n\n" .
                     "If you have any urgent concerns, please call us at 0922 861 1987.\n\n" .
                     "Best regards,\nLander Dental Clinic Team";

    // Send confirmation email
    $mail->send();

    // Success - redirect back to contact form
    header("Location: index.php?success=1#contact-form");
    exit();

} catch (Exception $e) {
    // Error sending email
    $errorMsg = urlencode("Sorry, there was an error sending your message. Please try again later or call us directly.");
    header("Location: index.php?error=" . $errorMsg . "#contact-form");
    exit();
}
?>

