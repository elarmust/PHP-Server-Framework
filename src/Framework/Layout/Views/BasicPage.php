<!DOCTYPE html>
<html lang="<?=$controller->getLocale()->getLocale()?>">
    <head>
        <title>Framework</title>
        <meta charset="UTF-8">
        <meta name="description" content="Lightweight PHP server framework powered by OpenSwoole.">
        <meta name="keywords" content="PHP, Server, Framework, OpenSwoole">
        <meta name="author" content="Elar Must">
    </head>
    <body>
        <h1><?=$controller->getLocale()->get('test-website')?></h1>
        <footer>
            <?=$controller->getLocale()->get('footer', ['year' => date('Y')])?>
        </footer>
    </body>
</html>
