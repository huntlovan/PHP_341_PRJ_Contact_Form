<?php
/**
 * formHandler.php
 * Processes the contact form submission and sends emails
 * @author Hunter Lovan
 * @version 1.0
 * @disclaimer This code is for educational purposes only.
 */

// Include the email functions
require_once 'sendEmail.php';

// Function to sanitize input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to validate email format
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Initialize variables
$errors = [];
$success = false;
$formData = [];

// Check if form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Sanitize and validate form data
    $formData['contact_name'] = sanitizeInput($_POST['contact_name'] ?? '');
    $formData['contact_email'] = sanitizeInput($_POST['contact_email'] ?? '');
    $formData['contact_reason'] = sanitizeInput($_POST['contact_reason'] ?? '');
    $formData['comments'] = sanitizeInput($_POST['comments'] ?? '');
    
    // Server-side validation
    if (empty($formData['contact_name']) || strlen($formData['contact_name']) < 2) {
        $errors[] = "Contact name must be at least 2 characters long.";
    }
    
    if (empty($formData['contact_email']) || !validateEmail($formData['contact_email'])) {
        $errors[] = "Please provide a valid email address.";
    }
    
    if (empty($formData['contact_reason'])) {
        $errors[] = "Please select a reason for contact.";
    }
    
    if (empty($formData['comments']) || strlen($formData['comments']) < 10) {
        $errors[] = "Comments must be at least 10 characters long.";
    }
    
    // If no errors, process the form
    if (empty($errors)) {
        
        // Configuration - UPDATE THESE WITH YOUR ACTUAL EMAIL ADDRESSES
        $adminEmail = 'hunter.lovan36@gmail.com'; // Change this to your email address
        
        // Send confirmation email to customer
        $customerResult = sendCustomerConfirmation($formData['contact_email'], $formData['contact_name']);
        
        // Send notification email to admin
        $adminResult = sendAdminNotification($adminEmail, $formData);
        
        // Check if both emails were sent successfully
        if ($customerResult['success'] && $adminResult['success']) {
            $success = true;
        } else {
            if (!$customerResult['success']) {
                $errors[] = "Failed to send confirmation email: " . $customerResult['message'];
            }
            if (!$adminResult['success']) {
                $errors[] = "Failed to send notification email: " . $adminResult['message'];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Form - Processing Result</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .content {
            padding: 40px;
        }

        .success-message {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }

        .success-message h2 {
            margin-bottom: 10px;
            font-size: 1.8em;
        }

        .error-message {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .error-message h2 {
            margin-bottom: 15px;
            font-size: 1.5em;
        }

        .error-list {
            list-style: none;
            padding: 0;
        }

        .error-list li {
            padding: 5px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .error-list li:last-child {
            border-bottom: none;
        }

        .summary-section {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 10px;
            margin: 30px 0;
        }

        .summary-section h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.4em;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .summary-table th,
        .summary-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .summary-table th {
            background-color: #3498db;
            color: white;
            font-weight: bold;
        }

        .summary-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .btn-container {
            text-align: center;
            margin-top: 30px;
        }

        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: linear-gradient(135deg, #2980b9, #1c598a);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        .footer {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 20px;
            font-size: 0.9em;
        }

        .instructions {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .instructions h4 {
            margin-bottom: 10px;
            color: #856404;
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 10px;
            }
            
            .header {
                padding: 30px 20px;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .summary-table {
                font-size: 0.9em;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Contact Form Results</h1>
            <p>Professional Business Services</p>
        </div>

        <div class="content">
            <?php if ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
                
                <?php if ($success): ?>
                    <div class="success-message">
                        <h2>✓ Thank You for Contacting Us!</h2>
                        <p>Your message has been successfully submitted on <strong><?php echo date('m/d/Y'); ?></strong>.</p>
                        <p>We have sent a confirmation email to <strong><?php echo htmlspecialchars($formData['contact_email']); ?></strong></p>
                        <p>Our team will respond to your inquiry within 24-48 hours.</p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="error-message">
                        <h2>⚠ There were some issues with your submission:</h2>
                        <ul class="error-list">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!empty($formData['contact_name'])): ?>
                    <div class="summary-section">
                        <h3>Submission Summary</h3>
                        <p><strong>Date of Contact:</strong> <?php echo date('m/d/Y'); ?></p>
                        
                        <table class="summary-table">
                            <thead>
                                <tr>
                                    <th>Field</th>
                                    <th>Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Contact Name</strong></td>
                                    <td><?php echo htmlspecialchars($formData['contact_name']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Email Address</strong></td>
                                    <td><?php echo htmlspecialchars($formData['contact_email']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Reason for Contact</strong></td>
                                    <td><?php echo htmlspecialchars($formData['contact_reason']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Comments</strong></td>
                                    <td><?php echo nl2br(htmlspecialchars($formData['comments'])); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="instructions">
                    <h4>No form data received</h4>
                    <p>This page processes contact form submissions. Please use the contact form to submit your inquiry.</p>
                </div>
            <?php endif; ?>

            <div class="btn-container">
                <a href="inputForm.html" class="btn">← Back to Contact Form</a>
            </div>

            <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && (!$success || !empty($errors))): ?>
                <div class="instructions">
                    <h4>Important Configuration Note:</h4>
                    <p><strong>For Developers:</strong> To enable email functionality, please update the SMTP configuration in <code>sendEmail.php</code>:</p>
                    <ul>
                        <li>Set your SMTP server details (Host, Username, Password)</li>
                        <li>Update the admin email address in <code>formHandler.php</code></li>
                        <li>Configure proper email authentication (use App Passwords for Gmail)</li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p>&copy; 2024 Professional Business Services. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
