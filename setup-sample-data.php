<?php
require_once 'config/app.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

requireAuth();

$userId = getCurrentUserId();
$pdo = getDBConnection();

// Check if user already has emails
$stmt = $pdo->prepare("SELECT COUNT(*) as email_count FROM emails WHERE user_id = ?");
$stmt->execute([$userId]);
$emailCount = $stmt->fetch()['email_count'];

if ($emailCount > 0) {
    header('Location: dashboard.php');
    exit();
}

$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_sample'])) {
    try {
        // Sample emails for the user
        $sampleEmails = [
            ['email_address' => 'john.doe@gmail.com', 'password' => 'SamplePass123!', 'description' => 'Personal Gmail account', 'category' => 'Personal'],
            ['email_address' => 'j.doe.work@company.com', 'password' => 'WorkEmail456#', 'description' => 'Work email account', 'category' => 'Work'],
            ['email_address' => 'johnddoe@yahoo.com', 'password' => 'YahooPass789$', 'description' => 'Yahoo backup account', 'category' => 'Backup'],
            ['email_address' => 'john.doe.shopping@outlook.com', 'password' => 'ShopPass2024@', 'description' => 'Online shopping account', 'category' => 'Shopping'],
            ['email_address' => 'jdoe.newsletter@protonmail.com', 'password' => 'ProtonSecure999&', 'description' => 'Newsletter subscriptions', 'category' => 'Newsletters']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO emails (user_id, email_address, password, description, category) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($sampleEmails as $email) {
            $stmt->execute([
                $userId,
                $email['email_address'],
                $email['password'],
                $email['description'],
                $email['category']
            ]);
        }
        
        $message = 'Sample emails have been added successfully!';
        $messageType = 'success';
        
        // Redirect to dashboard after a short delay
        header("refresh:2;url=dashboard.php");
        
    } catch (Exception $e) {
        $message = 'Error adding sample emails: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

$title = 'Setup Sample Data - ' . APP_NAME;
?>
<?php include 'includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header text-center">
                <h4><i class="fas fa-rocket"></i> Welcome to Email Management System!</h4>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>
                
                <div class="text-center mb-4">
                    <p class="lead">It looks like you don't have any emails in your account yet.</p>
                    <p>To help you get started and test the sharing functionality, we can add some sample emails to your account.</p>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-list"></i> Sample emails that will be added:</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span><strong>john.doe@gmail.com</strong> - Personal Gmail account</span>
                                <span class="badge bg-primary">Personal</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span><strong>j.doe.work@company.com</strong> - Work email account</span>
                                <span class="badge bg-success">Work</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span><strong>johnddoe@yahoo.com</strong> - Yahoo backup account</span>
                                <span class="badge bg-warning">Backup</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span><strong>john.doe.shopping@outlook.com</strong> - Online shopping account</span>
                                <span class="badge bg-info">Shopping</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span><strong>jdoe.newsletter@protonmail.com</strong> - Newsletter subscriptions</span>
                                <span class="badge bg-secondary">Newsletters</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <form method="POST" class="d-inline">
                        <button type="submit" name="setup_sample" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus"></i> Add Sample Emails
                        </button>
                    </form>
                    <a href="dashboard.php" class="btn btn-outline-secondary btn-lg ms-2">
                        <i class="fas fa-skip-forward"></i> Skip for Now
                    </a>
                </div>
                
                <div class="mt-4">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-lightbulb"></i> What you can do after setup:</h6>
                        <ul class="mb-0">
                            <li>Create shared collections of your emails</li>
                            <li>Generate public links for sharing (with optional password protection)</li>
                            <li>Copy emails and passwords easily</li>
                            <li>Manage and track your shared collections</li>
                            <li>View analytics on collection access</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>