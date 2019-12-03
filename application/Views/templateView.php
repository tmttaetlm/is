<!DOCTYPE html>
<html lang="ru">
    <head>
        <title>Информационные системы NIS Kostanay</title>
        <meta charset="utf-8">
        <link rel="stylesheet" type="text/css" href="/public/css/style_ver.3.8.css">
    </head>
    <body>
        <header>
            <div class="wrapper">
                <div class="logo">
                    <h1><a href="/">NIS</a></h1>
                    <h2><a href="/">KOSTANAY</a></h2>
                </div>

                <div class="title">
                    <h1><a href="/">ИНФОРМАЦИОННЫЕ СИСТЕМЫ</a></h1>
                </div>

                <div class="user">
                <?php if ($data['admin']) echo '<a href="/admin">Администрирование</a>'; ?> 
                    <?php if ($data['user']){echo '<a href="#">'.$data['user'].'</a><a href="/user/logout">Выход</a>'; }?> 
                    
                </div>
            </div>
        </header>
<?php echo $data['content']; ?>
       
        <footer>
        <p class="copyright">Copyright © 2019 NIS Kostanay</p>
        </footer>
        <script src="/public/js/script_ver.3.5.js"></script>
        
    </body>
</html>
