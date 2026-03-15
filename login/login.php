<?php
session_start();

$login = false;
$showError = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    include '../connect_db.php';
    $email = $_POST["email"];
    $password = $_POST["password"];
    $password = hash('sha256', $password);

    $sql = "SELECT * FROM users WHERE email = ? AND password_hash = ?";
    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "ss", $email, $password);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if($result && mysqli_num_rows($result) == 1){
        $row = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['role_id'] = $row['role_id'];
        $_SESSION['first_name'] = $row['first_name'];
        $_SESSION['last_name'] = $row['last_name'];
        $_SESSION['email'] = $row['email'];
        $_SESSION['created_at'] = $row['created_at'];

        if($_SESSION['role_id'] == 1){
            header("Location: ../admin/home.php");
            exit();
        }
        elseif($_SESSION['role_id'] == 2){
            header("Location: ../manager/home.php");
            exit();
        }
        elseif($_SESSION['role_id'] == 3){
            header("Location: ../developer/home.php");
            exit();
        }
        elseif($_SESSION['role_id'] == 5){
            header("Location: ../stakeholder/home.php");
            exit();
        }
    } 
    else {
        $showError = "Invalid credentials!!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <title>Login</title>
    </head>

    <body>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid"><a class="navbar-brand" href="#">Project Tracker</a></div>
        </nav>

        <div class="container mt-5">
            <h2>Login</h2>
            <?php
                if($showError){
                    echo '<div class="alert alert-danger">'.$showError.'</div>';
                }
            ?>
            <form action="" method="POST">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary">Login</button>
            </form>
        </div>
    </body>
</html>