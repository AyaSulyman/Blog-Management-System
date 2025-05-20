<?php 

session_start();

$conn = new mysqli("localhost", "root", "", "blog_management");

if($conn->connect_error){
    die("connection failed: ". $conn->connect_error);
}

$username=$password="";
$errors=[];


//Form Submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $username=trim($_POST["username"]);
    $password=trim($_POST["password"]);


    if(empty($username) || empty($password)){
    $errors[]="Username and password must be filled";
}


 else {
        
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "Username already taken.";
             echo "<script>alert('Username already taken.');</script>";
            
        } else {
            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        
            $insert_stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $insert_stmt->bind_param("ss", $username, $hashed_password);

            if ($insert_stmt->execute()) {
                header("Location: login.php");
                exit();
            } else {
                $errors[] = "Error registering user.";
            }
            $insert_stmt->close();
        }
        $stmt->close();
    }
}
    $conn->close();

?>

<!-- HTML Registration Form -->
 <!DOCTYPE html>
 <html lang="en">
 <head>
    <link rel="stylesheet" href="style.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
 </head>
 <body>
<div class="container" style="height:825px;margin-top:0px">
   

   <?php

   if(!empty($errors)){
    foreach($errors as $e){
          echo "<p style='color:red;'>$e</p>";

    }
   }
   ?>

    <form method="post" action="register.php" class='form'>
         <div class='img-container'>
        <img src="images/peace.jpeg" style="width:500px" alt="peace">

</div>
<div>
      <h2>User Registration</h2>
        <label>Username</label><br>
        <input type="text" name="username" required><br><br>

        <label>Password</label><br>
        <input type="password" name="password" required><br><br>

        <button type="submit">Register</button>
        <p>Already have an account? <a href="login.php">Login here</a>.</p>
</div>
    </form>

    
</div>
 </body>
 </html>
