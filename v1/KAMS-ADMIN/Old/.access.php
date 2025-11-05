<?php
//put sha1() encrypted password here - example is 'hello'
$password = 'kovaADMIN';

session_start();
if (!isset($_SESSION['loggedIn'])) {
    $_SESSION['loggedIn'] = false;
}

if (isset($_POST['password'])) {
    if ($_POST['password'] == $password) {
        $_SESSION['loggedIn'] = true;
    } else {
        die ('Incorrect password');
    }
}

if (!$_SESSION['loggedIn']): ?>

<html><head><title>Login</title></head>
  <body>
    <p>You need to login</p>
    <form method="post">
      Password: <input type="password" name="password"> <br />
      <input type="submit" name="submit" value="Login">
    </form>
  </body>
</html>

<?php
exit();
endif;
?>
