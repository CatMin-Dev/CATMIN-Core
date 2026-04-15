<script src="/assets/vendor/bootstrap/5.3.8/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/catmin-sidebar.js?v=7"></script>
<script src="/assets/js/catmin-topbar.js?v=4"></script>
<script src="/assets/js/catmin-theme.js?v=1"></script>
<script src="/assets/js/catmin-confirm.js?v=1"></script>
<script src="/assets/js/catmin-components.js?v=9"></script>
<?php
$renderInlineScripts = static function (mixed $value): void {
	if (!is_string($value)) {
		return;
	}
	$trimmed = trim($value);
	if ($trimmed === '') {
		return;
	}

	if (stripos($trimmed, '<script') !== false) {
		echo $trimmed;
		return;
	}

	echo '<script>' . $trimmed . '</script>';
};

if (isset($inlineScripts)) {
	$renderInlineScripts($inlineScripts);
}

// Backward compatibility for older pages still using $scripts.
if (isset($scripts)) {
	$renderInlineScripts($scripts);
}
?>
