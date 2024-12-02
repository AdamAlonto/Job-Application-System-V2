<?php
session_start();
require_once 'dbConfig.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

function logActivity($user_id, $action, $details) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO ActivityLogs (user_id, action, details) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $action, $details]);
}

function logEdit($applicant_id, $user_id) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO EditLogs (applicant_id, user_id) VALUES (?, ?)");
    $stmt->execute([$applicant_id, $user_id]);
}

function updateApplicant($applicant_id, $first_name, $last_name, $email, $phone_number, $address, $experience_years, $specialization) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Applicants WHERE email = ? AND applicant_id != ?");
    $stmt->execute([$email, $applicant_id]);
    $email_exists = $stmt->fetchColumn();

    if ($email_exists > 0) {
        return [
            'message' => 'This email is already in use by another applicant.',
            'statusCode' => 400
        ];
    }

    $stmt = $pdo->prepare("UPDATE Applicants SET first_name = ?, last_name = ?, email = ?, phone_number = ?, address = ?, experience_years = ?, specialization = ? WHERE applicant_id = ?");
    
    if ($stmt->execute([$first_name, $last_name, $email, $phone_number, $address, $experience_years, $specialization, $applicant_id])) {
        $details = "Updated applicant with ID: $applicant_id";
        logActivity($_SESSION['user_id'], 'Update', $details);

        logEdit($applicant_id, $_SESSION['user_id']);

        return [
            'message' => 'Applicant updated successfully.',
            'statusCode' => 200
        ];
    } else {
        return [
            'message' => 'Failed to update applicant. Please try again.',
            'statusCode' => 400
        ];
    }
}

if (isset($_GET['id'])) {
    $applicant_id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM Applicants WHERE applicant_id = ?");
    $stmt->execute([$applicant_id]);
    $applicant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$applicant) {
        die("Applicant not found.");
    }
}

$response = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    $response = updateApplicant($_POST['applicant_id'], $_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['phone_number'], $_POST['address'], $_POST['experience_years'], $_POST['specialization']);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Applicant</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
        }

        h1 {
            text-align: center;
            margin: 20px 0;
        }

        .form-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input, textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        input[type="submit"] {
            background-color: #234F1E;
            color: #fff;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }

        input[type="submit"]:hover {
            background-color: #80FC38;
        }

        .response {
            margin: 20px 0;
            padding: 10px;
            background-color: #f8d7da;
            color: #721c24;
            border-radius: 4px;
        }

        .buttons-container {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .buttons-container button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .buttons-container button:hover {
            background-color: #0056b3;
        }

        .buttons-container a button {
            background-color: #6c757d;
        }

        .buttons-container a button:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>

    <h1>Edit Applicant</h1>

    <div class="form-container">
        <form action="edit.php?id=<?php echo $applicant['applicant_id']; ?>" method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="applicant_id" value="<?php echo $applicant['applicant_id']; ?>">

            <label>First Name:</label>
            <input type="text" name="first_name" value="<?php echo htmlspecialchars($applicant['first_name']); ?>" required>

            <label>Last Name:</label>
            <input type="text" name="last_name" value="<?php echo htmlspecialchars($applicant['last_name']); ?>" required>

            <label>Email:</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($applicant['email']); ?>" required>

            <label>Phone Number:</label>
            <input type="text" name="phone_number" value="<?php echo htmlspecialchars($applicant['phone_number']); ?>" required>

            <label>Address:</label>
            <textarea name="address" required><?php echo htmlspecialchars($applicant['address']); ?></textarea>

            <label>Experience Years:</label>
            <input type="number" name="experience_years" value="<?php echo htmlspecialchars($applicant['experience_years']); ?>" required>

            <label>Specialization:</label>
            <input type="text" name="specialization" value="<?php echo htmlspecialchars($applicant['specialization']); ?>" required>

            <input type="submit" value="Update Applicant">
        </form>

        <?php if (isset($response)): ?>
            <div class="response"><?php echo $response['message']; ?></div>
        <?php endif; ?>

        <div class="buttons-container">
            <a href="index.php"><button>Back to Home</button></a>
            <a href="edit.php?id=<?php echo $applicant['applicant_id']; ?>&refresh=true"><button>Refresh</button></a>
        </div>
    </div>

</body>
</html>
