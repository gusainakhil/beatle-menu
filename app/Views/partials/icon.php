<?php
/**
 * Renders an icon using Font Awesome.
 * $name: icon name (e.g. 'utensils', 'pizza-slice')
 * $class: optional classes
 */
$iconName = $name ?? 'circle';
$iconClass = $class ?? '';
?>
<i class="fa-solid fa-<?= e($iconName) ?> <?= e($iconClass) ?>"></i>
