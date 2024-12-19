<div class="userlist"> <!-- Таблиця керування користувачами -->
    <h1>Список користувачів</h1>
    <table>
        <tr>
            <th>Логін</th>
            <th width="20%">Роль</th>
            <th width="40%">Керування користувачем</th>
        </tr>
        <?php $userList = new UserList($db);
        $userList->loadUsersFromDB();
        $roles = ["user" => "Користувач", "seller" => "Продавець"];
        foreach ($userList->getUsers() as $user):
            if ($user->getRole() !== 'administrator'): ?>
                <tr>
                    <td><a
                            href="management.php?table=log&user=<?php echo $user->getLogin() ?>"><?php echo $user->getLogin(); ?></a>
                    </td>
                    <td><?php echo $roles[$user->getRole()]; ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="login" value="<?php echo $user->getLogin(); ?>">
                            <select name="new_role" onchange="updateButtonText(this)">
                                <option value="">Редагування</option>
                                <?php foreach ($roles as $rkey => $rtit) {
                                    if ($rkey != $user->getRole()) {
                                        echo '<option value="' . $rkey . '">' . $rtit . '</option>';
                                    }
                                } ?>
                                <option value="changekey">Зміна ключа</option>
                                <option value="delete">Видалення</option>
                            </select>
                            <button type="submit" name="submit_action"
                                id="submit-button-<?php echo $user->getLogin(); ?>">Змінити</button>
                        </form>
                    </td>
                </tr>
            <?php endif;
        endforeach; ?>
    </table>
</div>