<?php /* Сторінка авторизації */
require_once('header.php'); /* Навігаційне меню */
?>

<h2>Форма авторизації</h2>
<?php /* Якщо користувач вже авторизований -> відправити на index.php */
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("location: ../index.php");
} else { /* В протилежному випадку запропонувати авторизуватись */
    if (isset($errorMessage)) {
        echo "<p>" . htmlspecialchars($errorMessage) . "</p>";
    }
    echo "<p>Ви ще не авторизовані</p>";
    require_once('../handler/authentication.php');
    ?><!-- Форма для авторизації -->
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <label for="login">Логін:</label><br>
        <input type="text" id="login" name="login"><br>
        <label for="password">Пароль:</label><br>
        <input type="password" id="password" name="password"><br><br>
        <input type="submit" name="login_submit" value="Авторизація">
    </form>
<?php } ?>
<?php require_once('footer.php');
