<?php

/* VALIDATION */
// Build an error handling function
function error_handler ($errors) {
    if (count($errors) > 0) {
        if (session_status() === PHP_SESSION_NONE)
            session_start();

        $_SESSION['form_values'] = $_POST;
        $_SESSION['errors'] = $errors;

        header('Location: register.php');
        //这里一定要exit，如果不exit的话，那这个页面哪怕被header到其他的地方，还是会继续往下loading
        exit();
    }
}

// Create an array to hold all the field errors
$errors = [];

// Collect our fields
$first_name = filter_input(INPUT_POST, 'first_name');
$last_name = filter_input(INPUT_POST, 'last_name');
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$email_confirmation = filter_input(INPUT_POST, 'email_confirmation', FILTER_VALIDATE_EMAIL);
$password = filter_input(INPUT_POST, 'password');
$password_confirmation = filter_input(INPUT_POST, 'password_confirmation');

// Validate the necessary fields are not empty
$required_fields = [
    'first_name',
    'last_name',
    'email',
    'email_confirmation',
    'password',
    'password_confirmation'
];

foreach ($required_fields as $field) {
    //一个variable的variable，因为$field是一个string，不是variable，所以我们使用两个$
    //如果是$$在这里就等于上面那个$first_name = filter_input(INPUT_POST, 'first_name');
    if (empty($$field)) {
        $human_field = str_replace("_", " ", $field);
        $errors[] = "You cannot leave the {$human_field} blank.";
    }
}

// Validate the email is in the correct format
if (!$email)
    $errors[] = "The email needs to be in a valid format.";

// Validate the email matches the email_confirmation
if ($email !== $email_confirmation)
    $errors[] = "The email must match the email confirmation.";

// Validate the password matches the password_confirmation
if ($password !== $password_confirmation)
    $errors[] = "The password must match the password confirmation.";

// Check if there errors
//看看是不是有错，如果有错，就会到registration的页面
error_handler($errors);
/* END OF VALIDATION */

/* NORMALIZATION */
// Normalize the string fields (convert to lowercase and capitalize the first letter)
foreach (['first_name', 'last_name'] as $field) {
    $$field = strtolower($$field);
    $$field = ucwords($$field);
}

// Lowercase the email
$email = strtolower($email);

// Hash the password
//密码，哈希算法，第一个是密码，第二个是选择的哈希算法的方式
$password = password_hash($password, PASSWORD_DEFAULT);
/* END NORMALIZATION */

/* SANITIZATION */
// Sanitize all values on their insertion
require_once("_connect.php");
$conn = dbo();
$sql = "INSERT INTO users (
    first_name,
    last_name,
    email,
    password
  ) VALUES (
    :first_name,
    :last_name,
    :email,
    :password
  )";
$stmt = $conn->prepare($sql);

// Sanitize using the binding
$stmt->bindParam(':first_name', $first_name, PDO::PARAM_STR);
$stmt->bindParam(':last_name', $last_name, PDO::PARAM_STR);
$stmt->bindParam(':email', $email, PDO::PARAM_STR);
//password这里加密了，所以不需要param——str
$stmt->bindParam(':password', $password);

/* END SANITIZATION */

// Insert our row
try {
    $stmt->execute();

    session_start();
    $_SESSION['successes'][] = "You have been registered successfully";
    header('Location: login.php');
    exit();

}
//因为email是unique，所以如果insert to the database的话，如果是已经存在的email就返回这个code
catch (PDOException $error) {
    if ($stmt->errorCode() === "23000")
        $errors[] = "You have already registered as a user.";
    else if ($stmt->errorCode() !== "00000")
        $errors[] = "There was an error during registration.";

    error_handler($errors);
}
