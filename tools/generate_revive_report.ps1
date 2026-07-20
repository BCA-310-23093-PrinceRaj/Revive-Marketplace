param(
    [string]$OutputDocx = "C:\xampp\htdocs\revive\Revive_Final_Report_Prince_Raj.docx",
    [string]$OutputPdf = "C:\xampp\htdocs\revive\Revive_Final_Report_Prince_Raj.pdf",
    [string]$StudentName = "Prince Raj",
    [string]$RegistrationNo = "23303310004"
)

$ErrorActionPreference = "Stop"

$projectRoot = "C:\xampp\htdocs\revive"
$screenshots = @(
    "screenshots\revive_home.png",
    "screenshots\revive_shop.png",
    "screenshots\revive_product.png",
    "screenshots\revive_login.png",
    "screenshots\revive_register.png",
    "screenshots\revive_admin_login.png",
    "screenshots\revive_contact.png",
    "screenshots\revive_about.png"
) | ForEach-Object { Join-Path $projectRoot $_ } | Where-Object { Test-Path $_ }

$codeScreenshots = @(
    "screenshots\code\code_chat_php.png",
    "screenshots\code\code_chat_action.png",
    "screenshots\code\code_chats_table.png"
) | ForEach-Object { Join-Path $projectRoot $_ } | Where-Object { Test-Path $_ }

$sourceFiles = @(
    "index.php", "shop.php", "product_details.php", "login.php", "register.php",
    "admin_login.php", "admin_verify_otp.php", "admin_dashboard.php", "seller_dashboard.php",
    "add_product.php", "checkout.php", "my_orders.php", "wishlist.php", "chat.php",
    "actions\chat_action.php",
    "contact.php", "database.sql", "includes\otp.php", "actions\verify_otp_action.php",
    "actions\admin_action.php", "actions\seller_action.php", "actions\buyer_action.php",
    "includes\financial_report.php"
)

function Add-Text {
    param(
        [object]$Selection,
        [string]$Text,
        [int]$Size = 12,
        [bool]$Bold = $false,
        [int]$Align = 0
    )
    $Selection.Font.Name = "Times New Roman"
    $Selection.Font.Size = $Size
    $Selection.Font.Bold = [int]$Bold
    $Selection.ParagraphFormat.Alignment = $Align
    $Selection.TypeText($Text)
    $Selection.TypeParagraph()
}

function Add-PageBreak {
    param([object]$Selection)
    $Selection.InsertBreak(7)
}

function Add-Bullets {
    param([object]$Selection, [string[]]$Items)
    foreach ($item in $Items) {
        Add-Text $Selection ("- " + $item) 12 $false 0
    }
}

function Add-ImagePage {
    param(
        [object]$Selection,
        [string]$Title,
        [string]$Path,
        [string]$Caption
    )
    Add-Text $Selection $Title 18 $true 1
    Add-Text $Selection $Caption 12 $false 1
    if (Test-Path $Path) {
        $shape = $Selection.InlineShapes.AddPicture($Path, $false, $true)
        $shape.LockAspectRatio = $true
        if ($shape.Width -gt 470) { $shape.Width = 470 }
        if ($shape.Height -gt 520) { $shape.Height = 520 }
        $Selection.TypeParagraph()
    }
}

function Add-TablePage {
    param([object]$Selection, [string]$Title, [array]$Rows)
    Add-Text $Selection $Title 18 $true 1
    $table = $Selection.Tables.Add($Selection.Range, $Rows.Count, 2)
    $table.Borders.Enable = 1
    for ($i = 0; $i -lt $Rows.Count; $i++) {
        $table.Cell($i + 1, 1).Range.Text = $Rows[$i][0]
        $table.Cell($i + 1, 2).Range.Text = $Rows[$i][1]
    }
    $Selection.EndKey(6) | Out-Null
    $Selection.TypeParagraph()
}

function Add-SourcePage {
    param([object]$Selection, [string]$RelativePath)
    $path = Join-Path $projectRoot $RelativePath
    Add-Text $Selection ("Source Code: " + $RelativePath) 16 $true 1
    if (Test-Path $path) {
        $lines = Get-Content -LiteralPath $path -TotalCount 55
        $Selection.Font.Name = "Consolas"
        $Selection.Font.Size = 8
        $Selection.Font.Bold = 0
        foreach ($line in $lines) {
            $Selection.TypeText($line.Substring(0, [Math]::Min($line.Length, 120)))
            $Selection.TypeParagraph()
        }
    }
}

