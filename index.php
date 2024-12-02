<?php
session_start(); 
require_once 'dbConfig.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); 
    exit;
}

function logActivity($user_id, $action, $details) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO ActivityLogs (user_id, action, details) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $action, $details]);
}

function createApplicant($first_name, $last_name, $email, $phone_number, $address, $experience_years, $specialization) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM Applicants WHERE email = ?");
    $stmt->execute([$email]);
    $existingApplicant = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existingApplicant) {
        return ['message' => 'The email address is already registered. Please use a different email.', 'statusCode' => 400];
    }
    $stmt = $pdo->prepare("INSERT INTO Applicants (first_name, last_name, email, phone_number, address, experience_years, specialization, edited_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$first_name, $last_name, $email, $phone_number, $address, $experience_years, $specialization, $_SESSION['username']]);

    logActivity($_SESSION['user_id'], 'Added new applicant', "Added $first_name $last_name");

    return ['message' => 'Applicant added successfully!', 'statusCode' => 200];
}

function getApplicants() {
    global $pdo;
    $stmt = $pdo->query("SELECT applicant_id, first_name, last_name, email, phone_number, address, experience_years, specialization, edited_by, edited_at FROM Applicants");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateApplicant($applicant_id, $first_name, $last_name, $email, $phone_number, $address, $experience_years, $specialization) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE Applicants SET first_name = ?, last_name = ?, email = ?, phone_number = ?, address = ?, experience_years = ?, specialization = ?, edited_by = ?, edited_at = CURRENT_TIMESTAMP WHERE applicant_id = ?");
    $stmt->execute([$first_name, $last_name, $email, $phone_number, $address, $experience_years, $specialization, $_SESSION['username'], $applicant_id]);

    logActivity($_SESSION['user_id'], 'Updated applicant', "Updated applicant ID $applicant_id");

    header('Location: index.php'); 
    exit();
}

function deleteApplicant($applicant_id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM Applicants WHERE applicant_id = ?");
    $stmt->execute([$applicant_id]);

    logActivity($_SESSION['user_id'], 'Deleted applicant', "Deleted applicant ID $applicant_id");

    return ['message' => 'Applicant deleted successfully!', 'statusCode' => 200];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $response = createApplicant($_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['phone_number'], $_POST['address'], $_POST['experience_years'], $_POST['specialization']);
        } elseif ($_POST['action'] == 'update') {
            $response = updateApplicant($_POST['applicant_id'], $_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['phone_number'], $_POST['address'], $_POST['experience_years'], $_POST['specialization']);
        }
    }
}

if (isset($_GET['delete'])) {
    $response = deleteApplicant($_GET['delete']);
}

$search_results = [];
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $stmt = $pdo->prepare("SELECT * FROM Applicants WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ?");
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
    $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $search_results = getApplicants(); 
}

if (isset($_GET['refresh'])) {
    header("Location: index.php");
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adam's Job Application System</title>
        <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
            color: #333;
        }

        h1, h2 {
            text-align: center;
            color: #000080;
        }

        form {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        form input[type="text"], form input[type="email"], form input[type="number"], form textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        form input[type="submit"] {
            background-color: #000080;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        form input[type="submit"]:hover {
            background-color: #01FFFF;
        }

        table {
            width: 90%;
            margin: 20px auto;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        table th, table td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }

        table th {
            background-color: #000080;
            color: #fff;
        }

        table td a {
            color: #000080;
            text-decoration: none;
        }

        table td a:hover {
            text-decoration: underline;
        }

        .response {
            text-align: center;
            padding: 10px;
            margin: 10px;
            background-color: #728C69;
            color: white;
            border-radius: 4px;
        }

        .response.error {
            background-color: #dc3545;
        }

        .search-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .search-container input[type="text"] {
            width: 250px;
            padding: 10px;
            margin-right: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .search-container input[type="submit"], .search-container button {
            padding: 10px;
            border: 1px solid #ccc;
            background-color: #000080;
            color: white;
            border-radius: 4px;
            cursor: pointer;
        }

        .search-container button:hover {
            background-color: #01FFFF;
        }
    </style>
</head>
<body>
    <h1>Adam's Mechanic Job Application</h1>

    <div class="search-container">
        <form method="GET" action="">
            <input type="text" name="search" placeholder="Search The Name of Applicants..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" required>
            <input type="submit" value="Search">
        </form>

        <form method="GET" action="">
            <button type="submit" name="refresh">Refresh</button>
        </form>

        <!-- Logout Button -->
        <form method="GET" action="">
            <button type="submit" name="logout">Logout</button>
        </form>
    </div>

    <h2>Applicants List</h2>
    <table>
        <thead>
            <tr>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>Phone Number</th>
                <th>Experience Years</th>
                <th>Specialization</th>
                <th>Edited By</th>
                <th>Edited At</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($search_results)): ?>
                <tr><td colspan="9">No applicants found.</td></tr>
            <?php else: ?>
                <?php foreach ($search_results as $applicant): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($applicant['first_name']); ?></td>
                        <td><?php echo htmlspecialchars($applicant['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($applicant['email']); ?></td>
                        <td><?php echo htmlspecialchars($applicant['phone_number']); ?></td>
                        <td><?php echo htmlspecialchars($applicant['experience_years']); ?></td>
                        <td><?php echo htmlspecialchars($applicant['specialization']); ?></td>
                        <td><?php echo htmlspecialchars($applicant['edited_by']); ?></td> <!-- Display the edited_by field -->
                        <td><?php echo htmlspecialchars($applicant['edited_at']); ?></td>
                        <td>
                            <a href="edit.php?id=<?php echo $applicant['applicant_id']; ?>">Edit</a> | 
                            <a href="index.php?delete=<?php echo $applicant['applicant_id']; ?>">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <h2>Add New Applicant</h2>
    <form method="POST" action="">
        <input type="hidden" name="action" value="add">
        <label>First Name:</label>
        <input type="text" name="first_name" required><br>
        <label>Last Name:</label>
        <input type="text" name="last_name" required><br>
        <label>Email:</label>
        <input type="email" name="email" required><br>
        <label>Phone Number:</label>
        <input type="text" name="phone_number" required><br>
        <label>Address:</label>
        <input type="text" name="address" required><br>
        <label>Experience (years):</label>
        <input type="number" name="experience_years" required><br>
        <label>Specialization:</label>
        <input type="text" name="specialization" required><br>
        <input type="submit" value="Add Applicant">
    </form>

    <?php if (isset($response)): ?>
        <p><?php echo htmlspecialchars($response['message']); ?></p>
    <?php endif; ?>

</body>
</html>
