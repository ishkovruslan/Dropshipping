<?php /* Сторінка реєстрації */
require_once('header.php'); /* Навігаційне меню */
?>

<h2>Форма реєстрації</h2>
<?php /* Якщо користувач вже авторизований -> відправити на index.php */
    require_once('../handler/authentication.php');
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("location: ../index.php");
} else { /* В протилежному випадку запропонувати зареєструватись */
    if (isset($errorMessage)) {
        echo "<p>" . htmlspecialchars($errorMessage) . "</p>";
    }
    echo "<p>Ви ще не авторизовані</p>";
    ?> <!-- Форма реєстрації -->
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <label for="login">Логін:</label><br>
        <input type="text" id="login" name="login"><br>
        <label for="password">Пароль:</label><br>
        <input type="password" id="password" name="password"><br><br>
        <input type="submit" name="register_submit" value="Зареєструватись">
    </form>
<?php }
require_once('footer.php');
