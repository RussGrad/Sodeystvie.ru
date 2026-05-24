<?php

declare(strict_types=1);

/**
 * SVG-иконки услуг (stroke, currentColor).
 */
function sodeystvie_services_render_icon(string $icon): void
{
    switch ($icon) {
        case 'valuation':
            ?>
            <svg class="services__icon" width="28" height="28" viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="10.5" cy="10.5" r="5.5" fill="none" stroke="currentColor" stroke-width="1.5"/>
                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" d="m16 16 4.5 4.5"/>
                <path fill="none" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round" d="M9 7.5v9M7.25 10h3.25a1.35 1.35 0 0 1 0 2.7H8.1a1.35 1.35 0 0 0 0 2.7H11"/>
            </svg>
            <?php
            break;
        case 'realtor':
            ?>
            <svg class="services__icon" width="28" height="28" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5Z"/>
                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" d="M9 14h6M12 11v6"/>
            </svg>
            <?php
            break;
        case 'selection':
            ?>
            <svg class="services__icon" width="28" height="28" viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="10" cy="10" r="6" fill="none" stroke="currentColor" stroke-width="1.5"/>
                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" d="m15.5 15.5 4 4"/>
                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" d="M7.5 10h5M10 7.5v5"/>
            </svg>
            <?php
            break;
        case 'analytics':
            ?>
            <svg class="services__icon" width="28" height="28" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" d="M4 19V5M4 19h16"/>
                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" d="M8 15V11M12 15V8M16 15v-3"/>
            </svg>
            <?php
            break;
        case 'chain':
            ?>
            <svg class="services__icon" width="28" height="28" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" d="M8 12h8M12 8v8"/>
                <circle cx="7" cy="12" r="2.5" fill="none" stroke="currentColor" stroke-width="1.5"/>
                <circle cx="17" cy="12" r="2.5" fill="none" stroke="currentColor" stroke-width="1.5"/>
                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" d="M9.5 12H6M18 12h-1.5"/>
            </svg>
            <?php
            break;
        case 'mortgage':
            ?>
            <svg class="services__icon" width="28" height="28" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" d="M8 17 16 7"/>
                <circle cx="8.5" cy="7.5" r="2" fill="none" stroke="currentColor" stroke-width="1.5"/>
                <circle cx="15.5" cy="16.5" r="2" fill="none" stroke="currentColor" stroke-width="1.5"/>
            </svg>
            <?php
            break;
        case 'legal':
            ?>
            <svg class="services__icon" width="28" height="28" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" d="M6 4h12v16H6z"/>
                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" d="M9 8h6M9 12h6M9 16h4"/>
                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" d="M12 4v3"/>
            </svg>
            <?php
            break;
        default:
            ?>
            <svg class="services__icon" width="28" height="28" viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="12" r="8" fill="none" stroke="currentColor" stroke-width="1.5"/>
            </svg>
            <?php
    }
}
