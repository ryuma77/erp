<?php
session_start();
session_destroy();
echo "✅ Session reset! <a href='login.php'>Login again</a>";
?>