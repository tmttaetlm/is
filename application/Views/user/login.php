<div class="wrapper content-login">
    <div class="login">
        <h2>ВХОД В СИСТЕМУ</h2>
        <form  method="post" action="/user/signin">
            <input id="login" type="text" maxlength="50" placeholder="Логин" name="login" autocomplete="off" required>
            <input id="password" type="password" maxlength="50" placeholder="Пароль" name="password" autocomplete="off" required>
            <div id="msg">    
                <p><?php if (isset($data['msg'])) echo $data['msg']; ?></p>
            </div>
            <input type="submit" name="signIn" autocomplete="off" value="Войти" class="signInButton">
            
        </form>
    </div>
</div>