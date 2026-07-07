<?php
// Shared header for help topic pages: back link to the hub.
// Expects $locale from the controller.
?>
<div class="help-back-row">
    <a href="<?= htmlspecialchars(Auth::url('/help'), ENT_QUOTES, 'UTF-8') ?>" class="help-topic-back">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
        </svg>
        <?= $locale === 'es' ? 'Centro de ayuda' : 'Help Center' ?>
    </a>
</div>
