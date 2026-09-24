<?php
if (!defined('ABSPATH')) exit;

class Sportedia_Action_Card {

    /**
     * Render a standardized monochrome action card component.
     *
     * @param array $args Card configuration options
     */
    public static function render($args = array()) {
        $defaults = array(
            'title'       => 'Action',
            'description' => '',
            'icon'        => 'grid',
            'action'      => 'alert("Action triggered")',
            'btn_text'    => 'Open',
            'capability'  => ''
        );

        $card = wp_parse_args($args, $defaults);

        if (!empty($card['capability']) && !current_user_can($card['capability'])) {
            return;
        }

        ?>
        <div class="sportedia-action-card" onclick="<?php echo esc_attr($card['action']); ?>">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div class="sportedia-action-card-title"><?php echo esc_html($card['title']); ?></div>
                <svg class="sportedia-action-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <?php if ($card['icon'] === 'user-plus'): ?>
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/>
                    <?php elseif ($card['icon'] === 'users'): ?>
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    <?php elseif ($card['icon'] === 'building'): ?>
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
                    <?php else: ?>
                        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                    <?php endif; ?>
                </svg>
            </div>
            <?php if (!empty($card['description'])): ?>
                <div class="sportedia-action-card-desc"><?php echo esc_html($card['description']); ?></div>
            <?php endif; ?>
            <div style="margin-top: 8px;">
                <span class="sportedia-btn sportedia-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;"><?php echo esc_html($card['btn_text']); ?></span>
            </div>
        </div>
        <?php
    }
}
