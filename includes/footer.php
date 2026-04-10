<?php
$current_page = basename($_SERVER['PHP_SELF']);
$is_auth = in_array($current_page, ['signin.php', 'register.php']);
?>
<?php if ($is_auth): ?>
    </div><!-- /auth-body -->
    </div><!-- /auth-layout -->
<?php else: ?>
    </div><!-- /container -->
    </div><!-- /page-content -->
    </div><!-- /content -->
    </div><!-- /student-layout -->
<?php endif; ?>
</body>

</html>