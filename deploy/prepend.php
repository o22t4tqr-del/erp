<?php
/**
 * 代码里大量使用 ./data/... 、./application/libraries/... 这类相对路径
 * （basedata/import.php、basedata/inventory.php、scm/invOi.php），
 * 依赖进程工作目录 = 站点根。这里统一钉死，避免不同 SAPI 下行为漂移。
 */
if (getcwd() !== '/var/www/html') {
    @chdir('/var/www/html');
}
