<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php if ( $collection && !empty ( $collection ) ): ?>
    <?php $total = 0 ?>
    <?php foreach ( $collection as $i => $payment ): ?>
    <tr style="<?php echo $i%2 ? 'background-color: #eee;' : '' ?>">
        <td><?php echo date( get_option( 'date_format' ), strtotime( $payment->created ) ) ?></td>
        <td>
            <?php
            switch( $payment->type ) {
                case 'paypal':
                    echo 'PayPal';
                    break;
                case 'authorizeNet':
                    echo 'Authorize.net';
                    break;
                case 'stripe':
                    echo 'Stripe';
                    break;
                case 'coupon':
                    echo __( 'Coupon', 'ab' );
                    break;
                default:
                    echo __( 'Local', 'ab' );
                    break;
            }
            ?>
        </td>
        <td><?php echo esc_html($payment->customer) ?></td>
        <td><?php echo esc_html($payment->provider) ?></td>
        <td><?php echo esc_html($payment->service) ?></td>
        <td><div class="pull-right"><?php echo $payment->total ?></div></td>
        <td><?php echo $payment->coupon ?></td>
        <td><?php if ($payment->start_date ) echo date( get_option( 'date_format' ), strtotime( $payment->start_date ) ) ?></td>
        <?php $total += $payment->total ?>
    </tr>
    <?php endforeach ?>
    <tr style="<?php echo (++$i)%2 ? 'background-color: #eee;' : '' ?>">
        <td colspan=6><div class=pull-right><strong><?php _e( 'Total: ', 'ab' ); ?> <?php echo $total;?></strong></div></td>
        <td></td>
        <td></td>
    </tr>
<?php endif ?>