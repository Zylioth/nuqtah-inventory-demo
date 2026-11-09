<?php
// ... after your successful loop that inserts into borrowing_requests ...

if (!empty($success)) { // If the database insert worked
    include_once '../includes/mail_helper.php';
    
    // 1. Get User Details (You likely have these in $_SESSION)
    $user_name = $_SESSION['full_name'];
    $user_email = $_SESSION['email']; // Ensure this is stored in session during login

    // 2. Prepare the Item List for the email
    // (If they borrowed multiple items, you can join them into a string)
    $itemList = "<ul>";
    foreach ($_SESSION['cart'] as $item) {
        $itemList .= "<li>" . htmlspecialchars($item['asset_name']) . "</li>";
    }
    $itemList .= "</ul>";

    // 3. EMAIL TO THE USER (The Receipt)
    $userSubject = "Request Received - Nuqtah Inventory";
    $userBody = "
        <p>Hello <b>$user_name</b>,</p>
        <p>Your request for the following items has been submitted to the ICT Department:</p>
        $itemList
        <p><b>Status:</b> Pending Approval</p>
        <p>Please wait for an approval notification before coming to the office.</p>";
    
    sendNuqtahEmail($user_email, $user_name, $userSubject, $userBody);

    // 4. EMAIL TO THE ADMIN (The Alert)
    $adminSubject = "ACTION REQUIRED: New Borrowing Request";
    $appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost/Nuqtah_IT', '/');
    $adminBody = "
        <h3 style='color: #00796B;'>New Request Submitted</h3>
        <p><b>User:</b> $user_name</p>
        <p><b>Items requested:</b></p>
        $itemList
        <br>
        <a href='{$appUrl}/admin/view_requests.php' 
           style='background: #00796B; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
           Review in Admin Dashboard
        </a>";
    
    // Send this to your system email
    sendNuqtahEmail('nuqtah.system@gmail.com', 'Nuqtah Admin', $adminSubject, $adminBody);

    // Clear the cart and redirect
    unset($_SESSION['cart']);
    header("Location: ../inventory_list.php?msg=submitted");
    exit();
}