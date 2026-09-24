<?php
if (!defined('ABSPATH')) exit;

$payments = Sportedia_DB::get_statistics();
?>
<div class="wrap sportedia-payments-wrapper" style="direction: rtl; font-family: 'Cairo', sans-serif;">
    <h1 style="font-weight: 800; color: #0f172a; margin-bottom: 20px;"> Subscriptions & Payments  - Sportedia</h1>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 25px;">
        <div style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-right: 4px solid #2563eb;">
            <div style="font-size: 13px; color: #64748b; font-weight: 700;">Total Monthly Revenue</div>
            <div style="font-size: 24px; font-weight: 900; color: #2563eb; margin-top: 5px;"><?php echo number_format($payments['monthly_revenue'] ?? 0, 2); ?> AED</div>
        </div>
        <div style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-right: 4px solid #16a34a;">
            <div style="font-size: 13px; color: #64748b; font-weight: 700;">Active Players</div>
            <div style="font-size: 24px; font-weight: 900; color: #16a34a; margin-top: 5px;"><?php echo esc_html($payments['active_players'] ?? 0); ?> No</div>
        </div>
        <div style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-right: 4px solid #dc2626;">
            <div style="font-size: 13px; color: #64748b; font-weight: 700;">Subscriptions Expiring Soon</div>
            <div style="font-size: 24px; font-weight: 900; color: #dc2626; margin-top: 5px;"><?php echo esc_html($payments['expiring_subscriptions'] ?? 0); ?> </div>
        </div>
    </div>

    <div style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
        <h3 style="margin-top: 0; font-weight: 800; color: #0f172a;">Payments & Invoices History (Newest to Oldest)</h3>
        <p style="font-size: 13px; color: #64748b;">    : Cash (Cash) Credit Card (Card) Bank Transfer (Bank Transfer)   (Online Payment).</p>
    </div>
</div>