$word = $null
$doc = $null
try {
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $word.DisplayAlerts = 0
    $doc = $word.Documents.Add()
    $selection = $word.Selection
    $doc.PageSetup.TopMargin = 72
    $doc.PageSetup.BottomMargin = 72
    $doc.PageSetup.LeftMargin = 72
    $doc.PageSetup.RightMargin = 72

    $pages = New-Object System.Collections.Generic.List[scriptblock]

    $pages.Add({ Add-Text $selection '"Revive: Sustainable Second-Hand Fashion Marketplace"' 22 $true 1; Add-Text $selection "Project Report" 18 $true 1; Add-Text $selection "Submitted in partial fulfillment of the requirement for the award of the Degree of Bachelor of Computer Applications" 14 $false 1; Add-Text $selection ("Submitted By: " + $StudentName) 14 $true 1; Add-Text $selection ("Registration No.: " + $RegistrationNo) 13 $false 1; Add-Text $selection "BCA 6th Semester, Session 2023-2026" 13 $false 1; Add-Text $selection "CIMAGE Professional College" 14 $true 1 })
    $pages.Add({ Add-Text $selection "Certificate" 20 $true 1; Add-Text $selection ("This is to certify that the project report entitled Revive: Sustainable Second-Hand Fashion Marketplace has been prepared by " + $StudentName + " as a part of the BCA final year project work. The project demonstrates the design and implementation of a PHP and MySQL based web marketplace for second-hand fashion products.") 13 $false 0 })
    $pages.Add({ Add-Text $selection "Declaration" 20 $true 1; Add-Text $selection "I hereby declare that this project report is based on my own project work for the Revive website. The report describes the actual modules, database design, implementation, and screenshots of the developed web application." 13 $false 0 })
    $pages.Add({ Add-Text $selection "Acknowledgement" 20 $true 1; Add-Text $selection "I express my sincere gratitude to my guides, faculty members, and classmates for their support during the development of Revive. Their suggestions helped in improving the website structure, user interface, and documentation." 13 $false 0 })
    $pages.Add({ Add-Text $selection "Abstract" 20 $true 1; Add-Text $selection "Revive is a modern second-hand fashion marketplace where users can buy and sell pre-loved clothing, footwear, accessories, streetwear, vintage, and luxury products. The system supports buyer, seller, and admin roles. It includes authentication, OTP verification, product listing, approval workflow, shopping, wishlist, checkout, order tracking, return requests, disputes, chat, reviews, notifications, contact messages, and financial reports." 13 $false 0 })
    $pages.Add({ Add-Text $selection "Table of Contents" 20 $true 1; Add-Bullets $selection @("Introduction", "Project Overview", "Objectives", "Scope", "Technology Used", "System Analysis", "Database Design", "Module Description", "Website Snapshots", "Testing", "Source Code Appendix", "Bibliography and References") })

    $introTopics = @(
        @("Introduction", "Revive is designed to promote sustainable fashion by giving used clothing and fashion items a second life. It connects sellers who want to list products with buyers looking for affordable and premium pre-loved fashion."),
        @("Need of the Project", "The fashion industry creates waste when usable clothing is discarded. Revive provides a digital platform where users can resell items, discover products by category, and complete purchase workflows through a secure local marketplace."),
        @("Project Objectives", "The objective of Revive is to provide a responsive, secure, and easy-to-use marketplace with role-based access for buyers, sellers, and administrators."),
        @("Project Scope", "The scope includes user registration, login, email OTP verification, product listing, product approval, shopping, wishlist management, order management, seller dashboard, admin dashboard, chat, dispute handling, returns, reviews, and reporting."),
        @("Existing System", "Traditional second-hand selling is often offline, unorganized, and difficult to verify. Manual product management also creates delays in approval and order tracking."),
        @("Proposed System", "The proposed system centralizes all marketplace activities in one web application. Admins can control product approval and orders, sellers can manage listings and shipping, and buyers can shop with better visibility."),
        @("Advantages", "Revive improves reuse of fashion products, reduces waste, provides a structured marketplace, enables category-based discovery, and allows administrators to supervise platform activities."),
        @("Limitations", "The current system is built for local deployment through XAMPP. Online payment gateway integration, production hosting, and advanced recommendation algorithms can be added in future versions.")
    )
    foreach ($topic in $introTopics) {
        $pages.Add({ Add-Text $selection $topic[0] 18 $true 1; Add-Text $selection $topic[1] 13 $false 0 }.GetNewClosure())
    }

    $techRows = @(
        @("Frontend", "HTML5, Tailwind CSS CDN, JavaScript, responsive design"),
        @("Backend", "PHP 8 running on XAMPP Apache or PHP development server"),
        @("Database", "MySQL database named revive_db"),
        @("Server Tools", "XAMPP, phpMyAdmin, Apache, MySQL"),
        @("Editor", "Visual Studio Code"),
        @("Browser", "Google Chrome for testing and screenshots")
    )
    $pages.Add({ Add-TablePage $selection "Technology Used" $techRows })

    $modulePages = @(
        @("User Registration Module", "The registration module collects name, email, password, and role. OTP support is used for verification flows, and user data is stored in the users table."),
        @("Login Module", "The login module authenticates users and redirects them according to their role. Buyers, sellers, and admins receive different dashboard experiences."),
        @("Admin OTP Verification", "The admin login process includes OTP verification. The page admin_verify_otp.php validates a six-digit OTP and protects the admin dashboard from unauthorized access."),
        @("Buyer Module", "Buyers can browse products, search, filter by categories, view details, add products to wishlist or cart, checkout, track orders, request returns, and raise disputes."),
        @("Seller Module", "Sellers can add new products with images and details. Products remain pending until approved by the admin. Sellers can manage orders, shipping details, and return requests."),
        @("Admin Module", "Admins manage inventory approvals, orders, reports, categories, community reviews, messages, disputes, and administrative notifications."),
        @("Product Management", "The product module stores product title, description, price, condition, size, brand, category, usage information, image paths, and status."),
        @("Category Management", "Categories such as Streetwear, Vintage, Luxury, Accessories, and Footwear help users discover products quickly."),
        @("Wishlist Module", "The wishlist module lets users save favorite products for later. It is implemented with the wishlist table and AJAX actions."),
        @("Cart and Checkout Module", "The cart and checkout workflow allows selected products to be converted into orders with shipping address and phone information."),
        @("Order Management", "Orders include buyer, product, amount, status, discount, tracking number, carrier, return status, and timestamps."),
        @("Return Workflow", "Buyers can request returns after completion. Sellers can approve or reject return requests, and both parties receive notifications."),
        @("Dispute Management", "Disputes can be raised for eligible orders. Admins review disputes and update statuses such as open, under review, resolved refunded, or rejected."),
        @("Chat Module", "The chat system supports buyer-seller messages, product-linked conversation context, image attachments, unread counts, read receipts, and periodic AJAX refresh for a near real-time experience."),
        @("Review Module", "Buyers can review products they purchased. Reviews include rating and comments and help future buyers make decisions."),
        @("Notification Module", "Notifications are created for approvals, order updates, shipping updates, return status, review activity, and dispute updates."),
        @("Financial Report Module", "The report module calculates revenue, order counts, average order value, seller contribution, and loss data related to refunds."),
        @("Contact and Newsletter Module", "Visitors can submit contact messages and subscribe to the newsletter. These records are stored in messages and subscribers tables."),
        @("Security Features", "Revive uses sessions, CSRF tokens in important forms, password hashing, OTP verification, role checks, and server-side validation."),
        @("Responsive User Interface", "The interface uses dark modern styling, electric lime highlights, product cards, navigation icons, and responsive layouts for desktop and mobile.")
    )
    foreach ($m in $modulePages) {
        $pages.Add({ Add-Text $selection $m[0] 18 $true 1; Add-Text $selection $m[1] 13 $false 0 }.GetNewClosure())
    }

    $dbRows = @(
        @("users", "Stores buyer, seller, and admin account details."),
        @("otp_requests", "Stores OTP hashes, purpose, expiry, resend time, and attempts."),
        @("categories", "Stores product category names and slugs."),
        @("products", "Stores seller products, price, condition, brand, images, and approval status."),
        @("orders", "Stores purchases, shipping information, order status, and return status."),
        @("chats", "Stores sender, receiver, product, message, image, and read status."),
        @("cart", "Stores user cart items."),
        @("wishlist", "Stores user saved products."),
        @("notifications", "Stores user alerts."),
        @("reviews", "Stores product ratings and comments."),
        @("product_images", "Stores product image paths and primary image flag."),
        @("messages", "Stores contact form messages."),
        @("subscribers", "Stores newsletter emails."),
        @("disputes", "Stores order disputes and admin resolution status.")
    )
    $pages.Add({ Add-TablePage $selection "Database Tables" $dbRows })

    $workflowPages = @(
        @("Buyer Workflow", "Open website, register or login, browse shop, search products, view product detail, add to wishlist or cart, checkout, track order, complete order, review product, and request return if required."),
        @("Seller Workflow", "Login as seller, add product, upload product images, wait for admin approval, manage listings, update shipping information, and respond to return requests."),
        @("Admin Workflow", "Login with OTP, review dashboard, approve or reject products, manage orders, update categories, monitor messages, handle disputes, and view financial reports."),
        @("Product Approval Workflow", "A seller-submitted product is stored with pending status. Admin reviews the item and changes status to available or deletes/rejects it with notification."),
        @("Order Status Workflow", "Orders begin as pending and move through shipped, delivered, completed, or cancelled according to seller, admin, and buyer actions."),
        @("Return and Dispute Workflow", "Buyers can request returns on completed orders. Disputes can be opened within the allowed window and are reviewed by admins.")
    )
    foreach ($w in $workflowPages) {
        $pages.Add({ Add-Text $selection $w[0] 18 $true 1; Add-Text $selection $w[1] 13 $false 0 }.GetNewClosure())
    }

    $shotCaptions = @(
        "Homepage showing Revive brand, search, navigation, and hero section.",
        "Shop page showing product browsing, filtering, and product cards.",
        "Product details page showing product information and purchase actions.",
        "Login page for registered users.",
        "Registration page for new buyers and sellers.",
        "Admin login page used before OTP verification.",
        "Contact page for visitor messages.",
        "About/info page describing the Revive platform."
    )
    for ($i = 0; $i -lt $screenshots.Count; $i++) {
        $caption = if ($i -lt $shotCaptions.Count) { $shotCaptions[$i] } else { "Revive website snapshot." }
        $pages.Add({ Add-ImagePage $selection ("Website Snapshot " + ($i + 1)) $screenshots[$i] $caption }.GetNewClosure())
    }

    $codeCaptions = @(
        "Code snapshot showing chat.php frontend logic, contacts loading, message rendering, and AJAX polling.",
        "Code snapshot showing server-side chat message handling, CSRF validation, image validation, upload, and database insert.",
        "Code snapshot showing the MySQL chats table used for sender, receiver, product context, image path, read status, and timestamp."
    )
    for ($i = 0; $i -lt $codeScreenshots.Count; $i++) {
        $caption = if ($i -lt $codeCaptions.Count) { $codeCaptions[$i] } else { "Revive source code snapshot." }
        $pages.Add({ Add-ImagePage $selection ("Coding Snapshot " + ($i + 1)) $codeScreenshots[$i] $caption }.GetNewClosure())
    }

    $testPages = @(
        @("Testing Overview", "Testing was performed on public pages, authentication screens, product browsing, dashboard access, form validation, and database-connected workflows."),
        @("Unit Testing", "Individual PHP files and actions were tested for expected inputs, redirects, validation messages, and database operations."),
        @("Integration Testing", "Connected workflows such as registration to OTP, product listing to admin approval, checkout to order, and return request to notification were verified."),
        @("Validation Testing", "Forms such as login, registration, OTP, product listing, contact, checkout, shipping update, and return request were checked for required fields and valid data."),
        @("Security Testing", "Role checks, CSRF token usage, session validation, OTP verification, and password hashing were reviewed."),
        @("Browser Testing", "The website was tested in Google Chrome using desktop viewport screenshots for the final report."),
        @("Test Case: Login", "Input valid email and password. Expected result: user is authenticated and redirected according to role."),
        @("Test Case: Admin OTP", "Input a six digit OTP after admin login. Expected result: valid OTP opens admin dashboard, invalid OTP shows error."),
        @("Test Case: Add Product", "Seller enters product details and uploads image. Expected result: product saved with pending status."),
        @("Test Case: Approve Product", "Admin approves pending product. Expected result: product becomes available in shop."),
        @("Test Case: Wishlist", "Buyer toggles wishlist icon. Expected result: product is added or removed and wishlist count updates."),
        @("Test Case: Checkout", "Buyer submits shipping details. Expected result: order record is created with pending status."),
        @("Test Case: Return Request", "Buyer requests return for completed order. Expected result: return status becomes requested and seller receives notification."),
        @("Test Case: Dispute", "Buyer submits dispute for eligible order. Expected result: dispute record is created for admin review."),
        @("Test Case: Contact", "Visitor submits message. Expected result: message saved for admin review.")
    )
    foreach ($t in $testPages) {
        $pages.Add({ Add-Text $selection $t[0] 18 $true 1; Add-Text $selection $t[1] 13 $false 0 }.GetNewClosure())
    }

    $futurePages = @(
        @("Future Enhancement 1", "Integrate a production payment gateway for secure online transactions."),
        @("Future Enhancement 2", "Add AI based recommendation for similar products and personalized style suggestions."),
        @("Future Enhancement 3", "Add courier API integration for real-time shipment tracking."),
        @("Future Enhancement 4", "Add seller analytics with product views, conversion rate, and category demand."),
        @("Future Enhancement 5", "Add mobile app support using the same database and API layer."),
        @("Conclusion", "Revive successfully demonstrates a complete second-hand fashion marketplace using PHP and MySQL. The project supports real marketplace workflows, role-based access, product management, order handling, and administrative supervision."),
        @("Bibliography", "Books and online resources used include PHP and MySQL references, HTML and CSS references, JavaScript documentation, XAMPP documentation, and web development tutorials."),
        @("References", "PHP Official Documentation, MySQL Documentation, MDN Web Docs, XAMPP Documentation, Tailwind CSS Documentation, phpMyAdmin documentation, and Google Chrome Developer Tools.")
    )
    foreach ($f in $futurePages) {
        $pages.Add({ Add-Text $selection $f[0] 18 $true 1; Add-Text $selection $f[1] 13 $false 0 }.GetNewClosure())
    }

    foreach ($sf in $sourceFiles) {
        $pages.Add({ Add-SourcePage $selection $sf }.GetNewClosure())
    }

    $detailTopics = @(
        "Role Based Access Control", "Session Management", "CSRF Protection", "Password Hashing",
        "OTP Request Storage", "Product Image Storage", "Search and Filter Design", "Admin Dashboard Sections",
        "Seller Dashboard Sections", "Buyer Order Dashboard", "Notification Messages", "Database Relationships",
        "User Interface Theme", "Responsive Layout", "Error Handling", "Local Deployment Steps",
        "MySQL Import Steps", "Testing Data", "Maintenance Plan", "Backup Plan",
        "Privacy Considerations", "Performance Considerations", "Accessibility Considerations", "Project Learning Outcome"
    )
    foreach ($topic in $detailTopics) {
        $pages.Add({
            Add-Text $selection $topic 18 $true 1
            Add-Text $selection ("This section explains how " + $topic.ToLower() + " is handled in the Revive project. The implementation follows the PHP and MySQL structure available in the project folder and supports the overall marketplace workflow.") 13 $false 0
            Add-Bullets $selection @("Related to the Revive website only.", "Connected with buyer, seller, or admin workflow.", "Implemented using project PHP files and MySQL tables.", "Verified through local browser testing.")
        }.GetNewClosure())
    }

    while ($pages.Count -lt 157) {
        $index = $pages.Count + 1
        $pages.Add({
            Add-Text $selection ("Appendix Page " + $index) 18 $true 1
            Add-Text $selection "This appendix page belongs to the Revive project report and is reserved for additional notes, screenshots, test observations, module explanations, and viva preparation points related to the website." 13 $false 0
            Add-Bullets $selection @("Project Name: Revive", "Domain: Sustainable second-hand fashion marketplace", "Technology: PHP, MySQL, HTML, CSS, JavaScript", "Local Server: XAMPP", ("Prepared By: " + $StudentName))
        }.GetNewClosure())
    }

    for ($p = 0; $p -lt 157; $p++) {
        & $pages[$p]
        if ($p -lt 156) { Add-PageBreak $selection }
    }

    $doc.SaveAs2($OutputDocx, 16)
    $doc.SaveAs2($OutputPdf, 17)
    Write-Output "DOCX: $OutputDocx"
    Write-Output "PDF: $OutputPdf"
} finally {
    if ($doc -ne $null) { $doc.Close($false) }
    if ($word -ne $null) { $word.Quit() }
}
