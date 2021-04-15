<?php

// Connect to the database
require("_connect.php");
$conn = dbo();

// Create our SQL with an email placeholder
//从$_POST那里获得了我们的email
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$email = strtolower($email);
$password = filter_input(INPUT_POST, 'password');

// Prepare the SQL， ready to go
$sql = "SELECT * FROM users WHERE email = :email";
$stmt = $conn->prepare($sql);

// Bind the value to the placeholder
//这里让$email的赋值于这个：email
$stmt->bindParam(':email', $email, PDO::PARAM_STR);

// Execute
$stmt->execute();

// Get user
//如果user不存在的话，就返回false，PDO::FETCH_ASSOC是保证回归的是 associate array， otherwise 返回的是 array
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if we have a user and their password is correct
//找到我们是否有这个email的用户
$authorized = false;
//如果有这个用户，那么验证密码和database里面的密码是否一致
if ($user) {
    //如果密码不一致的话，返回false
    $authorized = password_verify($password, $user['password']);
}

session_start();
if (!$authorized) {
    $_SESSION['errors'][] = "Your login/password combination could not be found.";
    //这里就是把他们的information back to them
    $_SESSION['form_values'] = $_POST;

    //重新被发配回了login.php页面让user重新输入
    header("Location: login.php");
    exit();
}
//如果是authorized通过了，那么直接就去新的页面，并且session
unset($user['password']);
$_SESSION['user'] = $user;
//在这里success有好几个，所以我们用一个数组代替
$_SESSION['successes'][] = "You have been successfully logged in.";
header("Location: profile.php");
exit();