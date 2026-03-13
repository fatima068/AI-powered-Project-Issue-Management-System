<?php
    $login = false;
    $showError = false;

    if($_SERVER["REQUEST_METHOD"] == "POST"){
        include '../connect_db.php';
        $email = $_POST["email"];
        $password = $_POST["password"];
        $password = hash('sha256', $password);

        $sql = "Select * from users where email = '$email' AND password_hash = '$password'";
        $result = mysqli_query($conn, $sql);
        
        if(!$result){
            die(mysqli_error($conn));
        }

        $num = mysqli_num_rows($result);
        if ($num ==1){
            $login = true;
            session_start();
            echo "logged in";
            $row = mysqli_fetch_assoc($result);
            $_SESSION['role_id'] = $row['role_id'];
            echo $_SESSION['role_id'];
            if ($_SESSION['role_id'] == 1) {
                header("Location: ../admin/home.php");
                exit();
            }
        }
        else{
            $showError = "Passwords do not match";
            echo "Passwords do not match";
        }
    }
?>


<!DOCTYPE html>
<html lang="en">
  <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
      <title>Login</title>
  </head>
  <body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
        <a class="navbar-brand" href="#">Navbar</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        </div>
    </nav>
    
    <h2>Login</h2>
    <form action="" method="POST">
        <label>Email:</label>
        <input type="email" name="email" required>
        <br><br>
        <label>Password:</label>
        <input type="password" name="password" required>
        <br><br>
        <button type="submit">Login</button>
    </form>
  </body>
</html> 