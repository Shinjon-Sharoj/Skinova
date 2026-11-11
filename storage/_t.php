<?php
require _DIR_ . '/../includes/init.php';
echo sanitize_html('<p onclick="x()">Hello <strong>world</strong><script>alert(1)</script><a href="javascript:alert(1)">bad</a> <a href="https://ok.com">ok</a><img src="x" onerror="y()"></p><h2>Title</h2>') . "\n";