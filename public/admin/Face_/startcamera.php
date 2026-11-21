<?php

$python_path = '"C:/Users/Parviz/Python310/python.exe"';
$script = '"C:/OSPanel/home/project.local/public/admin/Face_/camera.py"';

$cmd = 'start "" ' . $python_path . ' ' . $script;

echo shell_exec($cmd . ' 2>&1');

?>