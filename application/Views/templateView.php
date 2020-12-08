<!DOCTYPE html>
<html lang="ru">
    <head>
        <title>Информационные системы NIS Kostanay</title>
        <meta charset="utf-8">
        <link rel="stylesheet" type="text/css" href="/public/css/styles_3.4.css">
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.1/css/all.css" integrity="sha384-gfdkjb5BdAXd+lj+gudLWI+BXq4IuLW5IT+brZEZsLFm++aCMlF1V92rMkPaX4PP" crossorigin="anonymous">
        <link rel="apple-touch-icon" sizes="180x180" href="/public/images/icons/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/public/images/icons/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/public/images/icons/favicon-16x16.png">
        <link rel="manifest" href="/public/images/icons/site.webmanifest">
        <link rel="mask-icon" href="/public/images/icons/safari-pinned-tab.svg" color="#5e852c">
        <meta name="msapplication-TileColor" content="#365908">
        <meta name="theme-color" content="#678f33">
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
        <p class="copyright">Copyright © <?php echo date('Y') ?> NIS Kostanay</p>
        </footer>
        <script type="module" src="/public/js/scripts_3.5.js"></script>
    </body>
</html>
