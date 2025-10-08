<?php
/**
 * sendEmail.php
 * A reusable email function using PHPMailer library
 * Loads SMTP credentials from a .env file instead of hard-coding.
 * @author Hunter Lovan
 * @version 1.0
 * @disclaimer This code is for educational purposes only.
 */

// Simple .env loader (no external dependencies). Loads key=value pairs into getenv()/$_ENV.
function loadEnv($path) {
    if (!file_exists($path)) {
        return; // Silently ignore if missing so code can still run with system env vars.
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }
        // Split on first '=' only
        $pos = strpos($trimmed, '=');
        if ($pos === false) {
            continue; // invalid line
        }
        $key = trim(substr($trimmed, 0, $pos));
        $value = trim(substr($trimmed, $pos + 1));
        // Remove optional surrounding quotes
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }
        if ($key !== '' && getenv($key) === false) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value; // For direct access if desired.
        }
    }
}

// Attempt to load .env in current directory (only once per request)
if (!defined('ENV_LOADED')) {
    loadEnv(__DIR__ . DIRECTORY_SEPARATOR . '.env');
    define('ENV_LOADED', true);
}

// Include PHPMailer library
require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Send email using PHPMailer
 * todo: setup corresponding in the web host (still cannot get it to work)
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body Email body (can be HTML)
 * @param string $fromEmail Sender email address (optional)
 * @param string $fromName Sender name (optional)
 * @param bool $isHTML Whether the email body is HTML (default: true)
 * @return array Result array with 'success' boolean and 'message' string
 */
function sendEmail($to, $subject, $body, $fromEmail = null, $fromName = null, $isHTML = true) {
    $mail = new PHPMailer(true);
    
    try {
    // Read configuration from environment variables (.env preferred)
    $smtpHost       = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
    $smtpPort       = (int)(getenv('SMTP_PORT') ?: 587);
    $smtpUser       = getenv('SMTP_USERNAME') ?: ''; // REQUIRED
    $smtpPass       = getenv('SMTP_PASSWORD') ?: ''; // REQUIRED (or OAuth alternative)
    $smtpSecure     = strtolower(getenv('SMTP_ENCRYPTION') ?: 'tls'); // tls|ssl|''
    $defaultFrom    = getenv('MAIL_FROM_ADDRESS') ?: 'noreply@example.com';
    $defaultFromName= getenv('MAIL_FROM_NAME') ?: 'Website Contact';

    if (empty($smtpUser) || empty($smtpPass)) {
        throw new Exception('SMTP credentials are not set. Define SMTP_USERNAME and SMTP_PASSWORD in your .env file.');
    }

    $fromEmail = $fromEmail ?: $defaultFrom;
    $fromName  = $fromName  ?: $defaultFromName;

    // Server settings
    $mail->isSMTP();
    $mail->Host       = $smtpHost;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtpUser;
    $mail->Password   = $smtpPass;
    $mail->Port       = $smtpPort;

    switch ($smtpSecure) {
        case 'ssl':
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        if ($mail->Port === 587) { $mail->Port = 465; }
        break;
        case 'tls':
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        if ($mail->Port === 465) { $mail->Port = 587; }
        break;
        default:
        // No encryption (not recommended)
        $mail->SMTPSecure = false;
        break;
    }

        // Recipients
    $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to);
        $mail->addReplyTo($fromEmail, $fromName);

        // Content
        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        // Send the email
        $mail->send();
        
        return [
            'success' => true,
            'message' => 'Email sent successfully'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => "Email could not be sent. Error: {$mail->ErrorInfo}"
        ];
    }
}

/**
 * Send confirmation email to the customer
 * 
 * @param string $customerEmail Customer's email address
 * @param string $customerName Customer's name
 * @return array Result array
 */
function sendCustomerConfirmation($customerEmail, $customerName) {
    $subject = "Thank you for contacting us!";
    
    $body = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
            .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { background-color: #2c3e50; color: white; padding: 20px; text-align: center; border-radius: 5px; margin-bottom: 20px; }
            .content { line-height: 1.6; color: #333; }
            .footer { margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee; text-align: center; color: #666; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Contact Confirmation</h1>
            </div>
            <div class="content">
                <p>Dear ' . htmlspecialchars($customerName) . ',</p>
                <p>Thank you for contacting us! We have received your message and will respond to you as soon as possible.</p>
                <p>We appreciate your interest in our services and will get back to you within 24-48 hours.</p>
                <p>If you have any urgent questions, please feel free to call us directly.</p>
                <p>Best regards,<br>The Customer Service Team</p>
            </div>
            <div class="footer">
                <p><small>This is an automated confirmation email. Please do not reply to this message.</small></p>
            </div>
        </div>
    </body>
    </html>';
    
    return sendEmail($customerEmail, $subject, $body);
}

/**
 * Send notification email to the admin
 * 
 * @param string $adminEmail Admin's email address
 * @param array $formData Form data from the contact form
 * @return array Result array
 */
function sendAdminNotification($adminEmail, $formData) {
    $subject = "New Contact Form Submission - " . date('m/d/Y');
    
    $body = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f8f9fa; }
            .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 5px; }
            .header { background-color: #007bff; color: white; padding: 15px; margin-bottom: 20px; border-radius: 3px; }
            ul { list-style-type: none; padding: 0; }
            li { padding: 10px; margin: 5px 0; background-color: #f8f9fa; border-left: 4px solid #007bff; }
            .label { font-weight: bold; color: #333; }
            .value { margin-left: 10px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>New Contact Form Submission</h2>
            </div>
            <p><strong>Date of Contact:</strong> ' . date('m/d/Y') . '</p>
            <h3>Contact Information:</h3>
            <ul>
                <li><span class="label">Contact Name:</span><span class="value">' . htmlspecialchars($formData['contact_name']) . '</span></li>
                <li><span class="label">Email Address:</span><span class="value">' . htmlspecialchars($formData['contact_email']) . '</span></li>
                <li><span class="label">Reason for Contact:</span><span class="value">' . htmlspecialchars($formData['contact_reason']) . '</span></li>
                <li><span class="label">Comments:</span><span class="value">' . nl2br(htmlspecialchars($formData['comments'])) . '</span></li>
            </ul>
        </div>
    </body>
    </html>';
    
    return sendEmail($adminEmail, $subject, $body);
}

?>
