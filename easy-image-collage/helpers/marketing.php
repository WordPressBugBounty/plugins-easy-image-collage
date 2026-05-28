<?php

class EIC_Marketing {

    private $campaign = false;

    public function __construct()
    {
        $campaigns = array(
			'black-friday-2026' => array(
				'start' => new DateTime( '2026-11-23 10:00:00', new DateTimeZone( 'Europe/Brussels' ) ),
				'end' => new DateTime( '2026-12-01 10:00:00', new DateTimeZone( 'Europe/Brussels' ) ),
				'notice_title' => 'Black Friday & Cyber Monday Deal',
				'notice_text' => 'Get a 30% discount right now!',
				'page_title' => 'Black Friday Discount!',
				'page_text' => 'Good news: we\'re having a Black Friday & Cyber Monday sale and you can get a <strong>30% discount on any of our plugins</strong>.',
				'url' => 'https://bootstrapped.ventures/black-friday/',
			),
			'birthday-2027' => array(
				'start' => new DateTime( '2027-01-24 10:00:00', new DateTimeZone( 'Europe/Brussels' ) ),
				'end' => new DateTime( '2027-01-31 10:00:00', new DateTimeZone( 'Europe/Brussels' ) ),
				'notice_title' => 'Celebrating my birthday',
				'notice_text' => 'Get a 30% discount right now!',
				'page_title' => 'Birthday Discount!',
				'page_text' => 'Good news: I\'m celebrating my birthday with a <strong>30% discount on any of our plugins</strong>.',
				'url' => 'https://bootstrapped.ventures/birthday-discount/',
			),
		);

		$now = new DateTime();

		foreach ( $campaigns as $id => $campaign ) {
			if ( $campaign['start'] < $now && $now < $campaign['end'] ) {
				$campaign['id'] = $id;
				$this->campaign = $campaign;
				break;
			}
		}

		if ( false !== $this->campaign ) {
            add_action( 'eic_modal_notices', array( $this, 'marketing_notice' ) );
        }
    }

	public function get_campaign()
	{
		return $this->campaign;
	}

	public function get_campaign_url()
	{
		if ( false === $this->campaign ) {
			return false;
		}

		return add_query_arg(
			array(
				'utm_source' => 'eic',
				'utm_medium' => 'plugin',
				'utm_campaign' => $this->campaign['id'],
			),
			$this->campaign['url']
		);
	}

    public function marketing_notice()
    {
        if ( ! EasyImageCollage::is_premium_active() ) {
            $url = $this->get_campaign_url();

            echo '<div style="border: 1px solid darkgreen; padding: 5px; margin-bottom: 5px; background-color:rgba(0,255,0,0.15);">';
            echo '<strong>' . esc_html( $this->campaign['notice_title'] ) . '</strong><br/>';
            echo wp_kses_post( $this->campaign['page_text'] ) . '<br/><br/>';
            echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">'  . esc_html( $this->campaign['notice_text'] ) .  '</a>';
            echo '</div>';
        }
    }
}
